<template>
    <section class="space-y-10 md:space-y-12">
        <div class="max-w-3xl">
            <span
                class="inline-flex items-center rounded-full border border-accent-soft-border bg-accent-soft px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-accent-darkened"
            >
                Финансови решения
            </span>

            <h2
                class="mt-4 text-3xl font-bold tracking-tight text-text md:text-4xl"
            >
                Услуги
            </h2>

            <p
                class="mt-4 max-w-2xl text-sm leading-7 text-text-muted md:text-base"
            >
                Консултираме и структурираме решения за хора и семейства с
                фокус върху яснота, контрол и устойчивост при избора на кредит.
            </p>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <article
                v-for="service in services"
                :key="service.title"
                class="group rounded-[28px] border border-border bg-surface p-6 shadow-[0_10px_30px_-18px_rgba(17,24,39,0.35)] transition-all duration-300 hover:-translate-y-1 hover:border-border-strong hover:shadow-[0_18px_40px_-20px_rgba(17,24,39,0.45)]"
            >
                <div class="flex items-start justify-between gap-4">
                    <div
                        class="inline-flex h-12 w-12 items-center justify-center rounded-2xl border border-accent-soft-border bg-accent-soft text-accent-darkened shadow-sm"
                    >
                        <font-awesome-icon :icon="service.icon" />
                    </div>

                    <span
                        class="inline-flex rounded-full bg-background px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-text-subtle"
                    >
                        Услуга
                    </span>
                </div>

                <h3
                    class="mt-6 text-xl font-bold leading-7 tracking-tight text-text"
                >
                    {{ service.title }}
                </h3>

                <p class="mt-3 text-sm leading-7 text-text-muted">
                    {{ service.description }}
                </p>

                <div class="mt-6 flex items-center justify-between">
                    <span
                        class="inline-flex items-center gap-2 text-sm font-semibold text-accent-darkened"
                    >
                        Подход при консултация
                    </span>

                    <span
                        class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-accent-soft text-accent-darkened transition-transform duration-300 group-hover:translate-x-1"
                    >
                        <font-awesome-icon
                            icon="fa-solid fa-arrow-right"
                            class="text-sm"
                        />
                    </span>
                </div>
            </article>
        </div>

        <div
            class="relative overflow-hidden rounded-[32px] border border-border bg-surface p-6 shadow-[0_10px_30px_-18px_rgba(17,24,39,0.35)] sm:p-7"
        >
            <div
                class="absolute right-0 top-0 h-28 w-28 translate-x-8 -translate-y-8 rounded-full bg-accent-soft blur-2xl"
            ></div>

            <div class="relative z-10 max-w-3xl">
                <span
                    class="inline-flex items-center rounded-full border border-accent-soft-border bg-accent-soft px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-accent-darkened"
                >
                    Ключово послание
                </span>

                <h3
                    class="mt-4 text-2xl font-bold tracking-tight text-text md:text-3xl"
                >
                    Когато другите виждат риск, ние търсим работещо решение
                </h3>

                <div
                    class="mt-5 space-y-4 text-sm leading-7 text-text-muted md:text-[15px]"
                >
                    <p>
                        Влошена кредитна история, запори, множество активни
                        кредити или трудно доказуем доход не означават, че
                        няма възможност за адекватна посока.
                    </p>

                    <p>
                        Нашата роля е да подредим ситуацията, да обясним
                        параметрите спокойно и да очертаем следващите стъпки
                        според конкретния казус.
                    </p>

                    <p>
                        При нужда от по-добра структура на бюджета, търсим
                        решение, което да намали месечното натоварване и да
                        даде по-ясна предвидимост.
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
const services = [
    {
        title: "Анализ на текущата ситуация",
        description:
            "Разглеждаме доходи, ангажименти, кредитна история и реалната месечна тежест преди да очертаем посока.",
        icon: "fa-solid fa-file-lines",
    },
    {
        title: "Преглед на оферти и условия",
        description:
            "Подреждаме условията по офертите на разбираем език, така че да знаете какво е важно за вашия случай.",
        icon: "fa-solid fa-house",
    },
    {
        title: "Подготовка на документи",
        description:
            "Помагаме да подготвите информацията и документите, които обичайно се изискват в процеса.",
        icon: "fa-solid fa-building-columns",
    },
    {
        title: "План за следващи стъпки",
        description:
            "Получавате по-ясен план как да продължите след консултацията според конкретната ситуация.",
        icon: "fa-solid fa-check",
    },
];
</script>
