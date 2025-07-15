<?php

declare(strict_types=1);

namespace Zeggriim\YousignWebhookBundle\Tests\Controller;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\RemoteEvent\Exception\ParseException;
use Symfony\Component\RemoteEvent\Messenger\ConsumeRemoteEventMessage;
use Zeggriim\YousignWebhookBundle\Controller\YousignWebhookController;
use Zeggriim\YousignWebhookBundle\Security\YousignSignatureVerifier;
use Zeggriim\YousignWebhookBundle\Webhook\YousignConverter;

final class WebhookControllerTest extends TestCase
{
    public function testSignatureUnauthorize(): void
    {
        $controller = new YousignWebhookController(
            new YousignConverter(),
            new YousignSignatureVerifier('test'),
            $this->createMock(MessageBusInterface::class),
        );

        $response = $controller->handle(new Request());

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertSame('Invalid signature', $response->getContent());
    }

    public function testEmptyPayload(): void
    {
        $secret = 'keySecret';
        $hashMac = hash_hmac('sha256', '', $secret);
        $request = new Request();
        $request->headers->set('X-Yousign-Signature-256', $hashMac);

        $controller = new YousignWebhookController(
            new YousignConverter(),
            new YousignSignatureVerifier($secret),
            $this->createMock(MessageBusInterface::class),
        );

        $response = $controller->handle($request);

        $this->assertSame(Response::HTTP_NOT_ACCEPTABLE, $response->getStatusCode());
        $this->assertSame('Invalid payload', $response->getContent());
    }

    public function testParserAcceptsPayloadAndReturnsSingleEvent(): void
    {
        $secret = 'keySecret';
        $hashMac = hash_hmac('sha256', '', $secret);

        $payload = json_encode([
            'event_id'=> "b6c63685-c556-4a30-8fe9-b6f2b187d936",
            'event_name'=> 'signature_request.activated',
            'event_time'=> '1670855889',
            'subscription_id'=> 'webhook-subscription-id',
            'subscription_description'=> 'My webhook for signed documents',
            'sandbox'=> false,
            'data' => [
                'signature_request' => [
                    'id' => 'xxx-xxx',
                    'status' => 'approval',
                ]
            ]
        ]);
        $request = new Request(content: $payload);
        $request->headers->set('X-Yousign-Signature-256', $hashMac);
        $request->headers->set('Content-Type', 'application/json');

        $busMock = $this->createMock(MessageBusInterface::class);
        $busMock
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($message) {
                $this->assertInstanceOf(ConsumeRemoteEventMessage::class, $message);
                return true;
            }))
            ->willReturn(new Envelope(new \stdClass()));
        $controller = new YousignWebhookController(
            new YousignConverter(),
            new YousignSignatureVerifier($secret),
            $busMock,
        );

        $response = $controller->handle($request);

        $this->assertSame(Response::HTTP_ACCEPTED, $response->getStatusCode());
        $this->assertSame('', $response->getContent());
    }
}