<!-- resources/js/components/Chat/Sidebar/ConversationsList.vue -->
<template>
    <div class="flex-1 overflow-y-auto">
        <!-- Loading State -->
        <div v-if="loading" class="p-6 text-center">
            <div class="space-y-3">
                <!-- Skeleton Loaders -->
                <div
                    v-for="i in 3"
                    :key="i"
                    class="p-4 mx-4 rounded-lg bg-gray-100 animate-pulse"
                >
                    <div class="flex space-x-3">
                        <div class="w-12 h-12 bg-gray-300 rounded-full"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-4 bg-gray-300 rounded w-3/4"></div>
                            <div class="h-3 bg-gray-300 rounded w-1/2"></div>
                        </div>
                    </div>
                </div>
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
                {{ emptyStateTitle }}
            </h3>
            <p class="text-gray-500 text-sm mb-4">
                {{ emptyStateMessage }}
            </p>
            <button
                v-if="activeTab === 'all'"
                @click="$emit('newConversation')"
                class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors shadow-sm"
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
                    'relative border-b border-gray-200 cursor-pointer transition-all duration-200 group',
                    'hover:bg-gray-50',
                    currentConversationId === conversation.id
                        ? 'bg-indigo-50 border-indigo-200 shadow-sm'
                        : '',
                ]"
            >
                <!-- Main clickable area -->
                <div
                    class="flex items-start space-x-3 p-3"
                    @click="$emit('conversationSelected', conversation)"
                >
                    <!-- Conversation avatar -->
                    <div class="flex-shrink-0 relative">
                        <div
                            class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold shadow-sm"
                        >
                            {{
                                chatStore.getConversationInitials(conversation)
                            }}
                        </div>
                        <!-- Online indicator -->
                        <div
                            v-if="
                                chatStore.getOnlineParticipants(conversation)
                                    .length > 0
                            "
                            class="absolute -bottom-1 right-1 w-3 h-3 bg-green-500 rounded-full border-2 border-white"
                        ></div>
                    </div>

                    <!-- Conversation info -->
                    <div class="flex-1 min-w-0">
                        <!-- Header with name and time -->
                        <div class="flex items-center justify-between mb-1">
                            <h4
                                class="text-sm font-semibold text-gray-900 capitalize"
                            >
                                {{
                                    chatStore.getConversationName(conversation)
                                }}
                            </h4>
                            <div class="flex items-center space-x-1">
                                <!-- Time -->
                                <span class="text-xs text-gray-400">
                                    {{
                                        formatRelativeTime(
                                            conversation.last_message_at
                                        )
                                    }}
                                </span>
                            </div>
                        </div>

                        <!-- Message preview and right-aligned actions -->
                        <div class="flex items-start justify-between">
                            <p class="text-sm text-gray-600 truncate flex-1">
                                {{
                                    chatStore.getLastMessagePreview(
                                        conversation
                                    )
                                }}
                            </p>

                            <!-- Right-aligned: Arrow and Unread count side by side -->
                            <div class="flex items-center space-x-2 ml-2">
                                <!-- Unread count badge -->
                                <div
                                    v-if="
                                        chatStore.getUnreadCount(conversation) >
                                        0
                                    "
                                >
                                    <div
                                        class="bg-indigo-600 text-white text-xs w-5 h-5 rounded-full flex items-center justify-center font-bold shadow-sm"
                                    >
                                        {{
                                            Math.min(
                                                chatStore.getUnreadCount(
                                                    conversation
                                                ),
                                                99
                                            )
                                        }}
                                    </div>
                                </div>

                                <!-- Arrow dropdown -->
                                <div class="relative">
                                    <ConversationActions
                                        :conversation="conversation"
                                        @deleted="
                                            $emit('conversationDeleted', $event)
                                        "
                                        @favouriteToggled="
                                            handleFavouriteToggle
                                        "
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Additional indicators (moved below message preview) -->
                        <div class="flex items-center space-x-2 mt-1">
                            <!-- Attachment indicator -->
                            <span
                                v-if="
                                    conversation.latest_message?.attachments
                                        ?.length
                                "
                                class="text-xs text-gray-400"
                            >
                                📎
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </TransitionGroup>
    </div>
</template>

<script setup>
import { computed, defineProps, defineEmits } from "vue";
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
    activeTab: {
        type: String,
        default: "all",
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

// Empty state messages based on active tab
const emptyStateTitle = computed(() => {
    switch (props.activeTab) {
        case "unread":
            return "No unread messages";
        case "favourites":
            return "No favourite conversations";
        default:
            return "No conversations";
    }
});

const emptyStateMessage = computed(() => {
    switch (props.activeTab) {
        case "unread":
            return "You've read all your messages!";
        case "favourites":
            return "Mark conversations as favourites to see them here.";
        default:
            return "Start a new conversation to begin messaging.";
    }
});

function handleFavouriteToggle(eventData) {
    // You can update local state if needed
    const conversation = props.conversations.find(
        (c) => c.id === eventData.conversationId
    );
    if (conversation) {
        conversation.is_favourite = eventData.isFavourite;
    }

    // Emit to parent if needed
    // $emit('favouriteToggled', eventData);
}
</script>

<style scoped>
.conversation-list-enter-active,
.conversation-list-leave-active {
    transition: all 0.3s ease;
}

.conversation-list-enter-from {
    opacity: 0;
    transform: translateX(-20px);
}

.conversation-list-leave-to {
    opacity: 0;
    transform: translateX(20px);
}

.conversation-list-leave-active {
    position: absolute;
    width: calc(100% - 1rem);
}
</style>
