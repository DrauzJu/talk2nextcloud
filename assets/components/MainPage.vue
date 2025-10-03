<template>
    <v-container fluid max-height="100vh">
        <v-row class="fill-height" align-content="center">
            <v-col cols="12" class="text-center d-flex flex-column align-center">
                <v-icon size="x-large" color="primary">mdi-cloud-question</v-icon>
                <h1 class="mb-4">Talk2Nextcloud</h1>
                <v-select
                    v-model="selectedModel"
                    label="Gemini model"
                    :items="['gemini-2.5-pro', 'gemini-2.5-flash']"
                    class="modelSelect"
                ></v-select>
                <v-card theme="dark" class="main-card">
                    <v-tabs
                        v-model="openTab"
                        :items="tabs"
                        align-tabs="center"
                        color="primary"
                    >
                        <template v-slot:tab="{ item }">
                            <v-tab
                                :text="item.text"
                                :value="item.id"
                                :prepend-icon="item.icon"
                            ></v-tab>
                        </template>
                    </v-tabs>
                    <v-tabs-window v-model="openTab">
                        <v-tabs-window-item value="voice" class="pa-4 text-center">
                            <v-tooltip
                                :text="recordingSession ? 'Stop Recording' : 'Start Recording'"
                                location="bottom"
                            >
                                <template v-slot:activator="{ props }">
                                    <v-btn
                                        v-bind="props"
                                        :color="recordingSession ? 'red' : 'primary'"
                                        @click="toggleRecording"
                                        :disabled="loading"
                                        icon
                                        size="x-large"
                                        class="mb-4 mr-2"
                                    >
                                        <v-icon size="x-large">mdi-microphone</v-icon>
                                    </v-btn>
                                </template>
                            </v-tooltip>
                            <v-tooltip
                                text="Resend last recording"
                                :disabled="lastAudioBlob === null || loading"
                                location="bottom"
                            >
                                <template v-slot:activator="{ props }">
                                    <v-btn
                                        v-bind="props"
                                        @click="resendLastRecording"
                                        :disabled="lastAudioBlob === null || loading"
                                        icon
                                        size="x-large"
                                        class="mb-4 ml-2 mr-2"
                                    >
                                        <v-icon size="x-large">mdi-replay</v-icon>
                                    </v-btn>
                                </template>
                            </v-tooltip>
                            <v-tooltip
                                text="Play last recording"
                                location="bottom"
                            >
                                <template v-slot:activator="{ props }">
                                    <v-btn
                                        v-bind="props"
                                        @click="playLastRecording"
                                        :disabled="lastAudioBlob === null"
                                        icon
                                        size="x-large"
                                        class="mb-4 ml-2"
                                    >
                                        <v-icon size="x-large">mdi-play</v-icon>
                                    </v-btn>
                                </template>
                            </v-tooltip>
                            <div v-if="recordingSession" class="text-red">Recording...</div>
                        </v-tabs-window-item>

                        <v-tabs-window-item value="text" class="pa-4">
                            <v-textarea
                                v-model="textPrompt"
                                label="Enter your prompt"
                                variant="outlined"
                                rows="4"
                                auto-grow
                                hide-details
                                class="mb-4"
                            ></v-textarea>
                            <v-btn
                                color="primary"
                                @click="sendTextPrompt"
                                block
                                :disabled="textPrompt.trim() === '' || loading"
                            >
                                Send
                            </v-btn>
                        </v-tabs-window-item>
                    </v-tabs-window>
                </v-card>

                <LlmResponse
                    :llm-response="llmResponse"
                    :loading="loading"
                    :request-status="requestStatus"
                />
            </v-col>
        </v-row>
    </v-container>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { RequestStatus } from '../helper/types';
import { ApiClient } from '../services/api-client';
import LlmResponse from './LlmResponse.vue';

const selectedModel = ref('gemini-2.5-flash');

const tabs = ref([
    { id: 'voice', text: 'Voice', icon: 'mdi-microphone' },
    { id: 'text', text: 'Text', icon: 'mdi-card-text-outline' },
]);
const openTab = ref('voice');

