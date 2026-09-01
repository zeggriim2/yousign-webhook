<?php

declare(strict_types=1);

namespace Zeggriim\YouTrustWebhookBundle\Security;

use Symfony\Component\HttpFoundation\Request;

/**
 * Verifies the HMAC-SHA256 signature sent by Yousign (YouTrust).
 *
 * The signature is always computed against the *raw* request body: never hash
 * a decoded and re-encoded payload, the bytes would differ.
 *
 * @author Lilian D'orazio <zeggriim@gmail.com>
 */
final class YouTrustSignatureVerifier
{
    public const SIGNATURE_HEADER = 'X-Yousign-Signature-256';

    private const SIGNATURE_PREFIX = 'sha256=';

    public function __construct(private readonly string $secret)
    {
    }

    public function verifySignature(Request $request): bool
    {
        return $this->verify($request->getContent(), $request->headers->get(self::SIGNATURE_HEADER));
    }

    /**
     * @param string|null $signature the raw value of the signature header, e.g. "sha256=<hex digest>"
     */
    public function verify(string $rawBody, ?string $signature): bool
    {
        return self::isValid($rawBody, $signature, $this->secret);
    }

    /**
     * Same check against an explicitly provided secret, for setups holding one
     * secret per webhook subscription.
     */
    public static function isValid(string $rawBody, ?string $signature, string $secret): bool
    {
        if ('' === $secret || null === $signature) {
            return false;
        }

        $signature = trim($signature);

        if (!str_starts_with(strtolower($signature), self::SIGNATURE_PREFIX)) {
            return false;
        }

        $expected = self::SIGNATURE_PREFIX.hash_hmac('sha256', $rawBody, $secret);

        // The computed value comes first: it is the known-good one.
        return hash_equals($expected, self::SIGNATURE_PREFIX.substr($signature, \strlen(self::SIGNATURE_PREFIX)));
    }
}
