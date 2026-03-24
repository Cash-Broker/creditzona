<template>
    <div class="max-w-6xl mx-auto px-4 py-10">
        <section class="max-w-3xl">
            <span
                class="inline-flex items-center rounded-full border border-accent-soft-border bg-accent-soft px-3 py-1 text-xs font-semibold text-accent-darkened"
            >
                Финансиране с поръчител
            </span>

            <h1 class="mt-4 text-4xl font-bold tracking-tight text-text">
                Потребителски кредит – консултация и съдействие
            </h1>

            <p class="mt-5 text-lg leading-8 text-text-muted">
                Насочваме ви към подходяща кредитна оферта според доходите,
                текущите задължения и конкретния случай, с фокус върху яснота,
                спокойствие и реално изпълними стъпки.
            </p>

            <div class="mt-6 flex flex-wrap gap-3 text-sm text-text-muted">
                <span
                    class="inline-flex items-center gap-2 rounded-full bg-surface px-3 py-2 border border-border"
                >
                    ✔ Без предварителни такси
                </span>
                <span
                    class="inline-flex items-center gap-2 rounded-full bg-surface px-3 py-2 border border-border"
                >
                    ✔ Индивидуален подход
                </span>
                <span
                    class="inline-flex items-center gap-2 rounded-full bg-surface px-3 py-2 border border-border"
                >
                    ✔ Ясни следващи стъпки
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
                <ul class="mt-4 space-y-3 text-text-muted leading-7">
                    <li>• Хора, които търсят финансиране за лични нужди</li>
                    <li>• Клиенти с активни бързи кредити</li>
                    <li>
                        • Хора, които искат по-добри условия по текущи
                        задължения
                    </li>
                    <li>• Случаи, при които е нужна консултация и насочване</li>
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
                        <strong class="text-text">2.</strong> Анализираме
                        ситуацията
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
                <div class="rounded-2xl bg-background p-4 border border-border">
                    Висока месечна вноска по няколко кредита
                </div>
                <div class="rounded-2xl bg-background p-4 border border-border">
                    Нужда от по-добра структура на задълженията
                </div>
                <div class="rounded-2xl bg-background p-4 border border-border">
                    Нестандартни или труднодоказуеми доходи
                </div>
                <div class="rounded-2xl bg-background p-4 border border-border">
                    Нужда от консултация преди кандидатстване
                </div>
            </div>
        </section>

        <section class="mt-12 max-w-4xl">
            <h2 class="text-2xl font-semibold text-text">
                Какво е важно да знаете
            </h2>

            <div class="mt-5 space-y-4 text-text-muted leading-8">
                <p>
                    Потребителският кредит може да бъде подходящо решение при
                    нужда от средства за лични разходи, реорганизация на текущи
                    задължения или по-ясно планиране на бюджета. Най-важното е
                    офертата да бъде съобразена с реалните възможности на
                    клиента, а не само с желаната сума.
                </p>

                <p>
                    Именно затова консултацията и предварителният анализ са
                    толкова важни. При по-сложни случаи – например множество
                    активни кредити, по-висока месечна натовареност или
                    нестандартни доходи – правилната финансова структура може да
                    направи голяма разлика.
                </p>

                <p>
                    Нашата роля е да помогнем в ориентацията, подготовката и
                    следващите стъпки, така че решението да е по-ясно,
                    по-устойчиво и по-подходящо за конкретния случай.
                </p>
            </div>
        </section>

        <section class="mt-16">
            <QuickConsultationCard
                initial-credit-type="consumer_with_guarantor"
                :lock-credit-type="true"
            />
        </section>
    </div>
</template>

<script setup>
import QuickConsultationCard from "@/components/home/QuickConsultationCard.vue";
</script>
