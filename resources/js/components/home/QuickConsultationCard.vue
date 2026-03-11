<template>
    <section
        class="relative overflow-hidden rounded-[32px] bg-cover bg-center bg-no-repeat px-4 py-10 sm:px-6 lg:px-8"
        :style="{ backgroundImage: `url('/images/formBG.jpg')` }"
    >
        <div class="absolute inset-0 bg-black/40"></div>

        <div class="relative z-10 mx-auto w-full max-w-5xl">
            <div class="mb-6 text-center">
                <h1
                    class="mx-auto max-w-4xl text-3xl font-black leading-tight tracking-[-0.03em] text-white drop-shadow-[0_4px_18px_rgba(0,0,0,0.35)] sm:text-4xl lg:text-5xl"
                >
                    Когато другите виждат риск,
                    <span class="text-amber-300">ние търсим решение</span>
                </h1>

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
                <LeadFormCompact horizontal />
            </div>
        </div>
    </section>
</template>

<script setup>
import LeadFormCompact from "./LeadFormCompact.vue";
</script>
