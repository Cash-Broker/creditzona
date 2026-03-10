<template>
    <div
        class="rounded-3xl border border-border bg-surface p-6 shadow-sm lg:sticky lg:top-24"
    >
        <div class="mb-5">
            <div
                class="inline-flex items-center gap-2 rounded-full bg-accent-soft px-3 py-1 text-xs font-medium text-accent-ink"
            >
                <font-awesome-icon icon="fa-solid fa-comments" />
                <span>Безплатна консултация</span>
            </div>

            <h2 class="mt-4 text-2xl font-bold text-text">
                Оставете запитване
            </h2>

            <p class="mt-2 text-sm leading-6 text-text-muted">
                Попълнете кратката форма и ще се свържем с вас, за да обсъдим
                възможностите според вашия случай.
            </p>
        </div>

        <LeadFormCompact />

        <p class="mt-4 text-xs leading-5 text-text-subtle">
            С изпращане на формата потвърждавате, че данните ще се използват
            единствено за целите на консултацията.
        </p>
    </div>
</template>

<script setup>
import LeadFormCompact from "./LeadFormCompact.vue";
</script>
