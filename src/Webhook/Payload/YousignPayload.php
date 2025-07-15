<?php

declare(strict_types=1);

namespace Zeggriim\YousignWebhookBundle\Webhook\Payload;

use DateTimeImmutable;
use Webmozart\Assert\Assert;

final class YousignPayload
{
    public readonly string $eventId;
    public readonly string $eventName;
    /** @var array<string, mixed> */
    public readonly array $data;
    public readonly string $subscriptionId;
    public readonly string $subscriptionDescription;
    public readonly bool $sandbox;
    public readonly DateTimeImmutable $eventTime;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(array $payload)
    {
        Assert::keyExists($payload, 'event_id');
        Assert::string($payload['event_id']);
        $this->eventId = $payload['event_id'];

        Assert::keyExists($payload, 'event_name');
        Assert::string($payload['event_name']);
        $this->eventName = $payload['event_name'];

        Assert::keyExists($payload, 'data');
        /** @var array<string, mixed> $data */
        $data = $payload['data'];
        Assert::isMap($payload['data']);
        $this->data = $data;

        Assert::keyExists($payload, 'subscription_id');
        Assert::string($payload['subscription_id']);
        $this->subscriptionId = $payload['subscription_id'];

        Assert::keyExists($payload, 'subscription_description');
        Assert::string($payload['subscription_description']);
        $this->subscriptionDescription = $payload['subscription_description'];

        $this->sandbox = isset($payload['sandbox']) && (bool) $payload['sandbox'];

        Assert::keyExists($payload, 'event_time');
        Assert::numeric($payload['event_time']);
        $timestamp = (int) $payload['event_time'];

        $this->eventTime = (new DateTimeImmutable())->setTimestamp($timestamp);
    }
}
