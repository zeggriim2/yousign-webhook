<?php

declare(strict_types=1);

namespace Zeggriim\YousignWebhookBundle;

use Zeggriim\YouTrustWebhookBundle\YouTrustWebhookBundle;

/**
 * Backward compatible bundle keeping the "yousign_webhook" configuration key.
 *
 * @deprecated since 1.0, use {@see YouTrustWebhookBundle} and the
 *             "youtrust_webhook" configuration key instead. Removed in 2.0.
 */
class YousignWebhookBundle extends YouTrustWebhookBundle
{
    protected string $extensionAlias = 'yousign_webhook';

    /**
     * The bundle resources live one directory up, next to the new bundle class.
     */
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function __construct()
    {
        trigger_deprecation(
            'zeggriim/youtrust-webhook-bundle',
            '1.0',
            'The "%s" bundle is deprecated, register "%s" instead and rename the "yousign_webhook" configuration key to "youtrust_webhook".',
            self::class,
            YouTrustWebhookBundle::class,
        );
    }
}
