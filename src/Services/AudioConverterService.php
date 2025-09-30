<?php

namespace Talk2Nextcloud\Services;

use FFMpeg\FFMpeg;
use FFMpeg\Format\Audio\Wav;

class AudioConverterService
{
    public function convertAudioFileToWAV(string $inputFilePath, string $outputFilePath): void
    {
        $ffmpeg = FFMpeg::create();
        $ffmpegAudio = $ffmpeg->open($inputFilePath);

        $format = new Wav();
        $format->setAudioKiloBitrate(192);
        $format->setAudioChannels(1); // Optimize for LLM input. Some models only support mono audio.

        $ffmpegAudio->save($format, $outputFilePath);
    }
}