const loading = ref(false);
const llmResponse = ref('');
const textPrompt = ref('');
const requestStatus = ref<RequestStatus>(null);

const recordingSession = ref<{
    mediaRecorder: MediaRecorder;
    blobPromise: Promise<Blob>;
} | null>(null);
const lastAudioBlob = ref<Blob | null>(null);

const AUDIO_MIME_TYPE = 'audio/webm';

async function toggleRecording() {
    if (recordingSession.value) {
        await stopRecording();
    } else {
        await startRecording();
    }
}

async function startRecording() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({
            audio: true,
        });
        const mediaRecorder = new MediaRecorder(stream, {
            mimeType: AUDIO_MIME_TYPE,
        });
        const { promise, resolve } = Promise.withResolvers<Blob>();

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
    } catch (error) {
        console.error('Error starting recording:', error);

        llmResponse.value =
            'Error: Could not start recording. Please check microphone permissions.';
        requestStatus.value = 'error';
        recordingSession.value = null;
    }
}

async function stopRecording() {
    if (recordingSession.value === null) {
        return;
    }

    loading.value = true;
    requestStatus.value = null;
    const { mediaRecorder, blobPromise } = recordingSession.value;

    mediaRecorder.stop();

    const audioBlob = await blobPromise;
    lastAudioBlob.value = audioBlob;
    recordingSession.value = null;

    try {
        await sendRecording(audioBlob);
    } catch (error) {
        if (!(error instanceof Error)) {
            throw error;
        }

        llmResponse.value = `Error: ${error.message}`;
        requestStatus.value = 'error';
    } finally {
        loading.value = false;
    }
}

async function resendLastRecording() {
    if (lastAudioBlob.value === null) {
        llmResponse.value = 'No previous recording to resend.';
        requestStatus.value = 'error';

        return;
    }

    loading.value = true;
    requestStatus.value = null;

    try {
        await sendRecording(lastAudioBlob.value);
    } catch (error) {
        if (!(error instanceof Error)) {
            throw error;
        }

        llmResponse.value = `Error: ${error.message}`;
        requestStatus.value = 'error';
    } finally {
        loading.value = false;
    }
}

async function playLastRecording() {
    if (lastAudioBlob.value === null) {
        return;
    }

    const audioUrl = URL.createObjectURL(lastAudioBlob.value);
    const audio = new Audio(audioUrl);
    await audio.play();
}

const apiClient = new ApiClient();

async function sendRecording(blob: Blob) {
    const formData = new FormData();
    formData.append('audio', blob, 'recording.webm');
    formData.append('geminiModel', selectedModel.value);

    try {
        const response = await apiClient.fetch('/api/llm/audio-prompt', {
            method: 'POST',
            body: formData,
        });

        const jsonResponse = await response.json();
        llmResponse.value = jsonResponse.response ?? 'No response received';
        requestStatus.value = 'success';
    } catch (error) {
        console.error('Error sending recording:', error);
        llmResponse.value = 'Error: Could not send recording to server.';
        requestStatus.value = 'error';
    }
}

async function sendTextPrompt() {
    if (!textPrompt.value.trim()) {
        return;
    }

    loading.value = true;
    llmResponse.value = '';
    requestStatus.value = null;

    try {
        const response = await apiClient.fetch('/api/llm/text-prompt', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                prompt: textPrompt.value,
                geminiModel: selectedModel.value,
            }),
        });

        const jsonResponse = await response.json();
        llmResponse.value = jsonResponse.response ?? 'No response received';
        requestStatus.value = 'success';
    } catch (error) {
        console.error('Error sending text prompt:', error);
        llmResponse.value = 'Error: Could not send prompt to server.';
        requestStatus.value = 'error';
    } finally {
        loading.value = false;
    }
}
</script>

<style scoped>
.main-card {
    min-width: 500px;
}

.fill-height {
    min-height: 100vh;
}

.modelSelect {
    max-width: 400px;
}
</style>
