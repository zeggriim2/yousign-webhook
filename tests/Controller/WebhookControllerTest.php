<?php

declare(strict_types=1);

namespace Zeggriim\YousignWebhookBundle\Tests\Controller;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\RemoteEvent\Messenger\ConsumeRemoteEventMessage;
use Zeggriim\YousignWebhookBundle\Controller\YousignWebhookController;
use Zeggriim\YousignWebhookBundle\RemoteEvent\YousignRemoteEvent;
use Zeggriim\YousignWebhookBundle\Security\YousignIpChecker;
use Zeggriim\YousignWebhookBundle\Security\YousignSignatureVerifier;
use Zeggriim\YousignWebhookBundle\Webhook\YousignConverter;
use Zeggriim\YousignWebhookBundle\Webhook\YousignIdempotencyStore;

/**
 * @internal
 *
 * @coversNothing
 */
final class WebhookControllerTest extends TestCase
{
    private const SECRET = 'keySecret';

    public function testSignatureUnauthorize(): void
    {
        $bus = $this->bus();
        $bus->expects(self::never())->method('dispatch');

        $response = $this->controller($bus)->handle(new Request());

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame('Invalid signature', $response->getContent());
    }

    public function testEmptyPayload(): void
    {
        $bus = $this->bus();
        $bus->expects(self::never())->method('dispatch');

        $response = $this->controller($bus)->handle($this->signedRequest(''));

        self::assertSame(Response::HTTP_NOT_ACCEPTABLE, $response->getStatusCode());
        self::assertSame('Invalid payload', $response->getContent());
    }

    public function testMalformedJsonIsRejectedAsInvalidPayload(): void
    {
        $bus = $this->bus();
        $bus->expects(self::never())->method('dispatch');

        $response = $this->controller($bus)->handle($this->signedRequest('{"metadata":'));

        self::assertSame(Response::HTTP_NOT_ACCEPTABLE, $response->getStatusCode());
        self::assertSame('Invalid payload', $response->getContent());
    }

    public function testJsonScalarBodyIsRejectedAsInvalidPayload(): void
    {
        $bus = $this->bus();
        $bus->expects(self::never())->method('dispatch');

        $response = $this->controller($bus)->handle($this->signedRequest('"a string"'));

        self::assertSame(Response::HTTP_NOT_ACCEPTABLE, $response->getStatusCode());
    }

    public function testRequestFromAnUnknownIpIsForbidden(): void
    {
        $bus = $this->bus();
        $bus->expects(self::never())->method('dispatch');

        $controller = $this->controller($bus, new YousignIpChecker(['57.130.41.144/28']));
        $response = $controller->handle($this->signedRequest(self::payload()));

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame('Forbidden', $response->getContent());
    }

    public function testRequestFromAnAllowedIpIsAccepted(): void
    {
        $bus = $this->bus();
        $bus->expects(self::once())->method('dispatch')->willReturn(new Envelope(new \stdClass()));

        $request = $this->signedRequest(self::payload());
        $request->server->set('REMOTE_ADDR', '57.130.41.150');

        $controller = $this->controller($bus, new YousignIpChecker(YousignIpChecker::DOCUMENTED_RANGES));

        self::assertSame(Response::HTTP_ACCEPTED, $controller->handle($request)->getStatusCode());
    }

    public function testParserAcceptsPayloadAndReturnsSingleEvent(): void
    {
        $bus = $this->bus();
        $bus->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(function (mixed $message): bool {
                $this->assertInstanceOf(ConsumeRemoteEventMessage::class, $message);
                $this->assertSame('yousign', $message->getType());

                $event = $message->getEvent();
                $this->assertInstanceOf(YousignRemoteEvent::class, $event);
                $this->assertSame('signature_request.activated', $event->getName());
                $this->assertSame('b6c63685-c556-4a30-8fe9-b6f2b187d936', $event->getId());
                $this->assertSame(3, $event->getRetryCount());
                $this->assertTrue($event->isRetry());

                return true;
            }))
            ->willReturn(new Envelope(new \stdClass()))
        ;

