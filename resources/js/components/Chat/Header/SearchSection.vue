<!-- resources/js/components/Chat/Header/SearchSection.vue -->
<template>
    <div class="hidden md:flex items-center space-x-6">
        <button
            @click="emit('search')"
            class="group flex items-center space-x-2 px-4 py-2 bg-white/10 hover:bg-white/20 backdrop-blur-sm rounded-xl transition-all duration-200 border border-white/20 hover:border-white/30"
        >
            <svg
                class="w-4 h-4 text-white/80 group-hover:text-white"
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
                class="text-white/80 group-hover:text-white text-sm font-medium"
                >Search messages...</span
            >
            <kbd class="text-xs bg-white/20 text-white/60 px-1.5 py-0.5 rounded"
                >⌘K</kbd
            >
        </button>
    </div>
</template>

<script setup>
const emit = defineEmits(["search"]);
</script>
