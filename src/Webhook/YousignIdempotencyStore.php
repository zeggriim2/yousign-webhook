<?php

declare(strict_types=1);

namespace Zeggriim\YousignWebhookBundle\Webhook;

use Psr\Cache\CacheItemPoolInterface;

/**
 * Remembers the events already handled, so that a redelivery is not processed twice.
 *
 * Yousign (YouTrust) retries a failed delivery up to 8 times and the
 * documentation recommends de-duplicating on the "event_id", which stays
 * identical across retries.
 *
 * @see https://developers.youtrust.com/docs/failure-and-retry-policy
 */
final class YousignIdempotencyStore
{
    private const KEY_PREFIX = 'yousign_webhook.event.';

    public function __construct(
        private readonly ?CacheItemPoolInterface $cache = null,
        private readonly int $ttl = 86400,
    ) {
    }

    public function isEnabled(): bool
    {
        return null !== $this->cache;
    }

    /**
     * Marks an event as handled.
     *
     * @return bool true when the event is seen for the first time (it must be processed),
     *              false when it has already been handled (it must be skipped)
     */
    public function markAsHandled(string $eventId): bool
    {
        if (null === $this->cache) {
            return true;
        }

        $item = $this->cache->getItem(self::key($eventId));

        if ($item->isHit()) {
            return false;
        }

        $item->set(true);
        $item->expiresAfter($this->ttl);
        $this->cache->save($item);

        return true;
    }

    /**
     * PSR-6 forbids the characters {}()/\@: in keys: hashing keeps any event id usable.
     */
    private static function key(string $eventId): string
    {
        return self::KEY_PREFIX.hash('xxh128', $eventId);
    }
}
