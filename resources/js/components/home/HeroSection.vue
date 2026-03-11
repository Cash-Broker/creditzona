<template>
    <section class="relative pt-8 lg:pt-12">
        <div class="absolute inset-0 -z-10 pointer-events-none">
            <div
                class="absolute top-0 left-0 h-40 w-40 rounded-full bg-accent-soft blur-3xl opacity-70"
            ></div>
            <div
                class="absolute right-10 top-16 h-56 w-56 rounded-full bg-accent-soft blur-3xl opacity-40"
            ></div>
        </div>

        <!-- badge -->
        <div
            class="inline-flex items-center gap-2 rounded-full border border-accent-soft-border bg-accent-soft px-4 py-2 text-sm text-accent-ink shadow-sm"
        >
            <font-awesome-icon
                icon="fa-solid fa-circle-check"
                class="text-accent-darkened"
            />
            <span class="font-semibold">Кредитни консултанти</span>
            <span class="text-accent-darkened">•</span>
            <span>Пловдив и онлайн</span>
        </div>

        <!-- heading -->
        <h1
            class="mt-6 max-w-2xl text-4xl font-extrabold leading-tight tracking-tight text-text md:text-5xl xl:text-6xl"
        >
            По-бързо към
            <span class="text-accent-darkened">правилната кредитна оферта</span>
        </h1>

        <!-- description -->
        <p class="mt-5 max-w-xl text-lg leading-8 text-text-muted">
            Насочваме ви към най-подходящата кредитна оферта според профила ви и
            помагаме с документите и следващите стъпки.
            <span class="font-medium text-secondary">
                Не отпускaме кредити директно.
            </span>
        </p>

        <!-- cta buttons -->
        <div class="mt-8 flex flex-wrap gap-3">
            <RouterLink
                v-for="action in actions"
                :key="action.label"
                :to="action.url"
                :class="action.class"
            >
                <span>{{ action.label }}</span>

                <font-awesome-icon
                    v-if="action.primary"
                    icon="fa-solid fa-arrow-right"
                    class="text-sm"
                />
            </RouterLink>
        </div>

        <!-- trust line -->
        <div
            class="mt-5 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-text-subtle"
        >
            <div class="inline-flex items-center gap-2">
                <font-awesome-icon
                    icon="fa-solid fa-circle-check"
                    class="text-accent-darkened"
                />
                <span>Безплатна първоначална консултация</span>
            </div>

            <div class="inline-flex items-center gap-2">
                <font-awesome-icon
                    icon="fa-solid fa-circle-check"
                    class="text-accent-darkened"
                />
                <span>Ясни стъпки без излишно мотане</span>
            </div>
        </div>

        <!-- stats -->
        <div class="mt-10 grid gap-4 sm:grid-cols-3">
            <div
                v-for="stat in stats"
                :key="stat.label"
                class="group rounded-2xl border bg-surface p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
            >
                <div
                    class="mb-3 inline-flex h-10 w-10 items-center justify-center rounded-xl bg-accent-soft text-accent-darkened"
                >
                    <font-awesome-icon :icon="stat.icon" />
                </div>

                <div class="text-sm text-text-subtle">
                    {{ stat.label }}
                </div>

                <div class="mt-1 text-lg font-semibold text-text">
                    {{ stat.value }}
                </div>
            </div>
        </div>

        <!-- note -->
        <p class="mt-4 text-xs text-text-subtle">
            * Срокът зависи от конкретния случай, наличните документи и
            обратната връзка.
        </p>
    </section>
</template>

<script setup>
const stats = [
    {
        label: "Отговор",
        value: "до 24 часа",
        icon: "fa-solid fa-phone",
    },
    {
        label: "Процес",
        value: "ясни стъпки",
        icon: "fa-solid fa-file-lines",
    },
    {
        label: "Консултация",
        value: "по телефон и форма",
        icon: "fa-solid fa-comments",
    },
];

const actions = [
    {
        label: "Потребителски кредит",
        url: "/potrebitelski-kredit",
        primary: true,
        class: "inline-flex items-center justify-center gap-2 rounded-xl bg-accent px-6 py-3 font-semibold text-surface shadow-sm transition hover:bg-accent-hover hover:shadow-md",
    },
    {
        label: "Ипотечен кредит",
        url: "/ipotechen-kredit",
        primary: false,
        class: "inline-flex items-center justify-center gap-2 rounded-xl border bg-surface px-6 py-3 font-semibold text-text transition hover:bg-background hover:shadow-sm",
    },
    {
        label: "Контакти",
        url: "/contacts",
        primary: false,
        class: "inline-flex items-center justify-center gap-2 rounded-xl border bg-surface px-6 py-3 font-semibold text-text transition hover:bg-background hover:shadow-sm",
    },
];
</script>


