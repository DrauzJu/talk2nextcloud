<?php

namespace App\NextcloudTools;

use Symfony\AI\Agent\Toolbox\Attribute\AsTool;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsTool('nextcloud-list-notes', description: 'Lists all notes.', method: 'list')]
#[AsTool('nextcloud-read-note', description: 'Read a note by its ID', method: 'read')]
#[AutoconfigureTag('symfony.ai.agent.toolbox.tool')]
class NextcloudNoteTools
{
    public function __construct(
        private HttpClientInterface $nextcloudClient,
    ) {
    }

    public function list(): string
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

    /**
     * @param string $id The ID of the note to read
     */
    public function read(string $id): string
    {
        $response = $this->nextcloudClient->request(
            'GET',
            '/apps/notes/api/v1/notes/' . $id,
        );

        $note = $response->toArray();

        return $note['content'] ?? 'Invalid or empty note!';
    }
}
