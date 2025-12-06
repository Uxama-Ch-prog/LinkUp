<!-- resources/js/components/Chat/Conversation/FileUploadButton.vue -->
<template>
    <div class="relative">
        <input
            type="file"
            ref="fileInput"
            multiple
            @change="handleFileSelect"
            class="hidden"
            accept="image/*,.pdf,.doc,.docx,.txt,.xlsx"
        />
        <button
            type="button"
            @click="$refs.fileInput?.click()"
            class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-all"
            title="Attach file"
        >
            <svg
                class="w-6 h-6"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"
                />
            </svg>
        </button>
    </div>
</template>

<script setup>
import { ref } from "vue";

const fileInput = ref(null);

const emit = defineEmits(["files-selected"]);

function handleFileSelect(event) {
    const files = Array.from(event.target.files || []);
    if (files.length > 0) {
        emit("files-selected", files);
    }
    event.target.value = "";
}
</script>
