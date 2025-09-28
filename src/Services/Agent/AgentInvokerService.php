<?php

namespace Talk2Nextcloud\Services\Agent;

use Symfony\AI\Platform\Message\Content\Audio;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;

class AgentInvokerService
{
    private const SYSTEM_MESSAGE =
        'You are a helpful AI assistant. You can answer general knowledge questions. You also have tools to interact with Nextcloud notes. '
        . ' Do not act in conversation mode. Always respond in a single message. Do not ask questions.';

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

    public function invokeAgentWithUserAudioMessage(string $audioFile, ?string $additionalTextMessage = null): string
    {
        $messages = new MessageBag(
            Message::forSystem(self::SYSTEM_MESSAGE),
            Message::ofUser(Audio::fromFile($audioFile)),
        );

        if ($additionalTextMessage !== null) {
            $messages->add(Message::ofUser($additionalTextMessage));
        }

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
