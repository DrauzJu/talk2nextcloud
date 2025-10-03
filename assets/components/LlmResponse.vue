<template>
    <v-card
        class="mt-4 responseCard"
        theme="dark"
        :class="{
            success: requestStatus === 'success',
            error: requestStatus === 'error'
        }"
    >
        <v-card-title>Response</v-card-title>
        <v-card-text>
            <div v-if="loading" class="text-center">
                <v-progress-circular indeterminate color="primary"></v-progress-circular>
            </div>
            <div v-else-if="llmResponse !== ''">
                <iframe :srcdoc="llmResponse" sandbox class="llmResponseIFrame"></iframe>
            </div>
        </v-card-text>
    </v-card>
</template>

<script setup lang="ts">
import { RequestStatus } from '../helper/types';

const props = defineProps<{
    loading: boolean;
    llmResponse: string;
    requestStatus: RequestStatus;
}>();
</script>

<style scoped>
.responseCard {
    min-width: 500px;
}

.llmResponseIFrame {
    width: 100%;
    border: none;
    height: 500px;
}

.success {
    border: 4px solid #4CAF50;
}

.error {
    border: 4px solid #F44336;
}
</style>
