<template>
    <div class="max-w-6xl mx-auto px-4 py-10">
        <div class="grid lg:grid-cols-2 gap-10 items-start">
            <HeroSection />
            <QuickConsultationCard />
        </div>

        <ServicesSection class="mt-16" />
    </div>
</template>

<script setup>
import HeroSection from "@/components/home/HeroSection.vue";
import QuickConsultationCard from "@/components/home/QuickConsultationCard.vue";
import ServicesSection from "@/components/home/ServicesSection.vue";
</script>
