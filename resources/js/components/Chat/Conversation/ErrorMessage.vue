<!-- resources/js/components/Chat/Conversation/ErrorMessage.vue -->
<template>
    <div
        class="mb-3 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm"
    >
        <div class="flex items-center justify-between">
            <span>{{ message }}</span>
            <button
                @click="$emit('dismiss')"
                class="ml-2 text-red-600 hover:text-red-800 transition-colors"
            >
                ×
            </button>
        </div>
    </div>
</template>

<script setup>
defineProps({
    message: {
        type: String,
        default: "",
    },
});

defineEmits(["dismiss"]);
</script>
