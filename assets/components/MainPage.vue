<template>
    <div>
        <h1>Talk2Nextcloud</h1>
        <v-card>
            <v-tabs
                :items="[{ id: 'voice', text: 'Voice' }, { id: 'text', text: 'Text' }]"
                align-tabs="center"
                color="blue"
            >
                <template v-slot:tab="{ item }">
                    <v-tab
                        :text="item.text"
                        :value="item.id"
                    ></v-tab>
                </template>

                <template v-slot:item="{ item }">
                    <v-tabs-window-item v-if="item.id === 'voice'" :value="item.id" class="pa-4">
                        <v-btn class="mr-3" @click="startRecording" :disabled="recordingSession !== null">Record</v-btn>
                        <v-btn class="mr-3" @click="stopRecording" :disabled="recordingSession === null">Stop and send</v-btn>
                        <v-btn @click="resendLastRecording" :disabled="lastAudioBlob === null">Re-send last</v-btn>
                    </v-tabs-window-item>

                    <v-tabs-window-item v-if="item.id === 'text'" :value="item.id" class="pa-4">
                        <div>Text input</div>
                    </v-tabs-window-item>
                </template>
            </v-tabs>
        </v-card>

        <div v-if="loading">Loading...</div>
        <div v-else>{{ llmResponse }}</div>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { ApiClient } from "../services/api-client";

const loading = ref(false);
const llmResponse = ref('');

const recordingSession = ref<{
    mediaRecorder: MediaRecorder;
    blobPromise: Promise<Blob>;
} | null>(null);
let lastAudioBlob = ref<Blob | null>(null);

const AUDIO_MIME_TYPE = 'audio/webm';

async function startRecording() {
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    const mediaRecorder = new MediaRecorder(stream, { mimeType: AUDIO_MIME_TYPE });
    let { promise, resolve } = Promise.withResolvers<Blob>();

    recordingSession.value = {
        mediaRecorder,
        blobPromise: promise,
    };

    const audioChunks: Blob[] = [];

    mediaRecorder.ondataavailable = (event) => {
        audioChunks.push(event.data);
    };

    mediaRecorder.onstop = () => {
        resolve(new Blob(audioChunks, { type: AUDIO_MIME_TYPE }));
    };

    mediaRecorder.start();
}

async function stopRecording() {
    if (recordingSession.value === null) {
        return;
    }

    loading.value = true;
    const { mediaRecorder, blobPromise } = recordingSession.value;

    mediaRecorder.stop();

    try {
        const audioBlob = await blobPromise;
        lastAudioBlob.value = audioBlob;
        await sendRecording(audioBlob);
    } catch (error) {
        if (!(error instanceof Error)) {
            throw error;
        }

        llmResponse.value = `Error: ${error.message}`;
    } finally {
        loading.value = false;
        recordingSession.value = null;
    }
}

async function resendLastRecording() {
    if (lastAudioBlob.value === null) {
        throw new Error('No last recording to resend');
    }

    loading.value = true;

    try {
        await sendRecording(lastAudioBlob.value);
    } catch (error) {
        if (!(error instanceof Error)) {
            throw error;
        }

        llmResponse.value = `Error: ${error.message}`;
    } finally {
        loading.value = false;
    }
}

async function sendRecording(blob: Blob|null) {
    if (blob === null) {
        throw new Error('No audio blob to send');
    }

    const formData = new FormData();
    formData.append('audio', blob, 'recording.webm');

    const response = await ApiClient.fetch('/api/llm/audio-prompt', {
        method: 'POST',
        body: formData,
    });

    const jsonResponse = await response.json();
    llmResponse.value = jsonResponse.response ?? 'No response received';
}
</script>
