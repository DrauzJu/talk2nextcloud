<?php

namespace Talk2Nextcloud\Services\Agent;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\AI\Agent\Agent;
use Symfony\AI\Agent\Toolbox\AgentProcessor;
use Symfony\AI\Agent\Toolbox\Toolbox;
use Symfony\AI\Agent\Toolbox\FaultTolerantToolbox;
use Symfony\AI\Platform\Bridge\Gemini\PlatformFactory as GeminiPlatformFactory;
use Symfony\AI\Platform\Bridge\OpenAi\PlatformFactory as OpenAiPlatformFactory;
use Symfony\AI\Platform\Platform;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpClient\HttpClient;
use Talk2Nextcloud\Enum\Provider;
use Talk2Nextcloud\Services\ModelCatalogService;

class AgentProviderService
{
    public function __construct(
        #[AutowireIterator('symfony.ai.agent.toolbox.tool')]
        private readonly iterable $agentTools,
        #[Autowire('%env(GEMINI_API_KEY)%')]
        private readonly string $geminiApiKey,
        #[Autowire('%env(OPENAI_API_KEY)%')]
        private readonly string $openaiApiKey,
        private readonly ModelCatalogService $modelCatalogService,
        private readonly LoggerInterface $logger,
    ) {}

    public function getAgent(string $model): Agent
    {
        $platform = $this->getPlatform($model);
        $toolbox = new Toolbox($this->agentTools, logger: $this->logger);
        $agentProcessor = new AgentProcessor(
            new FaultTolerantToolbox($toolbox),
        );

        return new Agent(
            platform: $platform,
            model: $model,
            inputProcessors: [$agentProcessor],
            outputProcessors: [$agentProcessor],
            logger: $this->logger,
        );
    }

    private function getPlatform(string $model): Platform
    {
        $httpClient = HttpClient::create();

        // Determine provider using the model catalog service
        $provider = $this->modelCatalogService->getProviderForModel($model);

        if ($provider === null) {
            throw new InvalidArgumentException(
                sprintf(
                    'Unknown or unavailable model: %s. Please ensure the model is supported and the corresponding API key is configured.',
                    $model
                )
            );
        }

        return match ($provider) {
            Provider::OPENAI => OpenAiPlatformFactory::create($this->openaiApiKey, $httpClient),
            Provider::GEMINI => GeminiPlatformFactory::create($this->geminiApiKey, $httpClient),
        };
    }
}
