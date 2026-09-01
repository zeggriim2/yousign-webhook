<?php

declare(strict_types=1);

namespace Zeggriim\YouTrustWebhookBundle\Tests\Webhook;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Zeggriim\YouTrustWebhookBundle\Webhook\YouTrustIdempotencyStore;

/**
 * @internal
 *
 * @coversNothing
 */
final class YouTrustIdempotencyStoreTest extends TestCase
{
    public function testAnEventIsOnlyHandledOnce(): void
    {
        $store = new YouTrustIdempotencyStore(new ArrayAdapter());

        self::assertTrue($store->isEnabled());
        self::assertTrue($store->markAsHandled('event-1'));
        self::assertFalse($store->markAsHandled('event-1'));
        self::assertTrue($store->markAsHandled('event-2'));
    }

    public function testIdsWithReservedPsr6CharactersAreSupported(): void
    {
        $store = new YouTrustIdempotencyStore(new ArrayAdapter());

        self::assertTrue($store->markAsHandled('{weird}/id@yousign:1'));
        self::assertFalse($store->markAsHandled('{weird}/id@yousign:1'));
    }

    public function testEverythingIsHandledWhenDisabled(): void
    {
        $store = new YouTrustIdempotencyStore();

        self::assertFalse($store->isEnabled());
        self::assertTrue($store->markAsHandled('event-1'));
        self::assertTrue($store->markAsHandled('event-1'));
    }
}
