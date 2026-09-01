<?php

declare(strict_types=1);

namespace Zeggriim\YousignWebhookBundle\Model;

use DateTimeImmutable;

/**
 * A signer, as carried by the "data.signer" key or by the signers of a
 * signature request.
 *
 * Only the properties that are stable across events are exposed; the untouched
 * payload stays available through {@see self::$raw}.
 *
 * @see https://developers.youtrust.com/reference/signer-2
 */
final class Signer
{
    /**
     * @param array<string, mixed> $raw
     */
    private function __construct(
        public readonly ?string $id,
        public readonly ?string $status,
        public readonly ?string $firstName,
        public readonly ?string $lastName,
        public readonly ?string $email,
        public readonly ?string $phoneNumber,
        public readonly ?string $locale,
        public readonly ?string $signatureLevel,
        public readonly ?DateTimeImmutable $signedAt,
        public readonly array $raw,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $info = DataReader::map($data, 'info');

        return new self(
            DataReader::string($data, 'id'),
            DataReader::string($data, 'status'),
            DataReader::string($info, 'first_name'),
            DataReader::string($info, 'last_name'),
            DataReader::string($info, 'email'),
            DataReader::string($info, 'phone_number'),
            DataReader::string($info, 'locale'),
            DataReader::string($data, 'signature_level'),
            DataReader::dateTime($data, 'signed_at'),
            $data,
        );
    }

    public function fullName(): ?string
    {
        $name = trim(\sprintf('%s %s', $this->firstName ?? '', $this->lastName ?? ''));

        return '' === $name ? null : $name;
    }
}
