<?php

declare(strict_types=1);

namespace Zeggriim\YousignWebhookBundle\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\RemoteEvent\Exception\ParseException;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Symfony\Component\Routing\Attribute\Route;
use Zeggriim\YousignWebhookBundle\Webhook\YousignRequestParser;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class YousignWebhookController
{
    public function __construct(
        private readonly YousignRequestParser $parser,
        private readonly MessageBusInterface $messageBus,
        private readonly LoggerInterface $logger
    ) {
    }

    #[Route('/webhook/yousign', name: 'yousign_webhook', methods: [Request::METHOD_POST])]
    public function handle(Request $request)
    {
        try {
            if ($this->parser->verifySignature($request)) {
                $this->logger->warning('Invalid Yousign webhook signature');
                return new JsonResponse(['error' => 'Invalid signature'], Response::HTTP_UNAUTHORIZED);
            }

            $payload = json_decode($request->getContent(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new ParseException('Invalid JSON payload');
            }

            $remoteEvent = $this->parser->convert($payload);

            $this->messageBus->dispatch($remoteEvent);

            $this->logger->info('Yousign webhook processed successfully', [
                'event_name' => $remoteEvent->getName(),
                'signature_request_id' => $remoteEvent->getSignatureRequestId(),
                'status' => $remoteEvent->getStatus(),
            ]);

            return new JsonResponse(['status' => 'accepted']);
        } catch (ParseException $e) {
            $this->logger->error('Failed to parse Yousign webhook', ['error' => $e->getMessage()]);
            return new JsonResponse(['error' => 'Invalid payload'], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error processing Yousign webhook', ['error' => $e->getMessage()]);
            return new JsonResponse(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


}