<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Zeggriim\YouTrustWebhookBundle\Security\YouTrustIpChecker;
use Zeggriim\YouTrustWebhookBundle\Security\YouTrustSignatureVerifier;
use Zeggriim\YouTrustWebhookBundle\Webhook\YouTrustConverter;
use Zeggriim\YouTrustWebhookBundle\Webhook\YouTrustIdempotencyStore;
use Zeggriim\YouTrustWebhookBundle\Webhook\YouTrustRequestParser;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
    ;

    $services->set(YouTrustConverter::class);

    $services->set(YouTrustIdempotencyStore::class)
        ->arg('$cache', null)
    ;

    $services->set(YouTrustRequestParser::class)
        ->arg('$logger', service('logger')->nullOnInvalid())
        ->tag('monolog.logger', ['channel' => 'yousign'])
    ;

    $services->set(YouTrustIpChecker::class)
        ->arg('$allowedIps', param('yousign.webhook.allowed_ips'))
    ;

    $services->set(YouTrustSignatureVerifier::class)
        ->arg('$secret', param('yousign.webhook.secret'))
    ;
};
