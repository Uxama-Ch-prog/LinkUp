<!-- resources/js/components/Chat/Header/SearchSection.vue -->
<template>
    <div class="hidden md:flex items-center">
        <button
            @click="emit('search')"
            class="group flex items-center space-x-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-xl transition-all duration-200 hover:border-gray-400"
        >
            <svg
                class="w-4 h-4 text-gray-600 group-hover:text-gray-800"
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
            <span
                class="text-gray-600 group-hover:text-gray-800 text-sm font-medium"
                >Search messages...</span
            >
        </button>
    </div>
</template>

<script setup>
const emit = defineEmits(["search"]);
</script>
