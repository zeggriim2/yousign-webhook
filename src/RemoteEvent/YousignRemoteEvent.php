<?php

declare(strict_types=1);

namespace Zeggriim\YousignWebhookBundle\RemoteEvent;

use DateTimeImmutable;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Zeggriim\YousignWebhookBundle\Enum\YousignEvent;
use Zeggriim\YousignWebhookBundle\Model\SignatureRequest;
use Zeggriim\YousignWebhookBundle\Model\Signer;

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

    private bool $signatureRequestParsed = false;
    private ?SignatureRequest $signatureRequest = null;

    private bool $signerParsed = false;
    private ?Signer $signer = null;

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
     * The documented event this delivery matches, or null for an event Yousign
     * added after this release.
     */
    public function getEventType(): ?YousignEvent
    {
        return YousignEvent::tryFrom($this->getName());
    }

    /**
     * The signature request carried by the event, when there is one.
     */
    public function getSignatureRequest(): ?SignatureRequest
    {
        if (!$this->signatureRequestParsed) {
            $this->signatureRequestParsed = true;
            $data = $this->getData()['signature_request'] ?? null;

            if (\is_array($data)) {
                $this->signatureRequest = SignatureRequest::fromArray(self::toMap($data));
            }
        }

        return $this->signatureRequest;
    }

    /**
     * The signer carried by the event, when there is one.
     */
    public function getSigner(): ?Signer
    {
        if (!$this->signerParsed) {
            $this->signerParsed = true;
            $data = $this->getData()['signer'] ?? null;

            if (\is_array($data)) {
                $this->signer = Signer::fromArray(self::toMap($data));
            }
        }

        return $this->signer;
    }

    /**
     * The event specific part of the payload ("data" key).
     *
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        $data = $this->getPayload()['data'] ?? [];

        return \is_array($data) ? self::toMap($data) : [];
    }

    /**
     * @param array<mixed> $values
     *
     * @return array<string, mixed>
     */
    private static function toMap(array $values): array
    {
        $map = [];
        foreach ($values as $key => $value) {
            $map[(string) $key] = $value;
        }

        return $map;
    }
}
