<!-- resources/js/components/Chat/Conversation/MessageBubble.vue -->
<template>
    <div
        :data-message-id="message.id"
        :class="[
            'flex mb-3 group relative',
            message.user_id === authStore.user.id
                ? 'justify-end'
                : 'justify-start',
        ]"
    >
        <div
            class="flex max-w-xs lg:max-w-md"
            :class="[
                message.user_id === authStore.user.id
                    ? 'flex-row-reverse'
                    : 'flex-row',
            ]"
        >
            <!-- Avatar -->
            <div class="flex-shrink-0 mx-2">
                <div
                    :title="message.user?.name"
                    class="w-8 h-8 bg-indigo-500 rounded-full flex items-center justify-center text-white text-sm font-medium cursor-help hover:ring-2 hover:ring-indigo-300 transition-all"
                >
                    {{ getInitials(message.user?.name || "U") }}
                </div>
            </div>

            <!-- Message Content -->
            <div
                :class="[
                    'px-4 py-2 rounded-2xl relative group',
                    message.user_id === authStore.user.id
                        ? 'bg-blue-600 text-white rounded-br-none'
                        : 'bg-white text-gray-900 border border-gray-200 rounded-bl-none',
                    message.deleted_at ? 'opacity-60 italic' : '',
                ]"
            >
                <!-- Edit mode -->
                <MessageEditForm
                    v-if="editingMessageId === message.id"
                    v-model="editMessageText"
                    @save="saveMessageEdit(message.id)"
                    @cancel="cancelEdit"
                />

                <!-- Normal message content -->
                <div v-else>
                    <!-- Deleted message -->
                    <div
                        v-if="message.deleted_at"
                        class="text-gray-500 italic text-sm"
                    >
                        ❌ This message was deleted
                    </div>

                    <div v-else>
                        <!-- Sender name for group chats -->
                        <div
                            v-if="
                                isGroupChat &&
                                message.user_id !== authStore.user.id
                            "
                            class="text-xs font-medium text-blue-600 mb-1"
                        >
                            {{ message.user?.name }}
                        </div>

                        <!-- Message text -->
                        <p v-if="message.body" class="text-sm break-words mb-2">
                            {{ message.body }}
                            <span
                                v-if="message.edited_at"
                                class="text-xs text-gray-400 ml-1"
                                title="Edited"
                            >
                                (edited)
                            </span>
                        </p>

                        <!-- Attachments -->
                        <MessageAttachments
                            v-if="
                                message.attachments_urls?.length > 0 ||
                                message.attachments?.length > 0
                            "
                            :attachments="
                                message.attachments_urls || message.attachments
                            "
                        />

                        <!-- Reactions -->
                        <MessageReactions
                            v-if="message.reactions_summary?.length > 0"
                            :reactions="message.reactions_summary"
                            :message-id="message.id"
                            @reaction-toggled="toggleReaction"
                        />
                    </div>

                    <!-- Timestamp -->
                    <MessageTimestamp
                        :message="message"
                        :is-own-message="message.user_id === authStore.user.id"
                    />
                </div>

                <!-- Message actions menu -->
                <MessageActions
                    v-if="
                        message.user_id === authStore.user?.id &&
                        !message.deleted_at &&
                        !editingMessageId
                    "
                    :message="message"
                    @edit="() => startEditMessage(message)"
                    @delete="() => deleteMessage(message)"
                    @react="() => showReactionPicker(message.id)"
                />
                <!-- Reaction picker -->
                <ReactionPicker
                    v-if="showPickerFor === message.id"
                    @selected="(emoji) => addReaction(message.id, emoji)"
                    @close="closeReactionPicker"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, nextTick, computed } from "vue"; // FIX: Added computed import
import { useAuthStore, useChatStore } from "../../../stores";
import { useMessageUtils } from "../../../composables/useMessageUtils";
import { useMessageActions } from "../../../composables/useMessageActions";
import MessageEditForm from "./MessageEditForm.vue";
import MessageAttachments from "./MessageAttachments.vue";
import MessageReactions from "./MessageReactions.vue";
import MessageTimestamp from "./MessageTimestamp.vue";
import MessageActions from "./MessageActions.vue";
import ReactionPicker from "../../ReactionPicker.vue";

const props = defineProps({
    message: {
        type: Object,
        required: true,
    },
    index: {
        type: Number,
        required: true,
    },
});

const authStore = useAuthStore();
const chatStore = useChatStore();
const { getInitials } = useMessageUtils();
const {
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
} = useMessageActions();

// FIX: Added computed property
const isGroupChat = computed(() => {
    return chatStore.currentConversation?.is_group || false;
});
</script>
