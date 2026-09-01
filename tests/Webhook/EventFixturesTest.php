<?php

declare(strict_types=1);

namespace Zeggriim\YousignWebhookBundle\Tests\Webhook;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Zeggriim\YousignWebhookBundle\Enum\YousignEvent;
use Zeggriim\YousignWebhookBundle\Webhook\YousignConverter;

/**
 * Every documented event must be parsable, today and after Yousign adds fields.
 *
 * @internal
 *
 * @coversNothing
 */
final class EventFixturesTest extends TestCase
{
    private const FIXTURES_DIR = __DIR__.'/../Fixtures/Events';

    #[DataProvider('provideEventFixtures')]
    public function testEveryDocumentedEventIsParsed(string $eventName, string $file): void
    {
        $event = (new YousignConverter())->convert(self::load($file));

        self::assertSame($eventName, $event->getName());
        self::assertSame('d09f0367-d80a-309c-a2ed-7083991fc564', $event->getId());
        self::assertTrue($event->isSandbox());
        self::assertSame(1670855889, $event->getEventTime()->getTimestamp());
        self::assertSame(0, $event->getRetryCount());
        self::assertNotSame([], $event->getData());
        self::assertSame(YousignEvent::from($eventName), $event->getEventType());
    }

    #[DataProvider('provideEventFixtures')]
    public function testSignatureRequestAndSignerAreTypedWhenPresent(string $eventName, string $file): void
    {
        $event = (new YousignConverter())->convert(self::load($file));
        $data = $event->getData();

        self::assertSame(
            isset($data['signature_request']) ? '0ef334d2-7f36-4643-8a2a-4283b4a3e4a4' : null,
            $event->getSignatureRequest()?->id,
        );
        self::assertSame(
            isset($data['signer']) ? '550e8402-e29b-41d4-a716-446655440000' : null,
            $event->getSigner()?->id,
        );
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideEventFixtures(): iterable
    {
        foreach (glob(self::FIXTURES_DIR.'/*.json') ?: [] as $file) {
            $name = basename($file, '.json');

            yield $name => [$name, $file];
        }
    }

    public function testEveryEnumCaseHasAFixture(): void
    {
        $fixtures = array_map(
            static fn (string $file): string => basename($file, '.json'),
            glob(self::FIXTURES_DIR.'/*.json') ?: [],
        );

        $expected = array_map(static fn (YousignEvent $event): string => $event->value, YousignEvent::cases());

        sort($fixtures);
        sort($expected);

        self::assertSame($expected, $fixtures);
    }

    /**
     * @return array<string, mixed>
     */
    private static function load(string $file): array
    {
        $content = file_get_contents($file);
        self::assertIsString($content);

        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);

        $payload = [];
        foreach ($decoded as $key => $value) {
            $payload[(string) $key] = $value;
        }

        return $payload;
    }
}
