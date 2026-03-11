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
                Консултираме и структурираме решения за хора и семейства в
                период на финансово напрежение, с фокус върху яснота, контрол и
                стабилност.
            </p>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <RouterLink
                v-for="service in services"
                :key="service.title"
                :to="service.url"
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
                        Виж повече
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
            </RouterLink>
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
                    Когато другите виждат риск, ние търсим решение
                </h3>

                <div
                    class="mt-5 space-y-4 text-sm leading-7 text-text-muted md:text-[15px]"
                >
                    <p>
                        Влошена кредитна история. Запори от ЧСИ. Нестандартни
                        или труднодоказуеми доходи. Това са обстоятелства – не
                        присъда.
                    </p>

                    <p>
                        С правилна финансова структура и участието на надежден
                        поръчител може да бъде изградено решение, което да
                        намали месечното натоварване и да даде възможност за
                        стабилизиране на бюджета.
                    </p>

                    <p>
                        Поръчител може да бъде роднина или близък човек с чиста
                        кредитна история и доказуем доход, включително пенсионер
                        до 72 години.
                    </p>
                </div>
            </div>
        </div>

        <div
            class="rounded-[32px] border border-accent-soft-border bg-accent-soft p-6 shadow-[0_10px_30px_-18px_rgba(17,24,39,0.18)] sm:p-7"
        >
            <div
                class="inline-flex items-center rounded-full border border-accent-soft-border bg-surface px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-accent-darkened"
            >
                Примерен сценарий
            </div>

            <h3
                class="mt-4 text-2xl font-bold tracking-tight text-accent-ink md:text-3xl"
            >
                Пример за преструктуриране
            </h3>

            <div class="mt-5 grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
                <div
                    class="space-y-4 text-sm leading-7 text-accent-ink md:text-[15px]"
                >
                    <p>
                        Ако в момента обслужваш задължения в размер на около 10
                        000 евро, е възможно месечното ти натоварване да достига
                        или надхвърля 1 000 евро, разпределени по няколко
                        различни кредита.
                    </p>

                    <p>
                        При консолидиране на задълженията със съдействие на
                        поръчител, месечната вноска може да бъде приблизително
                        125 евро* при договорени условия и ясно разписан план за
                        погасяване.
                    </p>
                </div>

                <div class="grid gap-3">
                    <div
                        class="rounded-2xl border border-white/70 bg-surface/80 p-4 shadow-sm"
                    >
                        <div
                            class="text-xs font-semibold uppercase tracking-[0.12em] text-accent-ink/70"
                        >
                            Текущо натоварване
                        </div>
                        <div
                            class="mt-2 text-3xl font-black tracking-tight text-accent-ink"
                        >
                            ~ 1 000 €
                        </div>
                    </div>

                    <div
                        class="rounded-2xl border border-accent-soft-border bg-surface p-4 shadow-sm"
                    >
                        <div
                            class="text-xs font-semibold uppercase tracking-[0.12em] text-accent-darkened"
                        >
                            Примерна нова вноска
                        </div>
                        <div
                            class="mt-2 text-3xl font-black tracking-tight text-accent-darkened"
                        >
                            ~ 125 €
                        </div>
                    </div>
                </div>
            </div>

            <ul class="mt-6 grid gap-3 sm:grid-cols-2">
                <li
                    v-for="benefit in benefits"
                    :key="benefit"
                    class="inline-flex items-center gap-3 rounded-2xl bg-surface/70 px-4 py-3 text-sm font-medium text-accent-ink"
                >
                    <span
                        class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-surface text-xs text-accent-darkened"
                    >
                        <font-awesome-icon icon="fa-solid fa-check" />
                    </span>
                    {{ benefit }}
                </li>
            </ul>

            <p class="mt-5 text-xs leading-6 text-accent-ink/80">
                * Посочените стойности са примерни и зависят от конкретния
                случай, профила на клиента и договорените условия.
            </p>
        </div>

        <div class="content-section">
            <span
                class="inline-flex items-center rounded-full border border-accent-soft-border bg-accent-soft px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-accent-darkened"
            >
                Дългосрочен подход
            </span>

            <h3 class="mt-4 text-2xl font-bold tracking-tight text-text">
                Временна подкрепа, дългосрочна стабилност
            </h3>

            <div
                class="mt-5 space-y-4 text-sm leading-7 text-text-muted md:text-[15px]"
            >
                <p>
                    В много случаи участието на поръчителя е необходимо само в
                    началния период – обикновено до около една година, а
                    понякога и значително по-кратко.
                </p>

                <p>
                    След подобряване на финансовите показатели съществува
                    възможност за рефинансиране без поръчител.
                </p>

                <p>
                    Нашият подход не е „бързо решение“. Той е структурирана
                    финансова стратегия, насочена към устойчивост и реално
                    подобрение на кредитния профил.
                </p>
            </div>
        </div>
    </section>
</template>

<script setup>
const routes = window.laravelRoutes || {};

function normalizeRoutePath(url, fallback = "/") {
    const safeFallback =
        typeof fallback === "string" && fallback.trim() ? fallback : "/";

    if (typeof url !== "string") {
        return safeFallback;
    }

    const value = url.trim();

    if (!value) {
        return safeFallback;
    }

    try {
        if (/^https?:\/\//i.test(value)) {
            const parsed = new URL(value);
            const normalizedPath = `${parsed.pathname || ""}${parsed.search || ""}${parsed.hash || ""}`;

            return normalizedPath || safeFallback;
        }

        if (/^\/\//.test(value)) {
            const parsed = new URL(`${window.location.protocol}${value}`);
            const normalizedPath = `${parsed.pathname || ""}${parsed.search || ""}${parsed.hash || ""}`;

            return normalizedPath || safeFallback;
        }

        if (/^[a-z][a-z\d+\-.]*:/i.test(value)) {
            return safeFallback;
        }

        return value;
    } catch {
        return safeFallback;
    }
}

const services = [
    {
        title: "Рефинансиране и обединяване на задължения",
        description:
            "Обединете текущите си задължения в една по-ясна и по-лека месечна вноска.",
        url: normalizeRoutePath(routes.refinance, "/refinansirane"),
        icon: "fa-solid fa-file-lines",
    },
    {
        title: "Ипотечен кредит",
        description:
            "Финансиране срещу имот с индивидуален подход и ясно структурирани условия.",
        url: normalizeRoutePath(routes.mortgage, "/ipotechen-kredit"),
        icon: "fa-solid fa-house",
    },
];

const benefits = [
    "по-ниско месечно напрежение",
    "единна структура на плащане",
    "предвидимост",
    "възможност за възстановяване на кредитния профил",
];
</script>
