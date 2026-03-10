<template>
    <div class="max-w-4xl mx-auto px-4 py-10">
        <h1
            class="text-3xl md:text-4xl font-extrabold tracking-tight text-text"
        >
            Често задавани въпроси
        </h1>

        <div class="mt-8 space-y-6">
            <div
                v-for="item in faqItems"
                :key="item.question"
                class="bg-surface border rounded-2xl p-6 shadow-sm"
            >
                <h2 class="text-xl font-bold text-text">
                    {{ item.question }}
                </h2>

                <p class="mt-2 text-text-muted leading-7">
                    {{ item.answer }}
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
const faqItems = [
    {
        question: "Отпускате ли кредити?",
        answer: "Не. Ние консултираме и посредничим.",
    },
    {
        question: "Защо избирате ЕГН?",
        answer: "За предварителна оценка и коректно настроено съотношение.",
    },
];
</script>

