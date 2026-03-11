<template>
    <section class="space-y-5">
        <div class="content-section relative overflow-hidden">
            <div
                class="absolute right-0 top-0 h-28 w-28 translate-x-8 -translate-y-8 rounded-full bg-accent-soft blur-2xl"
            ></div>

            <div class="relative z-10">
                <div
                    class="inline-flex items-center gap-2 rounded-full bg-accent-soft px-3 py-1 text-xs font-semibold text-accent-ink"
                >
                    <span
                        class="h-2 w-2 rounded-full bg-accent-darkened"
                    ></span>
                    Персонален подход
                </div>

                <h2
                    class="mt-4 max-w-md text-2xl font-bold leading-tight tracking-tight text-text"
                >
                    Финансово решение дори при сложна ситуация
                </h2>

                <p class="mt-4 max-w-xl text-sm leading-7 text-text-muted">
                    Консултираме и структурираме решения при просрочени кредити,
                    запори или нестандартни доходи.
                </p>

                <div
                    class="mt-5 inline-flex items-center gap-2 rounded-2xl border border-accent-soft-border bg-accent-soft px-4 py-3 text-sm font-medium text-accent-ink"
                >
                    <font-awesome-icon icon="fa-solid fa-clock" />
                    <span
                        >Попълването на формата отнема по-малко от 1
                        минута</span
                    >
                </div>
            </div>
        </div>

        <div class="content-section">
            <div class="flex items-center justify-between gap-3">
                <h3 class="text-base font-semibold tracking-tight text-text">
                    Помагаме при ситуации като:
                </h3>

                <div
                    class="hidden rounded-full bg-background px-3 py-1 text-xs font-medium text-text-subtle sm:block"
                >
                    Често срещани казуси
                </div>
            </div>

            <ul class="mt-5 grid gap-3 sm:grid-cols-2">
                <li
                    v-for="item in problemAreas"
                    :key="item"
                    class="problem-item"
                >
                    <span class="problem-item-icon">
                        <font-awesome-icon icon="fa-solid fa-check" />
                    </span>
                    <span>{{ item }}</span>
                </li>
            </ul>
        </div>

        <div class="highlighted-info-block">
            <div class="flex items-center gap-2">
                <div
                    class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-surface text-accent-darkened shadow-sm"
                >
                    <font-awesome-icon icon="fa-solid fa-route" />
                </div>

                <div>
                    <h3
                        class="text-base font-semibold tracking-tight text-accent-ink"
                    >
                        Как протича процесът
                    </h3>
                    <p class="mt-1 text-sm text-accent-ink/80">
                        Ясно, бързо и без излишно разкарване
                    </p>
                </div>
            </div>

            <ol class="mt-5 space-y-4">
                <li
                    v-for="(step, index) in processSteps"
                    :key="step"
                    class="process-step"
                >
                    <span class="process-step-index">
                        {{ index + 1 }}
                    </span>

                    <div class="process-step-content">
                        <span class="process-step-title">
                            {{ step }}
                        </span>
                    </div>
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
