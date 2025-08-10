# talk2nextcloud

Symfony web application to interact with Nextcloud using audio input, which is processed by Gemini LLM using 
custom Nextcloud MCP tools.

## Installation

````shell
apt install ffmpeg # or any other way to install ffmpeg and ffprobe - used to convert browser audio
composer install
yarn install && yarn build
````

## Currently available tools

- Nextcloud Notes
  - `nextcloud-list-notes`
  - `nextcloud-read-note` (requires note ID)
