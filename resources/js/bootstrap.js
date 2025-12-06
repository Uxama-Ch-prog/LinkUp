// resources/js/bootstrap.js
import axios from "axios";
import Echo from "laravel-echo";
import Pusher from "pusher-js";

// Configure axios
window.axios = axios;
window.Pusher = Pusher;

window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
window.axios.defaults.withCredentials = true;
window.axios.defaults.baseURL = "http://localhost:8000";

// CSRF token
const csrfToken = document.querySelector('meta[name="csrf-token"]');
if (csrfToken) {
    window.axios.defaults.headers.common["X-CSRF-TOKEN"] = csrfToken.content;
}

// Get token from localStorage
const token = localStorage.getItem("token");

// Initialize Laravel Echo with Reverb
window.Echo = new Echo({
    broadcaster: "reverb",
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    wssPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? "https") === "https",
    enabledTransports: ["ws", "wss"],
    authEndpoint: "/api/broadcasting/auth",
    auth: {
        headers: {
            Accept: "application/json",
            Authorization: token ? `Bearer ${token}` : "",
        },
    },
    // Add these options for better debugging
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || "mt1",
    disableStats: true,
    encrypted: true,
});

console.log(
    "✅ Laravel Echo with Reverb initialized with token:",
    token ? "Yes" : "No"
);
