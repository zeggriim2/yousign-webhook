<?php

declare(strict_types=1);

namespace Zeggriim\YouTrustWebhookBundle\Tests\Model;

use PHPUnit\Framework\TestCase;
use Zeggriim\YouTrustWebhookBundle\Model\SignatureRequest;
use Zeggriim\YouTrustWebhookBundle\Model\Signer;

/**
 * @internal
 *
 * @coversNothing
 */
final class SignatureRequestTest extends TestCase
{
    public function testItReadsTheDocumentedPayload(): void
    {
        $request = SignatureRequest::fromArray([
            'id' => '0ef334d2-7f36-4643-8a2a-4283b4a3e4a4',
            'status' => 'ongoing',
            'name' => 'My Signature Request',
            'delivery_mode' => 'email',
            'created_at' => '2024-01-18T22:59:00Z',
            'completed_at' => null,
            'expiration_date' => '2024-01-18T22:59:59Z',
            'timezone' => 'Europe/Paris',
            'source' => 'public_api',
            'ordered_signers' => true,
            'external_id' => 'ref#1234',
            'workspace_id' => 'workspace-id',
            'sender' => ['id' => 'sender-id', 'email' => 'john.doe@example.com'],
            'signers' => [
                [
                    'id' => 'signer-1',
                    'status' => 'signed',
                    'signed_at' => '2024-01-18T22:59:00+00:00',
                    'info' => ['first_name' => 'Jane', 'last_name' => 'Doe', 'email' => 'jane@example.com'],
                ],
                'not-an-object',
            ],
        ]);

        self::assertSame('0ef334d2-7f36-4643-8a2a-4283b4a3e4a4', $request->id);
        self::assertSame('ongoing', $request->status);
        self::assertSame('email', $request->deliveryMode);
        self::assertSame('public_api', $request->source);
        self::assertTrue($request->orderedSigners);
        self::assertSame('ref#1234', $request->externalId);
        self::assertSame('john.doe@example.com', $request->senderEmail);
        self::assertSame('2024-01-18 22:59:00', $request->createdAt?->format('Y-m-d H:i:s'));
        self::assertNull($request->completedAt);
        self::assertSame('2024-01-18', $request->expirationDate?->format('Y-m-d'));

        self::assertCount(1, $request->signers);
        $signer = $request->signer('signer-1');
        self::assertInstanceOf(Signer::class, $signer);
        self::assertSame('Jane Doe', $signer->fullName());
        self::assertSame('signed', $signer->status);
        self::assertNull($request->signer('unknown'));
    }

    public function testItToleratesAnEmptyOrUnexpectedPayload(): void
    {
        $request = SignatureRequest::fromArray(['id' => 42, 'created_at' => 'not a date', 'signers' => 'nope']);

        self::assertSame('42', $request->id);
        self::assertNull($request->status);
        self::assertNull($request->createdAt);
        self::assertNull($request->orderedSigners);
        self::assertSame([], $request->signers);
    }

    public function testTheRawPayloadIsKept(): void
    {
        $raw = ['id' => 'x', 'a_property_added_later' => ['deep' => true]];

        self::assertSame($raw, SignatureRequest::fromArray($raw)->raw);
    }
}
