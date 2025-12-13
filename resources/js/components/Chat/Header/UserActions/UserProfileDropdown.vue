<!-- resources/js/components/Chat/Header/UserActions/UserProfileDropdown.vue -->
<template>
    <div class="relative group">
        <button
            class="flex items-center space-x-1 bg-gray-100 hover:bg-gray-200 rounded-full p-1.5 transition-all duration-200 border border-gray-300 hover:border-gray-400"
        >
            <!-- User Avatar Only -->
            <div class="relative">
                <div
                    class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white text-sm font-bold shadow-md"
                >
                    {{ getUserInitials }}
                </div>
                <!-- Online Status -->
                <div
                    class="absolute -bottom-1 -right-1 w-3 h-3 bg-green-400 rounded-full border-2 border-white shadow-sm"
                ></div>
            </div>
        </button>

        <!-- Dropdown Menu -->
        <div
            class="absolute right-0 top-full mt-2 w-64 bg-white/95 backdrop-blur-lg rounded-xl shadow-2xl border border-gray-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 transform translate-y-2 group-hover:translate-y-0 z-50"
        >
            <!-- User Summary -->
            <div class="p-4 border-b border-gray-100/50">
                <div class="flex items-center space-x-3">
                    <div
                        class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-lg"
                    >
                        {{ getUserInitials }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-gray-900 font-semibold truncate">
                            {{ authStore.user?.name || "User" }}
                        </p>
                        <p class="text-gray-500 text-sm truncate">
                            {{ authStore.user?.email || "" }}
                        </p>
                        <div class="flex items-center space-x-1 mt-1">
                            <div
                                class="w-2 h-2 bg-green-500 rounded-full"
                            ></div>
                            <span class="text-green-600 text-xs font-medium"
                                >Online</span
                            >
                        </div>
                    </div>
                </div>
            </div>

            <!-- Menu Items -->
            <div class="p-2">
                <button
                    class="w-full flex items-center space-x-3 px-3 py-2 text-gray-700 hover:bg-gray-100/80 rounded-lg transition-colors duration-150"
                >
                    <svg
                        class="w-5 h-5 text-gray-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                        />
                    </svg>
                    <span class="text-sm font-medium">My Profile</span>
                </button>

                <button
                    class="w-full flex items-center space-x-3 px-3 py-2 text-gray-700 hover:bg-gray-100/80 rounded-lg transition-colors duration-150"
                >
                    <svg
                        class="w-5 h-5 text-gray-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                        />
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                        />
                    </svg>
                    <span class="text-sm font-medium">Settings</span>
                </button>

                <button
                    class="w-full flex items-center space-x-3 px-3 py-2 text-gray-700 hover:bg-gray-100/80 rounded-lg transition-colors duration-150"
                >
                    <svg
                        class="w-5 h-5 text-gray-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"
                        />
                    </svg>
                    <span class="text-sm font-medium">Appearance</span>
                </button>
            </div>

            <!-- Logout Section -->
            <div class="p-2 border-t border-gray-100/50">
                <button
                    @click="handleLogout"
                    class="w-full flex items-center space-x-3 px-3 py-2 text-red-600 hover:bg-red-50/80 rounded-lg transition-colors duration-150 group"
                >
                    <svg
                        class="w-5 h-5 text-red-500 group-hover:scale-110 transition-transform"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                        />
                    </svg>
                    <span class="text-sm font-medium">Log out</span>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "../../../../stores/auth";
import { useConversationUtils } from "../../../../composables/useConversationUtils";

const router = useRouter();
const authStore = useAuthStore();
const { getUserInitials } = useConversationUtils();
const isLoggingOut = ref(false);

async function handleLogout() {
    if (isLoggingOut.value) return;

    isLoggingOut.value = true;

    try {
        // Leave Echo channels
        if (window.Echo) {
            window.Echo.leave(`user.${authStore.user?.id}`);
        }

        console.log("Starting logout process...");
        await authStore.logout();
        console.log("Logout completed successfully");
    } catch (error) {
        console.error("Error during logout:", error);
        authStore.clearAuth();
        router.push("/login");
    } finally {
        isLoggingOut.value = false;
    }
}
</script>
