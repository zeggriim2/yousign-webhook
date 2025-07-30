<?php

declare(strict_types=1);

namespace Zeggriim\YousignWebhookBundle\Controller;

use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\RemoteEvent\Exception\ParseException;
use Symfony\Component\RemoteEvent\Messenger\ConsumeRemoteEventMessage;
use Zeggriim\YousignWebhookBundle\Security\YousignSignatureVerifier;
use Zeggriim\YousignWebhookBundle\Webhook\YousignConverter;

/**
 * @author Lilian D'orazio <lilian.dorazio@hotmail.fr>
 */
final class YousignWebhookController
{
    public function __construct(
        private readonly YousignConverter $converter,
        private readonly YousignSignatureVerifier $signatureVerifier,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public function handle(Request $request): Response
    {
        try {
            if (!$this->signatureVerifier->verifySignature($request)) {
                return new Response(
                    'Invalid signature',
                    Response::HTTP_UNAUTHORIZED,
                    ['content-type' => 'text/plain']
                );
            }

            /** @var array<string, mixed> $payload */
            $payload = $request->getPayload()->all();

            $remoteEvent = $this->converter->convert($payload);

            $this->messageBus->dispatch(new ConsumeRemoteEventMessage('yousign', $remoteEvent));

            return new Response('', Response::HTTP_ACCEPTED);
        } catch (ParseException $e) {
            return new Response(
                'Invalid payload',
                Response::HTTP_NOT_ACCEPTABLE,
                ['content-type' => 'text/plain']
            );
        } catch (Exception $e) {
            return new Response(
                'Internal server error',
                Response::HTTP_INTERNAL_SERVER_ERROR,
                ['content-type' => 'text/plain']
            );
        }
    }
}
