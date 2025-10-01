# talk2nextcloud

Symfony web application to interact with Nextcloud using audio input, which is processed by Gemini LLM using 
custom Nextcloud tools.

## Installation

````shell
apt install ffmpeg # or any other way to install ffmpeg and ffprobe - used to convert browser audio

composer install

npm install && npm build
````

## Configuration

Add a .env.local file:

````dotenv
APP_ENV=prod
APP_SECRET=tbd
JWT_PASSPHRASE=tbd

NEXTCLOUD_URL=https://tbd.com
NEXTCLOUD_USER=tbd
NEXTCLOUD_PASSWORD=tbd

GEMINI_API_KEY=tbd
````

Generate JWT tokens using `bin/console lexik:jwt:generate-keypair`.

## Currently available AI Agent tools

- Nextcloud Notes
  - `nextcloud-list-notes`
  - `nextcloud-read-note` (requires note ID)
