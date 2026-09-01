<?php

declare(strict_types=1);

namespace Zeggriim\YousignWebhookBundle\Model;

use DateTimeImmutable;

/**
 * A signature request, as carried by the "data.signature_request" key.
 *
 * Only the properties that are stable across events are exposed; the untouched
 * payload stays available through {@see self::$raw}.
 *
 * @see https://developers.youtrust.com/reference/signature-request-1
 */
final class SignatureRequest
{
    /**
     * @param list<Signer>         $signers
     * @param array<string, mixed> $raw
     */
    private function __construct(
        public readonly ?string $id,
        public readonly ?string $status,
        public readonly ?string $name,
        public readonly ?string $deliveryMode,
        public readonly ?string $source,
        public readonly ?string $timezone,
        public readonly ?string $externalId,
        public readonly ?string $workspaceId,
        public readonly ?bool $orderedSigners,
        public readonly ?string $senderId,
        public readonly ?string $senderEmail,
        public readonly ?DateTimeImmutable $createdAt,
        public readonly ?DateTimeImmutable $activatedAt,
        public readonly ?DateTimeImmutable $completedAt,
        public readonly ?DateTimeImmutable $approvedAt,
        public readonly ?DateTimeImmutable $deletedAt,
        public readonly ?DateTimeImmutable $expirationDate,
        public readonly array $signers,
        public readonly array $raw,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $sender = DataReader::map($data, 'sender');

        return new self(
            DataReader::string($data, 'id'),
            DataReader::string($data, 'status'),
            DataReader::string($data, 'name'),
            DataReader::string($data, 'delivery_mode'),
            DataReader::string($data, 'source'),
            DataReader::string($data, 'timezone'),
            DataReader::string($data, 'external_id'),
            DataReader::string($data, 'workspace_id'),
            DataReader::bool($data, 'ordered_signers'),
            DataReader::string($sender, 'id'),
            DataReader::string($sender, 'email'),
            DataReader::dateTime($data, 'created_at'),
            DataReader::dateTime($data, 'activated_at'),
            DataReader::dateTime($data, 'completed_at'),
            DataReader::dateTime($data, 'approved_at'),
            DataReader::dateTime($data, 'deleted_at'),
            DataReader::dateTime($data, 'expiration_date'),
            array_map(Signer::fromArray(...), DataReader::mapList($data, 'signers')),
            $data,
        );
    }

    public function signer(string $id): ?Signer
    {
        foreach ($this->signers as $signer) {
            if ($signer->id === $id) {
                return $signer;
            }
        }

        return null;
    }
}
