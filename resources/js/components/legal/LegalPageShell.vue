<template>
    <div class="mx-auto max-w-5xl px-4 py-10 md:py-12">
        <section class="max-w-3xl">
            <span
                class="inline-flex items-center rounded-full border border-accent-soft-border bg-accent-soft px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-accent-darkened"
            >
                {{ eyebrow }}
            </span>

            <h1
                class="mt-4 text-3xl font-extrabold tracking-tight text-text md:text-4xl"
            >
                {{ title }}
            </h1>

            <p class="mt-4 text-sm leading-7 text-text-muted sm:text-base">
                {{ intro }}
            </p>

            <p class="mt-4 text-xs uppercase tracking-[0.12em] text-text-subtle">
                Актуално към {{ updatedAt }}
            </p>
        </section>

        <nav
            aria-label="Правни страници"
            class="mt-8 flex flex-wrap gap-3"
        >
            <RouterLink
                v-for="link in legalNavLinks"
                :key="link.to"
                :to="link.to"
                :class="getNavLinkClasses(link.to)"
            >
                {{ link.label }}
            </RouterLink>
        </nav>

        <div class="mt-10 space-y-6">
            <slot />
        </div>
    </div>
</template>

<script setup>
import { useRoute } from "vue-router";
import { cookieLegalLinks as legalNavLinks } from "@/constants/legalLinks";

defineProps({
    eyebrow: {
        type: String,
        required: true,
    },
    title: {
        type: String,
        required: true,
    },
    intro: {
        type: String,
        required: true,
    },
    updatedAt: {
        type: String,
        default: "12 март 2026",
    },
});

const route = useRoute();

function getNavLinkClasses(path) {
    return [
        "inline-flex items-center rounded-full border px-4 py-2.5 text-sm font-medium transition-all duration-200",
        route.path === path
            ? "border-accent-soft-border bg-accent-soft text-accent-darkened"
            : "border-border bg-surface text-secondary hover:border-border-strong hover:text-text",
    ];
}
</script>
