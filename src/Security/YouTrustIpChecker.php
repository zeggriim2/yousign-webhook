<?php

declare(strict_types=1);

namespace Zeggriim\YouTrustWebhookBundle\Security;

use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Request;

/**
 * Optional allowlist of the IP ranges Yousign (YouTrust) delivers webhooks from.
 *
 * Disabled as long as no range is configured. Enable it only when your app is
 * reached directly by Yousign, or when your reverse proxies are declared as
 * trusted proxies, otherwise the client IP cannot be trusted.
 *
 * @see https://developers.youtrust.com/docs/use-webhooks-in-your-app
 */
final class YouTrustIpChecker
{
    /**
     * Ranges documented by YouTrust. Kept as a constant so applications can
     * reference them instead of hard-coding the values.
     */
    public const DOCUMENTED_RANGES = [
        '57.130.41.144/28',
        '51.38.96.112/28',
        '5.39.7.128/28',
        '52.143.162.31',
        '51.103.81.166',
    ];

    /**
     * @param list<string> $allowedIps
     */
    public function __construct(private readonly array $allowedIps = [])
    {
    }

    public function isEnabled(): bool
    {
        return [] !== $this->allowedIps;
    }

    public function isAllowed(Request $request): bool
    {
        if (!$this->isEnabled()) {
            return true;
        }

        $clientIp = $request->getClientIp();

        return null !== $clientIp && IpUtils::checkIp($clientIp, $this->allowedIps);
    }
}
