<?php

namespace Talk2Nextcloud\NextcloudTools;

use Symfony\AI\Agent\Toolbox\Attribute\AsTool;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsTool(
    'nextcloud-list-notes',
    description: "
        Lists all notes.
        Returns the results as a structured JSON array with note IDs, titles and last modified unix timestamp.
    ",
    method: 'list',
)]
#[AsTool(
    'nextcloud-read-note',
    description: 'Read a note by its ID. Returns the note content as plain text.',
    method: 'read',
)]
#[AsTool(
    'nextcloud-append-to-note',
    description: "
        Append text to a note.
        Requires the note ID and the text to append as input.
    ",
    method: 'append',
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
                    'last_modified_unix_timestamp' => $note['modified'],
                    'last_modified' => date('c', $note['modified']),
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

    /**
     * @param string $id The ID of the note to edit
     * @param string $text The text to add
     */
    public function append(string $id, string $text): string
    {
        if (trim($text) === '') {
            throw new \Exception('No text to append provided!');
        }

        $noteReadResponse = $this->nextcloudClient->request(
            'GET',
            '/apps/notes/api/v1/notes/' . $id,
        );

        $note = $noteReadResponse->toArray();

        if (!isset($note['content'], $note['etag'])) {
            throw new \Exception('Could not fetch note or note is invalid!');
        }

        if (trim($note['content']) === '') {
            throw new \Exception(' Note is empty, nothing to append to!');
        }

        $updatedNoteResponse = $this->nextcloudClient->request(
            'PUT',
            '/apps/notes/api/v1/notes/' . $id,
            [
                'headers' => [
                    'If-Match' => '"' . $note['etag'] . '"',
                ],
                'json' => [
                    'content' => $note['content'] . "\n\n" . $text,
                ]
            ]
        );

        $statusCode = $updatedNoteResponse->getStatusCode();
        if ($statusCode !== 200) {
            throw new \Exception('An unknown error occurred while updating the note!');
        }

        return 'Successfully appended text to note.';
    }
}
