<!-- resources/js/components/Chat/Conversation/MessageInput.vue -->
<template>
    <div class="bg-white border-t border-gray-200 p-4">
        <!-- File preview area -->
        <FilePreview
            v-if="attachments.length > 0"
            :attachments="attachments"
            @clear="$emit('clear-attachments')"
            @remove="$emit('remove-attachment', $event)"
        />

        <!-- Error message -->
        <ErrorMessage
            v-if="error"
            :message="error"
            @dismiss="$emit('update:error', '')"
        />

        <!-- Message input form -->
        <form @submit.prevent="$emit('send')" class="flex space-x-3">
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
import { computed, ref } from "vue";
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
    "update:attachments", // ADD THIS LINE
    "update:error", // ADD THIS LINE
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
    const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
    const MAX_FILES = 5;
    const newAttachments = [...props.attachments];
    let errorMessage = "";

    for (const file of files) {
        // Check file size
        if (file.size > MAX_FILE_SIZE) {
            errorMessage = `File "${file.name}" is too large (max 10MB)`;
            emit("update:error", errorMessage);
            continue;
        }

        // Check max files limit
        if (newAttachments.length >= MAX_FILES) {
            errorMessage = `Maximum ${MAX_FILES} files allowed`;
            emit("update:error", errorMessage);
            break;
        }

        // Check for duplicate files by name and size
        const isDuplicate = newAttachments.some(
            (attachment) =>
                attachment.name === file.name && attachment.size === file.size
        );

        if (!isDuplicate) {
            // Create a file object with metadata
            const fileObject = {
                file: file, // The actual File object
                name: file.name,
                size: file.size,
                type: file.type,
                previewUrl: file.type.startsWith("image/")
                    ? URL.createObjectURL(file)
                    : null,
                id: Date.now() + Math.random(), // Unique ID for each file
            };

            newAttachments.push(fileObject);
        }
    }

    // Update attachments if we added any
    if (newAttachments.length > props.attachments.length) {
        emit("update:attachments", newAttachments);

        // Clear any previous error if files were successfully added
        if (errorMessage === "") {
            emit("update:error", "");
        }
    }
}
</script>
