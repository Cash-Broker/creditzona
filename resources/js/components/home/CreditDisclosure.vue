<template>
    <section
        :id="sectionId"
        class="content-section relative overflow-hidden"
        aria-labelledby="credit-disclosure-title"
    >
        <div class="relative z-10">
            <div
                class="inline-flex items-center gap-2 rounded-full bg-accent-soft px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-accent-darkened"
            >
                {{ creditDisclosure.eyebrow }}
            </div>

            <component
                :is="headingTag"
                id="credit-disclosure-title"
                class="mt-3 max-w-2xl text-xl font-bold leading-tight tracking-tight text-text sm:text-2xl"
            >
                {{ creditDisclosure.title }}
            </component>

            <p
                class="mt-4 rounded-2xl border border-accent-soft-border bg-accent-soft/70 px-4 py-3 text-sm font-medium leading-6 text-accent-ink"
            >
                {{ creditDisclosure.partnerNote }}
            </p>

            <!-- Срок на погасяване и максимален ГПР — видими, отделни полета (не в падащо меню) -->
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div class="rounded-2xl border border-border bg-background px-5 py-4">
                    <div
                        class="text-xs font-semibold uppercase tracking-[0.14em] text-text-subtle"
                    >
                        {{ creditDisclosure.repaymentTerm.label }}
                    </div>
                    <div class="mt-2 text-lg font-bold text-text sm:text-xl">
                        {{ creditDisclosure.repaymentTerm.value }}
                    </div>
                    <div class="mt-1 text-xs text-text-subtle">
                        {{ creditDisclosure.repaymentTerm.note }}
                    </div>
                </div>

                <div
                    class="rounded-2xl border border-accent-soft-border bg-accent-soft/50 px-5 py-4"
                >
                    <div
                        class="text-xs font-semibold uppercase tracking-[0.14em] text-accent-darkened"
                    >
                        {{ creditDisclosure.maxApr.label }}
                    </div>
                    <div
                        class="mt-2 text-3xl font-black tracking-tight text-accent-darkened"
                    >
                        {{ creditDisclosure.maxApr.value }}
                    </div>
                </div>
            </div>

            <!-- Представителен пример -->
            <div class="mt-5 rounded-2xl border border-border bg-surface p-5">
                <h3
                    class="text-sm font-semibold uppercase tracking-[0.12em] text-text"
                >
                    {{ creditDisclosure.representativeExample.heading }}
                </h3>

                <dl class="mt-4 grid gap-x-8 gap-y-3 sm:grid-cols-2">
                    <div
                        v-for="row in creditDisclosure.representativeExample.rows"
                        :key="row.label"
                        class="flex items-baseline justify-between gap-4 border-b border-border/70 pb-2"
                    >
                        <dt class="text-sm text-text-muted">{{ row.label }}</dt>
                        <dd class="text-sm font-bold text-text">{{ row.value }}</dd>
                    </div>
                </dl>

                <p class="mt-4 text-sm leading-6 text-text-subtle">
                    {{ creditDisclosure.representativeExample.note }}
                </p>
            </div>

            <!-- Физически адрес на фирмата -->
            <p class="mt-5 text-sm text-text-muted">
                <span class="font-semibold text-text">Адрес:</span>
                {{ contactInfo.city }}, {{ contactInfo.address }}
            </p>
        </div>
    </section>
</template>

<script setup>
import { contactInfo } from "@/data/contactInfo";
import { creditDisclosure } from "@/data/creditDisclosure";

defineProps({
    headingTag: {
        type: String,
        default: "h2",
    },
    sectionId: {
        type: String,
        default: "credit-disclosure",
    },
});
</script>
