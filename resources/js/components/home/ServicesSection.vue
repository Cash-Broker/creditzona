<template>
    <section class="space-y-8 md:space-y-10">
        <div class="max-w-3xl">
            <span
                class="inline-flex items-center rounded-full border border-accent-soft-border bg-accent-soft px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-accent-darkened"
            >
                Финансови решения
            </span>

            <h2
                class="mt-4 text-2xl font-bold tracking-tight text-text md:text-3xl"
            >
                Услуги
            </h2>

            <p class="mt-3 max-w-2xl text-sm leading-7 text-text-muted">
                Консултираме и структурираме решения за хора и семейства в
                период на финансово напрежение, с фокус върху яснота, контрол и
                стабилност.
            </p>
        </div>

        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            <RouterLink
                v-for="(service, index) in services"
                :key="service.title"
                :to="service.url"
                class="service-card group"
            >
                <div
                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-accent-soft-border bg-accent-soft text-xs font-semibold text-accent-darkened transition group-hover:scale-105"
                >
                    {{ String(index + 1).padStart(2, "0") }}
                </div>

                <h3 class="service-card-title mt-4">
                    {{ service.title }}
                </h3>

                <p class="service-card-text mt-3">
                    {{ service.description }}
                </p>

                <span
                    class="service-card-cta mt-5 inline-flex items-center gap-2"
                >
                    Виж повече
                    <font-awesome-icon
                        icon="fa-solid fa-arrow-right"
                        class="text-xs transition-transform duration-200 group-hover:translate-x-1"
                    />
                </span>
            </RouterLink>
        </div>

        <div class="content-section">
            <h3 class="text-2xl font-bold tracking-tight text-text">
                Когато другите виждат риск, ние търсим решение
            </h3>

            <div class="mt-5 space-y-4 text-sm leading-7 text-text-muted">
                <p>
                    Влошена кредитна история. Запори от ЧСИ. Нестандартни или
                    труднодоказуеми доходи. Това са обстоятелства – не присъда.
                </p>

                <p>
                    С правилна финансова структура и участието на надежден
                    поръчител може да бъде изградено решение, което да намали
                    месечното натоварване и да даде възможност за стабилизиране
                    на бюджета.
                </p>

                <p>
                    Поръчител може да бъде роднина или близък човек с чиста
                    кредитна история и доказуем доход, включително пенсионер до
                    72 години.
                </p>
            </div>
        </div>

        <div class="highlighted-info-block">
            <div
                class="inline-flex items-center rounded-full border border-accent-soft-border bg-surface px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-accent-darkened"
            >
                Примерен сценарий
            </div>

            <h3 class="mt-4 text-2xl font-bold tracking-tight text-accent-ink">
                Пример за преструктуриране
            </h3>

            <div class="mt-5 space-y-4 text-sm leading-7 text-accent-ink">
                <p>
                    Ако в момента обслужваш задължения в размер на около 10 000
                    евро, е възможно месечното ти натоварване да достига или
                    надхвърля 1 000 евро, разпределени по няколко различни
                    кредита.
                </p>

                <p>
                    При консолидиране на задълженията със съдействие на
                    поръчител, месечната вноска може да бъде приблизително 125
                    евро* при договорени условия и ясно разписан план за
                    погасяване.
                </p>
            </div>

            <ul class="mt-6 grid gap-3 sm:grid-cols-2">
                <li
                    class="inline-flex items-center gap-2 rounded-xl bg-surface/70 px-3 py-2 text-sm text-accent-ink"
                >
                    <span
                        class="h-1.5 w-1.5 rounded-full bg-accent-darkened"
                    ></span>
                    по-ниско месечно напрежение
                </li>
                <li
                    class="inline-flex items-center gap-2 rounded-xl bg-surface/70 px-3 py-2 text-sm text-accent-ink"
                >
                    <span
                        class="h-1.5 w-1.5 rounded-full bg-accent-darkened"
                    ></span>
                    единна структура на плащане
                </li>
                <li
                    class="inline-flex items-center gap-2 rounded-xl bg-surface/70 px-3 py-2 text-sm text-accent-ink"
                >
                    <span
                        class="h-1.5 w-1.5 rounded-full bg-accent-darkened"
                    ></span>
                    предвидимост
                </li>
                <li
                    class="inline-flex items-center gap-2 rounded-xl bg-surface/70 px-3 py-2 text-sm text-accent-ink"
                >
                    <span
                        class="h-1.5 w-1.5 rounded-full bg-accent-darkened"
                    ></span>
                    възможност за възстановяване на кредитния профил
                </li>
            </ul>

            <p class="mt-5 text-xs leading-6 text-accent-ink/80">
                * Посочените стойности са примерни и зависят от конкретния
                случай, профила на клиента и договорените условия.
            </p>
        </div>

        <div class="content-section">
            <h3 class="text-2xl font-bold tracking-tight text-text">
                Временна подкрепа, дългосрочна стабилност
            </h3>

            <div class="mt-5 space-y-4 text-sm leading-7 text-text-muted">
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
    },
    {
        title: "Бързи потребителски кредити",
        description:
            "Финансово решение при нужда от бърз достъп до средства и професионална консултация.",
        url: normalizeRoutePath(routes.consumer, "/potrebitelski-kredit"),
    },
    {
        title: "Кредити срещу имот",
        description:
            "Възможности за финансиране срещу имот с индивидуален подход и ясни условия.",
        url: normalizeRoutePath(routes.mortgage, "/ipotechen-kredit"),
    },
    {
        title: "Изкупуване на задължения",
        description:
            "Решения при натрупани кредити, просрочия и затруднено обслужване на плащанията.",
        url: normalizeRoutePath(
            routes.debtPurchase,
            "/izkupuvane-na-zadalzheniya",
        ),
    },
    {
        title: "Изкупуване на недвижими имоти",
        description:
            "Вариант за бърза ликвидност при нужда от продажба на имот в кратки срокове.",
        url: normalizeRoutePath(routes.propertyPurchase, "/izkupuvane-na-imoti"),
    },
];
</script>
