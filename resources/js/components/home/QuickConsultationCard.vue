<template>
    <section
        :id="sectionId"
        class="relative left-1/2 w-screen max-w-none -translate-x-1/2 overflow-hidden px-4 py-12 sm:px-6 sm:py-16 lg:px-8 lg:py-20"
    >
        <img
            :src="backgroundImage"
            alt=""
            aria-hidden="true"
            width="1382"
            height="921"
            class="absolute inset-0 h-full w-full scale-105 object-cover object-center"
            :loading="imageLoading"
            :fetchpriority="imageFetchPriority"
            decoding="async"
        />
        <div
            class="absolute inset-0 bg-gradient-to-b from-black/55 via-black/45 to-black/60"
        ></div>

        <div
            class="relative z-10 mx-auto flex min-h-[38rem] w-full max-w-5xl flex-col justify-center sm:min-h-[42rem] lg:min-h-[46rem]"
        >
            <div class="mb-6 text-center">
                <component
                    :is="headingTag"
                    class="mx-auto max-w-4xl text-3xl font-black leading-tight tracking-[-0.03em] text-white drop-shadow-[0_4px_18px_rgba(0,0,0,0.35)] sm:text-4xl lg:text-5xl"
                >
                    Когато другите отказват заради риск, ние намираме работещо
                    решение
                    <span class="mt-2 block text-accent-soft"
                        >за хора с откази, запори и лошо ЦКР</span
                    >
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

                <!-- <p
                    class="mx-auto mt-2 max-w-2xl text-sm text-white/90 sm:text-base"
                >
                    „КРЕДИТ ЗОНА БГ“ е консултанстка фирма, партньор на
                    лицензирани финансови институции. Всички кредити се отпускат от финансови институции.
                </p> -->
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

const backgroundImage = "/images/bg-image.png";

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
