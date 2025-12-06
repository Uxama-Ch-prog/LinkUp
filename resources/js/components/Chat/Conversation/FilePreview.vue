<!-- resources/js/components/Chat/Conversation/FilePreview.vue -->
<template>
    <div class="mb-3 p-3 bg-gray-50 rounded-lg">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-700">
                Attachments ({{ attachments.length }})
            </span>
            <button
                @click="$emit('clear')"
                class="text-red-500 hover:text-red-700 text-sm transition-colors"
            >
                Clear all
            </button>
        </div>
        <div class="space-y-2">
            <div
                v-for="(file, index) in attachments"
                :key="index"
                class="flex items-center justify-between p-2 bg-white rounded border hover:border-blue-300 transition-colors"
            >
                <div class="flex items-center space-x-3">
                    <div
                        class="w-8 h-8 bg-blue-100 rounded flex items-center justify-center"
                    >
                        <span class="text-blue-600 text-xs">
                            {{ getFileIcon(file.type) }}
                        </span>
                    </div>
                    <div>
                        <p
                            class="text-sm font-medium text-gray-900 truncate max-w-xs"
                        >
                            {{ file.name }}
                        </p>
                        <p class="text-xs text-gray-500">
                            {{ formatFileSize(file.size) }}
                        </p>
                    </div>
                </div>
                <button
                    @click="$emit('remove', index)"
                    class="text-red-400 hover:text-red-600 transition-colors"
                >
                    ×
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
