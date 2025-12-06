<!-- resources/js/components/Chat/Sidebar.vue -->
<template>
    <div class="w-80 bg-white border-r border-gray-200 flex flex-col">
        <!-- New Conversation Button -->
        <div class="p-4 border-b border-gray-200">
            <button
                @click="emit('newConversation')"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded-md font-medium"
            >
                New Conversation
            </button>
        </div>

        <!-- Conversations List -->
        <ConversationsList
            :conversations="conversations"
            :current-conversation-id="currentConversationId"
            @conversation-selected="emit('conversationSelected', $event)"
            @new-conversation="emit('newConversation')"
            @conversation-deleted="handleConversationDeleted"
        />
    </div>
</template>

<script setup>
import ConversationsList from "./Sidebar/ConversationList.vue";

const props = defineProps({
    conversations: {
        type: Array,
        default: () => [],
    },
    currentConversationId: {
        type: [String, Number],
        default: null,
    },
});

const emit = defineEmits([
    "newConversation",
    "conversationSelected",
    "conversationDeleted",
]);
function handleConversationDeleted(conversationId) {
    emit("conversationDeleted", conversationId);
}
</script>
