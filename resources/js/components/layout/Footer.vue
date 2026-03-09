<template>
    <footer class="bg-white border-t mt-20">
        <div class="max-w-6xl mx-auto px-4 py-8 text-sm text-gray-600">
            <p class="mb-2">
                Ние сме кредитни консултанти/посредници и не отпускaме кредити
                директно.
            </p>

            <p>© {{ year }} КредитЗона</p>
        </div>
    </footer>
</template>

<script setup>
const year = new Date().getFullYear();
</script>
