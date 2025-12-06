<!-- resources/js/components/Chat/Header/UserActions/NotificationBell.vue -->
<template>
    <div class="relative">
        <button
            class="relative p-2 text-white/80 hover:text-white transition-colors duration-200 hover:bg-white/10 rounded-xl group"
        >
            <svg
                class="w-5 h-5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 17h5l-5 5v-5zM10.24 8.56a5.97 5.97 0 01-3.77-5.19 1 1 0 00-1.94-.48 7.97 7.97 0 005.03 6.92 1 1 0 001.68-.25z"
                />
            </svg>
            <!-- Notification Badge -->
            <div
                v-if="chatStore.unreadCount > 0"
                class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full border-2 border-indigo-600 animate-pulse"
            ></div>
        </button>
    </div>
</template>

<script setup>
import { useChatStore } from "../../../../stores/chat";

const chatStore = useChatStore();
</script>
