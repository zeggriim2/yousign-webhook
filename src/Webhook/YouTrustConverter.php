<?php

declare(strict_types=1);

namespace Zeggriim\YouTrustWebhookBundle\Webhook;

use Symfony\Component\RemoteEvent\Exception\ParseException;
use Symfony\Component\RemoteEvent\PayloadConverterInterface;
use Throwable;
use Zeggriim\YouTrustWebhookBundle\RemoteEvent\YouTrustRemoteEvent;
use Zeggriim\YouTrustWebhookBundle\Webhook\Payload\YouTrustPayload;

final class YouTrustConverter implements PayloadConverterInterface
{
    /**
     * @param array<string, mixed> $payload
     * @param int                  $retryCount delivery attempt number, read from the X-Yousign-Retry header
     */
    public function convert(array $payload, int $retryCount = 0): YouTrustRemoteEvent
    {
        try {
            $wrapped = new YouTrustPayload($payload);
        } catch (Throwable $e) {
            throw new ParseException('Invalid Yousign payload: '.$e->getMessage(), 0, $e);
        }

        return new YouTrustRemoteEvent(
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
