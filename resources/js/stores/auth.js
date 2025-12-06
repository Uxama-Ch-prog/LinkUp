// resources/js/stores/auth.js
import { defineStore } from "pinia";
import { ref, computed } from "vue";
import axios from "axios";
import router from "../router";

export const useAuthStore = defineStore("auth", () => {
    const user = ref(null);
    const token = ref(localStorage.getItem("token"));
    const loading = ref(false);
    const error = ref(null);
    const initialized = ref(false); // Track if auth has been initialized

    const isAuthenticated = computed(() => !!token.value && !!user.value);
    const currentUser = computed(() => user.value);

    // Helper functions
    // Set token when logging in
    function setToken(newToken) {
        token.value = newToken;
        localStorage.setItem("token", newToken);
        window.axios.defaults.headers.common[
            "Authorization"
        ] = `Bearer ${newToken}`;
    }

    // Clear token when logging out
    function clearToken() {
        token.value = null;
        localStorage.removeItem("token");
        delete window.axios.defaults.headers.common["Authorization"];
    }
    function setAuth(authToken, userData) {
        token.value = authToken;
        user.value = userData;
        localStorage.setItem("token", authToken);
        axios.defaults.headers.common["Authorization"] = `Bearer ${authToken}`;
    }

    function clearAuth() {
        user.value = null;
        token.value = null;
        localStorage.removeItem("token");
        delete axios.defaults.headers.common["Authorization"];
    }

    async function login(credentials) {
        loading.value = true;
        error.value = null;

        try {
            console.log("Starting login process...");

            // Get CSRF cookie first (important for Sanctum)
            await axios.get("/sanctum/csrf-cookie", {
                withCredentials: true,
            });

            // Login request
            const response = await axios.post("/api/login", credentials, {
                withCredentials: true,
            });

            console.log("Login response:", response.data);

            if (!response.data.token) {
                throw new Error("No token received from server");
            }

            // Set the token and user
            setAuth(response.data.token, response.data.user);
            console.log("Auth set successfully, navigating to dashboard...");

            router.push("/");
            return response;
        } catch (err) {
            console.error("Login error details:", {
                message: err.message,
                response: err.response?.data,
                status: err.response?.status,
            });

            clearAuth();
            error.value =
                err.response?.data?.message || err.message || "Login failed";
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function register(userData) {
        loading.value = true;
        error.value = null;

        try {
            // Get CSRF cookie first
            await axios.get("/sanctum/csrf-cookie", {
                withCredentials: true,
            });

            const response = await axios.post("/api/register", userData, {
                withCredentials: true,
            });

            setAuth(response.data.token, response.data.user);
            router.push("/");
            return response;
        } catch (err) {
            clearAuth();
            error.value = err.response?.data?.message || "Registration failed";
            console.error(
                "Registration error:",
                err.response?.data || err.message
            );
            throw err;
        } finally {
            loading.value = false;
        }
    }
    async function logout() {
        // Set a flag to indicate logout is in progress
        window.isLoggingOut = true;

        try {
            if (token.value) {
                console.log("Making logout API call...");

                // Use a separate axios instance without the interceptor for logout
                const logoutAxios = axios.create({
                    baseURL: window.axios.defaults.baseURL,
                    withCredentials: true,
                    timeout: 5000,
                });

                await logoutAxios.post(
                    "/api/logout",
                    {},
                    {
                        headers: {
                            Authorization: `Bearer ${token.value}`,
                        },
                    }
                );
                console.log("Logout API call successful");
            }
        } catch (err) {
            // Only log if it's NOT the cancellation error
            if (err.message !== "Request cancelled during logout") {
                console.error("Logout endpoint error:", err);
            } else {
                console.log(
                    "Logout request cancelled (expected during logout)"
                );
            }
        } finally {
            console.log("Clearing local auth state...");
            clearAuth();
            console.log("Navigating to login...");
            router.push("/login");

            // Clear the logout flag after a short delay
            setTimeout(() => {
                window.isLoggingOut = false;
            }, 1000);
        }
    }
    async function fetchUser() {
        try {
            console.log("Fetching user with token:", token.value);
            const response = await axios.get("/api/user", {
                withCredentials: true,
            });
            console.log("Fetched user:", response.data);
            user.value = response.data;
            error.value = null;
            return response.data;
        } catch (err) {
            console.error(
                "fetchUser error:",
                err.response?.data || err.message
            );
            // If token is invalid, clear auth
            if (err.response?.status === 401) {
                clearAuth();
                throw err;
            }
            error.value = err.response?.data?.message || "Failed to fetch user";
            throw err;
        }
    }

    // Initialize auth state when app starts - UPDATED
    async function initializeAuth() {
        if (initialized.value) {
            console.log("Auth already initialized");
            return;
        }

        if (token.value) {
            console.log("Initializing auth with stored token...");
            // Set the authorization header immediately
            axios.defaults.headers.common[
                "Authorization"
            ] = `Bearer ${token.value}`;

            try {
                // Verify the token is still valid by fetching user
                await fetchUser();
                console.log("Auth initialized successfully");

                // If we're on login page, redirect to dashboard
                if (
                    router.currentRoute.value.path === "/login" ||
                    router.currentRoute.value.path === "/register"
                ) {
                    router.push("/");
                }
            } catch (error) {
                console.error("Token is invalid, clearing auth...");
                clearAuth();
                // If we're not on auth pages, redirect to login
                if (
                    router.currentRoute.value.path !== "/login" &&
                    router.currentRoute.value.path !== "/register"
                ) {
                    router.push("/login");
                }
            }
        } else {
            console.log("No token found, user is not logged in");
            // If we're on a protected route, redirect to login
            if (router.currentRoute.value.meta.requiresAuth) {
                router.push("/login");
            }
        }

        initialized.value = true;
    }

    return {
        user,
        token,
        loading,
        error,
        initialized,
        isAuthenticated,
        currentUser,
        login,
        register,
        logout,
        fetchUser,
        initializeAuth,
        clearAuth,
        setAuth,
        setToken,
        clearToken,
    };
});
