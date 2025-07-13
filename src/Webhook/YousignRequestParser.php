<?php

declare(strict_types=1);

namespace Zeggriim\YousignWebhookBundle\Webhook;

use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RemoteEvent\Exception\ParseException;
use Symfony\Component\RemoteEvent\PayloadConverterInterface;
use Webmozart\Assert\Assert;
use Zeggriim\YousignWebhookBundle\RemoteEvent\YousignRemoteEvent;

use function is_array;
use function is_string;

final class YousignRequestParser implements PayloadConverterInterface
{
    public function __construct(private readonly string $secret)
    {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function convert(array $payload): YousignRemoteEvent
    {
        $eventName = $payload['event_name'] ?? null;
        $eventId = $payload['event_id'] ?? null;
        $data = $payload['data'] ?? null;

        if (!is_string($eventId) || !is_string($eventName) || !is_array($data)) {
            throw new ParseException('Missing or invalid required fields in Yousign webhook payload');
        }

        $subscriptionId = $payload['subscription_id'] ?? null;
        $subscriptionDescription = $payload['subscription_description'] ?? null;

        Assert::string($subscriptionId);
        Assert::string($subscriptionDescription);

        Assert::keyExists($payload, 'event_time');
        Assert::numeric($payload['event_time']);

        $eventTime = (int) $payload['event_time'];

        $datetimeEventTime = new DateTimeImmutable();
        $datetimeEventTime = $datetimeEventTime->setTimestamp($eventTime);

        return new YousignRemoteEvent(
            $eventName,
            $eventId,
            $payload,
            $subscriptionId,
            $subscriptionDescription,
            $datetimeEventTime
        );
    }

    public function verifySignature(Request $request): bool
    {
        $expectedSignature = $request->headers->get('X-Yousign-Signature-256') ?? null;

        if (null === $expectedSignature) {
            return false;
        }

        $digest = hash_hmac('sha256', $request->getContent(), $this->secret);
        $computedSignature = sprintf('sha256=%s', $digest);

        return hash_equals($expectedSignature, $computedSignature);
    }
}
