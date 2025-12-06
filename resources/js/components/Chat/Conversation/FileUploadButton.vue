<!-- resources/js/components/Chat/Conversation/FileUploadButton.vue -->
<template>
    <button
        type="button"
        @click="openFilePicker"
        class="text-gray-600 hover:text-blue-600 transition-colors flex items-center justify-center w-10 h-10"
        title="Attach files"
    >
        <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-6 w-6"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"
            />
        </svg>
        <input
            type="file"
            ref="fileInput"
            @change="handleFileChange"
            multiple
            class="hidden"
            accept="image/*,.pdf,.doc,.docx,.txt,.zip"
        />
    </button>
</template>

<script setup>
import { ref } from "vue";

const emit = defineEmits(["files-selected"]);
const fileInput = ref(null);

function openFilePicker() {
    fileInput.value.click();
}

function handleFileChange(event) {
    const files = Array.from(event.target.files);

    if (files.length > 0) {
        emit("files-selected", files);
    }

    // Reset the input so the same file can be selected again
    event.target.value = "";
}
</script>
