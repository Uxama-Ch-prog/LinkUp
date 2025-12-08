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
            @update:attachments="updateAttachments"
            @update:error="sendError = $event"
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
    updateAttachments,
} = useConversation(props);

// realtime
const setupConversationWebSocket = () => {
    if (!effectiveConversationId.value || !authStore.user?.id) return;

    console.log("🔌 Setting up conversation-specific WebSocket listeners");

    // Listen for conversation-specific events
    window.Echo.private(`conversation.${effectiveConversationId.value}`)
        .listen(".MessageSent", (e) => {
            console.log("💬 Message received in conversation:", {
                message: e.message,
                read_at: e.message.read_at,
                isOwnMessage: e.message.user_id === authStore.user.id,
                shouldHaveReadReceipt:
                    e.message.read_at &&
                    e.message.user_id === authStore.user.id,
            });
            // If the message is for this conversation, add it
            if (e.message.conversation_id == effectiveConversationId.value) {
                // Ensure read_at is null for new messages from others
                if (e.message.user_id !== authStore.user.id) {
                    e.message.read_at = null;
                }
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
        })
        .listen(".MessageRead", (e) => {
            console.log("✅ Message read receipt received:", e);
            handleMessageReadReceipt(e);
        });
};

// Handle message read receipts
const handleMessageReadReceipt = (eventData) => {
    console.log("📨 Processing read receipt:", {
        eventData,
        currentConversation: effectiveConversationId.value,
        isOwnMessage: eventData.userId === authStore.user?.id,
    });

    // Only process if it's for this conversation
    if (eventData.conversationId != effectiveConversationId.value) {
        console.log("⚠️ Read receipt not for this conversation, skipping");
        return;
    }

    // Find the message in the store and update its read_at status
    const message = chatStore.messages.find((m) => m.id == eventData.messageId);

    if (message) {
        console.log("✅ Found message to update read status:", {
            messageId: message.id,
            previousReadAt: message.read_at,
            newReadAt: new Date().toISOString(),
        });

        // Update the read_at timestamp
        message.read_at = new Date().toISOString();

        // Force reactivity update
        chatStore.messages = [...chatStore.messages];

        // Also update in the conversations list if this message is the latest
        const conversation = chatStore.conversations.find(
            (c) => c.id == effectiveConversationId.value
        );

        if (conversation && conversation.latest_message?.id == message.id) {
            conversation.latest_message.read_at = message.read_at;
            // Force reactivity
            chatStore.conversations = [...chatStore.conversations];
        }

        console.log("✅ Message read status updated in real-time");
    } else {
        console.log(
            "⚠️ Message not found in store for read receipt:",
            eventData.messageId
        );
    }
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
            window.Echo.leave(`conversation.${oldId}`);
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
