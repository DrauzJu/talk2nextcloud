<?php

namespace App\NextcloudTools;

use Symfony\AI\Agent\Toolbox\Attribute\AsTool;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsTool('nextcloud-read-note', description: 'Read a note by its ID')]
#[AutoconfigureTag('symfony.ai.agent.toolbox.tool')]
class ReadNote
{
    public function __construct(
        private HttpClientInterface $nextcloudClient,
    ) {
    }

    /**
     * @param string $id The ID of the note to read
     */
    public function __invoke(string $id): string
    {
        $response = $this->nextcloudClient->request(
            'GET',
            '/apps/notes/api/v1/notes/' . $id,
        );

        $note = $response->toArray();

        return $note['content'] ?? 'Invalid or empty note!';
    }
}
