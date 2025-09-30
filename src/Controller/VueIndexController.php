<?php

namespace Talk2Nextcloud\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class VueIndexController extends AbstractController
{
    #[Route('/', name: 'app_vue_index')]
    public function index(): Response
    {
        return $this->render('index.html.twig', [
            'controller_name' => 'VueIndexController',
        ]);
    }
}
