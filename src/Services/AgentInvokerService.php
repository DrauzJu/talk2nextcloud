<?php

namespace App\Services;

use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;

class AgentInvokerService
{
    public function __construct(
        private readonly AgentProviderService $agentProviderService,
    )
    {}

    public function invokeAgent(string $userMessage): string
    {
        $messages = new MessageBag(
            Message::forSystem('You are a helpful assistant dealing with Nextcloud notes. Do not act in conversation mode. Always respond in a single message. Do not ask questions.'),
            Message::ofUser($userMessage)
        );
        $result = $this->agentProviderService->getAgent()->call($messages);
        $resultContent = $result->getContent();

        if (!is_string($resultContent)) {
            throw new \Exception('Model did not return a string response.');
        }

        return $resultContent;
    }
}
