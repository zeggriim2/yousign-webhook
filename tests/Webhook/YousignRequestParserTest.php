<?php

declare(strict_types=1);

namespace Zeggriim\YousignWebhookBundle\Tests\Webhook;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Webhook\Exception\RejectWebhookException;
use Zeggriim\YousignWebhookBundle\RemoteEvent\YousignRemoteEvent;
use Zeggriim\YousignWebhookBundle\Security\YousignIpChecker;
use Zeggriim\YousignWebhookBundle\Security\YousignSignatureVerifier;
use Zeggriim\YousignWebhookBundle\Webhook\YousignConverter;
use Zeggriim\YousignWebhookBundle\Webhook\YousignIdempotencyStore;
use Zeggriim\YousignWebhookBundle\Webhook\YousignRequestParser;

/**
 * @internal
 *
 * @coversNothing
 */
final class YousignRequestParserTest extends TestCase
{
    private const SECRET = 'wh-secret';

    public function testItParsesASignedRequest(): void
    {
        $event = $this->parser()->parse($this->request(self::payload(), retry: 2), self::SECRET);

        self::assertInstanceOf(YousignRemoteEvent::class, $event);
        self::assertSame('signature_request.done', $event->getName());
        self::assertSame(2, $event->getRetryCount());
        self::assertSame('sr-1', $event->getSignatureRequest()?->id);
    }

    public function testItRejectsRequestsThatAreNotJsonPosts(): void
    {
        $request = $this->request(self::payload());
        $request->setMethod('GET');

        // The Webhook component turns a non-matching request into a 406.
        $this->assertRejects(Response::HTTP_NOT_ACCEPTABLE, fn () => $this->parser()->parse($request, self::SECRET));
    }

    public function testItRejectsAMissingSignature(): void
    {
        $request = $this->request(self::payload());
        $request->headers->remove(YousignSignatureVerifier::SIGNATURE_HEADER);

        $this->assertRejects(Response::HTTP_UNAUTHORIZED, fn () => $this->parser()->parse($request, self::SECRET));
    }

    public function testItRejectsAWrongSignature(): void
    {
        $this->assertRejects(Response::HTTP_UNAUTHORIZED, fn () => $this->parser()->parse($this->request(self::payload()), 'another-secret'));
    }

    public function testItRejectsAnInvalidPayload(): void
    {
        $this->assertRejects(Response::HTTP_NOT_ACCEPTABLE, fn () => $this->parser()->parse($this->request('{"metadata":{},"data":{}}'), self::SECRET));
    }

    public function testItRejectsMalformedJson(): void
    {
        $this->assertRejects(Response::HTTP_NOT_ACCEPTABLE, fn () => $this->parser()->parse($this->request('{"metadata":'), self::SECRET));
    }

    public function testItRejectsAnUnknownClientIpWhenTheAllowlistIsEnabled(): void
    {
        $parser = $this->parser(ipChecker: new YousignIpChecker(YousignIpChecker::DOCUMENTED_RANGES));

        $this->assertRejects(Response::HTTP_FORBIDDEN, fn () => $parser->parse($this->request(self::payload()), self::SECRET));
    }

    public function testARedeliveredEventIsSkipped(): void
    {
        $parser = $this->parser(store: new YousignIdempotencyStore(new ArrayAdapter()));

        self::assertNotNull($parser->parse($this->request(self::payload()), self::SECRET));
        self::assertNull($parser->parse($this->request(self::payload(), retry: 1), self::SECRET));
    }

    private function parser(?YousignIpChecker $ipChecker = null, ?YousignIdempotencyStore $store = null): YousignRequestParser
    {
        return new YousignRequestParser(new YousignConverter(), null, $ipChecker, $store);
    }

    /**
     * RejectWebhookException carries the HTTP status, not the exception code.
     *
     * @param callable(): mixed $parse
     */
    private function assertRejects(int $statusCode, callable $parse): void
    {
        try {
            $parse();
        } catch (RejectWebhookException $e) {
            self::assertSame($statusCode, $e->getStatusCode());

            return;
        }

        self::fail(\sprintf('Expected the request to be rejected with a %d status.', $statusCode));
    }

    private function request(string $body, int $retry = 0): Request
    {
        return Request::create(
            '/webhook/yousign',
            'POST',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_YOUSIGN_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $body, self::SECRET),
                'HTTP_X_YOUSIGN_RETRY' => (string) $retry,
            ],
            content: $body,
        );
    }

    private static function payload(): string
    {
        return json_encode([
            'metadata' => [
                'event_id' => 'b6c63685-c556-4a30-8fe9-b6f2b187d936',
                'event_name' => 'signature_request.done',
                'event_time' => '1670855889',
            ],
            'data' => ['signature_request' => ['id' => 'sr-1', 'status' => 'done']],
        ], JSON_THROW_ON_ERROR);
    }
}
