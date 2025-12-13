<!-- resources/js/components/Chat/Sidebar/ConversationActions.vue -->
<template>
    <div class="relative">
        <!-- Chevron down button - Always visible -->
        <button
            @click.stop="showDropdown = !showDropdown"
            :class="[
                'p-1 rounded transition-all duration-200',
                showDropdown
                    ? 'bg-gray-200 text-gray-700 rotate-180'
                    : 'text-gray-400 hover:text-gray-600 hover:bg-gray-100',
            ]"
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
                    d="M19 9l-7 7-7-7"
                />
            </svg>
        </button>

        <!-- Dropdown menu -->
        <Transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
        >
            <div
                v-if="showDropdown"
                class="absolute right-0 top-full mt-1 w-48 bg-white rounded-lg shadow-xl border border-gray-200 z-50"
                @click.stop
            >
                <div class="py-1">
                    <!-- Toggle Favourite -->
                    <button
                        @click="toggleFavourite"
                        class="w-full text-left px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 transition-colors duration-150 group"
                    >
                        <div
                            :class="[
                                'p-1.5 rounded-lg transition-colors',
                                conversation.is_favourite
                                    ? 'bg-yellow-100 group-hover:bg-yellow-200'
                                    : 'bg-gray-100 group-hover:bg-gray-200',
                            ]"
                        >
                            <svg
                                :class="[
                                    'w-4 h-4 transition-colors',
                                    conversation.is_favourite
                                        ? 'text-yellow-600'
                                        : 'text-gray-600',
                                ]"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    v-if="conversation.is_favourite"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"
                                />
                                <path
                                    v-else
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"
                                />
                            </svg>
                        </div>
                        <div>
                            <span class="font-medium">
                                {{
                                    conversation.is_favourite
                                        ? "Remove from Favourites"
                                        : "Add to Favourites"
                                }}
                            </span>
                            <p class="text-xs text-gray-500 mt-0.5">
                                {{
                                    conversation.is_favourite
                                        ? "Remove from favourites list"
                                        : "Add to favourites list"
                                }}
                            </p>
                        </div>
                    </button>

                    <!-- Delete conversation -->
                    <button
                        @click="deleteConversation"
                        class="w-full text-left px-4 py-3 text-sm text-red-600 hover:bg-red-50 flex items-center space-x-3 transition-colors duration-150 group border-t border-gray-100"
                    >
                        <div
                            class="p-1.5 bg-red-100 rounded-lg group-hover:bg-red-200"
                        >
                            <svg
                                class="w-4 h-4 text-red-600"
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
                        </div>
                        <div>
                            <span class="font-medium">Delete Chat</span>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Remove from your list
                            </p>
                        </div>
                    </button>
                </div>
            </div>
        </Transition>
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

const emit = defineEmits(["deleted", "favouriteToggled"]);

const chatStore = useChatStore();
const showDropdown = ref(false);

function closeDropdown() {
    setTimeout(() => {
        showDropdown.value = false;
    }, 200);
}

async function toggleFavourite() {
    try {
        // Toggle the favourite status
        const newFavouriteStatus = !props.conversation.is_favourite;

        // You'll need to implement this API call in your chat store
        await chatStore.toggleConversationFavourite(
            props.conversation.id,
            newFavouriteStatus
        );

        // Emit event to parent if needed
        emit("favouriteToggled", {
            conversationId: props.conversation.id,
            isFavourite: newFavouriteStatus,
        });

        showDropdown.value = false;
    } catch (error) {
        console.error("Error toggling favourite:", error);
        alert("Failed to update favourite status. Please try again.");
    }
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
