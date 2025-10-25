<?php

namespace Talk2Nextcloud\Services;

use Symfony\AI\Platform\Bridge\Gemini\ModelCatalog as GeminiModelCatalog;
use Symfony\AI\Platform\Bridge\OpenAi\ModelCatalog as OpenAiModelCatalog;
use Symfony\AI\Platform\Capability;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Talk2Nextcloud\Enum\Provider;

class ModelCatalogService
{
    private readonly GeminiModelCatalog $geminiCatalog;
    private readonly OpenAiModelCatalog $openaiCatalog;

    public function __construct(
        #[Autowire('%env(GEMINI_API_KEY)%')]
        private readonly string $geminiApiKey,
        #[Autowire('%env(OPENAI_API_KEY)%')]
        private readonly string $openaiApiKey,
    ) {
        // Initialize model catalogs from Symfony AI Platform
        $this->geminiCatalog = new GeminiModelCatalog();
        $this->openaiCatalog = new OpenAiModelCatalog();
    }

    /**
     * Returns available models grouped by provider (only models with INPUT_AUDIO + TOOL_CALLING)
     */
    public function getAvailableModels(): array
    {
        $models = [];

        if (!empty($this->geminiApiKey)) {
            $geminiModels = $this->getAudioCapableModels($this->geminiCatalog->getModels());
            if (!empty($geminiModels)) {
                $models['gemini'] = [
                    'provider' => 'Google Gemini',
                    'models' => $geminiModels,
                ];
            }
        }

        if (!empty($this->openaiApiKey)) {
            $openaiModels = $this->getAudioCapableModels($this->openaiCatalog->getModels());
            if (!empty($openaiModels)) {
                $models['openai'] = [
                    'provider' => 'OpenAI',
                    'models' => $openaiModels,
                ];
            }
        }

        return $models;
    }

    /**
     * Returns the default model (first available model)
     */
    public function getDefaultModel(): ?string
    {
        $models = $this->getAvailableModelNames();
        return $models[0] ?? null;
    }

    /**
     * Determines which provider a model belongs to
     */
    public function getProviderForModel(string $model): ?Provider
    {
        // Check if model exists in Gemini catalog and supports audio
        if (!empty($this->geminiApiKey)) {
            $geminiModels = $this->getAudioCapableModels($this->geminiCatalog->getModels());
            if (in_array($model, $geminiModels, true)) {
                return Provider::GEMINI;
            }
        }

        // Check if model exists in OpenAI catalog and supports audio
        if (!empty($this->openaiApiKey)) {
            $openaiModels = $this->getAudioCapableModels($this->openaiCatalog->getModels());
            if (in_array($model, $openaiModels, true)) {
                return Provider::OPENAI;
            }
        }

        return null;
    }

    /**
     * Returns a flat list of all available model names
     */
    private function getAvailableModelNames(): array
    {
        $allModels = [];

        foreach ($this->getAvailableModels() as $providerData) {
            $allModels = array_merge($allModels, $providerData['models']);
        }

        return $allModels;
    }

    /**
     * Extracts models with INPUT_AUDIO and TOOL_CALLING capabilities from a model catalog
     * We require TOOL_CALLING because the app uses Nextcloud tools
     */
    private function getAudioCapableModels(array $catalogModels): array
    {
        $audioModels = [];

        foreach ($catalogModels as $modelName => $config) {
            $capabilities = $config['capabilities'] ?? [];

            // Check if model supports both audio input AND tool calling
            // This filters out models like whisper-1 which is speech-to-text only
            $hasAudioInput = in_array(Capability::INPUT_AUDIO, $capabilities, true);
            $hasToolCalling = in_array(Capability::TOOL_CALLING, $capabilities, true);

            if ($hasAudioInput && $hasToolCalling) {
                $audioModels[] = $modelName;
            }
        }

        return $audioModels;
    }
}
