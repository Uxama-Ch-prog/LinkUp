<template>
    <div v-if="effectiveConversationId" class="h-full flex flex-col">
        <!-- Conversation Header -->
        <ConversationHeader
            :conversation="chatStore.currentConversation"
            :typing-users="typingUsers"
        />

        <!-- Messages Area -->
        <MessagesArea
            ref="messagesContainer"
            :is-loading-more="isLoadingMore"
            @scroll="handleScroll"
            @load-more="loadMoreMessages"
        />

        <!-- Typing Indicator -->
        <TypingIndicator v-if="typingUsers.length > 0" :users="typingUsers" />

        <!-- Message Input Section -->
        <MessageInput
            v-model="newMessage"
            :attachments="attachments"
            :is-sending="isSending"
            :error="sendError"
            @send="sendMessage"
            @typing="handleTyping"
            @clear-attachments="clearAttachments"
            @remove-attachment="removeAttachment"
        />
    </div>

    <!-- No conversation selected -->
    <EmptyConversationState v-else />
</template>

<script setup>
import { onMounted, onUnmounted, watch, nextTick } from "vue";
import { useRoute } from "vue-router";
import { useAuthStore, useChatStore } from "../../stores";
import { useConversation } from "../../composables/useConversation";
import ConversationHeader from "../../components/Chat/Conversation/ConversationHeader.vue";
import MessagesArea from "../../components/Chat/Conversation/MessagesArea.vue";
import TypingIndicator from "../../components/Chat/Conversation/TypingIndicator.vue";
import MessageInput from "../../components/Chat/Conversation/MessageInput.vue";
import EmptyConversationState from "../../components/Chat/EmptyConversationState.vue";

const route = useRoute();
const authStore = useAuthStore();
const chatStore = useChatStore();

const props = defineProps({
    conversationId: {
        type: [String, Number],
        required: false,
        default: null,
    },
});

const {
    effectiveConversationId,
    newMessage,
    attachments,
    isSending,
    sendError,
    typingUsers,
    isLoadingMore,
    messagesContainer,
    loadConversation,
    setupWebSocketListeners,
    markMessagesAsRead,
    handleScroll,
    loadMoreMessages,
    handleTyping,
    sendMessage,
    removeAttachment,
    clearAttachments,
} = useConversation(props);
// realtime
const setupConversationWebSocket = () => {
    if (!effectiveConversationId.value || !authStore.user?.id) return;

    console.log("🔌 Setting up conversation-specific WebSocket listeners");

    // Listen for conversation-specific events
    window.Echo.private(`conversation.${effectiveConversationId.value}`)
        .listen(".MessageSent", (e) => {
            console.log("💬 Message received in conversation:", e);
            // If the message is for this conversation, add it
            if (e.message.conversation_id == effectiveConversationId.value) {
                chatStore.addMessage(e.message);
                // Mark as read if we're the recipient
                if (e.message.user_id !== authStore.user.id) {
                    markMessagesAsRead();
                }
            }
        })
        .listen(".UserTyping", (e) => {
            console.log("⌨️ Typing in conversation:", e);
            if (e.conversation_id == effectiveConversationId.value) {
                chatStore.setUserTyping(
                    e.conversation_id,
                    e.user_id,
                    e.is_typing
                );
            }
        });
};

onMounted(async () => {
    console.log(
        "📋 Conversation mounted with ID:",
        effectiveConversationId.value
    );

    if (effectiveConversationId.value) {
        await loadConversation();
        setupWebSocketListeners();
        setupConversationWebSocket();
        await markMessagesAsRead();
        await nextTick(); // Ensure DOM is updated
    }
});

onUnmounted(() => {
    if (effectiveConversationId.value) {
        window.Echo.leave(`conversation.${effectiveConversationId.value}`);
        // Stop typing when leaving
        handleTyping(false);
    }
});

watch(effectiveConversationId, async (newId, oldId) => {
    if (newId && newId !== oldId) {
        console.log("📋 Conversation ID changed to:", newId);
        // Leave old conversation channel
        if (oldId && window.Echo) {
            window.Echo.leave(`private-conversation.${oldId}`);
        }
        await loadConversation();
        await markMessagesAsRead();
        setupWebSocketListeners();
        setupConversationWebSocket();

        // Force focus on message input
        await nextTick();
    }
});
</script>
