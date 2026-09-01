<?php

declare(strict_types=1);

namespace Zeggriim\YousignWebhookBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Zeggriim\YousignWebhookBundle\Enum\YousignEvent;

/**
 * @internal
 *
 * @coversNothing
 */
final class YousignEventTest extends TestCase
{
    #[DataProvider('provideUnknownEventsResolveToNullInsteadOfThrowingCases')]
    public function testUnknownEventsResolveToNullInsteadOfThrowing(string $name): void
    {
        self::assertNull(YousignEvent::tryFrom($name));
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
        self::assertSame(YousignEvent::SIGNER_DONE, YousignEvent::tryFrom('signer.done'));
        self::assertSame(YousignEvent::WORKFLOW_SESSION_BLOCKED, YousignEvent::tryFrom('workflow_session.blocked'));
    }

    public function testGroupAndAction(): void
    {
        self::assertSame('signature_request', YousignEvent::SIGNATURE_REQUEST_DONE->group());
        self::assertSame('done', YousignEvent::SIGNATURE_REQUEST_DONE->action());

        self::assertSame('verification', YousignEvent::VERIFICATION_COMPANY_DONE->group());
        self::assertSame('done', YousignEvent::VERIFICATION_COMPANY_DONE->action());

        self::assertSame('monitoring', YousignEvent::MONITORING_NATURAL_PERSON_UPDATED->group());
        self::assertSame('updated', YousignEvent::MONITORING_NATURAL_PERSON_UPDATED->action());
    }

    public function testEveryCaseIsUniqueAndNamespaced(): void
    {
        $values = array_map(static fn (YousignEvent $event): string => $event->value, YousignEvent::cases());

        self::assertSame($values, array_values(array_unique($values)));

        foreach ($values as $value) {
            self::assertStringContainsString('.', $value, \sprintf('"%s" should be namespaced by resource.', $value));
        }
    }
}
