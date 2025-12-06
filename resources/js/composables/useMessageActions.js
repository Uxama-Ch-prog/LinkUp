import { ref } from "vue";
import { useChatStore, useAuthStore } from "../stores";
import axios from "axios";

export function useMessageActions() {
    const chatStore = useChatStore();
    const authStore = useAuthStore();

    const editingMessageId = ref(null);
    const editMessageText = ref("");
    const showPickerFor = ref(null);

    const startEditMessage = (message) => {
        editingMessageId.value = message.id;
        editMessageText.value = message.body || "";
    };

    const cancelEdit = () => {
        editingMessageId.value = null;
        editMessageText.value = "";
    };

    const saveMessageEdit = async (messageId) => {
        if (!editMessageText.value.trim()) return;

        try {
            const response = await axios.put(
                `/api/chat/messages/${messageId}`,
                {
                    body: editMessageText.value,
                }
            );

            chatStore.updateMessage(response.data.message);
            editingMessageId.value = null;
            editMessageText.value = "";
        } catch (error) {
            console.error("❌ Error editing message:", error);
            alert(error.response?.data?.error || "Failed to edit message");
        }
    };

    const deleteMessage = async (message) => {
        if (!message || !message.id) {
            console.error("❌ Cannot delete: Invalid message object", message);
            return;
        }

        if (!confirm("Are you sure you want to delete this message?")) {
            return;
        }

        try {
            console.log("🗑️ Deleting message:", message.id);
            const response = await axios.delete(
                `/api/chat/messages/${message.id}`
            );

            // The store will be updated via WebSocket event
            // But we can also update locally for immediate feedback
            chatStore.deleteMessage({
                id: message.id,
                conversation_id: message.conversation_id,
                deleted_at: new Date().toISOString(),
            });

            console.log("✅ Message deleted successfully:", response.data);
        } catch (error) {
            console.error("❌ Error deleting message:", error);
            const errorMessage =
                error.response?.data?.error ||
                error.response?.data?.message ||
                "Failed to delete message";
            alert(errorMessage);
        }
    };
    const showReactionPicker = (messageId) => {
        showPickerFor.value = messageId;
    };

    const closeReactionPicker = () => {
        showPickerFor.value = null;
    };

    const addReaction = async (messageId, emoji) => {
        try {
            const response = await axios.post(
                `/api/chat/messages/${messageId}/reactions`,
                { emoji }
            );

            if (response.data.action === "added") {
                chatStore.addReactionToMessage(
                    messageId,
                    response.data.reaction
                );
            }

            showPickerFor.value = null;
        } catch (error) {
            console.error("❌ Error adding reaction:", error);
            alert("Failed to add reaction");
        }
    };

    const toggleReaction = async (messageId, emoji) => {
        try {
            const response = await axios.post(
                `/api/chat/messages/${messageId}/reactions`,
                { emoji }
            );

            if (response.data.action === "added") {
                chatStore.addReactionToMessage(
                    messageId,
                    response.data.reaction
                );
            } else if (response.data.action === "removed") {
                chatStore.removeReactionFromMessage(
                    messageId,
                    authStore.user.id,
                    emoji
                );
            }
        } catch (error) {
            console.error("❌ Error toggling reaction:", error);
        }
    };

    return {
        editingMessageId,
        editMessageText,
        showPickerFor,
        startEditMessage,
        cancelEdit,
        saveMessageEdit,
        deleteMessage,
        showReactionPicker,
        closeReactionPicker,
        addReaction,
        toggleReaction,
    };
}
