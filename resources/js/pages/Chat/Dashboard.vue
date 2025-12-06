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

<!-- Dashboard.vue - Complete script section -->
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
const chatStore = useChatStore(); // Make sure this is imported
const { getUserInitials } = useConversationUtils();
let loadTimeout = null;
// State
const currentConversationId = ref(route.params.conversationId || null);
const showNewChatModal = ref(false);
const showSearchModal = ref(false);
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
watch(
    () => route.params.conversationId,
    (newId) => {
        if (newId) {
            // Debounce to prevent rapid reloads
            clearTimeout(loadTimeout);
            loadTimeout = setTimeout(() => {
                console.log("🔄 Route changed, loading conversation:", newId);
                currentConversationId.value = newId;
                markConversationAsRead(newId);
            }, 100);
        }
    }
);
// Lifecycle
onMounted(async () => {
    try {
        await Promise.all([
            chatStore.fetchConversations(),
            chatStore.fetchUsers(),
        ]);

        if (route.params.conversationId) {
            await chatStore.fetchConversation(route.params.conversationId);
            markConversationAsRead(route.params.conversationId);
        }

        initializeRealtimeListeners();
    } catch (error) {
        console.error("Error loading data:", error);
    }
});

onUnmounted(() => {
    if (window.Echo) {
        window.Echo.leave(`user.${authStore.user?.id}`);
    }
});

// Methods
function initializeRealtimeListeners() {
    if (!authStore.user?.id) return;

    try {
        window.Echo.private(`user.${authStore.user.id}`)
            .listen("MessageSent", (e) => {
                console.log("💬 Message received via Echo:", e.message);
                chatStore.handleIncomingMessage(e.message);
            })
            .listen("ConversationCreated", (e) => {
                console.log("💬 NEW conversation created:", e.conversation);
                chatStore.handleNewConversation(e.conversation);
            })
            .listen("MessageRead", (e) => {
                console.log("👀 Message read update:", e);
                handleMessageReadUpdate(e);
            })
            .listen("ConversationDeleted", (e) => {
                console.log("🗑️ Conversation deleted:", e);
                chatStore.handleConversationDeleted(e.conversation_id);

                if (currentConversationId.value == e.conversation_id) {
                    currentConversationId.value = null;
                    router.push("/chat");
                }
            })
            .listen("ConversationRestored", (e) => {
                console.log("🔄 Conversation restored:", e.conversation);
                chatStore.handleConversationRestored(e.conversation);

                if (!currentConversationId.value) {
                    selectConversation(e.conversation);
                }
            });

        console.log("✅ Real-time listeners initialized");
    } catch (error) {
        console.error("❌ Error initializing real-time listeners:", error);
    }
}

function handleMessageReadUpdate(eventData) {
    if (eventData.conversationId == currentConversationId.value) {
        markConversationAsRead(eventData.conversationId);

        // Also update the store
        const conversation = chatStore.conversations.find(
            (c) => c.id == eventData.conversationId
        );
        if (conversation) {
            conversation.unread_messages_count = 0;
            // Force reactivity
            chatStore.conversations = [...chatStore.conversations];
        }
    }
}

// FIXED: Use chatStore.conversations
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

function handleNewConversation(conversationData) {
    if (!conversationData) return;

    console.log("🆕 Handling new conversation:", conversationData.id);

    const existingIndex = chatStore.conversations.findIndex(
        (conv) => conv.id == conversationData.id
    );

    if (existingIndex === -1) {
        // Add new conversation to store
        chatStore.conversations.unshift({
            ...conversationData,
            unread_messages_count: 0, // New conversation, no messages yet
        });

        // Force UI update
        chatStore.conversations = [...chatStore.conversations];
    }
}

// FIXED: Use chatStore.conversations
function selectConversation(conversation) {
    if (!conversation?.id) return;

    currentConversationId.value = conversation.id;
    router.push(`/chat/${conversation.id}`);
    markConversationAsRead(conversation.id);
}

function handleConversationDeleted(conversationId) {
    // If the deleted conversation is currently open, close it
    if (currentConversationId.value == conversationId) {
        currentConversationId.value = null;
        router.push("/chat");
    }
}

function handleNewConversationCreated(conversation) {
    if (conversation && conversation.id) {
        currentConversationId.value = conversation.id;
        router.push(`/chat/${conversation.id}`);

        const existing = chatStore.conversations.find(
            (c) => c.id == conversation.id
        );
        if (!existing) {
            chatStore.conversations.unshift(conversation);
        }
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
