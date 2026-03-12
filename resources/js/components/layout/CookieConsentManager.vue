<template>
    <Teleport to="body">
        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="translate-y-4 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-4 opacity-0"
        >
            <section
                v-if="showBanner"
                class="fixed inset-x-0 bottom-0 z-[60] px-4 pb-4 sm:px-6"
                aria-label="Известие за бисквитки"
            >
                <div
                    class="mx-auto max-w-5xl rounded-[28px] border border-border bg-surface p-5 shadow-[0_20px_50px_-28px_rgba(17,24,39,0.48)] sm:p-6"
                >
                    <div
                        class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between"
                    >
                        <div class="max-w-3xl">
                            <span
                                class="inline-flex items-center rounded-full border border-accent-soft-border bg-accent-soft px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-accent-darkened"
                            >
                                Бисквитки и поверителност
                            </span>

                            <h2
                                class="mt-3 text-lg font-semibold tracking-tight text-text sm:text-xl"
                            >
                                Използваме бисквитки за правилна работа и
                                статистика
                            </h2>

                            <p
                                class="mt-3 text-sm leading-7 text-text-muted sm:text-base"
                            >
                                Използваме необходими бисквитки, за да работи
                                сайтът коректно, и статистически бисквитки само
                                ако ги разрешите. Можете да приемете, да
                                откажете или да настроите предпочитанията си по
                                всяко време.
                            </p>

                            <div
                                class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm"
                            >
                                <RouterLink
                                    v-for="link in cookieLegalLinks"
                                    :key="link.to"
                                    :to="link.to"
                                    class="font-medium text-accent-darkened underline decoration-accent-soft-border underline-offset-4 transition-colors duration-200 hover:text-accent-ink focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-accent-soft focus-visible:ring-offset-2 focus-visible:ring-offset-surface"
                                >
                                    {{ link.label }}
                                </RouterLink>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-3 lg:justify-end">
                            <button
                                type="button"
                                class="secondary-button min-w-[9.5rem] flex-1 sm:w-auto sm:flex-none"
                                @click="declineOptional"
                            >
                                Отказвам
                            </button>

                            <button
                                type="button"
                                class="secondary-button min-w-[9.5rem] flex-1 bg-accent-soft text-accent-darkened hover:border-accent-soft-border hover:bg-accent-soft sm:w-auto sm:flex-none"
                                @click="openSettings"
                            >
                                Настройки
                            </button>

                            <button
                                type="button"
                                class="primary-button min-w-[9.5rem] flex-1 sm:w-auto sm:flex-none"
                                @click="acceptAll"
                            >
                                Приемам
                            </button>
                        </div>
                    </div>
                </div>
            </section>
        </transition>

        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isSettingsOpen"
                class="fixed inset-0 z-[70] flex items-center justify-center bg-text/45 px-4 py-6 backdrop-blur-sm"
                @click.self="closeSettings"
            >
                <div
                    ref="dialogRef"
                    class="w-full max-w-3xl overflow-hidden rounded-[32px] border border-border bg-surface shadow-[0_28px_70px_-34px_rgba(17,24,39,0.55)]"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="cookie-settings-title"
                    aria-describedby="cookie-settings-description"
                    @keydown="handleDialogKeydown"
                >
                    <div
                        class="flex items-start justify-between gap-4 border-b border-border px-5 py-5 sm:px-7"
                    >
                        <div class="max-w-2xl">
                            <p
                                class="text-xs font-semibold uppercase tracking-[0.14em] text-accent-darkened"
                            >
                                Настройки на бисквитки
                            </p>

                            <h2
                                id="cookie-settings-title"
                                class="mt-2 text-2xl font-semibold tracking-tight text-text"
                            >
                                Управлявайте предпочитанията си
                            </h2>

                            <p
                                id="cookie-settings-description"
                                class="mt-3 text-sm leading-7 text-text-muted"
                            >
                                Необходимите бисквитки са винаги активни.
                                Статистическите бисквитки могат да се използват
                                само след ваш избор и не се активират
                                автоматично.
                            </p>
                        </div>

                        <button
                            ref="closeButtonRef"
                            type="button"
                            class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-border bg-background text-secondary transition-colors duration-200 hover:border-border-strong hover:text-text focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-accent-soft focus-visible:ring-offset-2 focus-visible:ring-offset-surface"
                            aria-label="Затвори настройките за бисквитки"
                            @click="closeSettings"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-4 px-5 py-5 sm:px-7 sm:py-6">
                        <article
                            class="rounded-[28px] border border-accent-soft-border bg-accent-soft px-5 py-5"
                        >
                            <div
                                class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                            >
                                <div class="max-w-2xl">
                                    <div
                                        class="inline-flex items-center rounded-full border border-accent-soft-border bg-surface px-3 py-1 text-xs font-semibold uppercase tracking-[0.12em] text-accent-darkened"
                                    >
                                        Винаги активни
                                    </div>

                                    <h3
                                        class="mt-3 text-base font-semibold text-text"
                                    >
                                        Необходими бисквитки
                                    </h3>

                                    <p
                                        class="mt-2 text-sm leading-7 text-text-muted"
                                    >
                                        Поддържат сигурността, навигацията и
                                        запазването на избора ви за бисквитки.
                                        Без тях сайтът не може да функционира
                                        правилно.
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    role="switch"
                                    aria-checked="true"
                                    aria-disabled="true"
                                    disabled
                                    class="relative inline-flex h-7 w-12 shrink-0 cursor-not-allowed rounded-full border border-accent-soft-border bg-accent-darkened/90 opacity-90"
                                >
                                    <span
                                        class="absolute right-1 top-1 inline-flex h-5 w-5 rounded-full bg-surface shadow-sm"
                                    ></span>
                                </button>
                            </div>
                        </article>

                        <article
                            class="rounded-[28px] border border-border bg-background px-5 py-5"
                        >
                            <div
                                class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                            >
                                <div class="max-w-2xl">
                                    <h3
                                        class="text-base font-semibold text-text"
                                    >
                                        Статистически бисквитки
                                    </h3>

                                    <p
                                        class="mt-2 text-sm leading-7 text-text-muted"
                                    >
                                        Помагат ни да разбираме посещаемостта и
                                        използването на сайта чрез обобщени
                                        данни. Те са по избор и могат да бъдат
                                        включени или изключени от вас.
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    role="switch"
                                    :aria-checked="
                                        preferences.analytics ? 'true' : 'false'
                                    "
                                    class="relative inline-flex h-7 w-12 shrink-0 rounded-full border transition-all duration-200 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-accent-soft focus-visible:ring-offset-2 focus-visible:ring-offset-surface"
                                    :class="
                                        preferences.analytics
                                            ? 'border-accent bg-accent'
                                            : 'border-border-strong bg-surface'
                                    "
                                    @click="
                                        preferences.analytics =
                                            !preferences.analytics
                                    "
                                >
                                    <span
                                        class="absolute top-1 inline-flex h-5 w-5 rounded-full bg-surface shadow-sm transition-transform duration-200"
                                        :class="
                                            preferences.analytics
                                                ? 'translate-x-6'
                                                : 'translate-x-1'
                                        "
                                    ></span>
                                </button>
                            </div>
                        </article>

                        <div
                            class="rounded-[28px] border border-border bg-surface px-5 py-4"
                        >
                            <p class="text-sm leading-7 text-text-muted">
                                Повече информация има в
                                <RouterLink
                                    v-for="(link, index) in cookieLegalLinks"
                                    :key="`dialog-${link.to}`"
                                    :to="link.to"
                                    class="font-medium text-accent-darkened underline decoration-accent-soft-border underline-offset-4 transition-colors duration-200 hover:text-accent-ink focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-accent-soft focus-visible:ring-offset-2 focus-visible:ring-offset-surface"
                                >
                                    {{ `${index > 0 ? ", " : ""}${link.label}` }}
                                </RouterLink>
                                .
                            </p>
                        </div>
                    </div>

                    <div
                        class="flex flex-col gap-4 border-t border-border px-5 py-5 sm:px-7 lg:flex-row lg:items-center lg:justify-between"
                    >
                        <p class="text-sm leading-6 text-text-muted">
                            Изборът може да бъде променен по-късно от линка
                            „Настройки на бисквитки“ във footer-а.
                        </p>

                        <div
                            class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:justify-end"
                        >
                            <button
                                type="button"
                                class="secondary-button sm:w-auto"
                                @click="declineOptional"
                            >
                                Отказвам
                            </button>

                            <button
                                type="button"
                                class="secondary-button bg-accent-soft text-accent-darkened hover:border-accent-soft-border hover:bg-accent-soft sm:w-auto"
                                @click="acceptAll"
                            >
                                Приемам всички
                            </button>

                            <button
                                type="button"
                                class="primary-button sm:w-auto"
                                @click="savePreferences"
                            >
                                Запази избора
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </Teleport>
</template>

