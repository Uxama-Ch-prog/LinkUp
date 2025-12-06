<!-- resources/js/components/Chat/Conversation/MessageEditForm.vue -->
<template>
    <div class="mb-2">
        <textarea
            :value="modelValue"
            @input="$emit('update:modelValue', $event.target.value)"
            @keydown.enter.prevent="$event.ctrlKey && $emit('save')"
            @keydown.escape="$emit('cancel')"
            class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 text-sm edit-textarea"
            rows="3"
            placeholder="Edit your message..."
            ref="textareaRef"
        ></textarea>
        <div class="flex space-x-2 mt-2">
            <button
                type="button"
                @click="$emit('save')"
                :disabled="!modelValue.trim()"
                class="px-3 py-1 bg-green-600 text-white text-sm rounded-md hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
                Save
            </button>
            <button
                type="button"
                @click="$emit('cancel')"
                class="px-3 py-1 bg-gray-500 text-white text-sm rounded-md hover:bg-gray-600 transition-colors"
            >
                Cancel
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from "vue";

const props = defineProps({
    modelValue: {
        type: String,
        default: "",
    },
});

const emit = defineEmits(["update:modelValue", "save", "cancel"]);

const textareaRef = ref(null);

onMounted(async () => {
    await nextTick();
    if (textareaRef.value) {
        textareaRef.value.focus();
        textareaRef.value.setSelectionRange(
            textareaRef.value.value.length,
            textareaRef.value.value.length
        );
    }
});
</script>
