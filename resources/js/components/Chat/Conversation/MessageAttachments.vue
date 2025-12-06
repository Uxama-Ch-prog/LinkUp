<!-- resources/js/components/Chat/Conversation/MessageAttachments.vue -->
<template>
    <div class="space-y-2 mb-2">
        <div
            v-for="(attachment, index) in formattedAttachments"
            :key="index"
            class="border rounded-lg p-3 bg-white bg-opacity-50 hover:bg-opacity-75 transition-all"
        >
            <div class="flex items-center space-x-3">
                <!-- File icon -->
                <div
                    class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center"
                >
                    <span class="text-blue-600 text-lg">
                        {{ getFileIcon(attachment.mime_type) }}
                    </span>
                </div>

                <!-- File info -->
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">
                        {{ attachment.name }}
                    </p>
                    <p class="text-xs text-gray-500">
                        {{ formatFileSize(attachment.size) }}
                    </p>
                </div>

                <!-- Download link -->
                <a
                    v-if="attachment.url"
                    :href="attachment.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-blue-600 hover:text-blue-800 text-sm font-medium transition-colors"
                >
                    Download
                </a>
                <span v-else class="text-gray-400 text-sm">
                    Processing...
                </span>
            </div>

            <!-- Image preview (for images) -->
            <div
                v-if="isImage(attachment.mime_type) && attachment.url"
                class="mt-3"
            >
                <img
                    :src="attachment.url"
                    :alt="attachment.name"
                    class="max-w-full max-h-64 rounded-lg object-cover"
                    @click="previewImage(attachment.url)"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from "vue";
import { useMessageUtils } from "../../../composables/useMessageUtils";

const props = defineProps({
    attachments: {
        type: Array,
        default: () => [],
    },
});

const { getFileIcon, formatFileSize, isImage } = useMessageUtils();

// Format attachments to ensure consistent structure
const formattedAttachments = computed(() => {
    if (!props.attachments || props.attachments.length === 0) {
        return [];
    }

    return props.attachments.map((attachment) => {
        // If attachment already has url (from attachments_urls)
        if (attachment.url) {
            return attachment;
        }

        // If attachment is from the raw attachments field
        if (attachment.path) {
            return {
                name: attachment.name || "File",
                url: `/storage/${attachment.path}`, // Construct URL
                mime_type: attachment.mime_type || "application/octet-stream",
                size: attachment.size || 0,
            };
        }

        // Fallback
        return {
            name: "File",
            url: null,
            mime_type: "application/octet-stream",
            size: 0,
        };
    });
});

// Image preview function
const previewImage = (url) => {
    window.open(url, "_blank");
};
</script>

<style scoped>
/* Add some hover effects */
.border {
    border-color: #e5e7eb;
}

.border:hover {
    border-color: #3b82f6;
    box-shadow: 0 2px 4px rgba(59, 130, 246, 0.1);
}
</style>
