<?php

namespace App\Services;

use Psr\Log\LoggerInterface;
use Symfony\AI\Agent\Agent;
use Symfony\AI\Agent\Toolbox\AgentProcessor;
use Symfony\AI\Agent\Toolbox\Toolbox;
use Symfony\AI\Platform\Bridge\Gemini\Gemini;
use Symfony\AI\Platform\Bridge\Gemini\PlatformFactory;
use Symfony\AI\Platform\Model;
use Symfony\AI\Platform\Platform;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpClient\HttpClient;

class AgentProviderService
{
    private readonly Platform $platform;
    private readonly Model $model;
    private readonly AgentProcessor $agentProcessor;

    public function __construct(
        #[AutowireIterator('symfony.ai.agent.toolbox.tool')]
        iterable $agentTools,
        #[Autowire('%env(GEMINI_API_KEY)%')]
        string $geminiApiKey,
        private readonly LoggerInterface $logger,
    )
    {
        $this->platform = PlatformFactory::create($geminiApiKey, HttpClient::create());
        $this->model = new Gemini('gemini-2.5-flash');
        $toolbox = new Toolbox($agentTools, logger: $this->logger);
        $this->agentProcessor = new AgentProcessor($toolbox);
    }

    public function getAgent()
    {
        return new Agent(
            platform: $this->platform,
            model: $this->model,
            inputProcessors: [$this->agentProcessor],
            outputProcessors: [$this->agentProcessor],
            logger: $this->logger,
        );
    }
}
