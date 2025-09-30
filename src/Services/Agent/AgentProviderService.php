<?php

namespace Talk2Nextcloud\Services\Agent;

use Psr\Log\LoggerInterface;
use Symfony\AI\Agent\Agent;
use Symfony\AI\Agent\Toolbox\AgentProcessor;
use Symfony\AI\Agent\Toolbox\Toolbox;
use Symfony\AI\Platform\Bridge\Gemini\Gemini;
use Symfony\AI\Platform\Bridge\Gemini\PlatformFactory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpClient\HttpClient;

class AgentProviderService
{
    public function __construct(
        #[AutowireIterator('symfony.ai.agent.toolbox.tool')]
        private readonly iterable $agentTools,
        #[Autowire('%env(GEMINI_API_KEY)%')]
        private readonly string $geminiApiKey,
        private readonly LoggerInterface $logger,
    ) {}

    public function getAgent(): Agent
    {
        $platform = PlatformFactory::create($this->geminiApiKey, HttpClient::create());
        $toolbox = new Toolbox($this->agentTools, logger: $this->logger);
        $agentProcessor = new AgentProcessor($toolbox);

        return new Agent(
            platform: $platform,
            model: 'gemini-2.5-pro',
            inputProcessors: [$agentProcessor],
            outputProcessors: [$agentProcessor],
            logger: $this->logger,
        );
    }
}
