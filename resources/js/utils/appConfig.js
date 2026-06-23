const appConfig = window.appConfig ?? {};

export function getAppConfig() {
    return appConfig;
}

export function getBusinessConfig() {
    return appConfig.business ?? {};
}

export function getSeoConfig() {
    return appConfig.seo ?? { site: {}, pages: {} };
}

export function getAnalyticsConfig() {
    return appConfig.analytics ?? {};
}

export function getTurnstileConfig() {
    return appConfig.turnstile ?? {};
}

export function getFormTimingToken() {
    const token = appConfig.forms?.timingToken;

    return typeof token === "string" ? token : "";
}

export function getInitialData(key, fallback = null) {
    if (!key || typeof key !== "string") {
        return fallback;
    }

    return appConfig.initialData?.[key] ?? fallback;
}

export function getNamedRoute(name, fallback = "/") {
    if (!name || typeof name !== "string") {
        return fallback;
    }

    const value = appConfig.routes?.[name];

    if (typeof value !== "string" || !value.trim()) {
        return fallback;
    }

    try {
        return new URL(value, window.location.origin).pathname;
    } catch {
        return fallback;
    }
}
