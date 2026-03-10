<template>
    <article
        class="flex h-full flex-col overflow-hidden rounded-3xl border border-border bg-surface shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-border-strong hover:shadow-md"
        @mouseenter="prefetchDetails"
        @focusin="prefetchDetails"
    >
        <RouterLink :to="`/blog/${post.slug}`" class="block overflow-hidden">
            <img
                :src="post.image_path || '/images/credit-consultation.jpg'"
                :alt="post.title"
                class="h-52 w-full object-cover transition-transform duration-300 hover:scale-[1.02]"
                loading="lazy"
            />
        </RouterLink>

        <div class="flex flex-1 flex-col p-5">
            <p
                class="text-xs font-medium uppercase tracking-[0.12em] text-text-subtle"
            >
                {{ formattedDate }}
            </p>

            <h2 class="mt-3 text-xl font-semibold leading-8 text-text">
                <RouterLink
                    :to="`/blog/${post.slug}`"
                    class="transition-colors hover:text-accent-darkened"
                >
                    {{ post.title }}
                </RouterLink>
            </h2>

            <p class="mt-3 flex-1 text-sm leading-7 text-text-muted">
                {{ post.excerpt || fallbackExcerpt }}
            </p>

            <RouterLink
                :to="`/blog/${post.slug}`"
                class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-accent-darkened transition-transform duration-200 hover:translate-x-0.5"
            >
                Прочети повече
                <span aria-hidden="true">→</span>
            </RouterLink>
        </div>
    </article>
</template>

<script setup>
import { computed } from "vue";
import { prefetchBlogBySlug } from "@/composables/useBlogStore";

const props = defineProps({
    post: {
        type: Object,
        required: true,
    },
});

const fallbackExcerpt =
    "Полезна статия с практични насоки за по-уверен избор на финансово решение.";

const formattedDate = computed(() => {
    if (!props.post?.published_at) {
        return "Без дата";
    }

    return new Intl.DateTimeFormat("bg-BG", {
        day: "2-digit",
        month: "long",
        year: "numeric",
    }).format(new Date(props.post.published_at));
});

function prefetchDetails() {
    prefetchBlogBySlug(props.post?.slug);
}
</script>
