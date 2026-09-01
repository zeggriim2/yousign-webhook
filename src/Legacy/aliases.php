<?php

declare(strict_types=1);

/*
 * Backward compatibility layer for the Yousign -> YouTrust renaming.
 *
 * Old class names keep working through lazily created aliases: the target
 * class is only loaded when the deprecated name is actually used.
 *
 * Deprecated since 1.0, removed in 2.0.
 */

const ZEGGRIIM_YOUTRUST_LEGACY_CLASSES = [
    'Zeggriim\YousignWebhookBundle\Controller\YousignWebhookController' => 'Zeggriim\YouTrustWebhookBundle\Controller\YouTrustWebhookController',
    'Zeggriim\YousignWebhookBundle\RemoteEvent\Consumer\AbstractYousignConsumer' => 'Zeggriim\YouTrustWebhookBundle\RemoteEvent\Consumer\AbstractYouTrustConsumer',
    'Zeggriim\YousignWebhookBundle\Enum\YousignEvent' => 'Zeggriim\YouTrustWebhookBundle\Enum\YouTrustEvent',
    'Zeggriim\YousignWebhookBundle\Exception\ExceptionInterface' => 'Zeggriim\YouTrustWebhookBundle\Exception\ExceptionInterface',
    'Zeggriim\YousignWebhookBundle\Exception\InvalidArgumentException' => 'Zeggriim\YouTrustWebhookBundle\Exception\InvalidArgumentException',
    'Zeggriim\YousignWebhookBundle\RemoteEvent\YousignRemoteEvent' => 'Zeggriim\YouTrustWebhookBundle\RemoteEvent\YouTrustRemoteEvent',
    'Zeggriim\YousignWebhookBundle\Security\YousignIpChecker' => 'Zeggriim\YouTrustWebhookBundle\Security\YouTrustIpChecker',
    'Zeggriim\YousignWebhookBundle\Security\YousignSignatureVerifier' => 'Zeggriim\YouTrustWebhookBundle\Security\YouTrustSignatureVerifier',
    'Zeggriim\YousignWebhookBundle\Webhook\Payload\YousignPayload' => 'Zeggriim\YouTrustWebhookBundle\Webhook\Payload\YouTrustPayload',
    'Zeggriim\YousignWebhookBundle\Webhook\YousignConverter' => 'Zeggriim\YouTrustWebhookBundle\Webhook\YouTrustConverter',
    'Zeggriim\YousignWebhookBundle\Webhook\YousignIdempotencyStore' => 'Zeggriim\YouTrustWebhookBundle\Webhook\YouTrustIdempotencyStore',
    'Zeggriim\YousignWebhookBundle\Webhook\YousignRequestParser' => 'Zeggriim\YouTrustWebhookBundle\Webhook\YouTrustRequestParser',
];

spl_autoload_register(static function (string $class): void {
    $target = ZEGGRIIM_YOUTRUST_LEGACY_CLASSES[$class] ?? null;

    if (null === $target) {
        return;
    }

    trigger_deprecation(
        'zeggriim/youtrust-webhook-bundle',
        '1.0',
        'The "%s" class is deprecated, use "%s" instead.',
        $class,
        $target,
    );

    class_alias($target, $class);
});
