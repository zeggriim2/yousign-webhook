<?php

declare(strict_types=1);

namespace Zeggriim\YousignWebhookBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Webmozart\Assert\Assert;

final class YousignWebhookExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        Assert::string($config['secret']);
        Assert::string($config['endpoint']);
        Assert::string($config['type']);

        $container->setParameter('yousign.webhook.secret', $config['secret']);
        $container->setParameter('yousign.webhook.endpoint', $config['endpoint']);
        $container->setParameter('yousign.webhook.type', $config['type']);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');
    }
}
