<?php

declare(strict_types=1);

namespace Zeggriim\YousignWebhookBundle\Tests\Enum;

use PHPUnit\Framework\TestCase;
use Zeggriim\YousignWebhookBundle\Enum\YousignEventName;
use Zeggriim\YousignWebhookBundle\RemoteEvent\YousignRemoteEvent;

final class YousignEventNameTest extends TestCase
{
    public function testFromKnownEventName(): void
    {
        $event = YousignEventName::from('signature_request.done');
        $this->assertSame(YousignEventName::SIGNATURE_REQUEST_DONE, $event);
    }

    public function testTryFromUnknownEventNameReturnsNull(): void
    {
        $this->assertNull(YousignEventName::tryFrom('unknown.event'));
    }

    public function testGetYousignEventNameReturnsEnumForKnownEvent(): void
    {
        $remoteEvent = new YousignRemoteEvent(
            'signature_request.activated',
            'event-id-123',
            [],
            'sub-id',
            'My subscription',
            false,
            new \DateTimeImmutable(),
        );

        $this->assertSame(YousignEventName::SIGNATURE_REQUEST_ACTIVATED, $remoteEvent->getYousignEventName());
    }

    public function testGetYousignEventNameReturnsNullForUnknownEvent(): void
    {
        $remoteEvent = new YousignRemoteEvent(
            'future.unknown_event',
            'event-id-456',
            [],
            'sub-id',
            'My subscription',
            false,
            new \DateTimeImmutable(),
        );

        $this->assertNull($remoteEvent->getYousignEventName());
    }
}
