<?php

declare(strict_types=1);

namespace Zeggriim\YousignWebhookBundle\Tests\Webhook\Payload;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Zeggriim\YousignWebhookBundle\Exception\InvalidArgumentException;
use Zeggriim\YousignWebhookBundle\Webhook\Payload\YousignPayload;

final class YousignPayloadTest extends TestCase
{
    /**
     * @param array<string, mixed> $payload
     */
    #[DataProvider('provideSupportedShapes')]
    public function testItParsesBothPayloadShapes(array $payload): void
    {
        $parsed = new YousignPayload($payload);

        $this->assertSame('b6c63685-c556-4a30-8fe9-b6f2b187d936', $parsed->eventId);
        $this->assertSame('signature_request.activated', $parsed->eventName);
        $this->assertSame('webhook-subscription-id', $parsed->subscriptionId);
        $this->assertSame('My webhook', $parsed->subscriptionDescription);
        $this->assertTrue($parsed->sandbox);
        $this->assertSame(1670855889, $parsed->eventTime->getTimestamp());
        $this->assertSame(['signature_request' => ['id' => 'xxx-xxx']], $parsed->data);
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function provideSupportedShapes(): iterable
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
        $parsed = new YousignPayload([
            'metadata' => [
                'event_id' => 'event-id',
                'event_name' => 'some.brand.new.event',
                'a_key_added_later' => 'ignored',
            ],
            'data' => [],
            'another_unknown_root_key' => ['whatever'],
        ]);

        $this->assertSame('event-id', $parsed->eventId);
        $this->assertSame('some.brand.new.event', $parsed->eventName);
        $this->assertSame('', $parsed->subscriptionId);
        $this->assertSame('', $parsed->subscriptionDescription);
        $this->assertFalse($parsed->sandbox);
        $this->assertSame([], $parsed->data);
    }

    public function testEventTimeFallsBackToNowWhenUnusable(): void
    {
        $before = time();

        $parsed = new YousignPayload([
            'metadata' => ['event_id' => 'id', 'event_name' => 'signer.done', 'event_time' => 'not-a-timestamp'],
            'data' => [],
        ]);

        $this->assertGreaterThanOrEqual($before, $parsed->eventTime->getTimestamp());
    }

    /**
     * @param array<string, mixed> $payload
     */
    #[DataProvider('provideInvalidPayloads')]
    public function testItRejectsPayloadsWithoutMandatoryKeys(array $payload): void
    {
        $this->expectException(InvalidArgumentException::class);

        new YousignPayload($payload);
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function provideInvalidPayloads(): iterable
    {
        yield 'empty payload' => [[]];
        yield 'missing event_name' => [['metadata' => ['event_id' => 'id'], 'data' => []]];
        yield 'empty event_id' => [['metadata' => ['event_id' => '', 'event_name' => 'signer.done'], 'data' => []]];
        yield 'missing data' => [['metadata' => ['event_id' => 'id', 'event_name' => 'signer.done']]];
        yield 'scalar data' => [['metadata' => ['event_id' => 'id', 'event_name' => 'signer.done'], 'data' => 'nope']];
    }
}
