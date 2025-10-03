<template>
    <v-container class="fill-height" fluid>
        <v-row class="fill-height" justify="center" align-content="center">
            <v-col cols="12" md="8" class="text-center">
                <v-icon size="x-large" color="primary">mdi-cloud-question</v-icon>
                <h1 class="mb-4">Talk2Nextcloud</h1>
                <v-card theme="dark">
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
                            <v-btn
                                :color="recordingSession ? 'red' : 'primary'"
                                @click="toggleRecording"
                                :disabled="loading"
                                icon
                                size="x-large"
                                class="mb-4 mr-2"
                            >
                                <v-icon size="x-large">mdi-microphone</v-icon>
                            </v-btn>
                            <v-btn
                                @click="resendLastRecording"
                                :disabled="lastAudioBlob === null || loading"
                                icon
                                size="x-large"
                                class="mb-4 ml-2"
                            >
                                <v-icon size="x-large">mdi-replay</v-icon>
                            </v-btn>
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

                <v-card v-if="loading || llmResponse" class="mt-4" theme="dark" :class="requestStatusClass">
                    <v-card-title>Response</v-card-title>
                    <v-card-text>
                        <div v-if="loading" class="text-center">
                            <v-progress-circular indeterminate color="primary"></v-progress-circular>
                        </div>
                        <div v-else>
                            <iframe :srcdoc="llmResponse" sandbox class="llmResponseIFrame"></iframe>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { ApiClient } from '../services/api-client';

type RequestStatus = 'success' | 'error' | '';

const tabs = ref([
    { id: 'voice', text: 'Voice', icon: 'mdi-microphone' },
    { id: 'text', text: 'Text', icon: 'mdi-card-text-outline' },
]);
const openTab = ref('voice');

const loading = ref(false);
const llmResponse = ref('');
const textPrompt = ref('');
const requestStatusClass = ref<RequestStatus>('');

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
            // stream.getTracks().forEach(track => track.stop());
        };

        mediaRecorder.start();
    } catch (error) {
        console.error('Error starting recording:', error);

        llmResponse.value =
            'Error: Could not start recording. Please check microphone permissions.';
        requestStatusClass.value = 'error';
        recordingSession.value = null;
    }
}

async function stopRecording() {
    if (recordingSession.value === null) {
        return;
    }

    loading.value = true;
    requestStatusClass.value = '';
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
        requestStatusClass.value = 'error';
    } finally {
        loading.value = false;
    }
}

async function resendLastRecording() {
    if (lastAudioBlob.value === null) {
        llmResponse.value = 'No previous recording to resend.';
        requestStatusClass.value = 'error';

        return;
    }

    loading.value = true;
    requestStatusClass.value = '';

    try {
        await sendRecording(lastAudioBlob.value);
    } catch (error) {
        if (!(error instanceof Error)) {
            throw error;
        }

        llmResponse.value = `Error: ${error.message}`;
        requestStatusClass.value = 'error';
    } finally {
        loading.value = false;
    }
}

const apiClient = new ApiClient();

async function sendRecording(blob: Blob) {
    const formData = new FormData();
    formData.append('audio', blob, 'recording.webm');

    try {
        const response = await apiClient.fetch('/api/llm/audio-prompt', {
            method: 'POST',
            body: formData,
        });

        const jsonResponse = await response.json();
        llmResponse.value = jsonResponse.response ?? 'No response received';
        requestStatusClass.value = 'success';
    } catch (error) {
        console.error('Error sending recording:', error);
        llmResponse.value = 'Error: Could not send recording to server.';
        requestStatusClass.value = 'error';
    }
}

async function sendTextPrompt() {
    if (!textPrompt.value.trim()) {
        return;
    }

    loading.value = true;
    llmResponse.value = '';
    requestStatusClass.value = '';

    try {
        const response = await apiClient.fetch('/api/llm/text-prompt', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ prompt: textPrompt.value }),
        });

        const jsonResponse = await response.json();
        llmResponse.value = jsonResponse.response ?? 'No response received';
        requestStatusClass.value = 'success';
    } catch (error) {
        console.error('Error sending text prompt:', error);
        llmResponse.value = 'Error: Could not send prompt to server.';
        requestStatusClass.value = 'error';
    } finally {
        loading.value = false;
    }
}
</script>

<style scoped>
.fill-height {
    min-height: 100vh;
}

.llmResponseIFrame {
    width: 100%;
    border: none;
    height: 400px;
}

.success {
    border: 4px solid #4CAF50;
}

.error {
    border: 4px solid #F44336;
}
</style>