        $request = $this->signedRequest(self::payload());
        $request->headers->set(YousignRemoteEvent::RETRY_HEADER, '3');

        $response = $this->controller($bus)->handle($request);

        self::assertSame(Response::HTTP_ACCEPTED, $response->getStatusCode());
        self::assertSame('', $response->getContent());
    }

    public function testLegacyFlatPayloadIsStillAccepted(): void
    {
        $bus = $this->bus();
        $bus->expects(self::once())->method('dispatch')->willReturn(new Envelope(new \stdClass()));

        $payload = json_encode([
            'event_id' => 'b6c63685-c556-4a30-8fe9-b6f2b187d936',
            'event_name' => 'signature_request.activated',
            'event_time' => '1670855889',
            'subscription_id' => 'webhook-subscription-id',
            'subscription_description' => 'My webhook for signed documents',
            'sandbox' => false,
            'data' => ['signature_request' => ['id' => 'xxx-xxx', 'status' => 'approval']],
        ], JSON_THROW_ON_ERROR);

        self::assertSame(
            Response::HTTP_ACCEPTED,
            $this->controller($bus)->handle($this->signedRequest($payload))->getStatusCode(),
        );
    }

    public function testDispatchFailureReturnsAServerError(): void
    {
        $bus = $this->bus();
        $bus->expects(self::once())->method('dispatch')->willThrowException(new TransportException('broker down'));

        $response = $this->controller($bus)->handle($this->signedRequest(self::payload()));

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        self::assertSame('Internal server error', $response->getContent());
    }

    public function testARedeliveredEventIsOnlyDispatchedOnce(): void
    {
        $bus = $this->bus();
        $bus->expects(self::once())->method('dispatch')->willReturn(new Envelope(new \stdClass()));

        $controller = $this->controller($bus, null, new YousignIdempotencyStore(new ArrayAdapter()));

        $first = $controller->handle($this->signedRequest(self::payload()));

        $retry = $this->signedRequest(self::payload());
        $retry->headers->set(YousignRemoteEvent::RETRY_HEADER, '1');
        $second = $controller->handle($retry);

        self::assertSame(Response::HTTP_ACCEPTED, $first->getStatusCode());
        self::assertSame(Response::HTTP_ACCEPTED, $second->getStatusCode());
    }

    /**
     * @return MockObject&MessageBusInterface
     */
    private function bus(): MockObject
    {
        return $this->createMock(MessageBusInterface::class);
    }

    private function controller(
        MessageBusInterface $bus,
        ?YousignIpChecker $ipChecker = null,
        ?YousignIdempotencyStore $idempotencyStore = null,
    ): YousignWebhookController {
        return new YousignWebhookController(
            new YousignConverter(),
            new YousignSignatureVerifier(self::SECRET),
            $bus,
            null,
            $ipChecker,
            $idempotencyStore,
        );
    }

    private function signedRequest(string $body): Request
    {
        $request = new Request(content: $body);
        $request->headers->set('Content-Type', 'application/json');
        $request->headers->set(
            YousignSignatureVerifier::SIGNATURE_HEADER,
            'sha256='.hash_hmac('sha256', $body, self::SECRET),
        );

        return $request;
    }

    private static function payload(): string
    {
        return json_encode([
            'metadata' => [
                'event_id' => 'b6c63685-c556-4a30-8fe9-b6f2b187d936',
                'event_name' => 'signature_request.activated',
                'event_time' => '1670855889',
                'subscription_id' => 'webhook-subscription-id',
                'subscription_description' => 'My webhook for signed documents',
                'sandbox' => false,
            ],
            'data' => ['signature_request' => ['id' => 'xxx-xxx', 'status' => 'ongoing']],
        ], JSON_THROW_ON_ERROR);
    }
}
