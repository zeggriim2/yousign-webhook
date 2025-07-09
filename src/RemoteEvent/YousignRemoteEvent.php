<?php

declare(strict_types=1);

namespace Zeggriim\YousignWebhookBundle\RemoteEvent;

use Symfony\Component\RemoteEvent\RemoteEvent;

final class YousignRemoteEvent extends RemoteEvent
{
    public function __construct(
        string $name,
        string $id,
        array $payload,
        private readonly string $signatureRequestId,
        private readonly string $status,
        private readonly ?\DateTimeImmutable $executedAt = null
    )
    {
        parent::__construct($name, $id, $payload);
    }

    public function getSignatureRequestId(): string
    {
        return $this->signatureRequestId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getExecutedAt(): ?\DateTimeImmutable
    {
        return $this->executedAt;
    }
}