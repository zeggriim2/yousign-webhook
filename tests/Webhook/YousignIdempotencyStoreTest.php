<?php

declare(strict_types=1);

namespace Zeggriim\YousignWebhookBundle\Tests\Webhook;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Zeggriim\YousignWebhookBundle\Webhook\YousignIdempotencyStore;

final class YousignIdempotencyStoreTest extends TestCase
{
    public function testAnEventIsOnlyHandledOnce(): void
    {
        $store = new YousignIdempotencyStore(new ArrayAdapter());

        $this->assertTrue($store->isEnabled());
        $this->assertTrue($store->markAsHandled('event-1'));
        $this->assertFalse($store->markAsHandled('event-1'));
        $this->assertTrue($store->markAsHandled('event-2'));
    }

    public function testIdsWithReservedPsr6CharactersAreSupported(): void
    {
        $store = new YousignIdempotencyStore(new ArrayAdapter());

        $this->assertTrue($store->markAsHandled('{weird}/id@yousign:1'));
        $this->assertFalse($store->markAsHandled('{weird}/id@yousign:1'));
    }

    public function testEverythingIsHandledWhenDisabled(): void
    {
        $store = new YousignIdempotencyStore();

        $this->assertFalse($store->isEnabled());
        $this->assertTrue($store->markAsHandled('event-1'));
        $this->assertTrue($store->markAsHandled('event-1'));
    }
}
