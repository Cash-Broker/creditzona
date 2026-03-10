<template>
    <div class="pt-4">
        <div
            class="inline-flex items-center gap-2 rounded-full bg-accent-soft px-4 py-2 text-sm text-accent-ink border border-accent-soft-border"
        >
            <span class="font-semibold">Кредитни консултанти</span>
            <span class="text-accent-darkened">•</span>
            <span>Пловдив и онлайн</span>
        </div>

        <h1 class="mt-5 text-4xl md:text-5xl font-extrabold tracking-tight">
            По-бързо към правилната кредитна оферта
        </h1>

        <p class="mt-4 text-lg text-text-muted">
            Насочваме ви към правилната опция с подходящ профил ви и помощта ни с
            документацията.
            <span class="font-medium text-secondary">
                Не отпускаме кредити директно.
            </span>
        </p>

        <div class="mt-6 grid sm:grid-cols-3 gap-4">
            <div
                v-for="stat in stats"
                :key="stat.label"
                class="bg-surface rounded-xl border p-4"
            >
                <div class="text-sm text-text-subtle">{{ stat.label }}</div>
                <div class="text-xl font-bold">{{ stat.value }}</div>
            </div>
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            <a
                v-for="action in actions"
                :key="action.label"
                :href="action.url"
                :class="action.class"
            >
                {{ action.label }}
            </a>
        </div>

        <p class="mt-3 text-xs text-text-subtle">
             * Текстът е примерен — смени го с реалното им SLA, за да не ги
            вкараш в обещания.
        </p>
    </div>
</template>

<script setup>
const routes = window.laravelRoutes || {};

const stats = [
    { label: "Оглед", value: "в рамките на дните*" },
    { label: "Процес", value: "ясни стъпки" },
    { label: "Контакти", value: "телефон + форма" },
];

const actions = [
    {
        label: "Потребителски кредит",
        url: routes.consumer || "/service/consumer",
        class: "inline-flex items-center justify-center rounded-xl bg-accent px-5 py-3 font-semibold text-primary hover:bg-accent-hover transition",
    },
    {
        label: "Ипотечен кредит",
        url: routes.mortgage || "/service/mortgage",
        class: "inline-flex items-center justify-center rounded-xl bg-surface px-5 py-3 font-semibold text-text border hover:bg-background transition",
    },
    {
        label: "Контакти",
        url: routes.contact || "/contact",
        class: "inline-flex items-center justify-center rounded-xl bg-surface px-5 py-3 font-semibold text-text border hover:bg-background transition",
    },
];
</script>

