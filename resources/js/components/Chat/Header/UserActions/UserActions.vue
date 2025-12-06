<!-- resources/js/components/Chat/Header/UserActions.vue -->
<template>
    <div class="flex items-center space-x-4">
        <!-- Notifications -->
        <NotificationBell />

        <!-- User Profile -->
        <UserProfileDropdown />
    </div>
</template>

<script setup>
import NotificationBell from "./NotificationBell.vue";
import UserProfileDropdown from "./UserProfileDropdown.vue";
</script>
