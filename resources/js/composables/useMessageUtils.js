// resources/js/composables/useMessageUtils.js
import { useAuthStore } from "../stores/auth";

export function useMessageUtils() {
    const authStore = useAuthStore();

    const getInitials = (name) => {
        return (
            name
                ?.split(" ")
                .map((part) => part.charAt(0))
                .join("")
                .toUpperCase()
                .substring(0, 2) || "U"
        );
    };

    const formatMessageTime = (dateString) => {
        if (!dateString || dateString === "Invalid Date") {
            return "";
        }

        try {
            const date = new Date(dateString);
            if (isNaN(date.getTime())) {
                return "";
            }
            return date.toLocaleTimeString("en-US", {
                hour: "numeric",
                minute: "2-digit",
                hour12: true,
            });
        } catch (error) {
            console.error("❌ Error formatting message time:", error);
            return "";
        }
    };

    const formatMessageDate = (dateString) => {
        if (!dateString || dateString === "Invalid Date") {
            return "";
        }

        try {
            const date = new Date(dateString);
            if (isNaN(date.getTime())) {
                return "";
            }

            const today = new Date();
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);

            const todayDate = new Date(
                today.getFullYear(),
                today.getMonth(),
                today.getDate()
            );
            const yesterdayDate = new Date(
                yesterday.getFullYear(),
                yesterday.getMonth(),
                yesterday.getDate()
            );
            const messageDate = new Date(
                date.getFullYear(),
                date.getMonth(),
                date.getDate()
            );

            if (messageDate.getTime() === todayDate.getTime()) {
                return "Today";
            } else if (messageDate.getTime() === yesterdayDate.getTime()) {
                return "Yesterday";
            } else {
                return date.toLocaleDateString("en-US", {
                    weekday: "long",
                    year: "numeric",
                    month: "long",
                    day: "numeric",
                });
            }
        } catch (error) {
            console.error("❌ Error formatting message date:", error);
            return "";
        }
    };
    const formatRelativeTime = (dateString) => {
        if (!dateString) return "";

        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now - date;
        const diffSec = Math.floor(diffMs / 1000);
        const diffMin = Math.floor(diffSec / 60);
        const diffHour = Math.floor(diffMin / 60);

        if (diffSec < 60) return "just now";
        if (diffMin < 60) return `${diffMin}m ago`;
        if (diffHour < 24) return `${diffHour}h ago`;

        return formatMessageTime(dateString);
    };
    const shouldShowDateSeparator = (message, index, allMessages) => {
        if (!allMessages || !Array.isArray(allMessages)) {
            console.warn("⚠️ allMessages is not defined or not an array");
            return false;
        }

        if (index === 0) return true;

        try {
            const currentDate = new Date(message.created_at);
            const prevDate = new Date(allMessages[index - 1].created_at);

            if (isNaN(currentDate.getTime()) || isNaN(prevDate.getTime())) {
                return false;
            }

            const currentDateOnly = new Date(
                currentDate.getFullYear(),
                currentDate.getMonth(),
                currentDate.getDate()
            );
            const prevDateOnly = new Date(
                prevDate.getFullYear(),
                prevDate.getMonth(),
                prevDate.getDate()
            );

            return currentDateOnly.getTime() !== prevDateOnly.getTime();
        } catch (error) {
            console.error("❌ Error checking date separator:", error);
            return false;
        }
    };

    const getFileIcon = (mimeType) => {
        if (!mimeType) return "📄";

        if (mimeType.startsWith("image/")) return "🖼️";
        if (mimeType.includes("pdf")) return "📕";
        if (mimeType.includes("word") || mimeType.includes("document"))
            return "📄";
        if (mimeType.includes("excel") || mimeType.includes("spreadsheet"))
            return "📊";
        if (mimeType.includes("zip") || mimeType.includes("compressed"))
            return "🗜️";
        if (mimeType.includes("audio/")) return "🎵";
        if (mimeType.includes("video/")) return "🎬";

        return "📄";
    };

    const formatFileSize = (bytes) => {
        if (bytes === 0) return "0 Bytes";
        const k = 1024;
        const sizes = ["Bytes", "KB", "MB", "GB"];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
    };
    const isImage = (mimeType) => {
        return mimeType && mimeType.startsWith("image/");
    };

    return {
        getInitials,
        formatMessageTime,
        formatMessageDate,
        shouldShowDateSeparator,
        getFileIcon,
        formatFileSize,
        isImage,
        formatRelativeTime,
    };
}
