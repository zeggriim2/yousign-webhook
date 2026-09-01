<?php

declare(strict_types=1);

namespace Zeggriim\YousignWebhookBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Zeggriim\YousignWebhookBundle\Webhook\YousignRequestParser;
use Zeggriim\YousignWebhookBundle\YousignWebhookBundle;

final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public const WEBHOOK_SECRET = 'wh-secret';

    /**
     * @param array<string, mixed> $bundleConfig
     */
    public function __construct(private readonly array $bundleConfig = ['legacy_controller' => false])
    {
        parent::__construct('test', false);
    }

    /**
     * @return iterable<\Symfony\Component\HttpKernel\Bundle\BundleInterface>
     */
    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new YousignWebhookBundle();
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/yousign-webhook-bundle/'.hash('xxh128', serialize($this->bundleConfig));
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
                        'service' => YousignRequestParser::class,
                        'secret' => self::WEBHOOK_SECRET,
                    ],
                ],
            ],
        ]);

        $container->extension('yousign_webhook', $this->bundleConfig);

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
