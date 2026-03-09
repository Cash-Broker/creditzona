<template>
    <div class="max-w-4xl mx-auto px-4 py-10">
        <h1
            class="text-3xl md:text-4xl font-extrabold tracking-tight text-gray-900"
        >
            Контакти
        </h1>

        <div class="mt-8 bg-white border rounded-2xl p-6 shadow-sm space-y-4">
            <p class="text-gray-700">
                Телефон:
                <a
                    :href="`tel:${phone}`"
                    class="font-semibold text-yellow-700 hover:underline"
                >
                    {{ phone }}
                </a>
            </p>

            <p class="text-gray-700">
                Имейл:
                <a
                    :href="`mailto:${email}`"
                    class="font-semibold text-yellow-700 hover:underline"
                >
                    {{ email }}
                </a>
            </p>
        </div>
    </div>
</template>

<script setup>
const phone = "+359000000000";
const email = "office@creditzona.bg";
</script>
