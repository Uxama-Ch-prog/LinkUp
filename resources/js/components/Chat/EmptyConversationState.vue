<!-- resources/js/components/Chat/EmptyConversationState.vue -->
<template>
    <div class="flex-1 flex items-center justify-center bg-gray-50">
        <div class="text-center">
            <div
                class="w-24 h-24 mx-auto mb-6 bg-white rounded-full flex items-center justify-center shadow-sm border border-gray-200"
            >
                <svg
                    class="w-12 h-12 text-gray-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                    />
                </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">
                Select a conversation
            </h3>
            <p class="text-gray-500 max-w-sm mx-auto">
                Choose a conversation from the sidebar or start a new one to
                begin messaging.
            </p>
        </div>
    </div>
</template>

<script setup>
// No props or logic needed for this simple component
</script>
