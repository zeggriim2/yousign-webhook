<?php

declare(strict_types=1);

namespace Zeggriim\YousignWebhookBundle\Webhook;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RemoteEvent\Exception\ParseException;
use Symfony\Component\RemoteEvent\PayloadConverterInterface;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Zeggriim\YousignWebhookBundle\RemoteEvent\YousignRemoteEvent;

final class YousignRequestParser implements PayloadConverterInterface
{
    public function __construct(private readonly string $secret)
    {
    }

    public function convert(array $payload): YousignRemoteEvent
    {
        if (!isset($payload['event_name'], $payload['data'])) {
            throw new ParseException('Missing required fields in Yousign webhook payload');
        }

        $data = $payload['data'];
        $eventName = $payload['event_name'];

        // Extraction des données selon le type d'événement
        $signatureRequestId = $data['signature_request']['id'] ?? $data['id'] ?? '';
        $status = $data['status'] ?? $data['signature_request']['status'] ?? 'unknown';

        $executedAt = null;
        if (isset($data['executed_at'])) {
            $executedAt = new \DateTimeImmutable($data['executed_at']);
        }

        return new YousignRemoteEvent(
            $eventName,
            $signatureRequestId,
            $payload,
            $signatureRequestId,
            $status,
            $executedAt
        );
    }

    public function verifySignature(Request $request): bool
    {
        $expectedSignature = $request->headers->get('X-Yousign-Signature-256') ?? null;

        if (null == $expectedSignature) {
            return false;
        }

        $digest = hash_hmac('sha256', $request->getContent(), $this->secret);
        $computedSignature = sprintf('sha256=%s', $digest);

        return hash_equals($expectedSignature, $computedSignature);
    }
}