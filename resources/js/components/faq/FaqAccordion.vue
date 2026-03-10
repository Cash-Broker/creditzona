<template>
    <div class="space-y-3">
        <article
            v-for="(item, index) in faqs"
            :key="item.id ?? `${item.question}-${index}`"
            class="overflow-hidden rounded-2xl border border-border bg-surface shadow-sm"
        >
            <button
                type="button"
                class="flex w-full items-start justify-between gap-4 px-5 py-4 text-left transition-colors hover:bg-background sm:px-6"
                :aria-expanded="isOpen(index) ? 'true' : 'false'"
                @click="toggle(index)"
            >
                <span class="text-base font-semibold leading-7 text-text">
                    {{ item.question }}
                </span>

                <span
                    class="mt-1 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-border bg-background text-text-subtle transition-all duration-200"
                    :class="
                        isOpen(index)
                            ? 'rotate-180 border-accent-soft-border bg-accent-soft text-accent-darkened'
                            : ''
                    "
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                        class="h-4 w-4"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.167l3.71-3.938a.75.75 0 0 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </span>
            </button>

            <transition
                enter-active-class="transition-all duration-200 ease-out"
                enter-from-class="max-h-0 opacity-0"
                enter-to-class="max-h-96 opacity-100"
                leave-active-class="transition-all duration-150 ease-in"
                leave-from-class="max-h-96 opacity-100"
                leave-to-class="max-h-0 opacity-0"
            >
                <div
                    v-if="isOpen(index)"
                    class="border-t border-border px-5 pb-5 pt-4 sm:px-6"
                >
                    <p class="text-sm leading-7 text-text-muted">
                        {{ item.answer }}
                    </p>
                </div>
            </transition>
        </article>
    </div>
</template>

<script setup>
import { ref } from "vue";

defineProps({
    faqs: {
        type: Array,
        default: () => [],
    },
});

const openIndex = ref(null);

function toggle(index) {
    openIndex.value = openIndex.value === index ? null : index;
}

function isOpen(index) {
    return openIndex.value === index;
}
</script>
