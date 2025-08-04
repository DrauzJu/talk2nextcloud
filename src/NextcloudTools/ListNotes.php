<?php

namespace App\NextcloudTools;

use Symfony\AI\Agent\Toolbox\Attribute\AsTool;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsTool('nextcloud-list-notes', description: 'Lists all notes.')]
#[AutoconfigureTag('symfony.ai.agent.toolbox.tool')]
class ListNotes
{
    public function __construct(
        private HttpClientInterface $nextcloudClient,
    ) {
    }

    public function __invoke(): string
    {
        $response = $this->nextcloudClient->request(
            'GET',
            '/apps/notes/api/v1/notes',
            [
                'query' => [
                    'exclude' => 'content',
                ],
            ],
        );

        $notes = $response->toArray();

        return implode(PHP_EOL, array_map(
            fn($note) => '- Title: ' . $note['title'] . ' (ID: ' . $note['id'] . ')',
            $notes,
        ));
    }
}
