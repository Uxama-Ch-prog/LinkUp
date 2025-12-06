<!-- resources/js/components/Chat/Conversation/TypingIndicator.vue -->
<template>
    <div class="px-6 py-2 bg-gray-50 border-t border-gray-100">
        <div class="flex items-center space-x-2 text-gray-500 text-sm">
            <!-- Animated dots -->
            <div class="flex space-x-1">
                <div
                    class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"
                    style="animation-delay: 0ms"
                ></div>
                <div
                    class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"
                    style="animation-delay: 150ms"
                ></div>
                <div
                    class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"
                    style="animation-delay: 300ms"
                ></div>
            </div>

            <!-- Typing text -->
            <span class="font-medium">
                {{ typingText }}
            </span>
        </div>
    </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
    users: {
        type: Array,
        default: () => [],
    },
});

const typingText = computed(() => {
    if (props.users.length === 0) return "";

    if (props.users.length === 1) {
        return `${props.users[0]} is typing...`;
    }

    if (props.users.length === 2) {
        return `${props.users[0]} and ${props.users[1]} are typing...`;
    }

    return `${props.users[0]} and ${
        props.users.length - 1
    } others are typing...`;
});
</script>

<style scoped>
.animate-bounce {
    animation: bounce 1.5s infinite;
}

@keyframes bounce {
    0%,
    100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-4px);
    }
}
</style>
