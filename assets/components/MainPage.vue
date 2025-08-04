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
        const prompt = "Gibt es eine Notiz zum Thema Essen?";
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

        this.response = await response.text();
    } finally {
        loading.value = false;
    }
}
</script>
