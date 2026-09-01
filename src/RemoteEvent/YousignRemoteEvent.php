<?php

declare(strict_types=1);

namespace Zeggriim\YousignWebhookBundle\RemoteEvent;

use DateTimeImmutable;
use Symfony\Component\RemoteEvent\RemoteEvent;

/**
 * @author Lilian D'orazio <lilian.dorazio@hotmail.fr>
 */
final class YousignRemoteEvent extends RemoteEvent
{
    /**
     * Header sent by Yousign (YouTrust) holding the delivery attempt number.
     * It equals 0 on the first delivery and is incremented on every retry.
     */
    public const RETRY_HEADER = 'X-Yousign-Retry';

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        string $name,
        string $id,
        array $payload,
        private readonly string $subscriptionId,
        private readonly string $subscriptionDescription,
        private readonly bool $sandbox,
        private readonly DateTimeImmutable $eventTime,
        private readonly int $retryCount = 0,
    ) {
        parent::__construct($name, $id, $payload);
    }

    public function getSubscriptionId(): string
    {
        return $this->subscriptionId;
    }

    public function getSubscriptionDescription(): string
    {
        return $this->subscriptionDescription;
    }

    public function getEventTime(): DateTimeImmutable
    {
        return $this->eventTime;
    }

    public function isSandbox(): bool
    {
        return $this->sandbox;
    }

    /**
     * Delivery attempt number: 0 on the first delivery, 1 to 8 on retries.
     */
    public function getRetryCount(): int
    {
        return $this->retryCount;
    }

    /**
     * Whether this delivery is a retry of an event already sent before.
     */
    public function isRetry(): bool
    {
        return $this->retryCount > 0;
    }

    /**
     * The event specific part of the payload ("data" key).
     *
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        $data = $this->getPayload()['data'] ?? [];

        if (!\is_array($data)) {
            return [];
        }

        $map = [];
        foreach ($data as $key => $value) {
            $map[(string) $key] = $value;
        }

        return $map;
    }
}
