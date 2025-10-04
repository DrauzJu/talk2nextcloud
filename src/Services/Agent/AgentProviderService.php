<?php

namespace Talk2Nextcloud\Services\Agent;

use Psr\Log\LoggerInterface;
use Symfony\AI\Agent\Agent;
use Symfony\AI\Agent\Toolbox\AgentProcessor;
use Symfony\AI\Agent\Toolbox\Toolbox;
use Symfony\AI\Agent\Toolbox\FaultTolerantToolbox;
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

    public function getAgent(string $geminiModel): Agent
    {
        $platform = PlatformFactory::create($this->geminiApiKey, HttpClient::create());
        $toolbox = new Toolbox($this->agentTools, logger: $this->logger);
        $agentProcessor = new AgentProcessor(
            new FaultTolerantToolbox($toolbox),
        );

        return new Agent(
            platform: $platform,
            model: $geminiModel,
            inputProcessors: [$agentProcessor],
            outputProcessors: [$agentProcessor],
            logger: $this->logger,
        );
    }
}
