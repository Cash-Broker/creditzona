<template>
    <div class="bg-surface border rounded-2xl shadow-sm p-6">
        <h2 class="text-xl font-bold">Безплатна консултация</h2>

        <p class="text-text-muted mt-1">Оставете данни и ще се свържем с вас.</p>

        <LeadFormCompact />

        <div class="mt-4 text-xs text-text-subtle">
            С изпращане на формата потвърждавате, че данните ви ще бъдат обработени за целите на консултацията.
        </div>
    </div>
</template>

<script setup>
import LeadFormCompact from "./LeadFormCompact.vue";
</script>

