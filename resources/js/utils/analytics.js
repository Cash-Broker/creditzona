import { getAnalyticsConfig } from "@/utils/appConfig";

const GTAG_SCRIPT_ATTR = "data-creditzona-gtag";

const state = {
    initialized: false,
    measurementId: "",
    consentGranted: false,
    scriptInjected: false,
};

function injectGtagScript(measurementId) {
    if (state.scriptInjected) {
        return;
    }

    window.dataLayer = window.dataLayer || [];

    function gtag() {
        window.dataLayer.push(arguments);
    }

    window.gtag = window.gtag || gtag;

    const script = document.createElement("script");
    script.async = true;
    script.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(measurementId)}`;
    script.setAttribute(GTAG_SCRIPT_ATTR, "true");
    document.head.appendChild(script);

    window.gtag("js", new Date());
    window.gtag("config", measurementId, { send_page_view: false });

    state.scriptInjected = true;
}

function handleConsentChange(event) {
    const analyticsAllowed = Boolean(event?.detail?.analytics);

    if (analyticsAllowed === state.consentGranted) {
        return;
    }

    state.consentGranted = analyticsAllowed;

    if (analyticsAllowed && state.measurementId) {
        injectGtagScript(state.measurementId);
    }
}

export function initializeAnalytics({ initialConsent } = {}) {
    if (state.initialized) {
        return;
    }

    state.initialized = true;

    const measurementId =
        typeof getAnalyticsConfig().googleMeasurementId === "string"
            ? getAnalyticsConfig().googleMeasurementId.trim()
            : "";

    if (!measurementId) {
        return;
    }

    state.measurementId = measurementId;
    state.consentGranted = Boolean(initialConsent?.analytics);

    window.addEventListener(
        "creditzona:cookie-consent-changed",
        handleConsentChange,
    );

    if (state.consentGranted) {
        injectGtagScript(measurementId);
    }
}

export function trackPageView(route) {
    if (typeof window.gtag !== "function") {
        return;
    }

    const path =
        typeof route?.fullPath === "string"
            ? route.fullPath
            : window.location.pathname + window.location.search;

    window.gtag("event", "page_view", {
        page_path: path,
        page_location: window.location.href,
        page_title: document.title,
    });
}
