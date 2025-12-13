<!-- resources/js/components/Chat/Header/UserActions/NotificationBell.vue -->
<template>
    <div class="relative">
        <button
            class="relative p-2 text-gray-600 text-gray-900 bg-gray-100 transition-colors duration-200 rounded-xl group"
            @click="toggleNotifications"
        >
            <!-- Standard Bell Icon -->
            <svg
                class="w-5 h-5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                />
            </svg>

            <!-- Notification Badge -->
            <div
                v-if="chatStore.unreadCount > 0"
                class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 rounded-full border-2 border-white flex items-center justify-center animate-pulse"
            >
                <span class="text-[10px] font-bold text-white">
                    {{ notificationCount }}
                </span>
            </div>
        </button>

        <!-- Notifications Dropdown (optional - you can implement this later) -->
        <!-- <div v-if="showNotifications" class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl border border-gray-200 z-50">
            Notifications content here
        </div> -->
    </div>
</template>

<script setup>
import { computed, ref } from "vue";
import { useChatStore } from "../../../../stores/chat";

const chatStore = useChatStore();
const showNotifications = ref(false);

const notificationCount = computed(() => {
    const count = chatStore.unreadCount;
    return count > 99 ? "99+" : count.toString();
});

function toggleNotifications() {
    showNotifications.value = !showNotifications.value;
    // Here you could fetch notifications or mark them as read
    console.log("Toggle notifications");
}
</script>
