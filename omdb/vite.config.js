import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
  server: {
    host: "0.0.0.0",
    port: 8173,
    hmr: {
      host: process.env.VITE_HOST,
    },
  },

  plugins: [
    laravel({
      input: ["resources/scss/app.scss", "resources/js/app.js"],
      refresh: true,
    }),
  ],
});
