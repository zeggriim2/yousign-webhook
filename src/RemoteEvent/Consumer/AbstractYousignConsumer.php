<?php

declare(strict_types=1);

namespace Zeggriim\YousignWebhookBundle\RemoteEvent\Consumer;

use Symfony\Component\RemoteEvent\Consumer\ConsumerInterface;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Zeggriim\YousignWebhookBundle\RemoteEvent\YousignRemoteEvent;

/**
 * Base consumer dispatching each Yousign (YouTrust) event to its own method,
 * so integrations do not have to write a large match on the event name.
 *
 * The method name is derived from the event name: "signature_request.done"
 * calls onSignatureRequestDone(), "verification.identity_document.done" calls
 * onVerificationIdentityDocumentDone(). Events without a matching method fall
 * back to onEvent().
 *
 *     #[AsRemoteEventConsumer('yousign')]
 *     final class MyConsumer extends AbstractYousignConsumer
 *     {
 *         protected function onSignatureRequestDone(YousignRemoteEvent $event): void
 *         {
 *             $signatureRequest = $event->getSignatureRequest();
 *             // ...
 *         }
 *     }
 */
abstract class AbstractYousignConsumer implements ConsumerInterface
{
    final public function consume(RemoteEvent $event): void
    {
        if (!$event instanceof YousignRemoteEvent) {
            return;
        }

        $method = self::methodFor($event->getName());
        $handler = [$this, $method];

        if (method_exists($this, $method) && \is_callable($handler)) {
            $handler($event);

            return;
        }

        $this->onEvent($event);
    }

    /**
     * Called for every event without a dedicated method.
     */
    protected function onEvent(YousignRemoteEvent $event): void
    {
    }

    /**
     * "signature_request.done" => "onSignatureRequestDone".
     */
    private static function methodFor(string $eventName): string
    {
        $words = ucwords(str_replace(['.', '_', '-'], ' ', $eventName));

        return 'on'.str_replace(' ', '', $words);
    }
}
