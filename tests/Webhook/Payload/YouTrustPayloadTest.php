<?php

declare(strict_types=1);

namespace Zeggriim\YouTrustWebhookBundle\Tests\Webhook\Payload;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Zeggriim\YouTrustWebhookBundle\Exception\InvalidArgumentException;
use Zeggriim\YouTrustWebhookBundle\Webhook\Payload\YouTrustPayload;

/**
 * @internal
 *
 * @coversNothing
 */
final class YouTrustPayloadTest extends TestCase
{
    /**
     * @param array<string, mixed> $payload
     */
    #[DataProvider('provideItParsesBothPayloadShapesCases')]
    public function testItParsesBothPayloadShapes(array $payload): void
    {
        $parsed = new YouTrustPayload($payload);

        self::assertSame('b6c63685-c556-4a30-8fe9-b6f2b187d936', $parsed->eventId);
        self::assertSame('signature_request.activated', $parsed->eventName);
        self::assertSame('webhook-subscription-id', $parsed->subscriptionId);
        self::assertSame('My webhook', $parsed->subscriptionDescription);
        self::assertTrue($parsed->sandbox);
        self::assertSame(1670855889, $parsed->eventTime->getTimestamp());
        self::assertSame(['signature_request' => ['id' => 'xxx-xxx']], $parsed->data);
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function provideItParsesBothPayloadShapesCases(): iterable
    {
        $metadata = [
            'event_id' => 'b6c63685-c556-4a30-8fe9-b6f2b187d936',
            'event_name' => 'signature_request.activated',
            'event_time' => '1670855889',
            'subscription_id' => 'webhook-subscription-id',
            'subscription_description' => 'My webhook',
            'sandbox' => true,
        ];
        $data = ['signature_request' => ['id' => 'xxx-xxx']];

        yield 'nested metadata (YouTrust v3)' => [['metadata' => $metadata, 'data' => $data]];
        yield 'flat root keys (legacy)' => [$metadata + ['data' => $data]];
    }

    public function testItToleratesMissingOptionalKeysAndUnknownOnes(): void
    {
        $parsed = new YouTrustPayload([
            'metadata' => [
                'event_id' => 'event-id',
                'event_name' => 'some.brand.new.event',
                'a_key_added_later' => 'ignored',
            ],
            'data' => [],
            'another_unknown_root_key' => ['whatever'],
        ]);

        self::assertSame('event-id', $parsed->eventId);
        self::assertSame('some.brand.new.event', $parsed->eventName);
        self::assertSame('', $parsed->subscriptionId);
        self::assertSame('', $parsed->subscriptionDescription);
        self::assertFalse($parsed->sandbox);
        self::assertSame([], $parsed->data);
    }

    public function testEventTimeFallsBackToNowWhenUnusable(): void
    {
        $before = time();

        $parsed = new YouTrustPayload([
            'metadata' => ['event_id' => 'id', 'event_name' => 'signer.done', 'event_time' => 'not-a-timestamp'],
            'data' => [],
        ]);

        self::assertGreaterThanOrEqual($before, $parsed->eventTime->getTimestamp());
    }

    /**
     * @param array<string, mixed> $payload
     */
    #[DataProvider('provideItRejectsPayloadsWithoutMandatoryKeysCases')]
    public function testItRejectsPayloadsWithoutMandatoryKeys(array $payload): void
    {
        $this->expectException(InvalidArgumentException::class);

        new YouTrustPayload($payload);
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function provideItRejectsPayloadsWithoutMandatoryKeysCases(): iterable
    {
        yield 'empty payload' => [[]];
        yield 'missing event_name' => [['metadata' => ['event_id' => 'id'], 'data' => []]];
        yield 'empty event_id' => [['metadata' => ['event_id' => '', 'event_name' => 'signer.done'], 'data' => []]];
        yield 'missing data' => [['metadata' => ['event_id' => 'id', 'event_name' => 'signer.done']]];
        yield 'scalar data' => [['metadata' => ['event_id' => 'id', 'event_name' => 'signer.done'], 'data' => 'nope']];
    }
}
