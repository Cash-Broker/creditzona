<template>
    <div class="mx-auto max-w-6xl px-4 py-10 md:py-12">
        <section class="max-w-3xl">
            <span
                class="inline-flex items-center gap-2 rounded-full border border-accent-soft-border bg-accent-soft px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-accent-darkened"
            >
                <font-awesome-icon icon="fa-solid fa-building-columns" />
                За нас
            </span>

            <h1
                class="mt-4 text-4xl font-extrabold tracking-tight text-text md:text-5xl"
            >
                Кредит Зона - консултация, анализ и ясна посока към
                по-подходящо финансово решение
            </h1>

            <p class="mt-5 text-lg leading-8 text-text-muted">
                Ние сме консултантски екип, който работи с хора в различни
                финансови ситуации – нестандартни кредитни казуси, сложни случаи
                с просрочия или запори.
            </p>

            <div class="mt-6 flex flex-wrap gap-3">
                <span
                    v-for="badge in trustBadges"
                    :key="badge"
                    class="inline-flex items-center gap-2 rounded-full border border-border bg-surface px-3 py-2 text-sm text-text-muted"
                >
                    <font-awesome-icon
                        icon="fa-solid fa-circle-check"
                        class="text-accent-darkened"
                    />
                    {{ badge }}
                </span>
            </div>
        </section>

        <section class="mt-12 grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
            <article class="content-section">
                <h2 class="text-2xl font-semibold text-text">Кои сме ние</h2>

                <div class="mt-4 space-y-4 text-sm leading-7 text-text-muted">
                    <p>
                        Кредит Зона предоставя консултации и съдействие при
                        потребителски кредити, ипотечни решения, рефинансиране
                        и преструктуриране на задължения. Работим с фокус върху
                        анализ, прозрачност и реалистични стъпки.
                    </p>
                    <p>
                        Нашата роля е да помогнем да се ориентирате спокойно в
                        процеса и да разберете кои варианти отговарят на
                        текущите ви възможности и дългосрочни цели.
                    </p>
                </div>
            </article>

            <article
                class="rounded-3xl border border-accent-soft-border bg-accent-soft p-6 sm:p-7"
            >
                <h3 class="text-xl font-semibold text-accent-ink">
                    Нашият подход
                </h3>

                <div class="mt-4 space-y-3 text-sm leading-7 text-accent-ink">
                    <p>
                        Сравняваме различни възможности без шаблонни решения и
                        без излишен натиск.
                    </p>
                    <p>
                        Даваме яснота за параметри, рискове и следващи стъпки,
                        за да вземете информирано решение с по-голяма увереност.
                    </p>
                </div>
            </article>
        </section>

        <section class="mt-12">
            <h2 class="text-2xl font-semibold text-text">Какво правим</h2>

            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article
                    v-for="item in whatWeDoItems"
                    :key="item.title"
                    class="rounded-2xl border border-border bg-surface p-5 shadow-sm"
                >
                    <div
                        class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-accent-soft text-accent-darkened"
                    >
                        <font-awesome-icon :icon="item.icon" />
                    </div>

                    <h3 class="mt-4 text-lg font-semibold text-text">
                        {{ item.title }}
                    </h3>

                    <p class="mt-2 text-sm leading-7 text-text-muted">
                        {{ item.text }}
                    </p>
                </article>
            </div>
        </section>

        <section class="mt-12 content-section">
            <h2 class="text-2xl font-semibold text-text">
                Защо да изберете Кредит Зона
            </h2>

            <div class="mt-6 grid gap-4 md:grid-cols-2">
                <article
                    v-for="benefit in benefitItems"
                    :key="benefit.title"
                    class="rounded-2xl border border-border bg-background p-5"
                >
                    <h3 class="text-base font-semibold text-text">
                        {{ benefit.title }}
                    </h3>

                    <p class="mt-2 text-sm leading-7 text-text-muted">
                        {{ benefit.text }}
                    </p>
                </article>
            </div>
        </section>

        <section class="mt-12">
            <h2 class="text-2xl font-semibold text-text">Как работим</h2>

            <div class="mt-6 grid gap-4 md:grid-cols-3">
                <article
                    v-for="(step, index) in processSteps"
                    :key="step.title"
                    class="rounded-3xl border border-border bg-surface p-6 shadow-sm"
                >
                    <div
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-accent-soft text-sm font-semibold text-accent-darkened"
                    >
                        {{ index + 1 }}
                    </div>

                    <h3 class="mt-4 text-lg font-semibold text-text">
                        {{ step.title }}
                    </h3>

                    <p class="mt-2 text-sm leading-7 text-text-muted">
                        {{ step.text }}
                    </p>
                </article>
            </div>
        </section>

        <section class="mt-12 max-w-4xl">
            <h2 class="text-2xl font-semibold text-text">
                Финансова консултация с фокус върху реалните възможности
            </h2>

            <div class="mt-5 space-y-4 text-sm leading-8 text-text-muted">
                <p>
                    Кредит Зона работи като консултант между клиента и
                    финансовия пазар. Целта ни е да улесним избора чрез
                    структуриран анализ на доходи, задължения, срокове и обща
                    устойчивост на бюджета.
                </p>
                <p>
                    При потребителски и ипотечни казуси, както и при
                    рефинансиране, най-важни са ясните параметри и
                    предвидимостта. Именно затова обясняваме условията на
                    разбираем език и очертаваме практични варианти за действие.
                </p>
                <p>
                    Когато случаят е по-сложен, подхождаме поетапно и търсим
                    решение, което може да облекчи месечното натоварване и да
                    създаде основа за по-стабилен кредитен профил във времето.
                </p>
            </div>
        </section>

        <section class="mt-16 grid items-start gap-8 lg:grid-cols-[0.9fr_1.1fr]">
            <div class="content-section">
                <h2 class="text-2xl font-semibold text-text">
                    Готови ли сте за следваща стъпка?
                </h2>

                <p class="mt-4 text-sm leading-7 text-text-muted">
                    Изпратете кратко запитване и ще получите обратна връзка с
                    насоки, съобразени с вашата ситуация.
                </p>

                <ul class="mt-5 space-y-3 text-sm text-secondary">
                    <li
                        v-for="point in ctaPoints"
                        :key="point"
                        class="inline-flex items-center gap-2"
                    >
                        <span
                            class="h-1.5 w-1.5 rounded-full bg-accent-darkened"
                        ></span>
                        {{ point }}
                    </li>
                </ul>
            </div>

            <QuickConsultationCard />
        </section>
    </div>
