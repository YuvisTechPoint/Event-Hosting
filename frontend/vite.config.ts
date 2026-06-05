import {defineConfig} from "vite";
import {lingui} from "@lingui/vite-plugin";
import react from "@vitejs/plugin-react";
import {copy} from "vite-plugin-copy";
import {existsSync, readFileSync} from "fs";
import {resolve} from "path";

function getVersion(): string {
    const candidates = [
        resolve(__dirname, "../VERSION"),
        resolve(__dirname, "../../VERSION"),
        "/app/VERSION",
    ];
    for (const path of candidates) {
        if (existsSync(path)) {
            return readFileSync(path, "utf-8").trim();
        }
    }
    return "unknown";
}

export default defineConfig(({isSsrBuild}) => ({
    optimizeDeps: {
        include: ["react-router"]
    },
    build: {
        rollupOptions: {
            output: {
                // manualChunks with react breaks SSR builds (react is external there)
                manualChunks: isSsrBuild ? undefined : {
                    vendor: ["react", "react-dom", "react-router", "react-router-dom"],
                    mantine: ["@mantine/core", "@mantine/hooks", "@mantine/form", "@mantine/notifications"],
                    query: ["@tanstack/react-query"],
                    charts: ["recharts", "@mantine/charts"],
                },
            },
        },
        chunkSizeWarningLimit: 600,
    },
    server: {
        port: 5678,
        strictPort: true,
        proxy: {
            "/api": {
                target: "http://localhost:1234",
                changeOrigin: true,
                secure: false,
                rewrite: (path) => path.replace(/^\/api/, ""),
            },
        },
        hmr: {
            port: 24678,
            protocol: "ws",
        },
    },
    plugins: [
        react({
            babel: {
                plugins: ["macros"],
            },
        }),
        lingui(),
        copy({
            targets: [{src: "src/embed/widget.js", dest: "public"}],
            hook: "writeBundle",
        }),
    ],
    define: {
        "process.env": process.env,
        "__APP_VERSION__": JSON.stringify(getVersion()),
    },
    ssr: {
        noExternal: ["react-helmet-async"],
    },
    css: {
        preprocessorOptions: {
            scss: {
                api: "modern-compiler",
            }
        }
    }
}));
