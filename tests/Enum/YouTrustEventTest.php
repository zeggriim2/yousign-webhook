<?php

declare(strict_types=1);

namespace Zeggriim\YouTrustWebhookBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Zeggriim\YouTrustWebhookBundle\Enum\YouTrustEvent;

/**
 * @internal
 *
 * @coversNothing
 */
final class YouTrustEventTest extends TestCase
{
    #[DataProvider('provideUnknownEventsResolveToNullInsteadOfThrowingCases')]
    public function testUnknownEventsResolveToNullInsteadOfThrowing(string $name): void
    {
        self::assertNull(YouTrustEvent::tryFrom($name));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideUnknownEventsResolveToNullInsteadOfThrowingCases(): iterable
    {
        yield 'event added after this release' => ['signature_request.something_new'];
        yield 'unknown resource' => ['not_a_resource.done'];
        yield 'empty name' => [''];
    }

    public function testKnownEventsAreResolved(): void
    {
        self::assertSame(YouTrustEvent::SIGNER_DONE, YouTrustEvent::tryFrom('signer.done'));
        self::assertSame(YouTrustEvent::WORKFLOW_SESSION_BLOCKED, YouTrustEvent::tryFrom('workflow_session.blocked'));
    }

    public function testGroupAndAction(): void
    {
        self::assertSame('signature_request', YouTrustEvent::SIGNATURE_REQUEST_DONE->group());
        self::assertSame('done', YouTrustEvent::SIGNATURE_REQUEST_DONE->action());

        self::assertSame('verification', YouTrustEvent::VERIFICATION_COMPANY_DONE->group());
        self::assertSame('done', YouTrustEvent::VERIFICATION_COMPANY_DONE->action());

        self::assertSame('monitoring', YouTrustEvent::MONITORING_NATURAL_PERSON_UPDATED->group());
        self::assertSame('updated', YouTrustEvent::MONITORING_NATURAL_PERSON_UPDATED->action());
    }

    public function testEveryCaseIsUniqueAndNamespaced(): void
    {
        $values = array_map(static fn (YouTrustEvent $event): string => $event->value, YouTrustEvent::cases());

        self::assertSame($values, array_values(array_unique($values)));

        foreach ($values as $value) {
            self::assertStringContainsString('.', $value, \sprintf('"%s" should be namespaced by resource.', $value));
        }
    }
}
