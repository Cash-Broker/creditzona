<template>
    <footer class="mt-20 border-t border-border bg-footer">
        <div class="mx-auto max-w-6xl px-4 py-12">
            <div class="grid gap-8 lg:grid-cols-[1.2fr_0.9fr_0.9fr]">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-text">
                        Кредит Зона
                    </h2>

                    <p class="mt-4 max-w-md text-sm leading-7 text-text-muted">
                        Консултации и съдействие при потребителски, ипотечни и
                        рефинансиращи решения. Насочваме към подходяща кредитна
                        оферта според конкретния случай.
                    </p>
                </div>

                <nav aria-label="Навигация във footer">
                    <h3 class="text-sm font-semibold text-text">Навигация</h3>

                    <ul class="mt-4 space-y-2.5">
                        <li v-for="link in navigationLinks" :key="link.label">
                            <RouterLink
                                :to="link.to"
                                class="text-sm text-text-muted transition-colors hover:text-accent-darkened"
                            >
                                {{ link.label }}
                            </RouterLink>
                        </li>
                    </ul>
                </nav>

                <div>
                    <h3 class="text-sm font-semibold text-text">
                        Бърза информация
                    </h3>

                    <ul class="mt-4 space-y-2.5">
                        <li
                            v-for="item in trustItems"
                            :key="item"
                            class="inline-flex items-center gap-2 text-sm text-text-muted"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full bg-accent-darkened"
                            ></span>
                            {{ item }}
                        </li>
                    </ul>
                </div>
            </div>

            <div
                class="mt-10 rounded-3xl border border-accent-soft-border bg-accent-soft p-6 sm:p-7"
            >
                <h3
                    class="text-sm font-semibold uppercase tracking-[0.12em] text-accent-darkened"
                >
                    Представителен пример
                </h3>

                <div class="mt-4 space-y-4 text-sm leading-7 text-accent-ink">
                    <p>
                        При отпуснат „мостов“ заем от 5 000 евро за срок от 4
                        месеца, общият размер на лихвата за периода на ползване
                        на кредита е 380,55 евро.
                    </p>

                    <p>
                        При коректно изпълнение на всички задължения по
                        договора, общата дължима сума за погасяване на
                        задължението е 5 380,55 евро, а ГПР – 42,58%.
                    </p>

                    <p>
                        Минималният срок на отпуснатия кредит е 4 месеца, а
                        максималният – 36 месеца. Максималният размер на ГПР е
                        49%.
                    </p>
                </div>
            </div>

            <div
                class="mt-8 flex flex-col gap-3 border-t border-border pt-5 sm:flex-row sm:items-center sm:justify-between"
            >
                <p class="text-xs text-text-subtle">© Кредит Зона</p>

                <nav
                    class="flex flex-wrap items-center gap-x-5 gap-y-2"
                    aria-label="Правни връзки"
                >
                    <RouterLink
                        v-for="link in legalLinks"
                        :key="link.label"
                        :to="link.to"
                        class="text-xs text-text-subtle transition-colors hover:text-accent-darkened"
                    >
                        {{ link.label }}
                    </RouterLink>
                </nav>
            </div>
        </div>
    </footer>
</template>

<script setup>
const navigationLinks = [
    { label: "Начало", to: "/" },
    { label: "Потребителски кредит", to: "/potrebitelski-kredit" },
    { label: "Ипотечен кредит", to: "/ipotechen-kredit" },
    { label: "Рефинансиране", to: "/refinansirane" },
    { label: "Контакти", to: "/contacts" },
];

const trustItems = [
    "Пловдив и онлайн",
    "Безплатна първоначална консултация",
    "Отговор до 24 часа",
];

const legalLinks = [
    { label: "Политика за поверителност", to: "/contacts#poveritelnost" },
    { label: "Условия", to: "/contacts#usloviya" },
    { label: "Контакти", to: "/contacts" },
];
</script>
