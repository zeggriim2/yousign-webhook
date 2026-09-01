<?php

declare(strict_types=1);

namespace Zeggriim\YouTrustWebhookBundle\Tests\Legacy;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Zeggriim\YouTrustWebhookBundle\Tests\Functional\TestKernel;
use Zeggriim\YouTrustWebhookBundle\Webhook\YouTrustRequestParser;

/**
 * The Yousign -> YouTrust renaming must not break existing installations.
 *
 * @internal
 *
 * @coversNothing
 */
#[IgnoreDeprecations]
final class BackwardCompatibilityTest extends TestCase
{
    /**
     * @param class-string $legacy
     * @param class-string $current
     */
    #[DataProvider('provideDeprecatedClassNamesStillResolveCases')]
    public function testDeprecatedClassNamesStillResolve(string $legacy, string $current): void
    {
        self::assertTrue(class_exists($legacy) || interface_exists($legacy) || enum_exists($legacy));
        self::assertTrue(is_a($legacy, $current, true), \sprintf('"%s" should alias "%s".', $legacy, $current));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideDeprecatedClassNamesStillResolveCases(): iterable
    {
        /** @var array<string, string> $map */
        $map = ZEGGRIIM_YOUTRUST_LEGACY_CLASSES;

        foreach ($map as $legacy => $current) {
            yield $legacy => [$legacy, $current];
        }
    }

    public function testTheDeprecatedBundleKeepsTheOldConfigurationKey(): void
    {
        $kernel = new TestKernel(['legacy_controller' => false], legacyBundle: true);
        $kernel->boot();

        try {
            $container = $kernel->getContainer()->get('test.service_container');
            \assert($container instanceof \Psr\Container\ContainerInterface);
            self::assertInstanceOf(YouTrustRequestParser::class, $container->get(YouTrustRequestParser::class));

            $body = json_encode([
                'metadata' => ['event_id' => 'id', 'event_name' => 'signer.done'],
                'data' => ['signer' => ['id' => 'signer-1']],
            ], JSON_THROW_ON_ERROR);

            $response = $kernel->handle(Request::create(
                '/webhook/yousign',
                'POST',
                server: [
                    'CONTENT_TYPE' => 'application/json',
                    'HTTP_X_YOUSIGN_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $body, TestKernel::WEBHOOK_SECRET),
                ],
                content: $body,
            ));

            self::assertSame(Response::HTTP_ACCEPTED, $response->getStatusCode());
        } finally {
            $cacheDir = $kernel->getCacheDir();
            $kernel->shutdown();
            (new Filesystem())->remove($cacheDir);
        }
    }
}
