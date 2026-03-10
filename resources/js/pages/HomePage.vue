<template>
    <div class="max-w-6xl mx-auto px-4 py-10">
        <HeroSection />

        <div class="mt-12 grid items-start gap-8 lg:grid-cols-[1fr_1fr]">
            <LeadInfo />
            <QuickConsultationCard />
        </div>

        <ServicesSection class="mt-20" />

        <WhyChooseUs />
    </div>
</template>

<script setup>
import HeroSection from "@/components/home/HeroSection.vue";
import LeadInfo from "@/components/home/LeadInfo.vue";
import QuickConsultationCard from "@/components/home/QuickConsultationCard.vue";
import ServicesSection from "@/components/home/ServicesSection.vue";
import WhyChooseUs from "@/components/home/WhyChooseUs.vue";
</script>
