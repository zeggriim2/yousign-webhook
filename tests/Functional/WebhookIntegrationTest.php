<?php

declare(strict_types=1);

namespace Zeggriim\YousignWebhookBundle\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Zeggriim\YousignWebhookBundle\RemoteEvent\YousignRemoteEvent;
use Zeggriim\YousignWebhookBundle\Security\YousignSignatureVerifier;
use Zeggriim\YousignWebhookBundle\Webhook\YousignRequestParser;

/**
 * @internal
 *
 * @coversNothing
 */
final class WebhookIntegrationTest extends TestCase
{
    private TestKernel $kernel;

    protected function setUp(): void
    {
        $this->kernel = new TestKernel();
        $this->kernel->boot();
    }

    protected function tearDown(): void
    {
        $cacheDir = $this->kernel->getCacheDir();
        $this->kernel->shutdown();
        (new Filesystem())->remove($cacheDir);
    }

    public function testTheBundleRegistersItsServices(): void
    {
        $container = $this->kernel->getContainer()->get('test.service_container');
        \assert($container instanceof \Psr\Container\ContainerInterface);

        self::assertInstanceOf(YousignRequestParser::class, $container->get(YousignRequestParser::class));
    }

    public function testAValidWebhookIsForwardedToTheConsumer(): void
    {
        $response = $this->kernel->handle($this->request(self::payload()));

        self::assertSame(Response::HTTP_ACCEPTED, $response->getStatusCode());

        $consumer = $this->kernel->getContainer()->get(RecordingConsumer::class);
        \assert($consumer instanceof RecordingConsumer);

        self::assertCount(1, $consumer->events);
        $event = $consumer->events[0];
        self::assertInstanceOf(YousignRemoteEvent::class, $event);
        self::assertSame('signature_request.done', $event->getName());
        self::assertSame('b6c63685-c556-4a30-8fe9-b6f2b187d936', $event->getId());
        self::assertSame(['signature_request' => ['id' => 'xxx-xxx', 'status' => 'done']], $event->getData());
    }

    public function testAnInvalidSignatureIsRejected(): void
    {
        $request = $this->request(self::payload());
        $request->headers->set(YousignSignatureVerifier::SIGNATURE_HEADER, 'sha256=deadbeef');

        $response = $this->kernel->handle($request);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testAnInvalidPayloadIsRejected(): void
    {
        $response = $this->kernel->handle($this->request('{"nope": true}'));

        self::assertSame(Response::HTTP_NOT_ACCEPTABLE, $response->getStatusCode());
    }

    private function request(string $body): Request
    {
        return Request::create(
            '/webhook/yousign',
            'POST',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_YOUSIGN_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $body, TestKernel::WEBHOOK_SECRET),
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
            'data' => ['signature_request' => ['id' => 'xxx-xxx', 'status' => 'done']],
        ], JSON_THROW_ON_ERROR);
    }
}
