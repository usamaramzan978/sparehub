import { initAuthTheme } from "../assets/js/auth/authenticate-main";
import { initShowPassword } from "../assets/js/auth/show-password";
import { initCoverParticles } from "../assets/js/auth/particles";

const initAuth = () => {
    initAuthTheme();
    initShowPassword();
    initCoverParticles();
};

document.addEventListener("DOMContentLoaded", initAuth);
