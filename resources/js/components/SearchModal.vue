<!-- resources/js/components/SearchModal.vue -->
<template>
    <div
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
    >
        <div
            class="bg-white rounded-lg max-w-2xl w-full max-h-96 overflow-hidden"
        >
            <!-- Header -->
            <div class="border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">Search</h3>
                    <button
                        @click="$emit('close')"
                        class="text-gray-400 hover:text-gray-600"
                    >
                        <svg
                            class="w-6 h-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <!-- Search input -->
                <div class="mt-4">
                    <input
                        v-model="searchQuery"
                        @input="performSearch"
                        type="text"
                        placeholder="Search messages and conversations..."
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        ref="searchInput"
                    />
                </div>
            </div>

            <!-- Search results -->
            <div class="overflow-y-auto max-h-64">
                <div v-if="loading" class="p-4 text-center text-gray-500">
                    Searching...
                </div>

                <div
                    v-else-if="searchQuery.length < 2"
                    class="p-4 text-center text-gray-500"
                >
                    Type at least 2 characters to search
                </div>

                <div
                    v-else-if="
                        searchResults.conversations.length === 0 &&
                        searchResults.messages.length === 0
                    "
                    class="p-4 text-center text-gray-500"
                >
                    No results found for "{{ searchQuery }}"
                </div>

                <div v-else>
                    <!-- Conversation results -->
                    <div
                        v-if="searchResults.conversations.length > 0"
                        class="border-b border-gray-200"
                    >
                        <div class="px-6 py-3 bg-gray-50">
                            <h4 class="text-sm font-medium text-gray-700">
                                Conversations
                            </h4>
                        </div>
                        <div
                            v-for="conversation in searchResults.conversations"
                            :key="conversation.id"
                            @click="selectConversation(conversation)"
                            class="px-6 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100"
                        >
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-gray-900">
                                        {{ getConversationName(conversation) }}
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        {{
                                            conversation.latest_message?.body ||
                                            "No messages"
                                        }}
                                    </p>
                                </div>
                                <span class="text-xs text-gray-400">
                                    {{
                                        formatTime(conversation.last_message_at)
                                    }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Message results -->
                    <div v-if="searchResults.messages.length > 0">
                        <div class="px-6 py-3 bg-gray-50">
                            <h4 class="text-sm font-medium text-gray-700">
                                Messages
                            </h4>
                        </div>
                        <div
                            v-for="message in searchResults.messages"
                            :key="message.id"
                            @click="selectMessage(message)"
                            class="px-6 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100"
                        >
                            <div class="flex justify-between items-start mb-1">
                                <span class="text-sm font-medium text-gray-900">
                                    {{
                                        getConversationName(
                                            message.conversation
                                        )
                                    }}
                                </span>
                                <span class="text-xs text-gray-400">
                                    {{ formatTime(message.created_at) }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-600">
                                {{ message.body }}
                            </p>
                            <p class="text-xs text-gray-400 mt-1">
                                By {{ message.user?.name }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore, useChatStore } from "../stores";

const emit = defineEmits(["close"]);

const router = useRouter();
const authStore = useAuthStore();
const chatStore = useChatStore();

const searchQuery = ref("");
const searchInput = ref(null);
const loading = ref(false);
const searchResults = ref({
    conversations: [],
    messages: [],
});

let searchTimeout = null;

onMounted(() => {
    nextTick(() => {
        searchInput.value.focus();
    });
});

function performSearch() {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    if (searchQuery.value.length < 2) {
        searchResults.value = { conversations: [], messages: [] };
        return;
    }

    loading.value = true;

    searchTimeout = setTimeout(async () => {
        try {
            const [conversationsResponse, messagesResponse] = await Promise.all(
                [
                    chatStore.searchConversations(searchQuery.value),
                    chatStore.searchMessages(searchQuery.value),
                ]
            );

            searchResults.value = {
                conversations: conversationsResponse,
                messages: messagesResponse,
            };
        } catch (error) {
            console.error("Search error:", error);
        } finally {
            loading.value = false;
        }
    }, 500);
}

function getConversationName(conversation) {
    if (conversation.name) return conversation.name;
    if (conversation.participants) {
        const otherParticipants = conversation.participants.filter(
            (p) => p.id !== authStore.user.id
        );
        return otherParticipants.map((p) => p.name).join(", ");
    }
    return "Unknown Conversation";
}

function formatTime(dateString) {
    if (!dateString) return "";
    const date = new Date(dateString);
    return date.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
}

function selectConversation(conversation) {
    emit("close");
    router.push(`/chat/${conversation.id}`);
}

function selectMessage(message) {
    emit("close");
    router.push(`/chat/${message.conversation_id}`);
    // You could add logic to highlight the specific message
}
</script>
