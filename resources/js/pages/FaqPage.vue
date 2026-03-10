<template>
    <div class="mx-auto max-w-5xl px-4 py-10 md:py-12">
        <section class="max-w-3xl">
            <span
                class="inline-flex items-center rounded-full border border-accent-soft-border bg-accent-soft px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-accent-darkened"
            >
                Полезна информация
            </span>

            <h1
                class="mt-4 text-3xl font-extrabold tracking-tight text-text md:text-4xl"
            >
                Често задавани въпроси
            </h1>

            <p class="mt-4 text-sm leading-7 text-text-muted sm:text-base">
                Събрахме най-честите въпроси за кредити, рефинансиране и
                финансова консултация. Ако не намирате вашия въпрос, свържете
                се с нас за персонална насока.
            </p>
        </section>

        <section class="mt-8 md:mt-10">
            <div v-if="loading" class="space-y-3">
                <div
                    v-for="n in 4"
                    :key="n"
                    class="animate-pulse rounded-2xl border border-border bg-surface p-6"
                >
                    <div class="h-4 w-2/3 rounded bg-background"></div>
                    <div class="mt-3 h-3 w-full rounded bg-background"></div>
                    <div class="mt-2 h-3 w-5/6 rounded bg-background"></div>
                </div>
            </div>

            <div
                v-else-if="error"
                class="rounded-2xl border border-border bg-surface p-5 text-sm text-text-muted"
            >
                {{ error }}
            </div>

            <FaqAccordion v-else-if="faqs.length" :faqs="faqs" />

            <div
                v-else
                class="rounded-2xl border border-border bg-surface p-5 text-sm text-text-muted"
            >
                В момента няма публикувани въпроси.
            </div>
        </section>
    </div>
</template>

<script setup>
import { onMounted, ref } from "vue";
import FaqAccordion from "@/components/faq/FaqAccordion.vue";

const faqs = ref([]);
const loading = ref(true);
const error = ref("");

async function loadFaqs() {
    loading.value = true;
    error.value = "";

    try {
        const response = await fetch("/api/faqs", {
            headers: {
                Accept: "application/json",
            },
        });

        if (!response.ok) {
            throw new Error("Неуспешно зареждане на често задаваните въпроси.");
        }

        const payload = await response.json();
        faqs.value = Array.isArray(payload) ? payload : [];
    } catch (e) {
        console.error(e);
        error.value =
            "Възникна проблем при зареждането. Моля, опитайте отново след малко.";
    } finally {
        loading.value = false;
    }
}

onMounted(loadFaqs);
</script>
