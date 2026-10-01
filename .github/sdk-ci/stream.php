<?php
declare(strict_types=1);
require getenv('SDK_DIRECTORY').'/vendor/autoload.php';
use Reacon\Sdk\Streaming\{VerificationStreamClient, StreamApiException, StreamProtocolException, StreamTransportException, StreamTimeoutException, StreamCancelledException};
function check(bool $value, string $message): void { if (!$value) throw new RuntimeException($message); }
$url = getenv('REACON_TEST_URL');
$proxy=parse_url(getenv('REACON_FIXTURE_PROXY_ENDPOINT'));
$encoded=rtrim(strtr(base64_encode($url), '+/', '-_'), '=');
putenv('https_proxy=http://'.$encoded.':'.$proxy['pass'].'@'.$proxy['host'].':'.$proxy['port']);
putenv('no_proxy='); putenv('NO_PROXY=');
$ca=tempnam(sys_get_temp_dir(), 'reacon-fixture-ca-');
file_put_contents($ca, getenv('REACON_FIXTURE_CA_PEM'));
register_shutdown_function(static fn() => unlink($ca));
$client = new VerificationStreamClient('synthetic-php', $ca);
$isolated = new VerificationStreamClient('isolated-php', $ca);
$client->streamVerification('never@example.test');
$collect = function (string $scenario, ?VerificationStreamClient $owner = null, array $options = []) use ($client): array {
    $stream = ($owner ?? $client)->streamVerification($scenario.'@example.test', ['onlyIfFree' => true] + $options);
    try { return iterator_to_array($stream->events()); } finally { $stream->close(); }
};
// Interleave two active generators in one process to verify per-client credentials.
$streams = [$client->streamVerification('success@example.test', ['onlyIfFree' => true]), $isolated->streamVerification('isolated@example.test', ['onlyIfFree' => true])];
$iterators = array_map(fn ($stream) => $stream->events(), $streams); $events = [[], []];
try {
    do {
        $active = false;
        foreach ($iterators as $i => $iterator) if ($iterator->valid()) { $active = true; $events[$i][] = $iterator->current(); $iterator->next(); }
    } while ($active);
} finally { foreach ($streams as $stream) $stream->close(); }
foreach ($events as $items) {
    check(array_map(fn ($item) => $item->kind, $items) === ['stage', 'unknown', 'progress', 'final'], 'event classification');
    check($items[0]->raw->label === 'hé🚀', 'split UTF-8');
    check($items[3]->data->getResult()->getAcceptsAll() === null && $items[3]->data->getResult()->getStatus() === 'future-status', 'typed final');
}
try { $collect('error'); throw new RuntimeException('Missing terminal error'); }
catch (StreamApiException $error) { check($error->status === 200 && $error->event->getCode() === 'INSUFFICIENT_CREDITS' && $error->event->getRemainingCredits() == 0 && $error->requestId() === 'req-stream', 'terminal error metadata'); }
foreach (['pre402' => 402, 'pre429' => 429, 'proxy' => 502, 'redirect' => 307] as $scenario => $status) {
    try { $collect($scenario); throw new RuntimeException('Missing HTTP error'); }
    catch (StreamApiException $error) {
        check($error->status === $status, 'HTTP status');
        if ($status === 402 || $status === 429) check($error->body->code === 'FIXTURE_ERROR' && $error->requestId() === 'req-stream', 'HTTP metadata');
        if ($status === 502) check(is_string($error->body) && $error->requestId() === null, 'proxy error');
    }
}
foreach (['wrongtype', 'malformed', 'invalidresult', 'eof'] as $scenario) {
    try { $collect($scenario); throw new RuntimeException('Missing protocol error: '.$scenario); } catch (StreamProtocolException) { }
}
try { $collect('disconnect'); throw new RuntimeException('Missing transport error'); } catch (StreamTransportException) { }
foreach (['idle', 'total', 'headers'] as $phase) {
    try { $collect($phase, options: ['idleTimeout' => 0.08, 'totalTimeout' => 0.2]); throw new RuntimeException('Missing timeout'); }
    catch (StreamTimeoutException $error) { if ($phase !== 'headers') check($error->phase === $phase, 'timeout phase'); }
}
$cancelAt = INF;
$stream = $client->streamVerification('cancel@example.test', ['onlyIfFree' => true], function () use (&$cancelAt) { return hrtime(true) / 1e9 >= $cancelAt; });
try {
    $iterator = $stream->events(); check($iterator->current()->kind === 'stage', 'cancel first event');
    $cancelAt = hrtime(true) / 1e9 + 0.02;
    try { $iterator->next(); throw new RuntimeException('Missing cancellation'); } catch (StreamCancelledException) { }
} finally { $stream->close(); }
$stream = $client->streamVerification('early@example.test', ['onlyIfFree' => true]);
try { foreach ($stream->events() as $event) { check($event->kind === 'stage', 'early stage'); break; } } finally { $stream->close(); }
$control = new GuzzleHttp\Client(); check($control->get($url.'/_assert_closed')->getStatusCode() === 200, 'closure while clients remain alive');
echo "PHP streaming protocol, cancellation and live closure assertions passed\n";
