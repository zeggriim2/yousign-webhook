<?php

declare(strict_types=1);

namespace Zeggriim\YouTrustWebhookBundle\Tests\Security;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Zeggriim\YouTrustWebhookBundle\Security\YouTrustSignatureVerifier;

/**
 * @internal
 *
 * @coversNothing
 */
final class YouTrustSignatureVerifierTest extends TestCase
{
    private const SECRET = 'keySecret';

    public function testItAcceptsAValidSignature(): void
    {
        $body = '{"metadata":{},"data":{}}';

        self::assertTrue((new YouTrustSignatureVerifier(self::SECRET))->verify($body, self::sign($body)));
    }

    public function testItAcceptsTheSignatureFromTheRequestHeader(): void
    {
        $body = '{"metadata":{},"data":{}}';
        $request = new Request(content: $body);
        // HeaderBag lookups are case-insensitive, mirroring what Yousign sends.
        $request->headers->set('x-yousign-signature-256', self::sign($body));

        self::assertTrue((new YouTrustSignatureVerifier(self::SECRET))->verifySignature($request));
    }

    #[DataProvider('provideItRejectsInvalidSignaturesCases')]
    public function testItRejectsInvalidSignatures(?string $signature): void
    {
        self::assertFalse((new YouTrustSignatureVerifier(self::SECRET))->verify('{"data":{}}', $signature));
    }

    /**
     * @return iterable<string, array{string|null}>
     */
    public static function provideItRejectsInvalidSignaturesCases(): iterable
    {
        yield 'missing header' => [null];
        yield 'empty header' => [''];
        yield 'missing prefix' => [hash_hmac('sha256', '{"data":{}}', self::SECRET)];
        yield 'wrong algorithm prefix' => ['sha1='.hash_hmac('sha256', '{"data":{}}', self::SECRET)];
        yield 'wrong digest' => ['sha256='.hash_hmac('sha256', 'tampered', self::SECRET)];
        yield 'wrong secret' => ['sha256='.hash_hmac('sha256', '{"data":{}}', 'other-secret')];
        yield 'not hexadecimal' => ['sha256=nope'];
    }

    public function testItRejectsEverythingWhenTheSecretIsEmpty(): void
    {
        $body = '{"data":{}}';

        self::assertFalse((new YouTrustSignatureVerifier(''))->verify($body, 'sha256='.hash_hmac('sha256', $body, '')));
    }

    public function testTheSignatureIsComputedOnTheRawBody(): void
    {
        $verifier = new YouTrustSignatureVerifier(self::SECRET);
        $raw = '{"data":   {"a":1}}';

        self::assertTrue($verifier->verify($raw, self::sign($raw)));
        // Re-encoding the payload changes the bytes, hence the signature.
        self::assertFalse($verifier->verify('{"data":{"a":1}}', self::sign($raw)));
    }

    private static function sign(string $body): string
    {
        return 'sha256='.hash_hmac('sha256', $body, self::SECRET);
    }
}
