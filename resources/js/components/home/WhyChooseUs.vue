<template>
    <section class="mt-24 grid items-center gap-12 lg:grid-cols-2">
        <!-- Image -->
        <div class="relative">
            <div
                class="absolute -inset-6 rounded-[40px] bg-accent-soft blur-2xl opacity-40"
            ></div>

            <img
                :src="imageUrl"
                alt="Кредитна консултация"
                loading="lazy"
                class="relative w-full rounded-[32px] border border-border shadow-[0_20px_60px_-30px_rgba(17,24,39,0.45)]"
            />
        </div>

        <!-- Content -->
        <div class="max-w-xl">
            <span
                class="inline-flex items-center rounded-full border border-accent-soft-border bg-accent-soft px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-accent-darkened"
            >
                Кредитна консултация
            </span>

            <h2
                class="mt-4 text-3xl font-bold tracking-tight text-text md:text-4xl"
            >
                Защо да изберете Кредит Зона
            </h2>

            <p class="mt-5 text-[15px] leading-7 text-text-muted">
                Сложи край на множеството бързи кредити и нарастващата
                задлъжнялост. Ще ти помогнем да обединиш задълженията си в един
                кредит с една месечна вноска и по-ясна финансова структура.
            </p>

            <!-- Requirements -->
            <div class="mt-8">
                <h3
                    class="text-sm font-semibold uppercase tracking-[0.12em] text-text"
                >
                    Какво е необходимо
                </h3>

                <ul class="mt-4 space-y-3">
                    <li
                        v-for="item in requirements"
                        :key="item"
                        class="flex items-start gap-3 text-sm text-text-muted"
                    >
                        <span
                            class="mt-0.5 inline-flex h-6 w-6 items-center justify-center rounded-full bg-accent-soft text-accent-darkened"
                        >
                            <font-awesome-icon
                                icon="fa-solid fa-check"
                                class="text-xs"
                            />
                        </span>

                        {{ item }}
                    </li>
                </ul>
            </div>

            <!-- Benefits -->
            <div class="mt-8">
                <h3
                    class="text-sm font-semibold uppercase tracking-[0.12em] text-text"
                >
                    Как ще ти помогнем
                </h3>

                <ul class="mt-4 space-y-3">
                    <li
                        v-for="item in benefits"
                        :key="item"
                        class="flex items-start gap-3 text-sm text-text-muted"
                    >
                        <span
                            class="mt-0.5 inline-flex h-6 w-6 items-center justify-center rounded-full bg-accent-soft text-accent-darkened"
                        >
                            <font-awesome-icon
                                icon="fa-solid fa-check"
                                class="text-xs"
                            />
                        </span>

                        {{ item }}
                    </li>
                </ul>
            </div>

            <!-- Highlight -->
            <div
                class="mt-8 rounded-2xl border border-accent-soft-border bg-accent-soft px-5 py-4 text-sm font-semibold text-accent-ink"
            >
                Без предварителни такси и комисионни.
            </div>
        </div>
    </section>
</template>

<script setup>
const imageUrl = "/images/credit-consultation.jpg";

const requirements = [
    "постоянен трудов договор за последните 6 месеца",
    "без просрочия над 4 месеца",
    "без активни запори от ЧСИ",
];

const benefits = [
    "погасяване на всички бързи кредити",
    "подобряване на кредитния профил",
    "възможност за банков кредит с по-ниска лихва",
];
</script>
