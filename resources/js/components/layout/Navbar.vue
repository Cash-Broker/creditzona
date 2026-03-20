<template>
    <header
        class="sticky top-0 z-50 border-b border-border/80 bg-surface/90 backdrop-blur-md"
    >
        <div
            class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 lg:py-5"
        >
            <RouterLink
                to="/"
                class="flex shrink-0 items-center transition-opacity hover:opacity-90"
            >
                <img
                    :src="logo"
                    alt="Кредит Зона"
                    width="200"
                    height="113"
                    class="h-20 w-auto lg:h-30"
                    decoding="async"
                />
            </RouterLink>

            <div class="hidden items-center gap-3 lg:flex">
                <nav
                    class="flex items-center gap-1 rounded-full border border-border bg-background/80 p-1.5"
                >
                    <RouterLink
                        v-for="item in navItems"
                        :key="item.label"
                        :to="item.url"
                        :class="getDesktopLinkClasses(item.url)"
                    >
                        {{ item.label }}
                    </RouterLink>
                </nav>

                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-full bg-accent px-5 py-3 text-sm font-semibold text-surface shadow-[0_10px_20px_-14px_rgba(17,24,39,0.6)] transition-all duration-200 hover:bg-accent-hover hover:-translate-y-[1px]"
                    @click="goToConsultationForm"
                >
                    Безплатна консултация
                </button>
            </div>

            <button
                type="button"
                class="inline-flex items-center justify-center rounded-2xl border border-border-strong bg-surface p-2.5 text-secondary transition hover:border-accent-soft-border hover:bg-accent-soft hover:text-accent-darkened lg:hidden"
                @click="isMobileMenuOpen = !isMobileMenuOpen"
                :aria-expanded="isMobileMenuOpen ? 'true' : 'false'"
                aria-label="Отвори менюто"
            >
                <svg
                    v-if="!isMobileMenuOpen"
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-6 w-6"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M4 6h16M4 12h16M4 18h16"
                    />
                </svg>

                <svg
                    v-else
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-6 w-6"
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

        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-2"
        >
            <div
                v-if="isMobileMenuOpen"
                class="border-t border-border bg-surface/95 backdrop-blur lg:hidden"
            >
                <div class="mx-auto max-w-6xl px-4 py-4">
                    <nav
                        class="rounded-[28px] border border-border bg-background p-3 shadow-[0_12px_30px_-20px_rgba(17,24,39,0.35)]"
                    >
                        <div class="flex flex-col gap-2">
                            <RouterLink
                                v-for="item in navItems"
                                :key="`mobile-${item.label}`"
                                :to="item.url"
                                :class="getMobileLinkClasses(item.url)"
                                @click="closeMobileMenu"
                            >
                                {{ item.label }}
                            </RouterLink>
                        </div>

                        <button
                            type="button"
                            class="mt-3 inline-flex w-full items-center justify-center rounded-2xl bg-accent px-5 py-3.5 text-sm font-semibold text-surface transition-all duration-200 hover:bg-accent-hover"
                            @click="goToConsultationForm"
                        >
                            Безплатна консултация
                        </button>
                    </nav>
                </div>
            </div>
        </transition>
    </header>
</template>

<script setup>
import { ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";

const logo = "/images/logo/logo.png";
const consultationHash = "#lead-form-compact";
const consultationRoutes = new Set(["/"]);

const route = useRoute();
const router = useRouter();
const isMobileMenuOpen = ref(false);

const navItems = [
    { label: "За нас", url: "/about" },
    { label: "ЧЗВ", url: "/faq" },
    { label: "Блог", url: "/blog" },
    { label: "Контакти", url: "/contacts" },
];

function isActive(url) {
    return route.path === url;
}

function getDesktopLinkClasses(url) {
    return [
        "rounded-full px-4 py-2.5 text-sm font-medium transition-all duration-200",
        isActive(url)
            ? "bg-accent-soft text-accent-darkened shadow-sm"
            : "text-secondary hover:bg-surface hover:text-accent-darkened",
    ];
}

function getMobileLinkClasses(url) {
    return [
        "rounded-2xl px-4 py-3 text-sm font-medium transition-all duration-200",
        isActive(url)
            ? "bg-accent-soft text-accent-darkened"
            : "text-secondary hover:bg-surface hover:text-accent-darkened",
    ];
}

function closeMobileMenu() {
    isMobileMenuOpen.value = false;
}

function scrollToConsultationForm() {
    const element = document.getElementById("lead-form-compact");

    if (!element) {
        return false;
    }

    const stickyHeader = document.querySelector("header.sticky");
    const offset =
        stickyHeader instanceof HTMLElement ? stickyHeader.offsetHeight + 16 : 96;
    const top = element.getBoundingClientRect().top + window.scrollY - offset;

    window.scrollTo({
        top: Math.max(top, 0),
        behavior: "smooth",
    });

    return true;
}

async function goToConsultationForm() {
    closeMobileMenu();

    if (scrollToConsultationForm()) {
        if (route.hash !== consultationHash) {
            await router.replace({
                path: route.path,
                hash: consultationHash,
            });
        }

        return;
    }

    const targetPath = consultationRoutes.has(route.path) ? route.path : "/";

    await router.push({
        path: targetPath,
        hash: consultationHash,
    });
}

watch(
    () => route.path,
    () => {
        closeMobileMenu();
    },
);
</script>
