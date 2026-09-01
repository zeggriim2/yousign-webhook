<?php

declare(strict_types=1);

/*
 * Deprecated since 0.3, removed in 1.0: configure framework.webhook.routing
 * with the YousignRequestParser instead of this controller.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Zeggriim\YousignWebhookBundle\Controller\YousignWebhookController;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->set(YousignWebhookController::class)
        ->arg('$logger', service('logger')->nullOnInvalid())
        ->arg('$type', param('yousign.webhook.type'))
        ->tag('controller.service_arguments')
        ->tag('monolog.logger', ['channel' => 'yousign'])
    ;
};
