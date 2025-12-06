<!-- resources/js/components/Chat/Conversation/MessageInput.vue -->
<template>
    <div class="bg-white border-t border-gray-200 p-4">
        <!-- File preview area -->
        <FilePreview
            v-if="attachments.length > 0"
            :attachments="attachments"
            @clear="emit('clear-attachments')"
            @remove="emit('remove-attachment', $event)"
        />

        <!-- Error message -->
        <ErrorMessage v-if="error" :message="error" @dismiss="error = ''" />

        <!-- Message input form -->
        <form @submit.prevent="emit('send')" class="flex space-x-3">
            <!-- File upload button -->
            <FileUploadButton @files-selected="handleFileSelect" />

            <!-- Message input -->
            <input
                :value="modelValue"
                @input="updateModelValue($event.target.value)"
                @keydown="handleKeydown"
                type="text"
                placeholder="Type a message..."
                class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
            />

            <!-- Send button -->
            <SendButton
                :disabled="!canSendMessage || isSending"
                :is-sending="isSending"
            />
        </form>
    </div>
</template>

<script setup>
import { computed } from "vue";
import { useMessageUtils } from "../../../composables/useMessageUtils";
import FilePreview from "./FilePreview.vue";
import ErrorMessage from "./ErrorMessage.vue";
import FileUploadButton from "./FileUploadButton.vue";
import SendButton from "./SendButton.vue";

const props = defineProps({
    modelValue: {
        type: String,
        default: "",
    },
    attachments: {
        type: Array,
        default: () => [],
    },
    isSending: {
        type: Boolean,
        default: false,
    },
    error: {
        type: String,
        default: "",
    },
});

const emit = defineEmits([
    "update:modelValue",
    "send",
    "typing",
    "clear-attachments",
    "remove-attachment",
]);

const { getFileIcon, formatFileSize } = useMessageUtils();

const canSendMessage = computed(() => {
    return (
        (props.modelValue.trim() || props.attachments.length > 0) &&
        !props.isSending
    );
});

function updateModelValue(value) {
    emit("update:modelValue", value);
    emit("typing");
}

function handleKeydown(event) {
    if (event.key === "Enter" && !event.shiftKey) {
        event.preventDefault();
        if (canSendMessage.value) {
            emit("send");
        }
    }
}

function handleFileSelect(files) {
    // Handle file selection logic here
    const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
    const MAX_FILES = 5;

    for (const file of files) {
        if (file.size > MAX_FILE_SIZE) {
            emit("update:error", `File "${file.name}" is too large (max 10MB)`);
            continue;
        }

        if (props.attachments.length >= MAX_FILES) {
            emit("update:error", `Maximum ${MAX_FILES} files allowed`);
            break;
        }

        // Add to attachments - you'll need to implement this
        // attachments.value.push(file);
    }
}
</script>
