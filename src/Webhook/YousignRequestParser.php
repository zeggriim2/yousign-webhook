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
        $data = $payload['data'] ?? null;

        if (!is_string($eventName) || !is_array($data)) {
            throw new ParseException('Missing or invalid required fields in Yousign webhook payload');
        }

        $signatureRequest = $data['signature_request'] ?? null;

        if (!is_array($signatureRequest)) {
            throw new ParseException('Missing or invalid "signature_request" structure');
        }

        $signatureRequestId = $signatureRequest['id'] ?? null;
        $status = $signatureRequest['status'] ?? null;

        Assert::string($signatureRequestId);
        Assert::string($status);

        $executedAt = null;
        if (isset($data['executed_at'])) {
            Assert::string($data['executed_at']);
            $executedAt = new DateTimeImmutable($data['executed_at']);
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

        if (null === $expectedSignature) {
            return false;
        }

        $digest = hash_hmac('sha256', $request->getContent(), $this->secret);
        $computedSignature = sprintf('sha256=%s', $digest);

        return hash_equals($expectedSignature, $computedSignature);
    }
}
