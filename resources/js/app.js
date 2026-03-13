import "./bootstrap";
import { createApp } from "vue";
import App from "./App.vue";
import router from "./router";
import { initializeCookieConsent } from "@/composables/useCookieConsent";
import { applyRouteSeo } from "@/seo";

import { library } from "@fortawesome/fontawesome-svg-core";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";

import {
    faPhone,
    faFileLines,
    faComments,
    faArrowRight,
    faCircleCheck,
    faPaperPlane,
    faHouse,
    faBuildingColumns,
    faLocationDot,
    faEnvelope,
    faClock,
    faCheck,
    faRoute,
} from "@fortawesome/free-solid-svg-icons";

library.add(
    faPhone,
    faFileLines,
    faComments,
    faArrowRight,
    faCircleCheck,
    faPaperPlane,
    faHouse,
    faBuildingColumns,
    faLocationDot,
    faEnvelope,
    faClock,
    faCheck,
    faRoute,
);

initializeCookieConsent();

const app = createApp(App);

app.component("font-awesome-icon", FontAwesomeIcon);

app.use(router).mount("#app");

router.isReady().then(() => {
    applyRouteSeo(router.currentRoute.value);
});
