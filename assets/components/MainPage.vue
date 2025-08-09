<template>
    <button @click="sendPrompt()">Start</button>
    <div v-if="loading">Loading...</div>
    <div v-else>{{ response }}</div>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const loading = ref(false);
const response = ref('');

async function sendPrompt() {
    loading.value = true;
    this.response = '';

    try {
        const prompt: string = ''; // ToDo
        const response = await fetch(
            '/api/llm',
            {
                method: 'POST',
                body: JSON.stringify({ prompt }),
                headers: {
                    'Content-Type': 'application/json',
                },
            },
        );

        const jsonResponse = await response.json();
        this.response = jsonResponse.response ?? 'No response received';
    } finally {
        loading.value = false;
    }
}
</script>
