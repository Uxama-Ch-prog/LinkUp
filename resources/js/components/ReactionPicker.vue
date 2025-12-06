<!-- resources/js/components/ReactionPicker.vue -->
<template>
    <div
        class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 bg-white rounded-xl shadow-xl border border-gray-300 p-3 z-50"
    >
        <!-- Pointer arrow -->
        <div
            class="absolute top-full left-1/2 transform -translate-x-1/2 -mt-1"
        >
            <div
                class="w-3 h-3 bg-white border-r border-b border-gray-300 transform rotate-45"
            ></div>
        </div>

        <div class="grid grid-cols-4 gap-2 w-[15vw]">
            <button
                v-for="emoji in commonEmojis"
                :key="emoji"
                @click="selectEmoji(emoji)"
                class="w-10 h-10 rounded-xl hover:bg-gray-100 flex items-center justify-center text-xl transition-all duration-200 hover:scale-125 active:scale-95"
                :title="getEmojiName(emoji)"
            >
                {{ emoji }}
            </button>
        </div>
    </div>
</template>

<script setup>
const emit = defineEmits(["selected"]);

const commonEmojis = ["👍", "❤️", "😂", "😮", "😢", "🙏", "🔥", "👏"];

const emojiNames = {
    "👍": "Thumbs Up",
    "❤️": "Red Heart",
    "😂": "Tears of Joy",
    "😮": "Surprised",
    "😢": "Crying",
    "🙏": "Praying",
    "🔥": "Fire",
    "👏": "Clapping",
};

function selectEmoji(emoji) {
    emit("selected", emoji);
}

function getEmojiName(emoji) {
    return emojiNames[emoji] || emoji;
}
</script>

<style scoped>
button {
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

button:hover {
    transform: scale(1.25);
}

button:active {
    transform: scale(0.95);
}
</style>
