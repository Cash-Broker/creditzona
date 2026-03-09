<template>
    <div class="bg-white border rounded-2xl shadow-sm p-6">
        <h2 class="text-xl font-bold">Безплатна консултация</h2>

        <p class="text-gray-600 mt-1">Оставете данни и ще се свържем с вас.</p>

        <LeadFormCompact />

        <div class="mt-4 text-xs text-gray-500">
            С изпращане на формата потвърждавате, че данните ще се използват
            само за целите на консултацията.
        </div>
    </div>
</template>

<script setup>
import LeadFormCompact from "./LeadFormCompact.vue";
</script>
