"use strict";

const togglePassword = (inputId, button) => {
    const input = document.getElementById(inputId);
    if (!input) {
        return;
    }

    input.type = input.type === "password" ? "text" : "password";
    const icon = button.querySelector("i");
    if (!icon) {
        return;
    }

    if (icon.classList.contains("ri-eye-line")) {
        icon.classList.remove("ri-eye-line");
        icon.classList.add("ri-eye-off-line");
    } else {
        icon.classList.add("ri-eye-line");
        icon.classList.remove("ri-eye-off-line");
    }
};

export const initShowPassword = () => {
    document.querySelectorAll(".show-password-button").forEach((button) => {
        const target = button.getAttribute("data-target");
        if (!target) {
            return;
        }

        button.addEventListener("click", (event) => {
            event.preventDefault();
            togglePassword(target, button);
        });
    });
};
