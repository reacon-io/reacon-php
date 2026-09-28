<?php
// Copyright Reacon contributors. Licensed under Apache-2.0.
declare(strict_types=1);
namespace Reacon\Sdk\Http;

use GuzzleHttp\{Client, ClientInterface, HandlerStack};
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\Promise\{PromiseInterface, Promise, Utils, CancellationException};
use Psr\Http\Message\{RequestInterface, ResponseInterface};
use Reacon\Sdk\{ApiException, Configuration, ObjectSerializer};

class RequestTimeoutException extends \RuntimeException {}
class TransportException extends \RuntimeException {}
class ResponseException extends ApiException
{
    public function __construct(public readonly ResponseInterface $response, string $message, ?\Throwable $cause = null)
    {
        parent::__construct($message, $response->getStatusCode(), $response->getHeaders(), (string)$response->getBody(), $cause);
    }
    public function requestId(): ?string { return $this->response->hasHeader('X-Request-Id') ? $this->response->getHeaderLine('X-Request-Id') : null; }
    public function parsedBody(): mixed
    {
        try { return json_decode($this->getResponseBody(), true, 512, JSON_THROW_ON_ERROR); }
        catch (\JsonException) { return null; }
    }
    public function errorCode(): ?string
    {
        $body = $this->parsedBody();
        $code = is_array($body) ? ($body['code'] ?? (is_array($body['error'] ?? null) ? ($body['error']['code'] ?? null) : null)) : null;
        return is_string($code) ? $code : null;
    }
}
class ResponseDecodeException extends ResponseException {}

/** @internal Drive the maintained cURL multi handler with an absolute deadline.
 * No signal handlers or process-global alarms; pending sibling calls keep running.
 */
final class DeadlineHandler
{
    private CurlMultiHandler $curl;
    private array $pending = [];
    private bool $ticking = false;
    public function __construct() { $this->curl = new CurlMultiHandler(['select_timeout' => 0.005]); }
    public function __invoke(RequestInterface $request, array $options): PromiseInterface
    {
        $deadline = $options['reacon_deadline'] ?? INF;
        $native = ($this->curl)($request, $options);
        $promise = null;
        $promise = new Promise(
            function () use (&$promise): void {
                if ($this->ticking) throw new \LogicException('Cannot synchronously wait inside a cURL callback.');
                while ($promise->getState() === PromiseInterface::PENDING) $this->tick();
            },
            static function () use ($native): void { $native->cancel(); }
        );
        $id = spl_object_id($promise);
        $this->pending[$id] = [$promise, $native, $deadline];
        $native->then(
            function ($response) use ($promise, $id, $deadline): void {
                unset($this->pending[$id]);
                if ($promise->getState() !== PromiseInterface::PENDING) return;
                if (hrtime(true) / 1e9 >= $deadline) $promise->reject(self::expired());
                else $promise->resolve($response);
            },
            function ($error) use ($promise, $id): void {
                unset($this->pending[$id]);
                if ($promise->getState() === PromiseInterface::PENDING) $promise->reject($error);
            }
        );
        return $promise;
    }
    private static function expired(): RequestTimeoutException
    {
        return new RequestTimeoutException('Reacon request deadline exceeded', 0, new CancellationException('Transfer cancelled at its deadline'));
    }
    private function expire(): void
    {
        $now = hrtime(true) / 1e9;
        foreach ($this->pending as $id => [$promise, $native, $deadline]) {
            if ($promise->getState() !== PromiseInterface::PENDING) { unset($this->pending[$id]); continue; }
            if ($now < $deadline) continue;
            unset($this->pending[$id]);
            $promise->reject(self::expired());
            $native->cancel();
        }
    }
    public function tick(): void
    {
        if ($this->ticking) throw new \LogicException('Cannot reenter the cURL event loop.');
        $this->ticking = true;
        try { $this->expire(); $this->curl->tick(); $this->expire(); Utils::queue()->run(); }
        finally { $this->ticking = false; }
    }
}

