<?php

declare(strict_types=1);

namespace Zeggriim\YouTrustWebhookBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Zeggriim\YouTrustWebhookBundle\Webhook\YouTrustIdempotencyStore;

class YouTrustWebhookBundle extends AbstractBundle
{
    /**
     * Pinned so the configuration key stays "youtrust_webhook" instead of the
     * "you_trust_webhook" the class name would produce.
     */
    protected string $extensionAlias = 'youtrust_webhook';

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
            ->scalarNode('secret')
            ->defaultValue('')
            ->info('Yousign webhook secret, used by the deprecated built-in controller. Prefer framework.webhook.routing.')
            ->end()
            ->scalarNode('endpoint')
            ->defaultValue('/webhook/yousign')
            ->info('Path of the deprecated built-in route.')
            ->end()
            ->scalarNode('type')
            ->defaultValue('yousign')
            ->cannotBeEmpty()
            ->info('RemoteEvent consumer name the events are dispatched to by the deprecated built-in controller.')
            ->end()
            ->booleanNode('legacy_controller')
            ->defaultTrue()
            ->info('Register the deprecated built-in controller and route. Set to false once framework.webhook.routing is configured.')
            ->end()
            ->arrayNode('idempotency')
            ->info('Skip events already handled, identified by their event_id.')
            ->addDefaultsIfNotSet()
            ->children()
            ->booleanNode('enabled')->defaultFalse()->end()
            ->scalarNode('pool')
            ->defaultValue('cache.app')
            ->cannotBeEmpty()
            ->info('PSR-6 cache pool service id.')
            ->end()
            ->integerNode('ttl')
            ->defaultValue(86400)
            ->min(1)
            ->info('How long an event id is remembered, in seconds.')
            ->end()
            ->end()
            ->end()
            ->arrayNode('allowed_ips')
            ->info('Optional allowlist of Yousign delivery IPs or CIDR ranges. Empty means disabled.')
            ->scalarPrototype()->cannotBeEmpty()->end()
            ->defaultValue([])
            ->end()
            ->end()
        ;
    }

    /**
     * @param array{
     *     secret: string,
     *     endpoint: string,
     *     type: string,
     *     legacy_controller: bool,
     *     idempotency: array{enabled: bool, pool: string, ttl: int},
     *     allowed_ips: list<string>,
     * } $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $builder->setParameter('yousign.webhook.secret', $config['secret']);
        $builder->setParameter('yousign.webhook.endpoint', $config['endpoint']);
        $builder->setParameter('yousign.webhook.type', $config['type']);
        $builder->setParameter('yousign.webhook.allowed_ips', $config['allowed_ips']);

        $container->import(__DIR__.'/Resources/config/services.php');

        $store = $builder->getDefinition(YouTrustIdempotencyStore::class);
        $store->setArgument('$ttl', $config['idempotency']['ttl']);

        if ($config['idempotency']['enabled']) {
            $store->setArgument('$cache', new Reference($config['idempotency']['pool']));
        }

        if (!$config['legacy_controller']) {
            return;
        }

        if ('' === $config['secret']) {
            throw new InvalidConfigurationException('The "secret" option is required as long as "legacy_controller" is enabled.');
        }

        trigger_deprecation(
            'zeggriim/yousign-webhook-bundle',
            '1.0',
            'The built-in webhook controller and route are deprecated and will be removed in 2.0. Configure "framework.webhook.routing" with "%s" instead, then set "youtrust_webhook.legacy_controller" to false.',
            'Zeggriim\YouTrustWebhookBundle\Webhook\YouTrustRequestParser',
        );

        $container->import(__DIR__.'/Resources/config/legacy.php');
    }
}
