export const initAuthTheme = () => {
    const html = document.documentElement;

    if (localStorage.getItem("zynixdarktheme")) {
        html.setAttribute("data-theme-mode", "dark");
        html.setAttribute("data-menu-styles", "dark");
        html.setAttribute("data-header-styles", "dark");
    }

    if (localStorage.zynixrtl) {
        html.setAttribute("dir", "rtl");
    }

    if (localStorage.getItem("zynixlayout") === "horizontal") {
        html.setAttribute("data-nav-layout", "horizontal");
    }

    const restoreTheme = () => {
        if (localStorage.primaryRGB) {
            const picker = document.querySelector(".theme-container-primary");
            if (picker) {
                picker.value = localStorage.primaryRGB;
            }
            html.style.setProperty("--primary-rgb", localStorage.primaryRGB);
        }

        if (localStorage.bodyBgRGB && localStorage.bodylightRGB) {
            const picker = document.querySelector(".theme-container-background");
            if (picker) {
                picker.value = localStorage.bodyBgRGB;
            }
            html.style.setProperty("--body-bg-rgb", localStorage.bodyBgRGB);
            html.style.setProperty("--body-bg-rgb2", localStorage.bodylightRGB);
            html.style.setProperty("--light-rgb", localStorage.bodylightRGB);
            html.style.setProperty(
                "--form-control-bg",
                `rgb(${localStorage.bodylightRGB})`,
            );
            html.style.setProperty("--input-border", "rgba(255,255,255,0.1)");
            html.setAttribute("data-theme-mode", "dark");
            html.setAttribute("data-menu-styles", "dark");
            html.setAttribute("data-header-styles", "dark");
        }

        if (localStorage.zynixdarktheme) {
            html.setAttribute("data-theme-mode", "dark");
        }

        if (localStorage.zynixrtl) {
            html.setAttribute("dir", "rtl");
        }
    };

    restoreTheme();
};
