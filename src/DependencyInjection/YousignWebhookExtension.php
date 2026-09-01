<?php

declare(strict_types=1);

namespace Zeggriim\YousignWebhookBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Webmozart\Assert\Assert;
use Zeggriim\YousignWebhookBundle\Webhook\YousignIdempotencyStore;

final class YousignWebhookExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        Assert::string($config['secret']);
        Assert::string($config['endpoint']);
        Assert::string($config['type']);
        Assert::isArray($config['allowed_ips']);
        Assert::allString($config['allowed_ips']);

        $container->setParameter('yousign.webhook.secret', $config['secret']);
        $container->setParameter('yousign.webhook.endpoint', $config['endpoint']);
        $container->setParameter('yousign.webhook.type', $config['type']);
        $container->setParameter('yousign.webhook.allowed_ips', array_values($config['allowed_ips']));

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        Assert::isArray($config['idempotency']);
        $idempotency = $config['idempotency'];
        Assert::boolean($idempotency['enabled']);
        Assert::string($idempotency['pool']);
        Assert::integer($idempotency['ttl']);

        $store = $container->getDefinition(YousignIdempotencyStore::class);
        $store->setArgument('$ttl', $idempotency['ttl']);

        if ($idempotency['enabled']) {
            $store->setArgument('$cache', new Reference($idempotency['pool']));
        }
    }
}
