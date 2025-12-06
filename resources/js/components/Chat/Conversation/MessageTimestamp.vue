<!-- resources/js/components/Chat/Conversation/MessageTimestamp.vue -->
<template>
    <p
        :class="[
            'text-xs mt-1 flex items-center space-x-1',
            isOwnMessage
                ? 'text-blue-200 justify-end'
                : 'text-gray-500 justify-start',
        ]"
    >
        <span>{{ formatMessageTime(message.created_at) }}</span>

        <!-- Edited indicator -->
        <span
            v-if="message.edited_at"
            class="ml-1"
            :title="'Edited at ' + formatMessageTime(message.edited_at)"
        >
            ✎
        </span>

        <!-- Read receipt for user's own messages -->
        <span
            v-if="isOwnMessage"
            class="text-xs"
            :title="message.read_at ? 'Read' : 'Sent'"
        >
            <span v-if="message.read_at" class="text-blue-300">✓✓</span>
            <span v-else class="text-gray-400">✓</span>
        </span>
    </p>
</template>

<script setup>
import { useMessageUtils } from "../../../composables/useMessageUtils";

const props = defineProps({
    message: {
        type: Object,
        required: true,
    },
    isOwnMessage: {
        type: Boolean,
        default: false,
    },
});

const { formatMessageTime } = useMessageUtils();
</script>
