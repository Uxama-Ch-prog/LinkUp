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
            class="text-xs ml-1 flex items-center"
            :title="getReadReceiptTitle"
        >
            <!-- 
                Logic:
                - No tick: Message is being sent (no ID yet)
                - Single gray tick: Message sent to server (has ID)
                - Double blue tick: Message read by recipient (has read_at timestamp)
            -->
            <template v-if="message.read_at">
                <!-- Read by recipient -->
                <span class="text-blue-400" title="Read">✓✓</span>
                <span v-if="showReadTime" class="text-xs text-blue-300 ml-1">
                    {{ formatRelativeTime(message.read_at) }}
                </span>
            </template>
            <template v-else-if="message.id">
                <!-- Sent to server but not read yet -->
                <span class="text-gray-400" title="Delivered">✓</span>
            </template>
            <template v-else>
                <!-- Still sending -->
                <span class="text-gray-300 animate-pulse" title="Sending..."
                    >✓</span
                >
            </template>
        </span>
    </p>
</template>

<script setup>
import { useMessageUtils } from "../../../composables/useMessageUtils";
import { computed } from "vue";

const props = defineProps({
    message: {
        type: Object,
        required: true,
    },
    isOwnMessage: {
        type: Boolean,
        default: false,
    },
    showReadTime: {
        type: Boolean,
        default: false,
    },
});

const { formatMessageTime, formatRelativeTime } = useMessageUtils();

const getReadReceiptTitle = computed(() => {
    if (props.message.read_at) {
        return `Read at ${formatMessageTime(props.message.read_at)}`;
    } else if (props.message.id) {
        return "Delivered - waiting to be read";
    } else {
        return "Sending...";
    }
});
</script>
