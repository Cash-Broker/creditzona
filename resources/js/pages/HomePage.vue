<template>
    <div class="max-w-6xl mx-auto px-4 py-10">
        <!-- <HeroSection /> -->

        <QuickConsultationCard class="mt-5 relative z-20" />

        <div class="mt-12">
            <LeadInfo />
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
