<?php

namespace Talk2Nextcloud\Controller;

use Talk2Nextcloud\Services\Agent\AgentInvokerService;
use Talk2Nextcloud\Services\AudioConverterService;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LlmController extends AbstractController
{
    public function __construct(
        private readonly AgentInvokerService $agentInvokerService,
        private readonly AudioConverterService $audioConverterService,
        private readonly LoggerInterface $logger,
    )
    {}

    #[Route('/api/llm/text-prompt', methods: ['POST'])]
    public function textPrompt(Request $request): Response
    {
        $data = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);
        $userMessage = $data['prompt'] ?? null;

        if ($userMessage === null) {
            throw new InvalidArgumentException('Prompt is required');
        }

        try {
            $agentResponse = $this->agentInvokerService->invokeAgentWithUserTextMessage($userMessage);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse([
            'response' => $agentResponse,
        ]);
    }

    #[Route('/api/llm/audio-prompt', methods: ['POST'])]
    public function audioPrompt(Request $request): Response
    {
        /** @var UploadedFile $audioFile */
        $audioFile = $request->files->get('audio');
        if (!$audioFile) {
            return new Response('No audio file sent.', Response::HTTP_BAD_REQUEST);
        }

        $convertedFilePath = Path::join(
            $this->getParameter('talk2nextcloud.audio_files.tmp_directory'),
            'audio-prompt-input' . '-' . uniqid(more_entropy: true) . '.wav',
        );

        try {
            $this->audioConverterService->convertAudioFileToWAV($audioFile->getRealPath(), $convertedFilePath);

            $agentResponse = $this->agentInvokerService->invokeAgentWithUserAudioMessage($convertedFilePath);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        } finally {
            unlink($convertedFilePath);
        }

        return new JsonResponse([
            'response' => $agentResponse,
        ]);
    }
}