<script setup>
import { nextTick, onBeforeUnmount, ref, watch } from "vue";
import { cookieLegalLinks } from "@/constants/legalLinks";
import { useCookieConsent } from "@/composables/useCookieConsent";

const {
    acceptAll,
    closeSettings,
    currentPreferences,
    declineOptional,
    isSettingsOpen,
    openSettings,
    saveCustomPreferences,
    showBanner,
} = useCookieConsent();

const dialogRef = ref(null);
const closeButtonRef = ref(null);
const preferences = ref({
    necessary: true,
    analytics: false,
});

const focusableSelector =
    'a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])';

function syncPreferences() {
    preferences.value = {
        necessary: true,
        analytics: Boolean(currentPreferences.value.analytics),
    };
}

function savePreferences() {
    saveCustomPreferences(preferences.value);
}

function handleDialogKeydown(event) {
    if (event.key === "Escape") {
        event.preventDefault();
        closeSettings();

        return;
    }

    if (event.key !== "Tab" || !dialogRef.value) {
        return;
    }

    const focusableElements = Array.from(
        dialogRef.value.querySelectorAll(focusableSelector),
    ).filter((element) => !element.hasAttribute("disabled"));

    if (focusableElements.length === 0) {
        return;
    }

    const firstElement = focusableElements[0];
    const lastElement = focusableElements[focusableElements.length - 1];

    if (event.shiftKey && document.activeElement === firstElement) {
        event.preventDefault();
        lastElement.focus();
    }

    if (!event.shiftKey && document.activeElement === lastElement) {
        event.preventDefault();
        firstElement.focus();
    }
}

watch(currentPreferences, syncPreferences, { immediate: true });

watch(
    isSettingsOpen,
    async (isOpen) => {
        document.body.classList.toggle("overflow-hidden", isOpen);

        if (!isOpen) {
            return;
        }

        syncPreferences();

        await nextTick();

        closeButtonRef.value?.focus();
    },
    { immediate: true },
);

onBeforeUnmount(() => {
    document.body.classList.remove("overflow-hidden");
});
</script>
