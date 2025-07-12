<?php

declare(strict_types=1);

namespace Zeggriim\YousignWebhookBundle\Controller;

use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\RemoteEvent\Exception\ParseException;
use Symfony\Component\RemoteEvent\Messenger\ConsumeRemoteEventMessage;
use Zeggriim\YousignWebhookBundle\Webhook\YousignRequestParser;

/**
 * @author Lilian D'orazio <lilian.dorazio@hotmail.fr>
 */
final class YousignWebhookController
{
    public function __construct(
        private readonly YousignRequestParser $parser,
        private readonly MessageBusInterface $messageBus,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handle(Request $request): Response
    {
        try {
            if ($this->parser->verifySignature($request)) {
                $this->logger->warning('Invalid Yousign webhook signature');

                return new Response(
                    'Invalid signature',
                    Response::HTTP_UNAUTHORIZED,
                    ['content-type' => 'text/plain']
                );
            }

            /** @var array<string, mixed> $payload */
            $payload = $request->getPayload()->all();

            $remoteEvent = $this->parser->convert($payload);

            $this->messageBus->dispatch(new ConsumeRemoteEventMessage('yousign', $remoteEvent));

            $this->logger->info('Yousign webhook processed successfully', [
                'event_name' => $remoteEvent->getName(),
                'signature_request_id' => $remoteEvent->getSignatureRequestId(),
                'status' => $remoteEvent->getStatus(),
            ]);

            return new Response('', Response::HTTP_ACCEPTED);
        } catch (ParseException $e) {
            $this->logger->error('Failed to parse Yousign webhook', ['error' => $e->getMessage()]);

            return new Response(
                'Invalid payload',
                Response::HTTP_BAD_REQUEST,
                ['content-type' => 'text/plain']
            );
        } catch (Exception $e) {
            $this->logger->error('Unexpected error processing Yousign webhook', ['error' => $e->getMessage()]);

            return new Response(
                'Internal server error',
                Response::HTTP_INTERNAL_SERVER_ERROR,
                ['content-type' => 'text/plain']
            );
        }
    }
}
