<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Zeggriim\YousignWebhookBundle\Security\YousignIpChecker;
use Zeggriim\YousignWebhookBundle\Security\YousignSignatureVerifier;
use Zeggriim\YousignWebhookBundle\Webhook\YousignConverter;
use Zeggriim\YousignWebhookBundle\Webhook\YousignIdempotencyStore;
use Zeggriim\YousignWebhookBundle\Webhook\YousignRequestParser;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
    ;

    $services->set(YousignConverter::class);

    $services->set(YousignIdempotencyStore::class)
        ->arg('$cache', null)
    ;

    $services->set(YousignRequestParser::class)
        ->arg('$logger', service('logger')->nullOnInvalid())
        ->tag('monolog.logger', ['channel' => 'yousign'])
    ;

    $services->set(YousignIpChecker::class)
        ->arg('$allowedIps', param('yousign.webhook.allowed_ips'))
    ;

    $services->set(YousignSignatureVerifier::class)
        ->arg('$secret', param('yousign.webhook.secret'));
};
