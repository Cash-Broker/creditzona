import { getAnalyticsConfig } from "@/utils/appConfig";

const GTAG_SCRIPT_ATTR = "data-creditzona-gtag";

const state = {
    initialized: false,
    measurementId: "",
    adsId: "",
    adsConversionLabel: "",
    consentGranted: false,
    scriptInjected: false,
};

function readConfig() {
    const config = getAnalyticsConfig();

    return {
        measurementId:
            typeof config.googleMeasurementId === "string"
                ? config.googleMeasurementId.trim()
                : "",
        adsId:
            typeof config.googleAdsId === "string"
                ? config.googleAdsId.trim()
                : "",
        adsConversionLabel:
            typeof config.googleAdsConversionLabel === "string"
                ? config.googleAdsConversionLabel.trim()
                : "",
    };
}

function injectGtagScript() {
    if (state.scriptInjected) {
        return;
    }

    const primaryTagId = state.measurementId || state.adsId;

    if (!primaryTagId) {
        return;
    }

    window.dataLayer = window.dataLayer || [];

    function gtag() {
        window.dataLayer.push(arguments);
    }

    window.gtag = window.gtag || gtag;

    const script = document.createElement("script");
    script.async = true;
    script.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(primaryTagId)}`;
    script.setAttribute(GTAG_SCRIPT_ATTR, "true");
    document.head.appendChild(script);

    window.gtag("js", new Date());

    if (state.measurementId) {
        window.gtag("config", state.measurementId, { send_page_view: false });
    }

    if (state.adsId) {
        window.gtag("config", state.adsId, { send_page_view: false });
    }

    state.scriptInjected = true;
}

function handleConsentChange(event) {
    const analyticsAllowed = Boolean(event?.detail?.analytics);

    if (analyticsAllowed === state.consentGranted) {
        return;
    }

    state.consentGranted = analyticsAllowed;

    if (analyticsAllowed) {
        injectGtagScript();
    }
}

export function initializeAnalytics({ initialConsent } = {}) {
    if (state.initialized) {
        return;
    }

    state.initialized = true;

    const { measurementId, adsId, adsConversionLabel } = readConfig();

    if (!measurementId && !adsId) {
        return;
    }

    state.measurementId = measurementId;
    state.adsId = adsId;
    state.adsConversionLabel = adsConversionLabel;
    state.consentGranted = Boolean(initialConsent?.analytics);

    window.addEventListener(
        "creditzona:cookie-consent-changed",
        handleConsentChange,
    );

    if (state.consentGranted) {
        injectGtagScript();
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

export function trackLeadConversion() {
    if (typeof window.gtag !== "function") {
        return;
    }

    // GA4 standard lead-generation event — visible in GA4 reports.
    // Mark it as a Key Event in the GA4 console to count it as a conversion.
    if (state.measurementId) {
        window.gtag("event", "generate_lead", {
            send_to: state.measurementId,
        });
    }

    // Google Ads conversion attribution — separate hit, routed only to the
    // Google Ads tag via `send_to` so it does not pollute the GA4 stream.
    if (state.adsId && state.adsConversionLabel) {
        window.gtag("event", "conversion", {
            send_to: `${state.adsId}/${state.adsConversionLabel}`,
        });
    }
}
