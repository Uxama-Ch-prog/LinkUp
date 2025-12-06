// resources/js/composables/useConversation.js
import { ref, computed, watch } from "vue";
import { useRoute } from "vue-router";
import { useAuthStore, useChatStore } from "../stores";
import axios from "axios";
import { format, parseISO, isSameDay } from "date-fns";

export function useConversation(props) {
    const route = useRoute();
    const authStore = useAuthStore();
    const chatStore = useChatStore();
    const messagesContainer = ref(null);
    const effectiveConversationId = computed(() => {
        return props.conversationId || route.params.conversationId;
    });

    // State
    const newMessage = ref("");
    const attachments = ref([]);
    const isSending = ref(false);
    const sendError = ref(null);
    const typingUsers = ref([]);
    const isLoadingMore = ref(false);
    const hasMoreMessages = ref(true);
    const page = ref(1);
    const perPage = 50;
    const typingTimeout = ref(null);
    // Load conversation with messages
    const loadConversation = async () => {
        if (!effectiveConversationId.value) return;

        try {
            // Load conversation details
            await chatStore.fetchConversation(effectiveConversationId.value);

            // Load initial messages
            await loadMessages();

            page.value = 1;
            hasMoreMessages.value = chatStore.messages.length >= perPage;

            console.log(
                `✅ Conversation loaded with ${chatStore.messages.length} messages`
            );
        } catch (error) {
            console.error("Error loading conversation:", error);
            throw error;
        }
    };
    // Load messages with pagination
    const loadMessages = async (loadMore = false) => {
        if (!effectiveConversationId.value) return;

        if (loadMore) {
            page.value++;
        } else {
            page.value = 1; // Reset page for new conversation
        }

        isLoadingMore.value = true;

        try {
            const response = await axios.get(
                `/api/chat/conversations/${effectiveConversationId.value}/messages`,
                {
                    params: {
                        page: page.value,
                        per_page: perPage,
                    },
                }
            );

            const messages = response.data || [];

            if (loadMore) {
                // Append older messages at the beginning (since we're loading more history)
                chatStore.messages = [...messages, ...chatStore.messages];
            } else {
                // Set initial messages - they should already be in chronological order
                chatStore.messages = messages;
            }

            hasMoreMessages.value = messages.length === perPage;

            console.log(
                `📨 Loaded ${messages.length} messages, page ${page.value}`
            );

            // After loading messages, scroll to bottom for new conversation
            if (!loadMore && messagesContainer.value) {
                // Wait for next tick to ensure DOM is updated
                setTimeout(() => {
                    messagesContainer.value.scrollToBottom();
                }, 100);
            }
        } catch (error) {
            console.error("Error loading messages:", error);
            if (loadMore) {
                page.value--; // Revert page on error
            }
            throw error;
        } finally {
            isLoadingMore.value = false;
        }
    };
    // Load more messages (for pagination)
    const loadMoreMessages = async () => {
        if (isLoadingMore.value || !hasMoreMessages.value) return;
        await loadMessages(true);
    };

    // Mark messages as read
    const markMessagesAsRead = async () => {
        if (!effectiveConversationId.value) return;

        try {
            await axios.post(
                `/api/chat/conversations/${effectiveConversationId.value}/read`
            );

            // Update unread count in store
            const conversation = chatStore.conversations.find(
                (c) => c.id == effectiveConversationId.value
            );
            if (conversation) {
                conversation.unread_messages_count = 0;
            }

            // Update read status in local messages
            chatStore.messages.forEach((message) => {
                if (
                    message.user_id !== authStore.user?.id &&
                    !message.read_at
                ) {
                    message.read_at = new Date().toISOString();
                }
            });

            console.log("✅ Messages marked as read");
        } catch (error) {
            console.error("Error marking messages as read:", error);
        }
    };

    // Send message
    const sendMessage = async () => {
        if (!newMessage.value.trim() && attachments.value.length === 0) return;

        isSending.value = true;
        sendError.value = null;

        const formData = new FormData();
        formData.append("conversation_id", effectiveConversationId.value);
        formData.append("body", newMessage.value.trim());

        // Add attachments if any
        if (attachments.value.length > 0) {
            attachments.value.forEach((file) => {
                formData.append("attachments[]", file);
            });
        }

        try {
            const message = await chatStore.sendMessage(formData);
            newMessage.value = "";
            attachments.value = [];

            // Handle typing stopped
            handleTyping(false);

            return message;
        } catch (error) {
            sendError.value =
                error.response?.data?.message || "Failed to send message";
            console.error("Error sending message:", error);
            throw error;
        } finally {
            isSending.value = false;
        }
    };
    const handleTyping = async (isTyping = true) => {
        if (!effectiveConversationId.value) return;

        // Clear existing timeout
        if (typingTimeout.value) {
            clearTimeout(typingTimeout.value);
            typingTimeout.value = null;
        }

        if (isTyping) {
            // Send typing started after delay
            typingTimeout.value = setTimeout(async () => {
                try {
                    await axios.post(
                        `/api/chat/conversations/${effectiveConversationId.value}/typing`,
                        {
                            is_typing: true,
                        }
                    );
                } catch (error) {
                    console.error("Error sending typing indicator:", error);
                } finally {
                    typingTimeout.value = null;
                }
            }, 500);
        } else {
            // Send typing stopped immediately
            try {
                await axios.post(
                    `/api/chat/conversations/${effectiveConversationId.value}/typing`,
                    {
                        is_typing: false,
                    }
                );
            } catch (error) {
                console.error("Error sending typing stopped:", error);
            }
        }
    };
    // Setup WebSocket listeners
    // In setupWebSocketListeners function
    const setupWebSocketListeners = () => {
        if (!window.Echo || !authStore.user?.id) return;

        console.log(
            "🔌 Setting up WebSocket listeners for user:",
            authStore.user.id
        );

        // User-specific events - Use correct channel name
        window.Echo.private(`user.${authStore.user.id}`)
            .listen(".MessageSent", (e) => {
                console.log("💬 New message via WebSocket:", e);
                chatStore.handleIncomingMessage(e.message);
            })
            .listen(".ConversationCreated", (e) => {
                console.log("💬 New conversation created:", e);
                chatStore.handleNewConversation(e.conversation);
            })
            .listen(".ConversationRestored", (e) => {
                console.log("🔄 Conversation restored:", e);
                chatStore.handleConversationRestored(e.conversation);
            });

        // Conversation-specific events - Use correct channel name
        if (effectiveConversationId.value) {
            window.Echo.private(`conversation.${effectiveConversationId.value}`)
                .listen(".MessageUpdated", (e) => {
                    console.log("✏️ Message updated:", e);
                    if (e.action === "updated") {
                        chatStore.updateMessage(e.message);
                    }
                })
                .listen(".MessageDeleted", (e) => {
                    console.log("🗑️ Message deleted:", e);
                    if (e.action === "deleted") {
                        chatStore.deleteMessage({
                            id: e.message_id,
                            conversation_id: e.conversation_id,
                            deleted_at: new Date().toISOString(),
                        });
                    }
                })
                .listen(".ReactionAdded", (e) => {
                    console.log("❤️ Reaction added:", e);
                    chatStore.addReactionToMessage(e.message_id, e.reaction);
                })
                .listen(".ReactionRemoved", (e) => {
                    console.log("💔 Reaction removed:", e);
                    chatStore.removeReactionFromMessage(
                        e.message_id,
                        e.user_id,
                        e.emoji
                    );
                });
        }
    };

    // Also update the watcher:
    watch(effectiveConversationId, (newId, oldId) => {
        if (newId && newId !== oldId) {
            // Re-setup conversation-specific listeners
            if (window.Echo) {
                // Leave old conversation channel
                if (oldId) {
                    window.Echo.leave(`conversation.${oldId}`);
                }

                // Join new conversation channel
                if (newId) {
                    window.Echo.private(`conversation.${newId}`)
                        .listen(".MessageUpdated", (e) => {
                            console.log("✏️ Message updated:", e);
                            chatStore.updateMessage(e.message);
                        })
                        .listen(".MessageDeleted", (e) => {
                            console.log("🗑️ Message deleted:", e);
                            chatStore.deleteMessage(e);
                        })
                        .listen(".ReactionAdded", (e) => {
                            console.log("❤️ Reaction added:", e);
                            chatStore.addReactionToMessage(
                                e.message_id,
                                e.reaction
                            );
                        })
                        .listen(".ReactionRemoved", (e) => {
                            console.log("💔 Reaction removed:", e);
                            chatStore.removeReactionFromMessage(
                                e.message_id,
                                e.user_id,
                                e.emoji
                            );
                        });
                }
            }
        }
    });
    // Handle attachments
    const addAttachment = (file) => {
        // Check file size (max 10MB)
        if (file.size > 10 * 1024 * 1024) {
            sendError.value = "File size too large. Maximum 10MB.";
            return;
        }

        attachments.value.push(file);
    };

    const removeAttachment = (index) => {
        attachments.value.splice(index, 1);
    };

    const clearAttachments = () => {
        attachments.value = [];
    };
    // Format message date
    const formatMessageDate = (dateString) => {
        if (!dateString) return "";
        const date = parseISO(dateString);
        return format(date, "MMMM d, yyyy");
    };

    // Check if should show date separator
    const shouldShowDateSeparator = (message, index, messages) => {
        if (index === 0) return true;

        const currentDate = parseISO(message.created_at);
        const prevDate = parseISO(messages[index - 1].created_at);

        return !isSameDay(currentDate, prevDate);
    };
    const handleScroll = (event) => {
        // Your scroll handling logic here
        const container = messagesContainer.value;
        if (container) {
            // Example: Check if near top to load more messages
            const scrollTop = container.scrollTop;
            if (
                scrollTop < 100 &&
                hasMoreMessages.value &&
                !isLoadingMore.value
            ) {
                loadMoreMessages();
            }
        }
    };

    return {
        effectiveConversationId,
        newMessage,
        attachments,
        isSending,
        sendError,
        typingUsers,
        isLoadingMore,
        hasMoreMessages,
        loadConversation,
        loadMoreMessages,
        markMessagesAsRead,
        sendMessage,
        handleTyping,
        setupWebSocketListeners,
        addAttachment,
        removeAttachment,
        clearAttachments,
        formatMessageDate,
        shouldShowDateSeparator,
        typingTimeout,
        messagesContainer,
        handleScroll,
    };
}
