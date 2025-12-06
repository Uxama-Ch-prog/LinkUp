<template>
    <div class="flex flex-wrap gap-1 mt-2">
        <div
            v-for="reaction in reactions"
            :key="reaction.emoji"
            @click="toggleReaction(reaction.emoji)"
            :title="getReactionTooltip(reaction)"
            :class="[
                'px-2 py-1 rounded-full text-xs border cursor-pointer transition-all reaction-bounce hover:scale-110',
                reaction.user_ids?.includes(authStore.user.id)
                    ? 'bg-blue-100 border-blue-300 text-blue-700'
                    : 'bg-gray-100 border-gray-300 text-gray-700 hover:bg-gray-200',
            ]"
        >
            <span class="mr-1">{{ reaction.emoji }}</span>
            <span>{{ reaction.count }}</span>
        </div>
    </div>
</template>

<script setup>
import { useAuthStore } from "../../../stores/auth";

const props = defineProps({
    reactions: {
        type: Array,
        default: () => [],
    },
    messageId: {
        type: [String, Number],
        required: true,
    },
});

const emit = defineEmits(["reaction-toggled"]);

const authStore = useAuthStore();

function getReactionTooltip(reaction) {
    // Check if user_ids exists and is an array
    if (!reaction.user_ids || !Array.isArray(reaction.user_ids)) {
        return `${reaction.count} reactions`;
    }

    const userCount = reaction.user_ids.length; // Changed from users to user_ids
    if (userCount === 1) {
        return "1 person reacted";
    }
    return `${userCount} people reacted`;
}

function toggleReaction(emoji) {
    emit("reaction-toggled", props.messageId, emoji);
}
</script>

<style scoped>
.reaction-bounce {
    animation: bounce 0.5s ease-in-out;
}

@keyframes bounce {
    0%,
    20%,
    53%,
    80%,
    100% {
        transform: translate3d(0, 0, 0);
    }
    40%,
    43% {
        transform: translate3d(0, -8px, 0);
    }
    70% {
        transform: translate3d(0, -4px, 0);
    }
    90% {
        transform: translate3d(0, -2px, 0);
    }
}
</style>
