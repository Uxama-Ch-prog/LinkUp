<template>
    <div class="h-screen flex flex-col">
        <!-- Header -->
        <ChatHeader @search="showSearchModal = true" />

        <!-- Main Content -->
        <div class="flex-1 flex overflow-hidden">
            <!-- Sidebar -->
            <ChatSidebar
                :conversations="sortedConversations"
                :current-conversation-id="currentConversationId"
                @new-conversation="showNewChatModal = true"
                @conversation-selected="selectConversation"
            />

            <!-- Main Chat Area -->
            <div class="flex-1 flex flex-col">
                <EmptyConversationState v-if="!currentConversationId" />
                <Conversation
                    v-else
                    :conversation-id="currentConversationId"
                    @new-conversation-created="handleNewConversationCreated"
                    @message-sent="handleMessageSent"
                />
            </div>
        </div>

        <!-- Modals -->
        <NewChatModal
            v-if="showNewChatModal"
            @close="closeModal"
            @create="createConversation"
        />

        <SearchModal v-if="showSearchModal" @close="showSearchModal = false" />
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from "vue";
import { useRouter, useRoute } from "vue-router";
import { useAuthStore, useChatStore } from "../../stores";
import { useConversationUtils } from "../../composables/useConversationUtils";
import ChatHeader from "../../components/Chat/Header.vue";
import ChatSidebar from "../../components/Chat/Sidebar.vue";
import EmptyConversationState from "../../components/Chat/EmptyConversationState.vue";
import NewChatModal from "../../components/Chat/NewChatModal.vue";
import Conversation from "./Conversation.vue";
import SearchModal from "../../components/SearchModal.vue";

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const chatStore = useChatStore();
const { getUserInitials } = useConversationUtils();

// State
const currentConversationId = ref(route.params.conversationId || null);
const showNewChatModal = ref(false);
const showSearchModal = ref(false);
const isRefreshingConversations = ref(false);

// Computed
const sortedConversations = computed(() => {
    return [...chatStore.conversations].sort((a, b) => {
        const timeA = new Date(a.last_message_at || a.created_at);
        const timeB = new Date(b.last_message_at || b.created_at);
        return timeB - timeA;
    });
});

// Watchers
watch(
    () => route.params.conversationId,
    (newId) => {
        if (newId) {
            currentConversationId.value = newId;
            markConversationAsRead(newId);
        }
    }
);

// Lifecycle
onMounted(async () => {
    try {
        initializeRealtimeListeners();

        await Promise.all([
            chatStore.fetchConversations(),
            chatStore.fetchUsers(),
        ]);

        if (route.params.conversationId) {
            await chatStore.fetchConversation(route.params.conversationId);
            markConversationAsRead(route.params.conversationId);
        }
    } catch (error) {
        console.error("Error loading data:", error);
    }
});

onUnmounted(() => {
    if (window.Echo) {
        window.Echo.leave(`user.${authStore.user?.id}`);
    }
});

