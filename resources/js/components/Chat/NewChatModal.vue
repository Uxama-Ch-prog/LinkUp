<!-- resources/js/components/Chat/NewChatModal.vue -->
<template>
    <div
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
    >
        <div class="bg-white rounded-lg max-w-md w-full p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
                Start New Conversation
            </h3>

            <!-- User List -->
            <div class="max-h-60 overflow-y-auto mb-4">
                <div
                    v-if="chatStore.users.length === 0"
                    class="text-center text-gray-500 py-4"
                >
                    Loading users...
                </div>
                <div v-else>
                    <div
                        v-for="user in chatStore.users"
                        :key="user.id"
                        @click="toggleUser(user)"
                        :class="[
                            'p-3 border rounded-md mb-2 cursor-pointer hover:bg-gray-50',
                            selectedUsers.has(user.id)
                                ? 'bg-blue-50 border-blue-200'
                                : 'border-gray-200',
                        ]"
                    >
                        <div class="flex items-center">
                            <div class="flex-shrink-0 mr-3">
                                <div class="relative">
                                    <div
                                        class="w-10 h-10 bg-indigo-500 rounded-full flex items-center justify-center text-white text-sm font-medium"
                                    >
                                        {{ getInitials(user.name) }}
                                    </div>
                                    <!-- Online status indicator -->
                                    <div
                                        :class="[
                                            'absolute -bottom-1 -right-1 w-3 h-3 rounded-full border-2 border-white',
                                            user.is_online
                                                ? 'bg-green-500'
                                                : 'bg-gray-300',
                                        ]"
                                    ></div>
                                </div>
                            </div>
                            <div class="flex-1">
                                <p class="font-medium text-gray-900">
                                    {{ user.name }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    {{ user.email }}
                                </p>
                            </div>
                            <div class="text-right">
                                <span class="text-xs text-gray-400 block">
                                    {{
                                        user.is_online
                                            ? "Online"
                                            : getLastSeenText(user.last_seen_at)
                                    }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Group Name Input (if multiple users selected) -->
            <div v-if="selectedUsers.size > 1" class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2"
                    >Group Name</label
                >
                <input
                    v-model="groupName"
                    type="text"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Enter group name"
                />
            </div>

            <!-- Actions -->
            <div class="flex justify-end space-x-3">
                <button
                    @click="closeModal"
                    class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900"
                >
                    Cancel
                </button>
                <button
                    @click="createConversation"
                    :disabled="selectedUsers.size === 0 || !authStore.user"
                    class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 disabled:opacity-50"
                >
                    Start Chat
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useAuthStore, useChatStore } from "../../stores";
import { useConversationUtils } from "../../composables/useConversationUtils";

const authStore = useAuthStore();
const chatStore = useChatStore();
const { getInitials, getLastSeenText } = useConversationUtils();

const selectedUsers = ref(new Set());
const groupName = ref("");

const emit = defineEmits(["close", "create"]);

onMounted(async () => {
    if (chatStore.users.length === 0) {
        await chatStore.fetchUsers();
    }
});

function toggleUser(user) {
    if (selectedUsers.value.has(user.id)) {
        selectedUsers.value.delete(user.id);
    } else {
        selectedUsers.value.add(user.id);
    }
}

function closeModal() {
    selectedUsers.value.clear();
    groupName.value = "";
    emit("close");
}

function createConversation() {
    if (selectedUsers.value.size === 0 || !authStore.user) return;

    emit("create", Array.from(selectedUsers.value), groupName.value);
    closeModal();
}
</script>
