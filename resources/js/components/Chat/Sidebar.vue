<!-- resources/js/components/Chat/Sidebar.vue -->
<template>
    <div class="w-80 bg-white border-r border-gray-200 flex flex-col">
        <!-- Header with Title and New Conversation Button -->
        <div class="p-4 border-gray-200">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-bold text-gray-900">Messages</h2>
                <button
                    @click="emit('newConversation')"
                    class="p-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors duration-200"
                    title="New Conversation"
                >
                    <svg
                        class="w-5 h-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 4v16m8-8H4"
                        />
                    </svg>
                </button>
            </div>

            <!-- Tabs - Text with bottom border -->
            <div class="flex space-x-8 border-b border-gray-200">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    @click="activeTab = tab.id"
                    :class="[
                        'pb-3 text-sm font-medium transition-all duration-200 relative',
                        'focus:outline-none',
                        activeTab === tab.id
                            ? 'text-indigo-600'
                            : 'text-gray-500 hover:text-gray-700',
                    ]"
                >
                    {{ tab.label }}
                    <!-- Active tab indicator -->
                    <div
                        v-if="activeTab === tab.id"
                        class="absolute bottom-0 left-0 right-0 h-0.5 bg-indigo-600 rounded-full"
                    ></div>
                </button>
            </div>
        </div>

        <!-- Conversations List -->
        <ConversationsList
            :conversations="filteredConversations"
            :current-conversation-id="currentConversationId"
            @conversation-selected="emit('conversationSelected', $event)"
            @new-conversation="emit('newConversation')"
            @conversation-deleted="handleConversationDeleted"
        />
    </div>
</template>

<script setup>
import { ref, computed } from "vue";
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

const activeTab = ref("all");

const tabs = [
    { id: "all", label: "All Messages" },
    { id: "unread", label: "Unread" },
    { id: "favourites", label: "Favourites" },
];

const filteredConversations = computed(() => {
    switch (activeTab.value) {
        case "unread":
            return props.conversations.filter(
                (conv) => conv.unread_messages_count > 0
            );
        case "favourites":
            // You'll need to add a favourite property to your conversation model
            return props.conversations.filter((conv) => conv.is_favourite);
        default:
            return props.conversations;
    }
});

function handleConversationDeleted(conversationId) {
    emit("conversationDeleted", conversationId);
}
</script>