/** Shared JSON/CSV transport. Streaming has its own explicitly bounded client. */
final class RequestPolicy
{
    public static function client(array $options = []): Client
    {
        if (isset($options['handler'])) throw new \InvalidArgumentException('Use an injected ClientInterface for custom handlers.');
        // Use cURL for both sync and async, including body deadlines/cancellation.
        $options['handler'] = HandlerStack::create(new DeadlineHandler());
        return new Client($options);
    }
    public static function timeout(float $seconds): float
    {
        if (!is_finite($seconds) || $seconds <= 0 || $seconds * 1000 > PHP_INT_MAX) {
            throw new \InvalidArgumentException('requestTimeout must be finite, positive and representable in milliseconds.');
        }
        return $seconds;
    }
    public static function send(ClientInterface $client, RequestInterface $request, Configuration $config, array $options): PromiseInterface
    {
        $seconds = self::timeout($config->getRequestTimeout());
        // Complete buffering is intentional. Prevent implicit redirect/retry and
        // libcurl's automatic replay of requests on stale pooled connections.
        $options['timeout'] = max(0.001, $seconds);
        $options['connect_timeout'] = max(0.001, $seconds);
        $options['http_errors'] = false;
        $options['allow_redirects'] = false;
        $options['stream'] = false;
        $options['version'] = '1.1';
        $options['_curl_retries'] = 2; // Guzzle's failed-rewind fallback must not re-dispatch.
        $options['reacon_deadline'] = hrtime(true) / 1e9 + $seconds;
        $options['curl'] = [CURLOPT_FRESH_CONNECT => true, CURLOPT_FORBID_REUSE => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1];
        return $client->sendAsync($request, $options)->then(
            static function (ResponseInterface $response): ResponseInterface {
                if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
                    throw new ResponseException($response, 'Reacon returned HTTP '.$response->getStatusCode());
                }
                return $response;
            },
            static function ($error) {
                if ($error instanceof CancellationException || $error instanceof RequestTimeoutException) throw $error;
                $cause = $error instanceof \Throwable ? $error : null;
                $context = is_object($error) && method_exists($error, 'getHandlerContext') ? $error->getHandlerContext() : [];
                if (($context['errno'] ?? null) === CURLE_OPERATION_TIMEDOUT) {
                    throw new RequestTimeoutException('Reacon request deadline exceeded', 0, $cause);
                }
                throw new TransportException('Reacon transport failed', 0, $cause);
            }
        );
    }
    public static function decode(ResponseInterface $response, ?string $type): array
    {
        if ($type === null) return [null, $response->getStatusCode(), $response->getHeaders()];
        $body = (string)$response->getBody();
        try {
            $media = strtolower(trim(explode(';', $response->getHeaderLine('Content-Type'))[0]));
            if ($media !== 'application/json' && !preg_match('~^application/[a-z0-9!#$&^_.+-]+\+json$~', $media)) throw new \UnexpectedValueException('Expected a JSON response');
            $value = json_decode($body, false, 512, JSON_THROW_ON_ERROR);
            self::validate($value, $type);
            $data = ObjectSerializer::deserialize($value, $type, []);
        } catch (\Throwable $error) {
            throw new ResponseDecodeException($response, 'Unable to decode Reacon response', $error);
        }
        return [$data, $response->getStatusCode(), $response->getHeaders()];
    }
    public static function csv(ResponseInterface $response): string
    {
        $body = (string)$response->getBody();
        if (strtolower(trim(explode(';', $response->getHeaderLine('Content-Type'))[0])) !== 'text/csv' || !mb_check_encoding($body, 'UTF-8')) {
            throw new ResponseDecodeException($response, 'Expected a UTF-8 CSV response', new \UnexpectedValueException('Invalid CSV response'));
        }
        return $body;
    }
    /** Validate known wire types before the generator's permissive scalar casts. */
    private static function validate(mixed $value, string $type): void
    {
        if ($type === 'mixed' || $type === 'object') return;
        if (str_ends_with($type, '[]')) {
            if (!is_array($value)) throw new \UnexpectedValueException('Expected an array');
            foreach ($value as $item) self::validate($item, substr($type, 0, -2));
            return;
        }
        if (str_starts_with($type, 'map[')) return;
        $valid = match ($type) {
            'string', '\\DateTime', '\\DateTimeInterface' => is_string($value),
            'int', 'integer' => is_int($value),
            'float', 'double', 'number' => is_int($value) || is_float($value),
            'bool', 'boolean' => is_bool($value),
            default => null,
        };
        if ($valid === false) throw new \UnexpectedValueException('Unexpected scalar type');
        if ($valid !== null || !is_subclass_of($type, '\\Reacon\\Sdk\\Model\\ModelInterface')) return;
        if (!$value instanceof \stdClass) throw new \UnexpectedValueException('Expected an object');
        foreach ($type::openAPITypes() as $property => $fieldType) {
            $name = $type::attributeMap()[$property];
            if (!property_exists($value, $name)) continue;
            if ($value->$name === null && $type::isNullable($property)) continue;
            self::validate($value->$name, $fieldType);
        }
    }
}
