<?php

declare(strict_types=1);

namespace Zeggriim\YouTrustWebhookBundle\Webhook\Payload;

use DateTimeImmutable;
use Zeggriim\YouTrustWebhookBundle\Exception\InvalidArgumentException;

/**
 * Normalized representation of a Yousign (YouTrust) webhook payload.
 *
 * Two shapes are supported:
 *  - the current YouTrust v3 shape, where the event metadata is nested
 *    under a "metadata" key;
 *  - the legacy shape, where the same keys live at the root of the payload.
 *
 * Following the "tolerant reader" recommendation of the YouTrust
 * documentation, only "event_id", "event_name" and "data" are mandatory:
 * unknown keys are ignored and missing optional keys fall back to a default.
 *
 * @author Lilian D'orazio <lilian.dorazio@hotmail.fr>
 */
final class YouTrustPayload
{
    public readonly string $eventId;
    public readonly string $eventName;

    /** @var array<string, mixed> */
    public readonly array $data;

    public readonly string $subscriptionId;
    public readonly string $subscriptionDescription;
    public readonly bool $sandbox;
    public readonly DateTimeImmutable $eventTime;

    /** @var array<string, mixed> */
    public readonly array $metadata;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(array $payload)
    {
        $metadata = self::extractMetadata($payload);

        $this->metadata = $metadata;
        $this->eventId = self::requiredString($metadata, 'event_id');
        $this->eventName = self::requiredString($metadata, 'event_name');
        $this->data = self::requiredMap($payload, 'data');
        $this->subscriptionId = self::optionalString($metadata, 'subscription_id');
        $this->subscriptionDescription = self::optionalString($metadata, 'subscription_description');
        $this->sandbox = isset($metadata['sandbox']) && filter_var($metadata['sandbox'], FILTER_VALIDATE_BOOL);
        $this->eventTime = self::readEventTime($metadata);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private static function extractMetadata(array $payload): array
    {
        if (isset($payload['metadata']) && \is_array($payload['metadata'])) {
            return self::toMap($payload['metadata']);
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $source
     */
    private static function requiredString(array $source, string $key): string
    {
        $value = $source[$key] ?? null;

        if (!\is_string($value) || '' === $value) {
            throw new InvalidArgumentException(\sprintf('The webhook payload key "%s" must be a non-empty string.', $key));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $source
     */
    private static function optionalString(array $source, string $key): string
    {
        $value = $source[$key] ?? null;

        return \is_scalar($value) ? (string) $value : '';
    }

    /**
     * @param array<string, mixed> $source
     *
     * @return array<string, mixed>
     */
    private static function requiredMap(array $source, string $key): array
    {
        $value = $source[$key] ?? null;

        if (!\is_array($value)) {
            throw new InvalidArgumentException(\sprintf('The webhook payload key "%s" must be an object.', $key));
        }

        return self::toMap($value);
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private static function readEventTime(array $metadata): DateTimeImmutable
    {
        $value = $metadata['event_time'] ?? null;

        if (\is_int($value) || (\is_string($value) && is_numeric($value))) {
            return (new DateTimeImmutable())->setTimestamp((int) $value);
        }

        return new DateTimeImmutable();
    }

    /**
     * @return array<string, mixed>
     */
    private static function toMap(mixed $value): array
    {
        if (!\is_array($value)) {
            return [];
        }

        $map = [];
        foreach ($value as $key => $item) {
            $map[(string) $key] = $item;
        }

        return $map;
    }
}
