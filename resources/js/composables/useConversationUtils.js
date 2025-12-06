// resources/js/composables/useConversationUtils.js
import { computed } from "vue";
import { useAuthStore } from "../stores/auth";

export function useConversationUtils() {
    const authStore = useAuthStore();

    const formatRelativeTime = (dateString) => {
        if (!dateString || dateString === "Invalid Date") return "";

        try {
            const date = new Date(dateString);
            if (isNaN(date.getTime())) return "";

            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / (1000 * 60));
            const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
            const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

            if (diffMins < 1) return "now";
            if (diffMins < 60) return `${diffMins}m`;
            if (diffHours < 24) return `${diffHours}h`;
            if (diffDays < 7) return `${diffDays}d`;

            return date.toLocaleDateString("en-US", {
                month: "short",
                day: "numeric",
            });
        } catch (error) {
            console.error("Error formatting relative time:", error);
            return "";
        }
    };

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

    const getLastSeenText = (lastSeenAt) => {
        if (!lastSeenAt) return "Offline";

        const lastSeen = new Date(lastSeenAt);
        const now = new Date();
        const diffMinutes = Math.floor((now - lastSeen) / (1000 * 60));

        if (diffMinutes < 1) return "Just now";
        if (diffMinutes < 60) return `${diffMinutes}m ago`;
        if (diffMinutes < 1440) return `${Math.floor(diffMinutes / 60)}h ago`;
        return `${Math.floor(diffMinutes / 1440)}d ago`;
    };

    const getUserInitials = computed(() => {
        const name = authStore.user?.name || "User";
        return getInitials(name);
    });

    return {
        formatRelativeTime,
        getInitials,
        getLastSeenText,
        getUserInitials,
    };
}
