import { computed, reactive } from "vue";

export const COOKIE_CONSENT_KEY = "creditzona_cookie_consent";
export const COOKIE_CONSENT_POLICY_VERSION = "2026-03-12";

const DEFAULT_CONSENT = Object.freeze({
    necessary: true,
    analytics: false,
    status: "declined",
});

const VALID_STATUSES = new Set(["accepted", "declined", "customized"]);

const state = reactive({
    initialized: false,
    consent: null,
    isSettingsOpen: false,
});

function getTimestamp() {
    return new Date().toISOString();
}

function normalizeConsent(value) {
    if (!value || typeof value !== "object") {
        return null;
    }

    const status = VALID_STATUSES.has(value.status) ? value.status : null;

    if (!status || typeof value.analytics !== "boolean") {
        return null;
    }

    return {
        necessary: true,
        analytics: value.analytics,
        status,
        policy_version:
            typeof value.policy_version === "string" &&
            value.policy_version.length > 0
                ? value.policy_version
                : COOKIE_CONSENT_POLICY_VERSION,
        updated_at:
            typeof value.updated_at === "string" && value.updated_at.length > 0
                ? value.updated_at
                : getTimestamp(),
    };
}

function persistConsent(consent) {
    const normalizedConsent = normalizeConsent(consent);

    if (!normalizedConsent) {
        return;
    }

    try {
        window.localStorage.setItem(
            COOKIE_CONSENT_KEY,
            JSON.stringify(normalizedConsent),
        );
    } catch {
        // Keep the in-memory consent state even if storage is unavailable.
    }

    state.consent = normalizedConsent;

    window.dispatchEvent(
        new CustomEvent("creditzona:cookie-consent-changed", {
            detail: normalizedConsent,
        }),
    );
}

function readStoredConsent() {
    try {
        const rawValue = window.localStorage.getItem(COOKIE_CONSENT_KEY);

        if (!rawValue) {
            return null;
        }

        const parsedValue = JSON.parse(rawValue);
        const normalizedConsent = normalizeConsent(parsedValue);

        if (
            !normalizedConsent ||
            normalizedConsent.policy_version !== COOKIE_CONSENT_POLICY_VERSION
        ) {
            window.localStorage.removeItem(COOKIE_CONSENT_KEY);

            return null;
        }

        return normalizedConsent;
    } catch {
        window.localStorage.removeItem(COOKIE_CONSENT_KEY);

        return null;
    }
}

export function initializeCookieConsent() {
    if (state.initialized) {
        return state.consent;
    }

    const storedConsent = readStoredConsent();

    state.consent = storedConsent;
    state.initialized = true;

    return storedConsent;
}

export function useCookieConsent() {
    const hasSavedConsent = computed(() => Boolean(state.consent));
    const showBanner = computed(
        () => state.initialized && !hasSavedConsent.value,
    );
    const consent = computed(() => state.consent);
    const currentPreferences = computed(
        () =>
            state.consent ?? {
                ...DEFAULT_CONSENT,
                policy_version: COOKIE_CONSENT_POLICY_VERSION,
                updated_at: getTimestamp(),
            },
    );

    function acceptAll() {
        persistConsent({
            necessary: true,
            analytics: true,
            status: "accepted",
            policy_version: COOKIE_CONSENT_POLICY_VERSION,
            updated_at: getTimestamp(),
        });

        state.isSettingsOpen = false;
    }

    function declineOptional() {
        persistConsent({
            necessary: true,
            analytics: false,
            status: "declined",
            policy_version: COOKIE_CONSENT_POLICY_VERSION,
            updated_at: getTimestamp(),
        });

        state.isSettingsOpen = false;
    }

    function saveCustomPreferences(preferences) {
        persistConsent({
            necessary: true,
            analytics: Boolean(preferences?.analytics),
            status: "customized",
            policy_version: COOKIE_CONSENT_POLICY_VERSION,
            updated_at: getTimestamp(),
        });

        state.isSettingsOpen = false;
    }

    function openSettings() {
        state.isSettingsOpen = true;
    }

    function closeSettings() {
        state.isSettingsOpen = false;
    }

    return {
        consent,
        currentPreferences,
        hasSavedConsent,
        isSettingsOpen: computed(() => state.isSettingsOpen),
        showBanner,
        acceptAll,
        closeSettings,
        declineOptional,
        initializeCookieConsent,
        openSettings,
        saveCustomPreferences,
    };
}