</template>

<script setup>
import QuickConsultationCard from "@/components/home/QuickConsultationCard.vue";

const trustBadges = [
    "Безпристрастен анализ",
    "Индивидуален подход",
    "Ясни следващи стъпки",
];

const whatWeDoItems = [
    {
        title: "Анализираме случая",
        text: "Преглеждаме финансовия профил, задълженията и възможните варианти за структурирано решение.",
        icon: "fa-solid fa-file-lines",
    },
    {
        title: "Консултираме ясно",
        text: "Обясняваме ключовите параметри, така че да знаете какво следва и какво да очаквате.",
        icon: "fa-solid fa-comments",
    },
    {
        title: "Насочваме към действие",
        text: "Предлагаме конкретни стъпки според вашите цели, срокове и финансова натовареност.",
        icon: "fa-solid fa-arrow-right",
    },
    {
        title: "Проверяваме детайлите",
        text: "Обръщаме внимание на условията, таксите и риска, за да има повече предвидимост.",
        icon: "fa-solid fa-building-columns",
    },
];

const benefitItems = [
    {
        title: "Експертност в реални казуси",
        text: "Работим с широк спектър от случаи, включително по-сложни финансови ситуации.",
    },
    {
        title: "Професионална комуникация",
        text: "Получавате информация в ясен и разбираем формат, без подвеждащи обещания.",
    },
    {
        title: "Фокус върху устойчивост",
        text: "Търсим решения, които могат да се обслужват спокойно в дългосрочен план.",
    },
    {
        title: "Персонална консултация",
        text: "Подхождаме спрямо вашата конкретна ситуация, а не с универсален шаблон.",
    },
];

const processSteps = [
    {
        title: "Оставяте запитване",
        text: "Попълвате кратката форма и описвате какъв тип съдействие търсите.",
    },
    {
        title: "Правим анализ",
        text: "Оценяваме параметрите на случая и обсъждаме реалистичните опции.",
    },
    {
        title: "Получавате насока",
        text: "Изготвяме структурирана посока за следващите стъпки и стабилизиране.",
    },
];

const ctaPoints = [
    "Попълване на формата за под 1 минута",
    "Конфиденциална обработка на данните",
    "Обратна връзка до 24 часа",
];
</script>