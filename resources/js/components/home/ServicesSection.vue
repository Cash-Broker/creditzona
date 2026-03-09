<template>
    <div>
        <h2 class="text-2xl font-bold mb-6">Услуги</h2>

        <div class="grid md:grid-cols-3 gap-6">
            <a
                v-for="service in services"
                :key="service.title"
                :href="service.url"
                class="bg-white border rounded-2xl p-6 hover:shadow-sm transition"
            >
                <h3 class="text-lg font-semibold">{{ service.title }}</h3>
                <p class="text-gray-600 mt-2">{{ service.description }}</p>
                <div class="mt-4 text-yellow-700 font-semibold">
                    Виж повече →
                </div>
            </a>
        </div>
    </div>
</template>

<script setup>
const routes = window.laravelRoutes || {};

const services = [
    {
        title: "Потребителски кредит",
        description: "Бърза консултация и насочване.",
        url: routes.consumer || "/service/consumer",
    },
    {
        title: "Ипотечен кредит",
        description: "Път през документите и процеса.",
        url: routes.mortgage || "/service/mortgage",
    },
    {
        title: "Рефинансиране",
        description: "Опит за по-добри условия.",
        url: routes.refinance || "/service/refinance",
    },
];
</script>
