<!-- resources/js/components/Chat/Header.vue -->
<template>
    <header class="bg-white shadow-sm border-b border-gray-200">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo Section -->
                <LogoSection />

                <!-- Right Section: Search + User Actions -->
                <div class="flex items-center space-x-4">
                    <!-- Search Section -->
                    <SearchSection @search="emit('search')" />

                    <!-- User Actions -->
                    <UserActions />
                </div>
            </div>
        </div>

        <!-- Mobile Search -->
        <div class="md:hidden px-4 pb-4">
            <button
                @click="emit('search')"
                class="w-full flex items-center justify-center space-x-2 px-4 py-3 bg-gray-100 hover:bg-gray-200 rounded-xl transition-all duration-200 border border-gray-300"
            >
                <svg
                    class="w-4 h-4 text-gray-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                    />
                </svg>
                <span class="text-gray-600 text-sm font-medium"
                    >Search messages...</span
                >
            </button>
        </div>
    </header>
</template>

<script setup>
import LogoSection from "./Header/LogoSection.vue";
import SearchSection from "./Header/SearchSection.vue";
import UserActions from "./Header/UserActions/UserActions.vue";

const emit = defineEmits(["search"]);
</script>
