import * as bootstrap from "bootstrap";
import * as Popper from "@popperjs/core";
import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";
import Pickr from "@simonwep/pickr";
import Waves from "node-waves";
import SimpleBar from "simplebar";
import $ from "jquery";
import select2 from "select2";
import "select2/dist/css/select2.css";
import defaultmenuUrl from "../assets/js/defaultmenu.min.js?url";
import switcherUrl from "../assets/js/custom-switcher.min.js?url";

const initTooltips = () => {
    const triggers = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    triggers.forEach((el) => {
        new bootstrap.Tooltip(el);
    });
};

const initPopovers = () => {
    const triggers = document.querySelectorAll('[data-bs-toggle="popover"]');
    triggers.forEach((el) => {
        new bootstrap.Popover(el);
    });
};

const initDateRange = () => {
    const input = document.querySelector("#daterange");
    if (!input) {
        return;
    }

    flatpickr(input, {
        mode: "range",
        dateFormat: "F, d Y",
        defaultDate: ["May, 01 2026", "May, 30 2026"],
        disableMobile: true,
    });
};

const initWaves = () => {
    const targets = document.querySelectorAll(".waves-effect");
    if (!targets.length) {
        return;
    }

    Waves.init();
    Waves.attach(".waves-effect");
};

const initSimplebar = () => {
    document.querySelectorAll("[data-simplebar]").forEach((el) => {
        new SimpleBar(el);
    });
};

const initSelect2 = () => {
    globalThis.$ = $;
    globalThis.jQuery = $;
    select2($);

    document.querySelectorAll(".singl-select-2").forEach((el) => {
        const $el = $(el);
        if ($el.hasClass("select2-hidden-accessible")) {
            return;
        }
        $el.select2({ width: "100%" });
    });

    document.querySelectorAll(".multi-select2").forEach((el) => {
        const $el = $(el);
        if ($el.hasClass("select2-hidden-accessible")) {
            return;
        }
        $el.select2({ width: "100%" });
    });
};

const initPickr = () => {
    const container = document.querySelector(".pickr-container-primary");
    if (!container) {
        return;
    }

    const pickr = Pickr.create({
        el: container,
        theme: "nano",
        default: "#735dff",
        components: {
            preview: true,
            opacity: false,
            hue: true,
            interaction: {
                hex: false,
                rgba: true,
                hsva: false,
                input: true,
                clear: false,
                save: false,
            },
        },
    });

    pickr.on("changestop", (source, instance) => {
        const color = instance.getColor().toRGBA();
        const value = `${Math.floor(color[0])}, ${Math.floor(color[1])}, ${Math.floor(color[2])}`;
        document.documentElement.style.setProperty("--primary-rgb", value);
        localStorage.setItem("primaryRGB", value);
    });
};

const initHeaderThemeToggle = () => {
    const toggle = document.querySelector(".layout-setting");
    if (!toggle) {
        return;
    }

    toggle.addEventListener("click", () => {
        const html = document.documentElement;
        const isDark = html.getAttribute("data-theme-mode") === "dark";
        const setChecked = (selector, checked) => {
            const input = document.querySelector(selector);
            if (input) {
                input.checked = checked;
            }
        };

        if (isDark) {
            html.setAttribute("data-theme-mode", "light");
            html.setAttribute("data-header-styles", "light");
            html.setAttribute("data-menu-styles", "light");
            html.removeAttribute("data-bg-theme");
            html.style.removeProperty("--body-bg-rgb");
            html.style.removeProperty("--body-bg-rgb2");
            html.style.removeProperty("--light-rgb");
            html.style.removeProperty("--form-control-bg");
            html.style.removeProperty("--input-border");
            localStorage.removeItem("zynixdarktheme");
            localStorage.removeItem("zynixMenu");
            localStorage.removeItem("zynixHeader");
            localStorage.removeItem("bodylightRGB");
            localStorage.removeItem("bodyBgRGB");
            setChecked("#switcher-light-theme", true);
            setChecked("#switcher-menu-light", true);
            setChecked("#switcher-header-light", true);
        } else {
            html.setAttribute("data-theme-mode", "dark");
            html.setAttribute("data-header-styles", "dark");
            html.setAttribute("data-menu-styles", "dark");
            localStorage.setItem("zynixdarktheme", "true");
            localStorage.setItem("zynixMenu", "dark");
            localStorage.setItem("zynixHeader", "dark");
            localStorage.removeItem("bodylightRGB");
            localStorage.removeItem("bodyBgRGB");
            setChecked("#switcher-dark-theme", true);
            setChecked("#switcher-menu-dark", true);
            setChecked("#switcher-header-dark", true);
        }
    });
};

const initFullscreenToggle = () => {
    const toggle = document.querySelector(".js-fullscreen-toggle");
    if (!toggle) {
        return;
    }

    toggle.addEventListener("click", (event) => {
        event.preventDefault();

        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen?.();
        } else {
            document.exitFullscreen?.();
        }
    });
};

const loadLegacyScript = (src) =>
    new Promise((resolve, reject) => {
        if (document.querySelector(`script[data-legacy-src="${src}"]`)) {
            resolve();
            return;
        }

        const script = document.createElement("script");
        script.src = src;
        script.async = false;
        script.dataset.legacySrc = src;
        script.onload = () => resolve();
        script.onerror = () => reject(new Error(`Failed to load ${src}`));
        document.head.appendChild(script);
    });

const initSwitcher = async () => {
    const switcher = document.querySelector("#switcher-canvas");
    if (!switcher) {
        return;
    }

    globalThis.Popper = Popper;
    await loadLegacyScript(defaultmenuUrl);
    await loadLegacyScript(switcherUrl);
};

const initTheme = () => {
    initTooltips();
    initPopovers();
    initDateRange();
    initWaves();
    initSimplebar();
    initSelect2();
    initPickr();
    initHeaderThemeToggle();
    initFullscreenToggle();
    initSwitcher();
};

document.addEventListener("DOMContentLoaded", initTheme);
