// resources/js/router/index.js
import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "../stores/auth";

const routes = [
    {
        path: "/login",
        name: "login",
        component: () => import("../pages/Auth/Login.vue"),
        meta: { requiresGuest: true },
    },
    {
        path: "/register",
        name: "register",
        component: () => import("../pages/Auth/Register.vue"),
        meta: { requiresGuest: true },
    },
    {
        path: "/",
        name: "dashboard",
        component: () => import("../pages/Chat/Dashboard.vue"),
        meta: { requiresAuth: true },
    },
    {
        path: "/chat/:conversationId?",
        name: "chat",
        component: () => import("../pages/Chat/Conversation.vue"),
        meta: { requiresAuth: true },
        props: true,
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// Enhanced navigation guard
router.beforeEach(async (to, from, next) => {
    // Check if we're in the middle of logout
    if (window.isLoggingOut && to.path !== "/login") {
        console.log("Redirecting to login during logout process");
        next("/login");
        return;
    }
    const authStore = useAuthStore();

    // If auth hasn't been initialized yet, wait for it
    if (!authStore.initialized) {
        console.log("Auth not initialized, waiting...");
        try {
            await authStore.initializeAuth();
        } catch (error) {
            console.error("Error initializing auth:", error);
        }
    }

    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        next({ name: "login" });
    } else if (to.meta.requiresGuest && authStore.isAuthenticated) {
        next({ name: "dashboard" });
    } else {
        next();
    }
});

export default router;
