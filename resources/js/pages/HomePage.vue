<template>
    <div class="max-w-6xl mx-auto px-4 py-10">
        <QuickConsultationCard
            class="mt-5 relative z-20"
            heading-tag="h1"
            image-loading="eager"
            image-fetch-priority="high"
        />

        <ServicesSection class="mt-20" />

        <div class="mt-12">
            <LeadInfo />
        </div>

        <WhyChooseUs />
    </div>
</template>

<script setup>
import LeadInfo from "@/components/home/LeadInfo.vue";
import QuickConsultationCard from "@/components/home/QuickConsultationCard.vue";
import ServicesSection from "@/components/home/ServicesSection.vue";
import WhyChooseUs from "@/components/home/WhyChooseUs.vue";
</script>
