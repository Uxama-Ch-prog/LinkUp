<!-- resources/js/components/Chat/Header/LogoSection.vue -->
<template>
    <div class="flex items-center space-x-4">
        <div class="flex items-center space-x-3">
            <div class="relative">
                <div
                    class="w-10 h-10 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center shadow-lg"
                >
                    <svg
                        class="w-6 h-6 text-white"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                        />
                    </svg>
                </div>
                <div
                    class="absolute -bottom-1 -right-1 w-3 h-3 bg-green-400 rounded-full border-2 border-indigo-600 shadow-sm"
                ></div>
            </div>
            <div>
                <h1 class="text-xl font-bold text-white">LinkUp</h1>
                <p class="text-indigo-200 text-xs">Real-time Chat</p>
            </div>
        </div>
    </div>
</template>
