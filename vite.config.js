import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/scss/theme.scss",
                "resources/assets/css/styles.css",
                "resources/assets/css/icons.css",
                "resources/assets/libs/simplebar/simplebar.min.css",
                "resources/assets/libs/node-waves/waves.min.css",
                "resources/assets/libs/flatpickr/flatpickr.min.css",
                "resources/assets/libs/@simonwep/pickr/themes/nano.min.css",
                "resources/assets/libs/@tarekraafat/autocomplete.js/css/autoComplete.css",

                "resources/js/bootstrap.js",
                "resources/js/app.js",
                "resources/js/auth.js",
                "resources/js/dashboard.js",
                "resources/js/reports.js",
                "resources/js/theme.js",
                "resources/assets/js/main.js",
                "resources/assets/js/custom.js",
                "resources/assets/js/defaultmenu.min.js",
                "resources/assets/js/custom-switcher.min.js",
                "resources/assets/js/simplebar.js",
                "resources/assets/js/sticky.js",
                "resources/assets/js/sales-dashboard.js",
                "resources/assets/libs/simplebar/simplebar.min.js",
                "resources/assets/libs/node-waves/waves.min.js",
                "resources/assets/libs/flatpickr/flatpickr.min.js",
                "resources/assets/libs/apexcharts/apexcharts.min.js",
                "resources/assets/libs/@tarekraafat/autocomplete.js/autoComplete.min.js",
            ],
            refresh: true,
        }),
    ],
    build: {
        sourcemap: false,
    },
});
