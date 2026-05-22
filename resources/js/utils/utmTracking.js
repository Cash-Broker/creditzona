const STORAGE_KEY = "creditzona_utm_attribution";
const EXPIRY_DAYS = 30;
const MAX_VALUE_LENGTH = 255;
const UTM_KEYS = ["utm_source", "utm_campaign", "utm_medium", "gclid"];

function isBrowser() {
    return typeof window !== "undefined" && typeof window.localStorage !== "undefined";
}

function sanitizeValue(value) {
    if (typeof value !== "string") {
        return "";
    }

    const trimmed = value.trim();

    if (trimmed === "") {
        return "";
    }

    return trimmed.slice(0, MAX_VALUE_LENGTH);
}

function readFromUrl() {
    const captured = {};

    try {
        const params = new URLSearchParams(window.location.search);

        for (const key of UTM_KEYS) {
            const value = sanitizeValue(params.get(key));

            if (value !== "") {
                captured[key] = value;
            }
        }
    } catch {
        // URLSearchParams unavailable or malformed location.search — degrade silently
    }

    return captured;
}

function writeToStorage(payload) {
    try {
        window.localStorage.setItem(STORAGE_KEY, JSON.stringify(payload));
    } catch {
        // Storage full, disabled, or in private mode — degrade silently
    }
}

function readFromStorage() {
    try {
        const raw = window.localStorage.getItem(STORAGE_KEY);

        if (!raw) {
            return null;
        }

        const parsed = JSON.parse(raw);

        if (parsed === null || typeof parsed !== "object") {
            return null;
        }

        return parsed;
    } catch {
        return null;
    }
}

function clearStorage() {
    try {
        window.localStorage.removeItem(STORAGE_KEY);
    } catch {
        // Ignore — best effort cleanup
    }
}

function isExpired(capturedAt) {
    const numeric = Number(capturedAt);

    if (!Number.isFinite(numeric)) {
        return true;
    }

    const ageMs = Date.now() - numeric;
    const maxAgeMs = EXPIRY_DAYS * 24 * 60 * 60 * 1000;

    return ageMs > maxAgeMs;
}

/**
 * Capture UTM / gclid parameters from current URL and store them
 * in localStorage. Uses last-touch attribution — any new UTM landing
 * overrides previous values. Call once on app boot.
 */
export function captureUtmFromCurrentUrl() {
    if (!isBrowser()) {
        return;
    }

    const captured = readFromUrl();

    if (Object.keys(captured).length === 0) {
        return;
    }

    writeToStorage({
        ...captured,
        captured_at: Date.now(),
    });
}

/**
 * Read stored UTM attribution if present and not expired.
 * Returns null when nothing valid is stored.
 *
 * @returns {{ utm_source: string|null, utm_campaign: string|null, utm_medium: string|null, gclid: string|null } | null}
 */
export function getStoredUtmParameters() {
    if (!isBrowser()) {
        return null;
    }

    const stored = readFromStorage();

    if (stored === null) {
        return null;
    }

    if (isExpired(stored.captured_at)) {
        clearStorage();
        return null;
    }

    const result = {
        utm_source: null,
        utm_campaign: null,
        utm_medium: null,
        gclid: null,
    };

    let hasAnyValue = false;

    for (const key of UTM_KEYS) {
        const value = sanitizeValue(stored[key]);

        if (value !== "") {
            result[key] = value;
            hasAnyValue = true;
        }
    }

    return hasAnyValue ? result : null;
}
