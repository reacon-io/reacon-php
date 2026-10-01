<?php
// Copyright Reacon contributors. Licensed under Apache-2.0.
declare(strict_types=1);
namespace Reacon\Sdk\Streaming;

use Reacon\Sdk\ObjectSerializer;
use Reacon\Sdk\Model\{VerificationStage, VerificationProgress, VerificationFinal, VerificationStreamError};
use Symfony\Component\HttpClient\{CurlHttpClient, EventSourceHttpClient};
use Symfony\Component\HttpClient\Chunk\ServerSentEvent;
use Symfony\Component\HttpClient\Response\ResponseStream;
use Symfony\Contracts\HttpClient\{HttpClientInterface, ResponseInterface, ResponseStreamInterface};
use Symfony\Contracts\HttpClient\Exception\{TransportExceptionInterface, TimeoutExceptionInterface};

class StreamProtocolException extends \RuntimeException {}
class StreamTransportException extends \RuntimeException {}
class StreamCancelledException extends \RuntimeException {}
class StreamTimeoutException extends \RuntimeException
{
    public function __construct(public readonly string $phase) { parent::__construct("Reacon $phase timeout"); }
}
class StreamApiException extends \RuntimeException
{
    public function __construct(public readonly int $status, public readonly array $headers, public readonly mixed $body, public readonly ?VerificationStreamError $event = null)
    { parent::__construct($event ? 'Reacon stream failed: '.$event->getCode() : "Reacon returned HTTP $status"); }
    public function requestId(): ?string { return $this->headers['x-request-id'][0] ?? null; }
}
class VerificationEvent
{
    public function __construct(public readonly string $kind, public readonly object|null $data, public readonly object $raw) {}
}

/** Per-instance credentials; streaming owns its transport separately from generated Guzzle JSON resources. */
final class VerificationStreamClient
{
    private const API_ORIGIN = 'https://api.reacon.io';
    public function __construct(private string $apiKey, private ?string $caFile = null)
    { if (trim($apiKey) === '') throw new \InvalidArgumentException('apiKey is required'); }
    /** Creating this object does no I/O. Always close it in finally when breaking iteration early. */
    public function streamVerification(string $email, array $options = [], ?callable $isCancelled = null): VerificationStream
    {
        $options += ['idleTimeout' => 30.0, 'totalTimeout' => 300.0, 'onlyIfFree' => null, 'cacheMaxAge' => null];
        if ($email === '' || $options['idleTimeout'] <= 0 || $options['totalTimeout'] <= 0) throw new \InvalidArgumentException('Email and positive timeouts are required');
        if (!in_array($options['onlyIfFree'], [null, true, false, 'true', 'false'], true) || !in_array($options['cacheMaxAge'], [null, 'live', '1d', '1w', '1m'], true)) throw new \InvalidArgumentException('Invalid verification option');
        $query = ['email' => $email];
        if ($options['onlyIfFree'] !== null) $query['onlyIfFree'] = is_bool($options['onlyIfFree']) ? ($options['onlyIfFree'] ? 'true' : 'false') : $options['onlyIfFree'];
        if ($options['cacheMaxAge'] !== null) $query['cacheMaxAge'] = $options['cacheMaxAge'];
        return new VerificationStream(self::API_ORIGIN.'/v1/verify?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986), $this->apiKey, $this->caFile, (float)$options['idleTimeout'], (float)$options['totalTimeout'], $isCancelled);
    }
}

/** @internal Guards Symfony's maintained SSE parser: failures escape before its reconnect path. */
final class SingleAttemptHttpClient implements HttpClientInterface
{
    private bool $requested = false;
    private float $lastActivity;
    public function __construct(private HttpClientInterface $client, private float $deadline, private float $idle, private \Closure $cancelled)
    { $this->lastActivity = hrtime(true) / 1e9; }
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        if ($this->requested) throw new StreamTransportException('Implicit SSE reconnection is disabled');
        $this->requested = true;
        return $this->client->request($method, $url, $options);
    }
    public function withOptions(array $options): static { $clone = clone $this; $clone->client = $this->client->withOptions($options); return $clone; }
    public function stream(ResponseInterface|iterable $responses, ?float $timeout = null): ResponseStreamInterface
    {
        return new ResponseStream((function () use ($responses) {
            $responses = $responses instanceof ResponseInterface ? [$responses] : iterator_to_array((function () use ($responses) { yield from $responses; })());
            $pending = new \SplObjectStorage(); foreach ($responses as $response) $pending[$response] = null;
            while (count($pending)) {
                if (($this->cancelled)()) throw new StreamCancelledException('Verification stream cancelled');
                $now = hrtime(true) / 1e9;
                if ($now >= $this->deadline) throw new StreamTimeoutException('total');
                if ($now - $this->lastActivity >= $this->idle) throw new StreamTimeoutException('idle');
                try {
                    foreach ($this->client->stream($pending, min(0.02, $this->deadline - $now)) as $response => $chunk) {
                        // A short polling timeout is not a protocol event or an SSE reconnect trigger.
                        if ($chunk->isTimeout()) continue;
                        $content = $chunk->getContent();
                        // Acknowledge error chunks before throwing our own cancellation;
                        // Symfony otherwise rethrows an unread error during destruction.
                        if (($this->cancelled)()) throw new StreamCancelledException('Verification stream cancelled');
                        if (hrtime(true) / 1e9 >= $this->deadline) throw new StreamTimeoutException('total');
                        if ($content !== '' || $chunk->isFirst()) $this->lastActivity = hrtime(true) / 1e9;
                        if ($chunk->isFirst() && $response->getStatusCode() >= 200 && $response->getStatusCode() < 300 && !preg_match('/^text\/event-stream(?:;|$)/i', $response->getHeaders(false)['content-type'][0] ?? '')) throw new StreamProtocolException('Expected a text/event-stream response body');
                        if ($chunk->isLast()) unset($pending[$response]);
                        yield $response => $chunk;
                    }
                } catch (TimeoutExceptionInterface $error) {
                    throw new StreamTimeoutException(hrtime(true) / 1e9 >= $this->deadline - 0.005 ? 'total' : 'idle');
                } catch (TransportExceptionInterface $error) {
                    if (hrtime(true) / 1e9 >= $this->deadline - 0.005) throw new StreamTimeoutException('total');
                    throw new StreamTransportException('Reacon stream transport failure');
                }
            }
        })());
    }
}

