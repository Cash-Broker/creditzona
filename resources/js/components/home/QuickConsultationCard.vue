<template>
    <section
        :id="sectionId"
        class="relative left-1/2 w-screen -translate-x-1/2 overflow-hidden rounded-[32px] px-4 py-10 sm:px-6 lg:px-8"
    >
        <img
            :src="backgroundImage"
            alt=""
            aria-hidden="true"
            width="1382"
            height="921"
            class="absolute inset-0 h-full w-full object-cover"
            :loading="imageLoading"
            :fetchpriority="imageFetchPriority"
            decoding="async"
        />
        <div class="absolute inset-0 bg-black/40"></div>

        <div class="relative z-10 mx-auto w-full max-w-5xl">
            <div class="mb-6 text-center">
                <component
                    :is="headingTag"
                    class="mx-auto max-w-4xl text-3xl font-black leading-tight tracking-[-0.03em] text-white drop-shadow-[0_4px_18px_rgba(0,0,0,0.35)] sm:text-4xl lg:text-5xl"
                >
                    Когато другите ви виждат риск,
                    <span class="text-accent-soft">ние търсим решение</span>
                </component>

                <div
                    class="mt-5 inline-flex items-center gap-2 rounded-full bg-white/90 px-4 py-1.5 text-xs font-semibold text-accent-ink shadow-sm"
                >
                    <font-awesome-icon icon="fa-solid fa-comments" />
                    <span>Безплатна консултация</span>
                </div>

                <h2 class="mt-4 text-xl font-bold text-white sm:text-2xl">
                    Попълни формата
                </h2>

                <p
                    class="mx-auto mt-2 max-w-2xl text-sm text-white/90 sm:text-base"
                >
                    Изберете сума и оставете данните си, за да се свържем с вас
                    до 48ч.
                </p>
            </div>

            <div
                class="rounded-[28px] border border-white/70 bg-white/95 p-5 shadow-[0_20px_60px_rgba(15,23,42,0.22)] backdrop-blur"
            >
                <LeadFormCompact
                    horizontal
                    :initial-credit-type="initialCreditType"
                    :lock-credit-type="lockCreditType"
                />
            </div>
        </div>
    </section>
</template>

<script setup>
import LeadFormCompact from "./LeadFormCompact.vue";

const backgroundImage = "/images/formBG.jpg";

defineProps({
    initialCreditType: {
        type: String,
        default: "",
    },
    lockCreditType: {
        type: Boolean,
        default: false,
    },
    headingTag: {
        type: String,
        default: "h2",
    },
    imageLoading: {
        type: String,
        default: "lazy",
    },
    imageFetchPriority: {
        type: String,
        default: "auto",
    },
    sectionId: {
        type: String,
        default: "consultation-form",
    },
});
</script>
