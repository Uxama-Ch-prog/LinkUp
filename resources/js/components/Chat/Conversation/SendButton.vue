<!-- resources/js/components/Chat/Conversation/SendButton.vue -->
<template>
    <button
        type="submit"
        :disabled="disabled"
        class="bg-blue-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center space-x-2"
    >
        <span v-if="!isSending">Send</span>
        <span v-else class="flex items-center space-x-1">
            <span
                class="w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin"
            ></span>
            <span>Sending</span>
        </span>
    </button>
</template>

<script setup>
defineProps({
    disabled: {
        type: Boolean,
        default: false,
    },
    isSending: {
        type: Boolean,
        default: false,
    },
});
</script>
