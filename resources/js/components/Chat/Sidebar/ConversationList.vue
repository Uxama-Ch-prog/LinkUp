<!-- resources/js/components/Chat/Sidebar/ConversationsList.vue -->
<template>
    <div class="flex-1 overflow-y-auto">
        <!-- Loading State -->
        <div v-if="loading" class="p-4 text-center text-gray-500">
            <div class="flex items-center justify-center space-x-2">
                <div
                    class="w-4 h-4 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"
                ></div>
                <span>Loading conversations...</span>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else-if="conversations.length === 0" class="p-6 text-center">
            <div
                class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center"
            >
                <svg
                    class="w-8 h-8 text-gray-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"
                    />
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">
                No conversations
            </h3>
            <p class="text-gray-500 text-sm mb-4">
                Start a new conversation to begin messaging.
            </p>
            <button
                @click="$emit('newConversation')"
                class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors"
            >
                Start Chat
            </button>
        </div>

        <!-- Conversations List -->
        <TransitionGroup v-else name="conversation-list" tag="div">
            <div
                v-for="conversation in conversations"
                :key="conversation.id"
                :class="[
                    'p-4 border-b border-gray-100 cursor-pointer transition-all duration-200 group relative',
                    'hover:bg-gray-50 transform hover:translate-x-1',
                    currentConversationId === conversation.id
                        ? 'bg-blue-50 border-blue-200 shadow-sm'
                        : '',
                ]"
            >
                <!-- Main clickable area -->
                <div
                    class="flex items-start space-x-3"
                    @click="$emit('conversationSelected', conversation)"
                >
                    <!-- Conversation avatar -->
                    <div class="flex-shrink-0">
                        <div class="relative">
                            <div
                                class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white text-sm font-medium shadow-sm"
                            >
                                {{
                                    chatStore.getConversationInitials(
                                        conversation
                                    )
                                }}
                            </div>
                            <!-- Online indicator -->
                            <div
                                v-if="
                                    chatStore.getOnlineParticipants(
                                        conversation
                                    ).length > 0
                                "
                                class="absolute -bottom-1 -right-1 w-3 h-3 bg-green-500 rounded-full border-2 border-white"
                            ></div>
                        </div>
                    </div>

                    <!-- Conversation info -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between mb-1">
                            <h4
                                class="text-sm font-semibold text-gray-900 truncate"
                            >
                                {{
                                    chatStore.getConversationName(conversation)
                                }}
                            </h4>
                            <span
                                class="text-xs text-gray-400 flex-shrink-0 ml-2"
                            >
                                {{
                                    formatRelativeTime(
                                        conversation.last_message_at
                                    )
                                }}
                            </span>
                        </div>

                        <p class="text-sm text-gray-600 truncate mb-1">
                            {{ chatStore.getLastMessagePreview(conversation) }}
                        </p>

                        <!-- Conversation status -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <!-- Unread badge -->
                                <span
                                    v-if="
                                        chatStore.getUnreadCount(conversation) >
                                        0
                                    "
                                    class="bg-red-500 text-white text-xs px-2 py-1 rounded-full font-medium animate-pulse"
                                >
                                    {{ chatStore.getUnreadCount(conversation) }}
                                </span>

                                <!-- Online count -->
                                <span
                                    v-if="
                                        chatStore.getOnlineParticipants(
                                            conversation
                                        ).length > 0
                                    "
                                    class="text-xs text-green-600 font-medium"
                                >
                                    {{
                                        chatStore.getOnlineParticipants(
                                            conversation
                                        ).length
                                    }}
                                    online
                                </span>
                            </div>

                            <!-- Message status indicators -->
                            <div
                                v-if="conversation.latest_message"
                                class="flex items-center space-x-1"
                            >
                                <span
                                    v-if="
                                        conversation.latest_message.user_id ===
                                        authStore.user.id
                                    "
                                    class="text-blue-500 text-xs"
                                >
                                    ✓
                                </span>
                                <span
                                    v-if="
                                        conversation.latest_message.attachments
                                    "
                                    class="text-gray-400 text-xs"
                                >
                                    📎
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions dropdown - Separate from main click area -->
                <div class="absolute right-3 top-3">
                    <ConversationActions
                        :conversation="conversation"
                        @deleted="$emit('conversationDeleted', $event)"
                    />
                </div>
            </div>
        </TransitionGroup>
    </div>
</template>

<script setup>
import { computed } from "vue";
import { useAuthStore, useChatStore } from "../../../stores";
import { useConversationUtils } from "../../../composables/useConversationUtils";
import ConversationActions from "./ConversationActions.vue";

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
    "conversationSelected",
    "newConversation",
    "conversationDeleted",
]);

const authStore = useAuthStore();
const chatStore = useChatStore();
const { formatRelativeTime } = useConversationUtils();

const loading = computed(() => !authStore.user);
</script>

<style scoped>
.conversation-list-enter-active,
.conversation-list-leave-active {
    transition: all 0.3s ease;
}

.conversation-list-enter-from {
    opacity: 0;
    transform: translateX(-30px);
}

.conversation-list-leave-to {
    opacity: 0;
    transform: translateX(30px);
}

.conversation-list-leave-active {
    position: absolute;
    width: calc(100% - 2rem);
}
</style>
