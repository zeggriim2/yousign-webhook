<?php

declare(strict_types=1);

namespace Zeggriim\YousignWebhookBundle\Tests\RemoteEvent;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Zeggriim\YousignWebhookBundle\Enum\YousignEvent;
use Zeggriim\YousignWebhookBundle\RemoteEvent\Consumer\AbstractYousignConsumer;
use Zeggriim\YousignWebhookBundle\RemoteEvent\YousignRemoteEvent;

/**
 * @internal
 *
 * @coversNothing
 */
final class AbstractYousignConsumerTest extends TestCase
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
        self::assertSame(YousignEvent::SIGNATURE_REQUEST_DONE, $lastEvent->getEventType());
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function event(string $name, array $data = []): YousignRemoteEvent
    {
        return new YousignRemoteEvent(
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

final class SpyConsumer extends AbstractYousignConsumer
{
    /** @var list<string> */
    public array $calls = [];

    public ?YousignRemoteEvent $lastEvent = null;

    protected function onSignatureRequestDone(YousignRemoteEvent $event): void
    {
        $this->calls[] = 'onSignatureRequestDone';
        $this->lastEvent = $event;
    }

    protected function onVerificationIdentityDocumentDone(YousignRemoteEvent $event): void
    {
        $this->calls[] = 'onVerificationIdentityDocumentDone';
        $this->lastEvent = $event;
    }

    protected function onEvent(YousignRemoteEvent $event): void
    {
        $this->calls[] = 'onEvent:'.$event->getName();
        $this->lastEvent = $event;
    }
}