function initializeRealtimeListeners() {
    if (!authStore.user?.id) return;

    console.log(
        "🔌 Initializing WebSocket listeners for user:",
        authStore.user.id
    );

    try {
        if (window.Echo) {
            window.Echo.leave(`user.${authStore.user.id}`);
        }

        window.Echo.private(`user.${authStore.user.id}`)
            .listen(".MessageSent", (e) => {
                console.log("💬 Message received via user channel:", {
                    messageId: e.message.id,
                    fromUser: e.message.user_id,
                    toUser: authStore.user?.id,
                    isOwnMessage: e.message.user_id === authStore.user?.id,
                    currentRoute: route.path,
                });

                // Check if this message is for the current user
                if (e.message && e.message.user_id !== authStore.user?.id) {
                    // Process the message in store
                    chatStore.handleIncomingMessage(e.message);

                    console.log(
                        "📨 Message received, but NOT marking as read (user is on dashboard)"
                    );

                    // Show notification if not viewing the conversation
                    if (
                        currentConversationId.value != e.message.conversation_id
                    ) {
                        showNotification(e.message);
                    }
                }
            })
            .listen(".ConversationCreated", (e) => {
                // Add detailed logging
                console.log("💬 NEW conversation created EVENT RECEIVED:", {
                    conversation: e.conversation,
                    conversationId: e.conversation?.id,
                    participantCount: e.conversation?.participants?.length,
                    isGroup: e.conversation?.is_group,
                    eventType: "ConversationCreated",
                });

                if (e.conversation) {
                    chatStore.handleNewConversation(e.conversation);
                    refreshConversations();

                    console.log(
                        "✅ ConversationCreated event processed successfully"
                    );
                } else {
                    console.error("❌ No conversation data in event:", e);
                }
            })
            .listen(".ConversationRestored", (e) => {
                console.log("🔄 ConversationRestored event received:", {
                    conversationId: e.conversation?.id,
                    participants: e.conversation?.participants?.length,
                    fullEvent: e,
                });
                if (e.conversation) {
                    chatStore.handleConversationRestored(e.conversation);
                    refreshConversations();
                } else {
                    console.error(
                        "No conversation data in ConversationRestored event:",
                        e
                    );
                }
            })
            .listen(".MessageRead", (e) => {
                console.log("👀 Message read update:", e);
                handleMessageReadUpdate(e);
                refreshConversations();
            })
            .listen(".ConversationDeleted", (e) => {
                console.log("🗑️ Conversation deleted:", e);
                chatStore.handleConversationDeleted(e.conversation_id);
                refreshConversations();

                if (currentConversationId.value == e.conversation_id) {
                    currentConversationId.value = null;
                    router.push("/chat");
                }
            })
            .listen(".UserTyping", (e) => {
                console.log("⌨️ User typing:", e);
                handleUserTyping(e);
            });

        console.log("✅ Real-time listeners initialized successfully");
    } catch (error) {
        console.error("❌ Error initializing real-time listeners:", error);
    }
}
async function refreshConversations() {
    try {
        // Only refresh if we're not currently loading
        if (!isRefreshingConversations.value) {
            isRefreshingConversations.value = true;
            await chatStore.fetchConversations();
            console.log("✅ Conversations refreshed in real-time");
        }
    } catch (error) {
        console.error("❌ Error refreshing conversations:", error);
    } finally {
        isRefreshingConversations.value = false;
    }
}
function handleUserTyping(eventData) {
    chatStore.setUserTyping(
        eventData.conversation_id,
        eventData.user_id,
        eventData.is_typing
    );
}
// In Dashboard.vue - add this function
function showNotification(message) {
    if (!("Notification" in window)) {
        return;
    }

    if (Notification.permission === "granted") {
        const conversation = chatStore.conversations.find(
            (c) => c.id == message.conversation_id
        );
        const senderName = message.user?.name || "Someone";
        const preview = message.body
            ? message.body.length > 50
                ? message.body.substring(0, 50) + "..."
                : message.body
            : "📎 Attachment";

        new Notification(senderName, {
            body: preview,
            icon: "/favicon.ico",
            tag: `message-${message.id}`,
        });
    } else if (Notification.permission !== "denied") {
        Notification.requestPermission().then((permission) => {
            if (permission === "granted") {
                showNotification(message);
            }
        });
    }
}
function handleMessageReadUpdate(eventData) {
    console.log("📊 Handling MessageRead event:", eventData);

    // The event data should have conversationId, userId, messageId
    if (eventData.conversationId) {
        // Update unread count for this conversation
        const conversation = chatStore.conversations.find(
            (c) => c.id == eventData.conversationId
        );

        if (conversation) {
            // If the message was read by another user, we might want to update UI
            // For now, just log it
            console.log(
                `User ${eventData.userId} read message ${eventData.messageId} in conversation ${eventData.conversationId}`
            );

            // If the current user is viewing this conversation, update unread count
            if (currentConversationId.value == eventData.conversationId) {
                markConversationAsRead(eventData.conversationId);
            }
        }
    }
}
// Update markConversationAsRead
function markConversationAsRead(conversationId) {
    if (conversationId) {
        const conversation = chatStore.conversations.find(
            (c) => c.id == conversationId
        );
        if (conversation && conversation.unread_messages_count > 0) {
            conversation.unread_messages_count = 0;
            // Force reactivity
            chatStore.conversations = [...chatStore.conversations];
        }
    }
}

// Update handleMessageSent
function handleMessageSent(messageData) {
    if (!messageData || !messageData.conversation_id) return;

    const existingConversation = chatStore.conversations.find(
        (conv) => conv.id == messageData.conversation_id
    );

    if (existingConversation) {
        existingConversation.latest_message = messageData;
        existingConversation.last_message_at = messageData.created_at;
        chatStore.moveConversationToTop(messageData.conversation_id);
        // Force reactivity
        chatStore.conversations = [...chatStore.conversations];
    }
}

function selectConversation(conversation) {
    if (!conversation?.id) return;

    currentConversationId.value = conversation.id;
    router.push(`/chat/${conversation.id}`);
    markConversationAsRead(conversation.id);
}

function handleNewConversationCreated(conversation) {
    console.log("🎯 New conversation created from modal:", conversation);
    if (conversation && conversation.id) {
        // Force refresh the conversations list
        chatStore.fetchConversations();

        currentConversationId.value = conversation.id;
        router.push(`/chat/${conversation.id}`);
    }
}

function closeModal() {
    showNewChatModal.value = false;
}

async function createConversation(users, groupName) {
    try {
        const conversation = await chatStore.createConversation(
            users,
            groupName,
            users.length > 1
        );

        closeModal();
        await chatStore.fetchConversations();
        // Check if the conversation is already in the store
        const existingConversation = chatStore.conversations.find(
            (c) => c.id === conversation.id
        );

        if (!existingConversation) {
            // Add to conversations list if not already there
            chatStore.conversations.unshift(conversation);
        }

        // Select the conversation
        selectConversation(conversation);
    } catch (error) {
        console.error("Error creating conversation:", error);
        // Handle error appropriately
        alert(error.response?.data?.message || "Failed to create conversation");
    }
}
</script>
