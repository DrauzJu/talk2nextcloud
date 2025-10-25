<?php

namespace Talk2Nextcloud\Services\Agent;

use Exception;
use Symfony\AI\Platform\Message\Content\Audio;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;

class AgentInvokerService
{
    private const SYSTEM_MESSAGE = "
        You are a helpful AI assistant.
        You can answer general knowledge questions.
        You also have tools to interact with Nextcloud notes.

        IMPORTANT: Always respond with TEXT ONLY. Never output audio or other media formats.
        Always format your response in HTML. Make it look beautiful.
        Use headings, lists, bold, italics, and other HTML elements to structure your response.
        A dark theme must be used.

        Do not act in conversation mode.
        Always respond in a single message.
        Do not ask questions.";

    private const ADDITIONAL_SYSTEM_MESSAGE_AUDIO_INPUT =
        'Always start your response with a summary of what the user requested you to do. ';

    public function __construct(
        private readonly AgentProviderService $agentProviderService,
    )
    {}

    public function invokeAgentWithUserTextMessage(
        string $userMessage,
        string $model,
    ): string
    {
        $messages = new MessageBag(
            Message::forSystem(self::SYSTEM_MESSAGE),
            Message::ofUser($userMessage)
        );

        return $this->invokeAgent($messages, $model);
    }

    public function invokeAgentWithUserAudioMessage(
        string $audioFile,
        string $model,
        ?string $additionalTextMessage = null
    ): string
    {
        $messages = new MessageBag(
            Message::forSystem(self::SYSTEM_MESSAGE . self::ADDITIONAL_SYSTEM_MESSAGE_AUDIO_INPUT),
            Message::ofUser(Audio::fromFile($audioFile)),
        );

        if ($additionalTextMessage !== null) {
            $messages->add(Message::ofUser($additionalTextMessage));
        }

        return $this->invokeAgent($messages, $model);
    }

    private function invokeAgent(MessageBag $messages, string $model): string
    {
        $result = $this->agentProviderService->getAgent($model)->call($messages);
        $resultContent = $result->getContent();

        if (!is_string($resultContent)) {
            throw new Exception('Model did not return a string response.');
        }

        return $resultContent;
    }
}
