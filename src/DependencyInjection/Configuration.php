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
                ->scalarNode('type')
                    ->defaultValue('yousign')
                    ->info('RemoteEvent consumer name the events are dispatched to')
                ->end()
                ->arrayNode('idempotency')
                    ->info('Skip events already handled, identified by their event_id.')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultFalse()->end()
                        ->scalarNode('pool')
                            ->defaultValue('cache.app')
                            ->cannotBeEmpty()
                            ->info('PSR-6 cache pool service id')
                        ->end()
                        ->integerNode('ttl')
                            ->defaultValue(86400)
                            ->min(1)
                            ->info('How long an event id is remembered, in seconds')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('allowed_ips')
                    ->info('Optional allowlist of Yousign delivery IPs or CIDR ranges. Empty means disabled.')
                    ->scalarPrototype()->cannotBeEmpty()->end()
                    ->defaultValue([])
                ->end()
            ->end();

        return $treeBuilder;
    }
}