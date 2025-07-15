<?php

declare(strict_types=1);

namespace Zeggriim\YousignWebhookBundle\Security;

use Symfony\Component\HttpFoundation\Request;

/**
 * @author Lilian D'orazio <zeggriim@gmail.com>
 */
final class YousignSignatureVerifier
{
    public function __construct(private readonly string $secret)
    {
    }

    public function verifySignature(Request $request): bool
    {
        $expectedSignature = $request->headers->get('X-Yousign-Signature-256') ?? null;

        if (null === $expectedSignature) {
            return true;
        }

        $digest = hash_hmac('sha256', $request->getContent(), $this->secret);
        $computedSignature = sprintf('sha256=%s', $digest);

        return hash_equals($expectedSignature, $computedSignature);
    }
}
