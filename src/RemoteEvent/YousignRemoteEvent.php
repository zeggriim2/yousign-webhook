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
}