final class VerificationStream
{
    private ?ResponseInterface $response = null;
    private bool $closed = false;
    private bool $started = false;
    private ?\Closure $isCancelled;
    public function __construct(private string $url, private string $key, private ?string $caFile, private float $idle, private float $total, ?callable $isCancelled)
    { $this->isCancelled = $isCancelled === null ? null : \Closure::fromCallable($isCancelled); }
    public function close(): void { $this->closed = true; $this->response?->cancel(); }
    public function __destruct() { $this->close(); }
    /** @return \Generator<VerificationEvent> */
    public function events(): \Generator
    {
        if ($this->closed || ($this->isCancelled && ($this->isCancelled)())) throw new StreamCancelledException('Verification stream cancelled');
        if ($this->started) throw new \LogicException('Stream has already been consumed');
        $this->started = true;
        $deadline = hrtime(true) / 1e9 + $this->total;
        // Fresh HTTP/1 connection avoids libcurl's transparent replay after a stale
        // reused connection. No retry decorator is installed. TLS verification stays on.
        $transport = new CurlHttpClient(['http_version' => '1.1', 'extra' => ['curl' => [CURLOPT_FRESH_CONNECT => true, CURLOPT_FORBID_REUSE => true]]]);
        $once = new SingleAttemptHttpClient($transport, $deadline, $this->idle, fn () => $this->closed || ($this->isCancelled && ($this->isCancelled)()));
        $client = new EventSourceHttpClient($once);
        try {
            $options = ['headers' => ['X-API-Key' => $this->key], 'max_redirects' => 0, 'timeout' => $this->idle, 'max_duration' => $this->total, 'buffer' => false];
            if ($this->caFile !== null) $options['cafile'] = $this->caFile;
            $this->response = $response = $client->connect($this->url, $options);
            $status = $response->getStatusCode(); $headers = $response->getHeaders(false);
            if ($status < 200 || $status >= 300) {
                $text = '';
                foreach ($client->stream($response) as $chunk) { $text .= substr($chunk->getContent(), 0, 65536 - strlen($text)); if (strlen($text) >= 65536) break; }
                try { $body = json_decode($text, false, 512, JSON_THROW_ON_ERROR); } catch (\JsonException) { $body = $text; }
                throw new StreamApiException($status, $headers, $body);
            }
            foreach ($client->stream($response) as $chunk) {
                if (!$chunk instanceof ServerSentEvent) continue;
                // Frames containing only comments/id/retry are not data events.
                if ($chunk->getData() === '') continue;
                $event = $this->decode($chunk->getData(), $status, $headers);
                if ($event->kind === 'final') { $this->close(); yield $event; return; }
                yield $event;
            }
            throw new StreamProtocolException('Verification stream ended before a terminal event');
        } finally { $this->close(); }
    }
    private function decode(string $data, int $status, array $headers): VerificationEvent
    {
        try { $raw = json_decode($data, false, 512, JSON_THROW_ON_ERROR); }
        catch (\JsonException) { throw new StreamProtocolException('Malformed SSE JSON payload'); }
        if (!$raw instanceof \stdClass) throw new StreamProtocolException('Expected an SSE JSON object');
        if (property_exists($raw, 'error')) {
            $this->requireFields($raw, ['error' => 'string', 'code' => 'string', 'updatedAt' => 'string']);
            throw new StreamApiException($status, $headers, $raw, $this->model($raw, VerificationStreamError::class));
        }
        if (property_exists($raw, 'result')) {
            $this->requireFields($raw, ['result' => 'object', 'updatedAt' => 'string']);
            $this->requireFields($raw->result, ['status' => 'string', 'catchAll' => 'boolean', 'disposable' => 'boolean']);
            return new VerificationEvent('final', $this->model($raw, VerificationFinal::class), $raw);
        }
        if (property_exists($raw, 'stage')) {
            $this->requireFields($raw, ['stage' => 'string', 'updatedAt' => 'string']);
            return new VerificationEvent('stage', $this->model($raw, VerificationStage::class), $raw);
        }
        if (property_exists($raw, 'state')) {
            $this->requireFields($raw, ['state' => 'string', 'updatedAt' => 'string']);
            return new VerificationEvent('progress', $this->model($raw, VerificationProgress::class), $raw);
        }
        return new VerificationEvent('unknown', null, $raw);
    }
    private function requireFields(object $raw, array $fields): void
    { foreach ($fields as $name => $type) if (!property_exists($raw, $name) || gettype($raw->$name) !== $type) throw new StreamProtocolException("Invalid verification field: $name"); }
    private function model(object $raw, string $class): object
    {
        try { return ObjectSerializer::deserialize($raw, $class, []); }
        catch (\Throwable) { throw new StreamProtocolException('Malformed verification event'); }
    }
}
