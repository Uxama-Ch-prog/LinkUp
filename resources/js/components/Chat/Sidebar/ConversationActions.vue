<!-- resources/js/components/Chat/Sidebar/ConversationActions.vue -->
<template>
    <div class="relative group">
        <!-- More options button -->
        <button
            @click="showDropdown = !showDropdown"
            class="p-1 text-gray-400 hover:text-gray-600 rounded opacity-0 group-hover:opacity-100 transition-all"
            @blur="closeDropdown"
        >
            <svg
                class="w-4 h-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"
                />
            </svg>
        </button>

        <!-- Dropdown menu -->
        <div
            v-if="showDropdown"
            class="absolute right-0 top-full mt-1 w-48 bg-white rounded-md shadow-lg border border-gray-200 z-50"
        >
            <div class="py-1">
                <!-- Delete conversation -->
                <button
                    @click="deleteConversation"
                    class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center space-x-2"
                >
                    <svg
                        class="w-4 h-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                        />
                    </svg>
                    <span>Delete Chat</span>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from "vue";
import { useChatStore } from "../../../stores";

const props = defineProps({
    conversation: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(["deleted"]);

const chatStore = useChatStore();
const showDropdown = ref(false);

function closeDropdown() {
    setTimeout(() => {
        showDropdown.value = false;
    }, 200);
}

async function deleteConversation() {
    if (
        !confirm(
            "Are you sure you want to delete this conversation? This will only remove it from your chat list."
        )
    ) {
        return;
    }

    try {
        await chatStore.deleteConversation(props.conversation.id);
        showDropdown.value = false;
        emit("deleted", props.conversation.id);
    } catch (error) {
        console.error("Error deleting conversation:", error);
        alert("Failed to delete conversation. Please try again.");
    }
}
</script>
