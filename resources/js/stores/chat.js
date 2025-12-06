import { defineStore } from "pinia";
import { ref, computed } from "vue";
import { useAuthStore } from "./auth";
import axios from "axios";

export const useChatStore = defineStore("chat", () => {
    const authStore = useAuthStore();
    const conversations = ref([]);
    const currentConversation = ref(null);
    const messages = ref([]);
    const users = ref([]);
    const onlineUsers = ref(new Set());
    const typingUsers = ref(new Map());
    const videoCall = ref(null);
    const localStream = ref(null);
    const remoteStream = ref(null);
    const peerConnection = ref(null);
    const isInCall = ref(false);
    const isCallActive = ref(false);
    const incomingCall = ref(null);

    const unreadCount = computed(() => {
        return conversations.value.reduce(
            (total, conv) => total + (conv.unread_messages_count || 0),
            0
        );
    });
    function forceUpdate(array) {
        return [...array];
    }
    async function fetchConversations() {
        try {
            const response = await axios.get("/api/chat/conversations");
            conversations.value = response.data;
        } catch (error) {
            console.error("Error fetching conversations:", error);
            throw error;
        }
    }
    async function deleteConversation(conversationId) {
        try {
            await axios.delete(`/api/chat/conversations/${conversationId}`);

            // Remove from local store
            const index = conversations.value.findIndex(
                (c) => c.id == conversationId
            );
            if (index !== -1) {
                conversations.value.splice(index, 1);
            }

            return true;
        } catch (error) {
            console.error("Error deleting conversation:", error);
            throw error;
        }
    }

    async function restoreConversation(conversationId) {
        try {
            const response = await axios.post(
                `/api/chat/conversations/${conversationId}/restore`
            );

            // Add back to conversations list
            conversations.value.unshift(response.data.conversation);

            return response.data.conversation;
        } catch (error) {
            console.error("Error restoring conversation:", error);
            throw error;
        }
    }

    function handleConversationDeleted(conversationId) {
        // Remove from local store
        const index = conversations.value.findIndex(
            (c) => c.id == conversationId
        );
        if (index !== -1) {
            conversations.value.splice(index, 1);
        }
    }

    // resources/js/stores/chat.js
    async function createConversation(userIds, name = null, isGroup = false) {
        try {
            console.log("Creating conversation with:", {
                userIds,
                name,
                isGroup,
            });

            const response = await axios.post(
                "/api/chat/conversations",
                {
                    user_ids: userIds,
                    name: name || "",
                    is_group: isGroup,
                },
                {
                    withCredentials: true,
                }
            );

            const conversation = response.data;

            // Check if conversation already exists in store
            const existingIndex = conversations.value.findIndex(
                (c) => c.id === conversation.id
            );

            if (existingIndex === -1) {
                // Add new conversation
                conversations.value.unshift(conversation);
            } else {
                // Update existing conversation (in case it was restored)
                conversations.value[existingIndex] = conversation;
            }

            return conversation;
        } catch (error) {
            console.error("Error creating conversation:", {
                message: error.message,
                response: error.response?.data,
                status: error.response?.status,
            });
            throw error;
        }
    }

    async function fetchConversation(id) {
        try {
            const response = await axios.get(`/api/chat/conversations/${id}`);
            currentConversation.value = response.data;
            messages.value = response.data.messages || [];
            console.log("🔄 Conversation loaded:", {
                id: response.data.id,
                name: response.data.name,
                participants: response.data.participants,
                participantCount: response.data.participants?.length,
            });
        } catch (error) {
            console.error("Error fetching conversation:", error);
            throw error;
        }
    }

    async function fetchMessages(conversationId) {
        try {
            const response = await axios.get(
                `/api/chat/conversations/${conversationId}/messages`
            );
            messages.value = response.data;
        } catch (error) {
            console.error("Error fetching messages:", error);
            throw error;
        }
    }

    async function fetchUsers() {
        try {
            const response = await axios.get("/api/chat/users");
            users.value = response.data;
        } catch (error) {
            console.error("Error fetching users:", error);
            throw error;
        }
    }

    async function sendMessage(formData) {
        try {
            const response = await axios.post("/api/chat/messages", formData, {
                headers: {
                    "Content-Type": "multipart/form-data",
                },
                withCredentials: true,
            });

            messages.value.push(response.data);
            return response.data;
        } catch (error) {
            console.error("Error sending message:", error);
            throw error;
        }
    }

    function addMessage(message) {
        console.log("📨 Adding message to store:", message);

        // Check if message already exists to avoid duplicates
        const messageExists = messages.value.some((m) => m.id === message.id);
        if (messageExists) {
            console.log("⚠️ Message already exists in store, skipping");
            return;
        }

        // Add new message to the end of the array (chronological order)
        messages.value = [...messages.value, message];

        // Update conversation's last message and unread count
        const conversation = conversations.value.find(
            (c) => c.id == message.conversation_id
        );

        if (conversation) {
            // Update last message
            conversation.latest_message = message;
            conversation.last_message_at = message.created_at;

            // If message is from another user and we're not in that conversation, increment unread count
            if (
                message.user_id !== useAuthStore.user?.id &&
                message.conversation_id !== currentConversation.value?.id
            ) {
                conversation.unread_messages_count =
                    (conversation.unread_messages_count || 0) + 1;
                console.log(
                    "📈 Incremented unread count for conversation:",
                    conversation.id
                );
            }
        }

        console.log(
            "✅ Message added to store. Total messages:",
            messages.value.length
        );
    }
    function setUserOnline(userId) {
        onlineUsers.value.add(userId);
    }

    function setUserOffline(userId) {
        onlineUsers.value.delete(userId);
    }

    function setUserTyping(conversationId, userId, isTyping) {
        const key = `${conversationId}-${userId}`;

        if (isTyping) {
            typingUsers.value.set(key, true);

            // Auto-clear typing indicator after 3 seconds
            setTimeout(() => {
                typingUsers.value.delete(key);
            }, 3000);
        } else {
            typingUsers.value.delete(key);
        }
    }

    function getTypingUsers(conversationId) {
        const users = [];
        for (let [key, isTyping] of typingUsers.value) {
            if (isTyping && key.startsWith(`${conversationId}-`)) {
                const userId = key.split("-")[1];
                const user =
                    users.value.find((u) => u.id == userId) ||
                    currentConversation.value?.participants?.find(
                        (p) => p.id == userId
                    );
                if (user) {
                    users.push(user);
                }
            }
        }
        return users;
    }

    async function searchConversations(query) {
        try {
            console.log("Searching conversations with query:", query);

            const response = await axios.get("/api/chat/search/conversations", {
                params: { query: query },
                withCredentials: true,
            });

            console.log("Search results:", response.data);
            return response.data;
        } catch (error) {
            console.error("Error searching conversations:", {
                message: error.message,
                response: error.response?.data,
                status: error.response?.status,
            });
            throw error;
        }
    }

    async function searchMessages(query, conversationId = null) {
        try {
            const params = { query: query };
            if (conversationId) {
                params.conversation_id = conversationId;
            }

            console.log("Searching messages with params:", params);

            const response = await axios.get("/api/chat/search/messages", {
                params: params,
                withCredentials: true,
            });

            console.log("Message search results:", response.data);
            return response.data;
        } catch (error) {
            console.error("Error searching messages:", {
                message: error.message,
                response: error.response?.data,
                status: error.response?.status,
            });
            throw error;
        }
    }

    function updateUnreadCount(conversationId, count) {
        const conversation = conversations.value.find(
            (c) => c.id == conversationId
        );
        if (conversation) {
            conversation.unread_messages_count = count;
        }
    }
    // Video call methods
    function setIncomingCall(callData) {
        incomingCall.value = callData;
    }

    function clearIncomingCall() {
        incomingCall.value = null;
    }

    function setVideoCall(callData) {
        videoCall.value = callData;
    }

    function setCallActive(active) {
        isCallActive.value = active;
    }

    function setInCall(inCall) {
        isInCall.value = inCall;
    }

    function setLocalStream(stream) {
        localStream.value = stream;
    }

    function setRemoteStream(stream) {
        remoteStream.value = stream;
    }

    function setPeerConnection(pc) {
        peerConnection.value = pc;
    }
    function isUserInCall() {
        return (
            isInCall.value || isCallActive.value || incomingCall.value !== null
        );
    }

    // Conversation utilities
    function getConversationName(conversation) {
        const authStore = useAuthStore();
        if (!authStore.user) return "Loading...";

        if (conversation.name) return conversation.name;
        if (conversation.participants) {
            const otherParticipants = conversation.participants.filter(
                (p) => p.id !== authStore.user.id
            );
            return otherParticipants.map((p) => p.name).join(", ");
        }
        return "Unknown Conversation";
    }

    function getConversationInitials(conversation) {
        const name = getConversationName(conversation);
        return name
            .split(" ")
            .map((part) => part.charAt(0))
            .join("")
            .toUpperCase()
            .substring(0, 2);
    }

    function getOnlineParticipants(conversation) {
        const authStore = useAuthStore();
        if (!conversation.participants) return [];
        return conversation.participants.filter(
            (p) => p.id !== authStore.user.id && p.is_online
        );
    }

    function getLastMessagePreview(conversation) {
        if (!conversation.latest_message) return "No messages yet";

        if (conversation.latest_message.attachments) {
            const attachmentCount =
                conversation.latest_message.attachments.length;
            return `${attachmentCount} attachment${
                attachmentCount > 1 ? "s" : ""
            }`;
        }

        return conversation.latest_message.body || "📎 Attachment";
    }

    function getUnreadCount(conversation) {
        return conversation.unread_messages_count || 0;
    }

    function handleNewConversation(conversationData) {
        if (!conversationData) return;

        console.log(
            "🆕 Store: Handling new conversation:",
            conversationData.id
        );

        const existingIndex = conversations.value.findIndex(
            (conv) => conv.id == conversationData.id
        );

        if (existingIndex === -1) {
            // Add new conversation to store with proper reactivity
            conversations.value = [
                {
                    ...conversationData,
                    unread_messages_count: 0,
                },
                ...conversations.value,
            ];
            console.log("✅ Store: New conversation added");
        } else {
            console.log("⚠️ Store: Conversation already exists");
        }
    }

    function handleConversationRestored(conversationData) {
        if (!conversationData) return;

        console.log("🔄 Store: Handling conversation restoration:", {
            id: conversationData.id,
            name: conversationData.name,
            participants: conversationData.participants?.length || 0,
        });

        // First check if conversation already exists (to avoid duplicates)
        const existingIndex = conversations.value.findIndex(
            (c) => c.id == conversationData.id
        );

        if (existingIndex !== -1) {
            console.log("🔄 Conversation already in store, updating...");

            // Update existing conversation with fresh data
            const updatedConversations = [...conversations.value];

            // Merge the restored conversation data with existing data
            updatedConversations[existingIndex] = {
                ...updatedConversations[existingIndex],
                ...conversationData,
                // Keep the unread count from existing or set to 1
                unread_messages_count: Math.max(
                    updatedConversations[existingIndex].unread_messages_count ||
                        0,
                    1
                ),
                // Ensure participants are loaded
                participants:
                    conversationData.participants ||
                    updatedConversations[existingIndex].participants,
            };

            conversations.value = updatedConversations;
            console.log("✅ Store: Existing conversation updated");

            // Move to top since it has a new message
            moveConversationToTop(conversationData.id);
        } else {
            console.log("🆕 Adding restored conversation to store...");

            // Add new conversation with initial unread count
            const restoredConversation = {
                ...conversationData,
                unread_messages_count: 1, // Since we just received a message that triggered restoration
            };

            // Add to beginning of array and ensure reactivity
            conversations.value = [
                restoredConversation,
                ...conversations.value,
            ];
            console.log("✅ Store: Restored conversation added to sidebar");
        }

        // Force a UI update
        conversations.value = [...conversations.value];
    }

    function handleIncomingMessage(message) {
        if (!message || !message.conversation_id) {
            console.log("⚠️ Invalid message received:", message);
            return;
        }

        console.log("🔄 Store: Processing incoming message:", {
            messageId: message.id,
            conversationId: message.conversation_id,
            fromUser: message.user_id,
            toUser: authStore.user?.id,
        });

        // Check if conversation exists in store
        const existingConversation = conversations.value.find(
            (conv) => conv.id == message.conversation_id
        );

        if (!existingConversation) {
            // Conversation doesn't exist in store - it might have been deleted and restored
            console.log(
                "🆕 Message from deleted/restored conversation, fetching..."
            );
            return;
        }

        // Add message to store first (with reactivity)
        const messageExists = messages.value.some((m) => m.id === message.id);
        if (!messageExists) {
            // Add to the end (chronological order)
            messages.value = [...messages.value, message];

            // Update the conversation in store if it exists
            if (existingConversation) {
                // Update last message and move to top
                existingConversation.latest_message = message;
                existingConversation.last_message_at = message.created_at;

                // If message is from another user, increment unread count
                if (message.user_id !== authStore.user?.id) {
                    existingConversation.unread_messages_count =
                        (existingConversation.unread_messages_count || 0) + 1;
                    console.log(
                        "📈 Incremented unread count for restored conversation:",
                        message.conversation_id
                    );
                }

                // Move conversation to top
                moveConversationToTop(message.conversation_id);
            }

            // If this is the current conversation, scroll to bottom
            if (currentConversation.value?.id == message.conversation_id) {
                // Trigger scroll to bottom after a short delay
                setTimeout(() => {
                    const event = new CustomEvent("scroll-to-bottom");
                    window.dispatchEvent(event);
                }, 100);
            }
        }
    }
    // Update moveConversationToTop to use proper reactivity
    function moveConversationToTop(conversationId) {
        const index = conversations.value.findIndex(
            (c) => c.id == conversationId
        );
        if (index > 0) {
            const updatedConversations = [...conversations.value];
            const [conversation] = updatedConversations.splice(index, 1);
            updatedConversations.unshift(conversation);
            conversations.value = updatedConversations;
        }
    }
    async function fetchNewConversation(conversationId) {
        try {
            console.log("🔄 Fetching conversation details:", conversationId);

            // First check if it's already in the store (to avoid duplicates)
            const alreadyExists = conversations.value.find(
                (c) => c.id == conversationId
            );
            if (alreadyExists) {
                console.log(
                    "⚠️ Conversation already exists in store, skipping"
                );
                return;
            }

            // Fetch conversation details
            const response = await axios.get(
                `/api/chat/conversations/${conversationId}`
            );
            const conversation = response.data;

            if (conversation) {
                console.log("✅ Conversation fetched:", conversation);

                // Add the conversation to the store with proper reactivity
                conversations.value = [
                    {
                        ...conversation,
                        unread_messages_count: 1, // Since we just received a message
                    },
                    ...conversations.value,
                ];

                console.log("✅ Restored conversation added to sidebar");
            }
        } catch (error) {
            console.error("❌ Error fetching conversation:", error);
        }
    }
    function updateMessage(updatedMessage) {
        const index = messages.value.findIndex(
            (m) => m.id === updatedMessage.id
        );
        if (index !== -1) {
            messages.value[index] = {
                ...messages.value[index],
                ...updatedMessage,
                edited_at:
                    updatedMessage.edited_at || messages.value[index].edited_at,
            };

            // Force reactivity
            messages.value = [...messages.value];
        }

        // Also update in conversations if needed
        const conversation = conversations.value.find(
            (c) => c.id === updatedMessage.conversation_id
        );
        if (
            conversation &&
            conversation.latest_message?.id === updatedMessage.id
        ) {
            conversation.latest_message = updatedMessage;
        }
    }

    function deleteMessage(messageData) {
        const message = messages.value.find((m) => m.id === messageData.id);
        if (message) {
            // Mark as deleted instead of removing to maintain order
            message.deleted_at = messageData.deleted_at;
            message.body = null;
            message.attachments = null;
            message.attachments_urls = [];

            // Force reactivity
            messages.value = [...messages.value];
        }
    }

    function addReactionToMessage(messageId, reaction) {
        const message = messages.value.find((m) => m.id === messageId);
        if (message) {
            // Initialize reactions array if it doesn't exist
            if (!message.reactions) {
                message.reactions = [];
            }

            // Add or update reaction
            const existingIndex = message.reactions.findIndex(
                (r) => r.id === reaction.id
            );

            if (existingIndex === -1) {
                message.reactions.push(reaction);
            } else {
                message.reactions[existingIndex] = reaction;
            }

            // Update reactions summary
            updateMessageReactionsSummary(message);

            // Force reactivity
            messages.value = [...messages.value];
        }
    }

    function removeReactionFromMessage(messageId, userId, emoji) {
        const message = messages.value.find((m) => m.id === messageId);
        if (message && message.reactions) {
            // Remove the reaction
            message.reactions = message.reactions.filter(
                (r) => !(r.user_id === userId && r.emoji === emoji)
            );

            // Update reactions summary
            updateMessageReactionsSummary(message);

            // Force reactivity
            messages.value = [...messages.value];
        }
    }

    function updateMessageReactionsSummary(message) {
        if (!message.reactions || message.reactions.length === 0) {
            message.reactions_summary = [];
            return;
        }

        // Group reactions by emoji
        const grouped = message.reactions.reduce((acc, reaction) => {
            if (!acc[reaction.emoji]) {
                acc[reaction.emoji] = {
                    emoji: reaction.emoji,
                    count: 0,
                    user_ids: [],
                };
            }
            acc[reaction.emoji].count++;
            acc[reaction.emoji].user_ids.push(reaction.user_id);
            return acc;
        }, {});

        message.reactions_summary = Object.values(grouped);
    }

    return {
        conversations,
        currentConversation,
        messages,
        users,
        onlineUsers,
        typingUsers,
        unreadCount,
        fetchConversations,
        fetchConversation,
        fetchMessages,
        fetchUsers,
        sendMessage,
        createConversation,
        addMessage,
        setUserOnline,
        setUserOffline,
        setUserTyping,
        getTypingUsers,
        searchConversations,
        searchMessages,
        updateUnreadCount,
        deleteMessage,
        updateMessage,
        videoCall,
        localStream,
        remoteStream,
        peerConnection,
        isInCall,
        isCallActive,
        incomingCall,
        setIncomingCall,
        clearIncomingCall,
        setVideoCall,
        setCallActive,
        setInCall,
        setLocalStream,
        setRemoteStream,
        setPeerConnection,
        isUserInCall,
        getConversationName,
        getConversationInitials,
        getOnlineParticipants,
        getLastMessagePreview,
        getUnreadCount,
        handleIncomingMessage,
        moveConversationToTop,
        fetchNewConversation,
        deleteConversation,
        restoreConversation,
        handleConversationDeleted,
        handleConversationRestored,
        handleNewConversation,
        forceUpdate,
        addReactionToMessage,
        removeReactionFromMessage,
    };
});
