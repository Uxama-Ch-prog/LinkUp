<template>
    <div
        ref="containerRef"
        class="flex-1 overflow-y-auto p-6 bg-gray-50 space-y-3 messages-container"
        @scroll="handleScroll"
    >
        <!-- Load more indicator -->
        <div v-if="isLoadingMore" class="flex justify-center items-center py-4">
            <div
                class="w-6 h-6 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"
            ></div>
        </div>

        <div
            v-if="chatStore.messages.length === 0 && !isLoadingMore"
            class="text-center text-gray-500 mt-8"
        >
            <div class="text-lg">No messages yet</div>
            <div class="text-sm">
                Start the conversation by sending a message!
            </div>
        </div>

        <div v-else-if="chatStore.messages.length > 0">
            <!-- Group messages by date -->
            <div
                v-for="(message, index) in chatStore.messages"
                :key="message.id"
                class="message-item"
            >
                <!-- Date separator -->
                <div
                    v-if="
                        shouldShowDateSeparator(
                            message,
                            index,
                            chatStore.messages
                        )
                    "
                    class="text-center my-6"
                >
                    <span
                        class="bg-gray-200 text-gray-600 text-xs px-3 py-1 rounded-full inline-block"
                    >
                        {{
                            formatMessageDate(message.created_at) ||
                            "Unknown Date"
                        }}
                    </span>
                </div>

                <!-- Message bubble -->
                <MessageBubble :message="message" :index="index" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onUpdated, nextTick, watch } from "vue";
import { useChatStore } from "../../../stores";
import { useMessageUtils } from "../../../composables/useMessageUtils";
import MessageBubble from "./MessageBubble.vue";

const props = defineProps({
    isLoadingMore: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["scroll", "load-more"]);

const chatStore = useChatStore();
const { formatMessageDate, shouldShowDateSeparator } = useMessageUtils();

// Add this ref for the container element
const containerRef = ref(null);

// Add a flag to prevent auto-scroll from triggering load more
const isAutoScrolling = ref(false);

function handleScroll(event) {
    emit("scroll", event);

    // Don't load more if we're auto-scrolling
    if (isAutoScrolling.value) {
        isAutoScrolling.value = false;
        return;
    }

    // Load more messages if scrolled to top
    const container = event.target;
    const scrollPosition = container.scrollTop;
    const containerHeight = container.clientHeight;
    const scrollHeight = container.scrollHeight;

    // Check if user is near the top (within 100px)
    if (scrollPosition < 100 && !props.isLoadingMore) {
        emit("load-more");
    }
}

// Add these missing functions that are being exposed
function scrollToBottom() {
    if (containerRef.value) {
        isAutoScrolling.value = true;
        nextTick(() => {
            containerRef.value.scrollTop = containerRef.value.scrollHeight;
            // Reset the flag after a short delay
            setTimeout(() => {
                isAutoScrolling.value = false;
            }, 100);
        });
    }
}

function scrollToMessage(messageId) {
    if (containerRef.value) {
        isAutoScrolling.value = true;
        nextTick(() => {
            // Find the message element
            const messageElement = containerRef.value.querySelector(
                `[data-message-id="${messageId}"]`
            );
            if (messageElement) {
                messageElement.scrollIntoView({ behavior: "smooth" });
            }
            // Reset the flag after a short delay
            setTimeout(() => {
                isAutoScrolling.value = false;
            }, 100);
        });
    }
}

// Watch for new messages and scroll to bottom if user is near bottom
let lastMessageCount = 0;
onUpdated(() => {
    if (!containerRef.value || chatStore.messages.length === 0) return;

    // Only auto-scroll if new messages were added (not during initial load or pagination)
    if (chatStore.messages.length > lastMessageCount) {
        const container = containerRef.value;
        const isNearBottom =
            container.scrollHeight -
                container.scrollTop -
                container.clientHeight <
            100;

        // If user is near bottom or it's the first load, scroll to bottom
        if (isNearBottom || lastMessageCount === 0) {
            scrollToBottom();
        }

        lastMessageCount = chatStore.messages.length;
    }
});

// Expose the methods to parent component
defineExpose({
    scrollToBottom,
    scrollToMessage,
});
</script>

<style scoped>
.messages-container {
    scroll-behavior: smooth;
}

.messages-container::-webkit-scrollbar {
    width: 6px;
}

.messages-container::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.messages-container::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.messages-container::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

.message-item {
    animation: fadeInUp 0.3s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
