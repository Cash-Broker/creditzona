import { onBeforeUnmount, ref } from "vue";
import { getTurnstileConfig } from "@/utils/appConfig";

const SCRIPT_SRC =
    "https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit";

let scriptPromise = null;

function loadTurnstileScript() {
    if (typeof window === "undefined") {
        return Promise.reject(new Error("Turnstile unavailable outside browser."));
    }

    if (window.turnstile) {
        return Promise.resolve(window.turnstile);
    }

    if (scriptPromise) {
        return scriptPromise;
    }

    scriptPromise = new Promise((resolve, reject) => {
        const resolveWhenReady = () => {
            if (window.turnstile) {
                resolve(window.turnstile);
            } else {
                reject(new Error("Turnstile script loaded without global."));
            }
        };

        const existing = document.querySelector(
            'script[data-creditzona-turnstile="true"]',
        );

        if (existing) {
            existing.addEventListener("load", resolveWhenReady, { once: true });
            existing.addEventListener(
                "error",
                () => reject(new Error("Turnstile script failed to load.")),
                { once: true },
            );

            if (window.turnstile) {
                resolve(window.turnstile);
            }

            return;
        }

        const script = document.createElement("script");
        script.src = SCRIPT_SRC;
        script.async = true;
        script.defer = true;
        script.setAttribute("data-creditzona-turnstile", "true");
        script.addEventListener("load", resolveWhenReady, { once: true });
        script.addEventListener(
            "error",
            () => reject(new Error("Turnstile script failed to load.")),
            { once: true },
        );

        document.head.appendChild(script);
    });

    return scriptPromise;
}

export function useTurnstile() {
    const { siteKey } = getTurnstileConfig();
    const isEnabled = Boolean(siteKey);

    const token = ref("");
    const ready = ref(false);
    const failed = ref(false);

    let turnstile = null;
    let widgetId = null;

    async function render(element) {
        if (!isEnabled || !element) {
            return;
        }

        try {
            turnstile = await loadTurnstileScript();

            widgetId = turnstile.render(element, {
                sitekey: siteKey,
                callback: (value) => {
                    token.value = value;
                    failed.value = false;
                },
                "expired-callback": () => {
                    token.value = "";
                },
                "error-callback": () => {
                    token.value = "";
                    failed.value = true;

                    return true;
                },
            });

            ready.value = true;
        } catch {
            failed.value = true;
        }
    }

    function reset() {
        token.value = "";

        if (turnstile && widgetId !== null) {
            try {
                turnstile.reset(widgetId);
            } catch {
                // The widget may already be gone; nothing to reset.
            }
        }
    }

    onBeforeUnmount(() => {
        if (turnstile && widgetId !== null) {
            try {
                turnstile.remove(widgetId);
            } catch {
                // Widget already removed.
            }
        }
    });

    return {
        isEnabled,
        token,
        ready,
        failed,
        render,
        reset,
    };
}
