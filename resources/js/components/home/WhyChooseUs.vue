<template>
    <section class="mt-20 grid gap-10 lg:grid-cols-2 items-center">
        <div>
            <img
                :src="imageUrl"
                alt="Кредитна консултация"
                loading="lazy"
                class="w-full h-auto rounded-3xl shadow-md"
            />
        </div>

        <div>
            <h2 class="text-3xl font-bold text-text">
                Защо да изберете Кредит Зона
            </h2>

            <p class="mt-4 text-text-muted leading-7">
                Сложи край на множеството бързи кредити и нарастващата
                задлъжнялост. Ще ти помогнем да обединиш задълженията си в един
                кредит с една месечна вноска и по-ясна финансова структура.
            </p>

            <h3 class="mt-6 font-semibold text-text">Какво е необходимо</h3>

            <ul class="mt-3 space-y-2 text-text-muted">
                <li>✔ постоянен трудов договор за последните 6 месеца</li>
                <li>✔ без просрочия над 4 месеца</li>
                <li>✔ без активни запори от ЧСИ</li>
            </ul>

            <h3 class="mt-6 font-semibold text-text">Как ще ти помогнем</h3>

            <ul class="mt-3 space-y-2 text-text-muted">
                <li>✔ погасяване на всички бързи кредити</li>
                <li>✔ подобряване на кредитния профил</li>
                <li>✔ възможност за банков кредит с по-ниска лихва</li>
            </ul>

            <p class="mt-6 font-semibold text-accent-darkened">
                Без предварителни такси и комисионни.
            </p>
        </div>
    </section>
</template>

<script setup>
const imageUrl = "/images/credit-consultation.jpg";
</script>
