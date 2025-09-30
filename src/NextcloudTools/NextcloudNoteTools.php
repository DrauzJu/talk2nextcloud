<?php

namespace Talk2Nextcloud\NextcloudTools;

use Symfony\AI\Agent\Toolbox\Attribute\AsTool;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsTool(
    'nextcloud-list-notes',
    description: 'Lists all notes. Returns the results as a structured JSON array with note titles and IDs.',
    method: 'list',
)]
#[AsTool(
    'nextcloud-read-note',
    description: 'Read a note by its ID. Returns the note content as plain text.',
    method: 'read',
)]
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

        // Structure the response as a JSON object with an array of notes.
        // Do not return a plain array, because this is not supported by some AI models as tool response.
        $toolResponse = [
            'notes' => array_map(
                static fn ($note) => [
                    'id' => $note['id'],
                    'title' => $note['title'],
                ],
                $notes,
            ),
        ];


        return json_encode($toolResponse, JSON_FORCE_OBJECT & JSON_THROW_ON_ERROR);
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
