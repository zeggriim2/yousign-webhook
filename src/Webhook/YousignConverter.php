<?php

declare(strict_types=1);

namespace Zeggriim\YousignWebhookBundle\Webhook;

use Symfony\Component\RemoteEvent\Exception\ParseException;
use Symfony\Component\RemoteEvent\PayloadConverterInterface;
use Throwable;
use Zeggriim\YousignWebhookBundle\RemoteEvent\YousignRemoteEvent;
use Zeggriim\YousignWebhookBundle\Webhook\Payload\YousignPayload;

final class YousignConverter implements PayloadConverterInterface
{
    /**
     * @param array<string, mixed> $payload
     * @param int                  $retryCount delivery attempt number, read from the X-Yousign-Retry header
     */
    public function convert(array $payload, int $retryCount = 0): YousignRemoteEvent
    {
        try {
            $wrapped = new YousignPayload($payload);
        } catch (Throwable $e) {
            throw new ParseException('Invalid Yousign payload: '.$e->getMessage(), 0, $e);
        }

        return new YousignRemoteEvent(
            $wrapped->eventName,
            $wrapped->eventId,
            $payload,
            $wrapped->subscriptionId,
            $wrapped->subscriptionDescription,
            $wrapped->sandbox,
            $wrapped->eventTime,
            max(0, $retryCount),
        );
    }
}
