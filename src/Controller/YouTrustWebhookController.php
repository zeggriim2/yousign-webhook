<?php

declare(strict_types=1);

namespace Zeggriim\YouTrustWebhookBundle\Controller;

use JsonException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\RemoteEvent\Exception\ParseException;
use Symfony\Component\RemoteEvent\Messenger\ConsumeRemoteEventMessage;
use Throwable;
use Zeggriim\YouTrustWebhookBundle\RemoteEvent\YouTrustRemoteEvent;
use Zeggriim\YouTrustWebhookBundle\Security\YouTrustIpChecker;
use Zeggriim\YouTrustWebhookBundle\Security\YouTrustSignatureVerifier;
use Zeggriim\YouTrustWebhookBundle\Webhook\YouTrustConverter;
use Zeggriim\YouTrustWebhookBundle\Webhook\YouTrustIdempotencyStore;

/**
 * @author Lilian D'orazio <lilian.dorazio@hotmail.fr>
 */
final class YouTrustWebhookController
{
    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly YouTrustConverter $converter,
        private readonly YouTrustSignatureVerifier $signatureVerifier,
        private readonly MessageBusInterface $messageBus,
        ?LoggerInterface $logger = null,
        private readonly ?YouTrustIpChecker $ipChecker = null,
        private readonly ?YouTrustIdempotencyStore $idempotencyStore = null,
        private readonly string $type = 'yousign',
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function handle(Request $request): Response
    {
        if (null !== $this->ipChecker && !$this->ipChecker->isAllowed($request)) {
            $this->logger->warning('Yousign webhook rejected: client IP is not allowed.', [
                'client_ip' => $request->getClientIp(),
            ]);

            return self::text('Forbidden', Response::HTTP_FORBIDDEN);
        }

        if (!$this->signatureVerifier->verifySignature($request)) {
            $this->logger->warning('Yousign webhook rejected: invalid signature.');

            return self::text('Invalid signature', Response::HTTP_UNAUTHORIZED);
        }

        try {
            $remoteEvent = $this->converter->convert(
                $this->decode($request->getContent()),
                (int) $request->headers->get(YouTrustRemoteEvent::RETRY_HEADER, '0'),
            );
        } catch (ParseException|JsonException $e) {
            $this->logger->warning('Yousign webhook rejected: invalid payload.', ['exception' => $e]);

            return self::text('Invalid payload', Response::HTTP_NOT_ACCEPTABLE);
        }

        if (null !== $this->idempotencyStore && !$this->idempotencyStore->markAsHandled($remoteEvent->getId())) {
            $this->logger->info('Yousign webhook skipped: event already handled.', [
                'event_id' => $remoteEvent->getId(),
                'event_name' => $remoteEvent->getName(),
                'retry_count' => $remoteEvent->getRetryCount(),
            ]);

            return new Response('', Response::HTTP_ACCEPTED);
        }

        try {
            $this->messageBus->dispatch(new ConsumeRemoteEventMessage($this->type, $remoteEvent));
        } catch (Throwable $e) {
            $this->logger->error('Yousign webhook could not be dispatched.', [
                'exception' => $e,
                'event_id' => $remoteEvent->getId(),
                'event_name' => $remoteEvent->getName(),
            ]);

            return self::text('Internal server error', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new Response('', Response::HTTP_ACCEPTED);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws JsonException when the body is not a JSON object
     */
    private function decode(string $content): array
    {
        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        if (!\is_array($decoded)) {
            throw new JsonException('The webhook body must be a JSON object.');
        }

        $payload = [];
        foreach ($decoded as $key => $value) {
            $payload[(string) $key] = $value;
        }

        return $payload;
    }

    private static function text(string $message, int $status): Response
    {
        return new Response($message, $status, ['content-type' => 'text/plain']);
    }
}
