<?php

declare(strict_types=1);

namespace Zeggriim\YousignWebhookBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('yousign_webhook');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('secret')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->info('Yousign webhook secret')
                ->end()
                ->scalarNode('endpoint')
                    ->defaultValue('/webhook/yousign')
                    ->info('Webhook endpoint path')
                ->end()
            ->end();

        return $treeBuilder;
    }
}