<?php

declare(strict_types=1);

namespace App\Controller;

use App\Imperium\Runtime\LaCortine\AgentMailWebhookVerifier;
use App\Imperium\Runtime\LaCortine\InboundArtifactStore;
use App\Imperium\Runtime\LaCortine\InboundExternalPayload;
use App\Imperium\Runtime\LaCortine\InboundLazaretto;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class AgentMailWebhookController
{
    public function __construct(
        private AgentMailWebhookVerifier $verifier,
        private InboundLazaretto $lazaretto,
        private InboundArtifactStore $store,
    ) {
    }

    #[Route('/lacortine/inbound/agentmail', name: 'lacortine_inbound_agentmail', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        $rawBody = $request->getContent();
        if ('' === $rawBody) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        $headers = [];
        foreach (['svix-id', 'svix-timestamp', 'svix-signature', 'webhook-id', 'webhook-timestamp', 'webhook-signature'] as $name) {
            $value = $request->headers->get($name);
            if (is_string($value)) {
                $headers[$name] = $value;
            }
        }

        try {
            $svixId = $this->verifier->verify($rawBody, $headers);
            $receivedAt = new \DateTimeImmutable();
            $artifact = $this->lazaretto->admit(new InboundExternalPayload(
                'agentmail-webhook.'.$svixId,
                'agentmail.webhook',
                $rawBody,
                [
                    'provider' => 'agentmail',
                    'svix_id' => $svixId,
                ],
                $receivedAt,
            ), $receivedAt);
            $this->store->persistOnce($svixId, $artifact);
        } catch (\Throwable) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
