<?php

namespace App\Services;

use Symfony\AI\Platform\Message\Content\Audio;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;

class AgentInvokerService
{
    private const SYSTEM_MESSAGE = 'You are a helpful assistant dealing with Nextcloud notes. '
        . 'Do not act in conversation mode. Always respond in a single message. Do not ask questions.';

    public function __construct(
        private readonly AgentProviderService $agentProviderService,
    )
    {}

    public function invokeAgentWithUserTextMessage(string $userMessage): string
    {
        $messages = new MessageBag(
            Message::forSystem(self::SYSTEM_MESSAGE),
            Message::ofUser($userMessage)
        );

        return $this->invokeAgent($messages);
    }

    public function invokeAgentWithUserAudioMessage(string $audioFile): string
    {
        $messages = new MessageBag(
            Message::forSystem(self::SYSTEM_MESSAGE),
            Message::ofUser(Audio::fromFile($audioFile)),
        );

        return $this->invokeAgent($messages);
    }

    private function invokeAgent(MessageBag $messages): string
    {
        $result = $this->agentProviderService->getAgent()->call($messages);
        $resultContent = $result->getContent();

        if (!is_string($resultContent)) {
            throw new \Exception('Model did not return a string response.');
        }

        return $resultContent;
    }
}
