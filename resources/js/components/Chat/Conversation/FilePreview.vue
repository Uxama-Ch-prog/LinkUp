<!-- resources/js/components/Chat/Conversation/FilePreview.vue -->
<template>
    <div class="mb-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
        <div class="flex items-center justify-between mb-2">
            <h4 class="text-sm font-medium text-gray-700">
                Attachments ({{ attachments.length }})
            </h4>
            <button
                @click="$emit('clear')"
                type="button"
                class="text-sm text-red-600 hover:text-red-800"
            >
                Clear all
            </button>
        </div>

        <div class="space-y-2">
            <div
                v-for="(attachment, index) in attachments"
                :key="attachment.id || index"
                class="flex items-center justify-between bg-white p-2 rounded border"
            >
                <div class="flex items-center space-x-3">
                    <!-- Preview for images -->
                    <div v-if="attachment.previewUrl" class="w-10 h-10">
                        <img
                            :src="attachment.previewUrl"
                            :alt="attachment.name"
                            class="w-full h-full object-cover rounded"
                        />
                    </div>

                    <!-- Icon for other files -->
                    <div
                        v-else
                        class="w-10 h-10 bg-blue-100 rounded flex items-center justify-center"
                    >
                        <span class="text-blue-600">
                            {{ getFileIcon(attachment.type) }}
                        </span>
                    </div>

                    <div>
                        <p
                            class="text-sm font-medium text-gray-900 truncate max-w-xs"
                        >
                            {{ attachment.name }}
                        </p>
                        <p class="text-xs text-gray-500">
                            {{ formatFileSize(attachment.size) }}
                        </p>
                    </div>
                </div>

                <button
                    @click="$emit('remove', index)"
                    type="button"
                    class="text-red-500 hover:text-red-700"
                    aria-label="Remove attachment"
                >
                    <!-- Simple X icon without complex path -->
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useMessageUtils } from "../../../composables/useMessageUtils";

const props = defineProps({
    attachments: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["clear", "remove"]);

const { getFileIcon, formatFileSize } = useMessageUtils();
</script>
