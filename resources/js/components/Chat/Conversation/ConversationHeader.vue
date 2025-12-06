<!-- resources/js/components/Chat/Conversation/ConversationHeader.vue -->
<template>
    <div class="bg-white border-b border-gray-200 px-6 py-4 shadow-sm">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <h2 class="text-lg font-semibold text-gray-900">
                    {{ getConversationName() }}
                </h2>
                <VideoCall />
                <div class="flex items-center space-x-1">
                    <div
                        v-for="participant in getOtherParticipants()"
                        :key="participant.id"
                        class="flex items-center"
                    >
                        <div
                            :class="[
                                'w-2 h-2 rounded-full mr-1',
                                chatStore.onlineUsers.has(participant.id)
                                    ? 'bg-green-500'
                                    : 'bg-gray-300',
                            ]"
                        ></div>
                        <span class="text-sm text-gray-500">{{
                            participant.name
                        }}</span>
                    </div>
                </div>
            </div>
            <div
                v-if="typingUsers.length > 0"
                class="text-sm text-gray-500 animate-pulse"
            >
                {{ typingUsers.join(", ") }}
                {{ typingUsers.length === 1 ? "is" : "are" }} typing...
            </div>
        </div>
    </div>
</template>

<script setup>
import { useAuthStore, useChatStore } from "../../../stores";
import VideoCall from "../../VideoCall.vue";

const props = defineProps({
    conversation: {
        type: Object,
        default: null,
    },
    typingUsers: {
        type: Array,
        default: () => [],
    },
});

const authStore = useAuthStore();
const chatStore = useChatStore();

function getConversationName() {
    if (!props.conversation) return "Loading...";

    if (props.conversation.name) {
        return props.conversation.name;
    }

    const otherParticipants = getOtherParticipants();
    return otherParticipants.map((p) => p.name).join(", ");
}

function getOtherParticipants() {
    if (!props.conversation?.participants) return [];
    return props.conversation.participants.filter(
        (p) => p.id !== authStore.user.id
    );
}
</script>
