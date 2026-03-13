<template>
    <footer class="mt-20 border-t border-border bg-footer">
        <div class="mx-auto max-w-6xl px-4 py-12">
            <div class="grid gap-8 lg:grid-cols-[1.2fr_0.9fr_0.9fr]">
                <div
                    class="rounded-[28px] border border-border bg-surface p-6 shadow-[0_10px_30px_-22px_rgba(17,24,39,0.35)]"
                >
                    <div
                        class="inline-flex items-center rounded-full border border-accent-soft-border bg-accent-soft px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-accent-darkened"
                    >
                        Кредитна консултация
                    </div>

                    <RouterLink
                        to="/"
                        class="mt-5 flex items-center gap-4 transition-opacity hover:opacity-90"
                    >
                        <img
                            :src="logo"
                            alt="Кредит Зона"
                            width="200"
                            height="113"
                            class="h-14 w-auto shrink-0"
                            decoding="async"
                        />

                        <div class="min-w-0">
                            <div
                                class="text-lg font-bold tracking-tight text-text"
                            >
                                Кредит Зона
                            </div>

                            <p class="mt-1 text-sm text-text-subtle">
                                Кредитна консултация за Пловдив и онлайн
                            </p>
                        </div>
                    </RouterLink>

                    <p class="mt-5 max-w-md text-sm leading-7 text-text-muted">
                        Консултации и съдействие при избор на по-ясна
                        финансова посока с фокус върху реално изпълним план,
                        спокойна комуникация и конкретни следващи стъпки.
                    </p>

                    <RouterLink
                        to="/contacts"
                        class="mt-6 inline-flex items-center justify-center rounded-2xl border border-accent-soft-border bg-accent-soft px-4 py-3 text-sm font-medium text-accent-ink transition-colors hover:bg-white"
                    >
                        Безплатна първоначална консултация
                    </RouterLink>
                </div>

                <nav
                    aria-label="Навигация във footer"
                    class="rounded-[28px] border border-border bg-surface p-6 shadow-[0_10px_30px_-22px_rgba(17,24,39,0.35)]"
                >
                    <h3
                        class="text-sm font-semibold uppercase tracking-[0.12em] text-text"
                    >
                        Навигация
                    </h3>

                    <ul class="mt-5 space-y-3">
                        <li v-for="link in navigationLinks" :key="link.label">
                            <RouterLink
                                :to="link.to"
                                class="group inline-flex items-center gap-2 text-sm text-text-muted transition-colors duration-200 hover:text-accent-darkened"
                            >
                                <span
                                    class="h-1.5 w-1.5 rounded-full bg-accent-darkened transition-transform duration-200 group-hover:scale-125"
                                ></span>
                                {{ link.label }}
                            </RouterLink>
                        </li>
                    </ul>
                </nav>

                <div
                    class="rounded-[28px] border border-border bg-surface p-6 shadow-[0_10px_30px_-22px_rgba(17,24,39,0.35)]"
                >
                    <h3
                        class="text-sm font-semibold uppercase tracking-[0.12em] text-text"
                    >
                        Бърза информация
                    </h3>

                    <ul class="mt-5 space-y-3">
                        <li
                            v-for="item in trustItems"
                            :key="item"
                            class="flex items-center gap-3 rounded-2xl bg-background px-4 py-3 text-sm text-text-muted"
                        >
                            <span
                                class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-accent-soft text-accent-darkened"
                            >
                                <span
                                    class="h-1.5 w-1.5 rounded-full bg-accent-darkened"
                                ></span>
                            </span>
                            <span>{{ item }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div
                class="mt-8 flex flex-col gap-3 border-t border-border pt-5 sm:flex-row sm:items-center sm:justify-between"
            >
                <p class="text-xs text-text-subtle">© Кредит Зона</p>

                <nav
                    class="flex flex-wrap items-center gap-x-5 gap-y-2"
                    aria-label="Правни връзки"
                >
                    <RouterLink
                        v-for="link in footerLegalLinks"
                        :key="link.to"
                        :to="link.to"
                        class="text-xs text-text-subtle transition-colors duration-200 hover:text-accent-darkened focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-accent-soft focus-visible:ring-offset-2 focus-visible:ring-offset-footer"
                    >
                        {{ link.label }}
                    </RouterLink>

                    <button
                        type="button"
                        class="cursor-pointer text-xs font-medium text-accent-darkened transition-colors duration-200 hover:text-accent-ink focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-accent-soft focus-visible:ring-offset-2 focus-visible:ring-offset-footer"
                        @click="openSettings"
                    >
                        Настройки на бисквитки
                    </button>
                </nav>
            </div>
        </div>
    </footer>
</template>

<script setup>
import { legalLinks } from "@/constants/legalLinks";
import { useCookieConsent } from "@/composables/useCookieConsent";

const { openSettings } = useCookieConsent();

const logo = "/images/logo/logo.png";

const navigationLinks = [
    { label: "Начало", to: "/" },
    { label: "За нас", to: "/about" },
    { label: "ЧЗВ", to: "/faq" },
    { label: "Блог", to: "/blog" },
    { label: "Контакти", to: "/contacts" },
];

const trustItems = [
    "Пловдив и онлайн",
    "Безплатна първоначална консултация",
    "Отговор до 24 часа",
];

const footerLegalLinks = [
    legalLinks.privacy,
    legalLinks.cookies,
    legalLinks.terms,
];
</script>
