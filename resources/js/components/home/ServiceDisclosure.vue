<template>
    <section
        :id="sectionId"
        class="highlighted-info-block relative overflow-hidden"
        aria-labelledby="service-disclosure-title"
    >
        <div
            class="pointer-events-none absolute right-0 top-0 h-32 w-32 translate-x-10 -translate-y-10 rounded-full bg-surface/60 blur-2xl"
            aria-hidden="true"
        ></div>

        <div class="relative z-10">
            <div class="flex items-start gap-4">
                <span
                    class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-surface text-accent-darkened shadow-sm"
                    aria-hidden="true"
                >
                    <font-awesome-icon icon="fa-solid fa-file-lines" />
                </span>

                <div>
                    <div
                        class="inline-flex items-center gap-2 rounded-full bg-surface/70 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-accent-darkened"
                    >
                        {{ serviceDisclosure.eyebrow }}
                    </div>

                    <component
                        :is="headingTag"
                        id="service-disclosure-title"
                        class="mt-3 max-w-2xl text-xl font-bold leading-tight tracking-tight text-accent-ink sm:text-2xl"
                    >
                        {{ serviceDisclosure.title }}
                    </component>
                </div>
            </div>

            <ul class="mt-5 grid gap-3 sm:grid-cols-2">
                <li
                    v-for="point in serviceDisclosure.points"
                    :key="point"
                    class="flex items-start gap-3 rounded-2xl border border-accent-soft-border bg-surface/80 px-4 py-3"
                >
                    <span
                        class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-accent-soft text-xs text-accent-darkened"
                        aria-hidden="true"
                    >
                        <font-awesome-icon icon="fa-solid fa-check" />
                    </span>

                    <span class="text-sm leading-6 text-accent-ink">
                        {{ point }}
                    </span>
                </li>
            </ul>

            <p
                class="mt-4 rounded-2xl border border-accent-soft-border bg-surface/70 px-4 py-3 text-sm leading-6 text-text-muted"
            >
                {{ serviceDisclosure.note }}
            </p>

            <p
                v-if="serviceDisclosure.registrationNote"
                class="mt-3 text-xs leading-6 text-text-subtle"
            >
                {{ serviceDisclosure.registrationNote }}
            </p>
        </div>
    </section>
</template>

<script setup>
import { serviceDisclosure } from "@/data/serviceDisclosure";

defineProps({
    headingTag: {
        type: String,
        default: "h2",
    },
    sectionId: {
        type: String,
        default: "service-disclosure",
    },
});
</script>
