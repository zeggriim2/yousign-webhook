<?php

declare(strict_types=1);

namespace Zeggriim\YouTrustWebhookBundle\Tests\RemoteEvent;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Zeggriim\YouTrustWebhookBundle\Enum\YouTrustEvent;
use Zeggriim\YouTrustWebhookBundle\RemoteEvent\Consumer\AbstractYouTrustConsumer;
use Zeggriim\YouTrustWebhookBundle\RemoteEvent\YouTrustRemoteEvent;

/**
 * @internal
 *
 * @coversNothing
 */
final class AbstractYouTrustConsumerTest extends TestCase
{
    public function testItRoutesAnEventToItsDedicatedMethod(): void
    {
        $consumer = new SpyConsumer();
        $consumer->consume(self::event('signature_request.done'));

        self::assertSame(['onSignatureRequestDone'], $consumer->calls);
    }

    public function testItRoutesNestedEventNames(): void
    {
        $consumer = new SpyConsumer();
        $consumer->consume(self::event('verification.identity_document.done'));

        self::assertSame(['onVerificationIdentityDocumentDone'], $consumer->calls);
    }

    public function testUnhandledEventsFallBackToOnEvent(): void
    {
        $consumer = new SpyConsumer();
        $consumer->consume(self::event('signer.link_opened'));

        self::assertSame(['onEvent:signer.link_opened'], $consumer->calls);
    }

    public function testEventsFromAnotherProviderAreIgnored(): void
    {
        $consumer = new SpyConsumer();
        $consumer->consume(new RemoteEvent('signature_request.done', 'id', []));

        self::assertSame([], $consumer->calls);
    }

    public function testTypedAccessorsAreAvailableInTheConsumer(): void
    {
        $consumer = new SpyConsumer();
        $consumer->consume(self::event('signature_request.done', [
            'signature_request' => ['id' => 'sr-1', 'status' => 'done'],
            'signer' => ['id' => 'signer-1', 'status' => 'signed'],
        ]));

        $lastEvent = $consumer->lastEvent;
        self::assertNotNull($lastEvent);
        self::assertSame('sr-1', $lastEvent->getSignatureRequest()?->id);
        self::assertSame('signer-1', $lastEvent->getSigner()?->id);
        self::assertSame(YouTrustEvent::SIGNATURE_REQUEST_DONE, $lastEvent->getEventType());
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function event(string $name, array $data = []): YouTrustRemoteEvent
    {
        return new YouTrustRemoteEvent(
            $name,
            'event-id',
            ['metadata' => ['event_name' => $name], 'data' => $data],
            'subscription-id',
            '',
            false,
            new DateTimeImmutable('@1670855889'),
        );
    }
}

final class SpyConsumer extends AbstractYouTrustConsumer
{
    /** @var list<string> */
    public array $calls = [];

    public ?YouTrustRemoteEvent $lastEvent = null;

    protected function onSignatureRequestDone(YouTrustRemoteEvent $event): void
    {
        $this->calls[] = 'onSignatureRequestDone';
        $this->lastEvent = $event;
    }

    protected function onVerificationIdentityDocumentDone(YouTrustRemoteEvent $event): void
    {
        $this->calls[] = 'onVerificationIdentityDocumentDone';
        $this->lastEvent = $event;
    }

    protected function onEvent(YouTrustRemoteEvent $event): void
    {
        $this->calls[] = 'onEvent:'.$event->getName();
        $this->lastEvent = $event;
    }
}
