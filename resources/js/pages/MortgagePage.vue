<template>
    <div class="max-w-6xl mx-auto px-4 py-10">
        <section class="max-w-3xl">
            <span
                class="inline-flex items-center rounded-full border border-accent-soft-border bg-accent-soft px-3 py-1 text-xs font-semibold text-accent-darkened"
            >
                Ипотечен кредит
            </span>

            <h1 class="mt-4 text-4xl font-bold tracking-tight text-text">
                Ипотечен кредит – консултация и съдействие
            </h1>

            <p class="mt-5 text-lg leading-8 text-text-muted">
                Насочваме ви към подходящо решение при ипотечно финансиране,
                съобразено с имота, нужната сума и конкретния ви случай.
            </p>

            <div class="mt-6 flex flex-wrap gap-3 text-sm text-text-muted">
                <span
                    class="inline-flex items-center gap-2 rounded-full border border-border bg-surface px-3 py-2"
                >
                    ✔ Индивидуален подход
                </span>
                <span
                    class="inline-flex items-center gap-2 rounded-full border border-border bg-surface px-3 py-2"
                >
                    ✔ Ясни следващи стъпки
                </span>
                <span
                    class="inline-flex items-center gap-2 rounded-full border border-border bg-surface px-3 py-2"
                >
                    ✔ Без излишно мотане
                </span>
            </div>
        </section>

        <section class="mt-12 grid gap-6 md:grid-cols-2">
            <div
                class="rounded-3xl border border-border bg-surface p-6 shadow-sm"
            >
                <h2 class="text-xl font-semibold text-text">
                    За кого е подходящо
                </h2>

                <ul class="mt-4 space-y-3 leading-7 text-text-muted">
                    <li>• Хора, които търсят финансиране срещу имот</li>
                    <li>• Клиенти с нужда от по-голяма сума</li>
                    <li>• Случаи, при които е нужен по-дълъг срок</li>
                    <li>
                        • Хора, които искат консултация за по-подходяща
                        структура на кредита
                    </li>
                </ul>
            </div>

            <div
                class="rounded-3xl border border-border bg-surface p-6 shadow-sm"
            >
                <h2 class="text-xl font-semibold text-text">
                    Как протича процесът
                </h2>

                <div class="mt-4 space-y-4 text-text-muted">
                    <div>
                        <strong class="text-text">1.</strong> Оставяте запитване
                    </div>
                    <div>
                        <strong class="text-text">2.</strong> Анализираме случая
                        и параметрите
                    </div>
                    <div>
                        <strong class="text-text">3.</strong> Насочваме ви към
                        подходящо решение
                    </div>
                </div>
            </div>
        </section>

        <section
            class="mt-12 rounded-3xl border border-border bg-surface p-6 shadow-sm"
        >
            <h2 class="text-2xl font-semibold text-text">
                Чести ситуации, при които можем да съдействаме
            </h2>

            <div class="mt-6 grid gap-4 md:grid-cols-2">
                <div class="rounded-2xl border border-border bg-background p-4">
                    Нужда от по-голяма сума срещу недвижим имот
                </div>

                <div class="rounded-2xl border border-border bg-background p-4">
                    Търсене на по-добри условия по текущо финансиране
                </div>

                <div class="rounded-2xl border border-border bg-background p-4">
                    Нужда от по-дълъг срок и по-ясна месечна вноска
                </div>

                <div class="rounded-2xl border border-border bg-background p-4">
                    Сложен случай, при който е нужна предварителна консултация
                </div>
            </div>
        </section>

        <section class="mt-12 max-w-4xl">
            <h2 class="text-2xl font-semibold text-text">
                Какво е важно да знаете
            </h2>

            <div class="mt-5 space-y-4 leading-8 text-text-muted">
                <p>
                    Ипотечният кредит е решение, което обикновено се използва
                    при нужда от по-голяма сума и по-дълъг срок на изплащане.
                    Най-често той е свързан с предоставяне на обезпечение чрез
                    недвижим имот, което позволява различна структура на
                    условията в сравнение с потребителския кредит.
                </p>

                <p>
                    При такъв тип финансиране е важно да се направи внимателна
                    предварителна оценка на възможностите, месечното натоварване
                    и общата устойчивост на бъдещото плащане. Яснотата в
                    параметрите и добрата подготовка на документите имат голямо
                    значение за следващите стъпки.
                </p>

                <p>
                    Нашата роля е да съдействаме с ориентация, консултация и
                    подготовка, така че решението да бъде по-ясно, по-спокойно и
                    по-подходящо за конкретния случай.
                </p>
            </div>
        </section>

        <section
            class="mt-12 rounded-3xl border border-accent-soft-border bg-accent-soft p-6 shadow-sm"
        >
            <h2 class="text-2xl font-semibold text-accent-ink">Важен акцент</h2>

            <p class="mt-4 leading-8 text-accent-ink">
                При ипотечно финансиране правилната консултация още в началото
                може да спести време, излишни откази и неподходящи оферти.
                Именно затова първоначалният анализ на случая е толкова важен.
            </p>
        </section>

        <section class="mt-16">
            <QuickConsultationCard
                initial-credit-type="mortgage"
                :lock-credit-type="true"
            />
        </section>
    </div>
</template>

<script setup>
import QuickConsultationCard from "@/components/home/QuickConsultationCard.vue";
</script>
