<template>
    <div class="pt-4">
        <div
            class="inline-flex items-center gap-2 rounded-full bg-yellow-50 px-4 py-2 text-sm text-yellow-900 border border-yellow-200"
        >
            <span class="font-semibold">Кредитни консултанти</span>
            <span class="text-yellow-700">•</span>
            <span>Пловдив и онлайн</span>
        </div>

        <h1 class="mt-5 text-4xl md:text-5xl font-extrabold tracking-tight">
            По-бързо към правилната кредитна оферта
        </h1>

        <p class="mt-4 text-lg text-gray-600">
            Насочваме ви към подходяща опция според профила ви и помагаме с
            документацията.
            <span class="font-medium text-gray-800">
                Не отпускaме кредити директно.
            </span>
        </p>

        <div class="mt-6 grid sm:grid-cols-3 gap-4">
            <div
                v-for="stat in stats"
                :key="stat.label"
                class="bg-white rounded-xl border p-4"
            >
                <div class="text-sm text-gray-500">{{ stat.label }}</div>
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

        <p class="mt-3 text-xs text-gray-500">
            * Текстът е примерен — смени го с реалното им SLA, за да не ги
            вкараш в обещания.
        </p>
    </div>
</template>

<script setup>
const routes = window.laravelRoutes || {};

const stats = [
    { label: "Отговор", value: "в рамките на деня*" },
    { label: "Процес", value: "ясни стъпки" },
    { label: "Контакт", value: "телефон + форма" },
];

const actions = [
    {
        label: "Потребителски кредит",
        url: routes.consumer || "/service/consumer",
        class: "inline-flex items-center justify-center rounded-xl bg-yellow-500 px-5 py-3 font-semibold text-black hover:bg-yellow-400 transition",
    },
    {
        label: "Ипотечен кредит",
        url: routes.mortgage || "/service/mortgage",
        class: "inline-flex items-center justify-center rounded-xl bg-white px-5 py-3 font-semibold text-gray-900 border hover:bg-gray-50 transition",
    },
    {
        label: "Контакти",
        url: routes.contact || "/contact",
        class: "inline-flex items-center justify-center rounded-xl bg-white px-5 py-3 font-semibold text-gray-900 border hover:bg-gray-50 transition",
    },
];
</script>
