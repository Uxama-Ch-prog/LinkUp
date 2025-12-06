// resources/js/app.js
import { createApp } from "vue";
import { createPinia } from "pinia";
import router from "./router";
import "./bootstrap";
import App from "./App.vue";

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);
window.isLoggingOut = false;
// Initialize auth before mounting the app
const initializeApp = async () => {
    try {
        const { useAuthStore } = await import("./stores/auth");
        const authStore = useAuthStore();

        // Wait for auth to initialize before mounting
        await authStore.initializeAuth();

        // Now mount the app
        app.mount("#app");

        console.log("App mounted successfully");
    } catch (error) {
        console.error("Failed to initialize app:", error);
        app.mount("#app"); // Mount anyway even if auth fails
    }
};

initializeApp();
