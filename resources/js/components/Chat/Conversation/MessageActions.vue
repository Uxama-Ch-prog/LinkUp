<!-- resources/js/components/Chat/Conversation/MessageActions.vue -->
<template>
    <div
        class="absolute -top-8 right-0 opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex space-x-1"
    >
        <!-- Edit button -->
        <button
            v-if="message.is_editable"
            @click="$emit('edit', message)"
            class="w-6 h-6 bg-white border border-gray-300 rounded flex items-center justify-center text-gray-500 hover:text-blue-600 text-xs shadow-sm hover:scale-110 transition-all"
            title="Edit message (Ctrl+Enter to save)"
        >
            ✎
        </button>

        <!-- Delete button -->
        <button
            @click="$emit('delete', message.id)"
            class="w-6 h-6 bg-white border border-gray-300 rounded flex items-center justify-center text-gray-500 hover:text-red-600 text-xs shadow-sm hover:scale-110 transition-all"
            title="Delete message"
        >
            ×
        </button>

        <!-- Reaction button -->
        <button
            @click="$emit('react', message.id)"
            class="w-6 h-6 bg-white border border-gray-300 rounded flex items-center justify-center text-gray-500 hover:text-gray-700 text-xs shadow-sm hover:scale-110 transition-all"
            title="Add reaction"
        >
            👍
        </button>
    </div>
</template>

<script setup>
defineProps({
    message: {
        type: Object,
        required: true,
    },
});

defineEmits(["edit", "delete", "react"]);
</script>
