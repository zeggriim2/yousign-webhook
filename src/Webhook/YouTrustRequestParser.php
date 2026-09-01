<?php

declare(strict_types=1);

namespace Zeggriim\YouTrustWebhookBundle\Webhook;

use JsonException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\ChainRequestMatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher\IsJsonRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher\MethodRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RemoteEvent\Exception\ParseException;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Symfony\Component\Webhook\Client\AbstractRequestParser;
use Symfony\Component\Webhook\Exception\RejectWebhookException;
use Zeggriim\YouTrustWebhookBundle\RemoteEvent\YouTrustRemoteEvent;
use Zeggriim\YouTrustWebhookBundle\Security\YouTrustIpChecker;
use Zeggriim\YouTrustWebhookBundle\Security\YouTrustSignatureVerifier;

/**
 * Parses incoming Yousign (YouTrust) webhook requests for the Symfony Webhook component.
 *
 * Wire it through the framework configuration, once per webhook subscription:
 *
 *     framework:
 *         webhook:
 *             routing:
 *                 yousign:
 *                     service: Zeggriim\YouTrustWebhookBundle\Webhook\YouTrustRequestParser
 *                     secret: '%env(YOUSIGN_WEBHOOK_SECRET)%'
 *
 * @author Lilian D'orazio <lilian.dorazio@hotmail.fr>
 */
final class YouTrustRequestParser extends AbstractRequestParser
{
    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly YouTrustConverter $converter,
        ?LoggerInterface $logger = null,
        private readonly ?YouTrustIpChecker $ipChecker = null,
        private readonly ?YouTrustIdempotencyStore $idempotencyStore = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    protected function getRequestMatcher(): RequestMatcherInterface
    {
        return new ChainRequestMatcher([
            new MethodRequestMatcher('POST'),
            new IsJsonRequestMatcher(),
        ]);
    }

    protected function doParse(Request $request, string $secret): ?RemoteEvent
    {
        if (null !== $this->ipChecker && !$this->ipChecker->isAllowed($request)) {
            $this->logger->warning('Yousign webhook rejected: client IP is not allowed.', [
                'client_ip' => $request->getClientIp(),
            ]);

            throw new RejectWebhookException(Response::HTTP_FORBIDDEN, 'Client IP is not allowed.');
        }

        $signature = $request->headers->get(YouTrustSignatureVerifier::SIGNATURE_HEADER);

        if (!YouTrustSignatureVerifier::isValid($request->getContent(), $signature, $secret)) {
            $this->logger->warning('Yousign webhook rejected: invalid signature.');

            throw new RejectWebhookException(Response::HTTP_UNAUTHORIZED, 'Invalid signature.');
        }

        try {
            $event = $this->converter->convert(
                $this->decode($request->getContent()),
                (int) $request->headers->get(YouTrustRemoteEvent::RETRY_HEADER, '0'),
            );
        } catch (ParseException|JsonException $e) {
            $this->logger->warning('Yousign webhook rejected: invalid payload.', ['exception' => $e]);

            throw new RejectWebhookException(Response::HTTP_NOT_ACCEPTABLE, 'Invalid payload.', $e);
        }

        if (null !== $this->idempotencyStore && !$this->idempotencyStore->markAsHandled($event->getId())) {
            $this->logger->info('Yousign webhook skipped: event already handled.', [
                'event_id' => $event->getId(),
                'event_name' => $event->getName(),
                'retry_count' => $event->getRetryCount(),
            ]);

            // Returning null acknowledges the delivery without consuming it twice.
            return null;
        }

        return $event;
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
}
