<?php

declare(strict_types=1);

namespace Zeggriim\YousignWebhookBundle\Tests\Functional;

use Symfony\Component\RemoteEvent\Attribute\AsRemoteEventConsumer;
use Symfony\Component\RemoteEvent\Consumer\ConsumerInterface;
use Symfony\Component\RemoteEvent\RemoteEvent;

#[AsRemoteEventConsumer('yousign')]
final class RecordingConsumer implements ConsumerInterface
{
    /** @var list<RemoteEvent> */
    public array $events = [];

    public function consume(RemoteEvent $event): void
    {
        $this->events[] = $event;
    }
}
