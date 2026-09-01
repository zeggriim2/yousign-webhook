<?php

declare(strict_types=1);

namespace Zeggriim\YouTrustWebhookBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Zeggriim\YousignWebhookBundle\YousignWebhookBundle;
use Zeggriim\YouTrustWebhookBundle\Webhook\YouTrustRequestParser;
use Zeggriim\YouTrustWebhookBundle\YouTrustWebhookBundle;

final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public const WEBHOOK_SECRET = 'wh-secret';

    /**
     * @param array<string, mixed> $bundleConfig
     * @param bool                 $legacyBundle register the deprecated bundle and its "yousign_webhook" key
     */
    public function __construct(
        private readonly array $bundleConfig = ['legacy_controller' => false],
        private readonly bool $legacyBundle = false,
    ) {
        parent::__construct('test', false);
    }

    /**
     * @return iterable<\Symfony\Component\HttpKernel\Bundle\BundleInterface>
     */
    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield $this->legacyBundle ? new YousignWebhookBundle() : new YouTrustWebhookBundle();
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/youtrust-webhook-bundle/'.hash('xxh128', serialize([$this->bundleConfig, $this->legacyBundle]));
    }

    public function getLogDir(): string
    {
        return $this->getCacheDir().'/log';
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'test',
            'test' => true,
            'http_method_override' => false,
            'router' => ['utf8' => true],
            'webhook' => [
                'routing' => [
                    'yousign' => [
                        'service' => YouTrustRequestParser::class,
                        'secret' => self::WEBHOOK_SECRET,
                    ],
                ],
            ],
        ]);

        $container->extension($this->legacyBundle ? 'yousign_webhook' : 'youtrust_webhook', $this->bundleConfig);

        $container->services()
            ->set(RecordingConsumer::class)
            ->autoconfigure()
            ->public()
        ;
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        // Route exposed by the Webhook component: POST /webhook/{type}
        $routes->import('@FrameworkBundle/Resources/config/routing/webhook.php')->prefix('/webhook');
    }
}
