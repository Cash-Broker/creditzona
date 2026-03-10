<template>
    <section class="space-y-5">
        <div class="rounded-3xl border border-border bg-surface p-6 shadow-sm">
            <h2 class="text-2xl font-bold tracking-tight text-text">
                Финансово решение дори при сложна ситуация
            </h2>

            <p class="mt-4 text-sm leading-7 text-text-muted">
                Консултираме и структурираме решения при просрочени кредити,
                запори или нестандартни доходи.
            </p>

            <p class="mt-4 text-sm font-medium text-text-subtle">
                Попълването на формата отнема по-малко от 1 минута.
            </p>
        </div>

        <div class="rounded-3xl border border-border bg-surface p-6 shadow-sm">
            <h3 class="text-base font-semibold tracking-tight text-text">
                Помагаме при ситуации като:
            </h3>

            <ul class="mt-4 grid gap-2.5">
                <li
                    v-for="item in problemAreas"
                    :key="item"
                    class="inline-flex items-center gap-2 text-sm text-secondary"
                >
                    <span
                        class="h-1.5 w-1.5 rounded-full bg-accent-darkened"
                    ></span>
                    {{ item }}
                </li>
            </ul>
        </div>

        <div
            class="rounded-3xl border border-accent-soft-border bg-accent-soft p-6"
        >
            <h3 class="text-base font-semibold tracking-tight text-accent-ink">
                Как протича процесът
            </h3>

            <ol class="mt-4 space-y-3">
                <li
                    v-for="(step, index) in processSteps"
                    :key="step"
                    class="inline-flex items-center gap-3 text-sm text-accent-ink"
                >
                    <span
                        class="inline-flex h-7 w-7 items-center justify-center rounded-full border border-accent-soft-border bg-surface text-xs font-semibold text-accent-darkened"
                    >
                        {{ index + 1 }}
                    </span>
                    {{ step }}
                </li>
            </ol>
        </div>
    </section>
</template>

<script setup>
const problemAreas = [
    "Влошена кредитна история",
    "Запори от ЧСИ",
    "Просрочени кредити",
    "Нестандартни доходи",
    "Обединяване на задължения",
];

const processSteps = [
    "Попълвате формата",
    "Получавате консултация",
    "Изграждаме решение",
];
</script>
