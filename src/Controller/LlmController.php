<?php

namespace App\Controller;

use App\Services\AgentInvokerService;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LlmController extends AbstractController
{
    public function __construct(
        private readonly AgentInvokerService $agentInvokerService,
    )
    {}

    #[Route('/api/llm', name: 'app_llm', methods: ['POST'])]
    public function index(Request $request): Response
    {
        $data = $request->toArray();
        $userMessage = $data['prompt'] ?? null;

        if ($userMessage === null) {
            throw new InvalidArgumentException('Prompt is required');
        }

        return new JsonResponse([
            'response' => $this->agentInvokerService->invokeAgent($userMessage),
        ]);
    }
}
