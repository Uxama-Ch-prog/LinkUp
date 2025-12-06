import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        tailwindcss(),
    ],
    server: {
        host: "127.0.0.1",
        port: 5173,
        hmr: {
            host: "127.0.0.1",
        },
    },
    resolve: {
        alias: {
            vue: "vue/dist/vue.esm-bundler.js",
        },
    },
    define: {
        "process.env": {
            VITE_REVERB_APP_KEY: JSON.stringify(
                process.env.VITE_REVERB_APP_KEY
            ),
            VITE_REVERB_HOST: JSON.stringify(process.env.VITE_REVERB_HOST),
            VITE_REVERB_PORT: JSON.stringify(process.env.VITE_REVERB_PORT),
            VITE_REVERB_SCHEME: JSON.stringify(process.env.VITE_REVERB_SCHEME),
        },
    },
});
