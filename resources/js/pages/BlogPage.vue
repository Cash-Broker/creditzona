<template>
    <div class="mx-auto max-w-6xl px-4 py-10 md:py-12">
        <section class="max-w-3xl">
            <span
                class="inline-flex items-center rounded-full border border-accent-soft-border bg-accent-soft px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-accent-darkened"
            >
                Финансов блог
            </span>

            <h1 class="mt-4 text-3xl font-extrabold tracking-tight text-text md:text-4xl">
                Блог
            </h1>

            <p class="mt-4 text-sm leading-7 text-text-muted sm:text-base">
                Практични статии за потребителски и ипотечни кредити,
                рефинансиране, управление на задължения и по-сигурен финансов
                избор.
            </p>
        </section>

        <section class="mt-8 md:mt-10">
            <div v-if="loading" class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <div
                    v-for="n in 6"
                    :key="n"
                    class="animate-pulse overflow-hidden rounded-3xl border border-border bg-surface"
                >
                    <div class="h-52 w-full bg-background"></div>
                    <div class="space-y-3 p-5">
                        <div class="h-3 w-1/3 rounded bg-background"></div>
                        <div class="h-4 w-full rounded bg-background"></div>
                        <div class="h-4 w-4/5 rounded bg-background"></div>
                        <div class="h-3 w-2/5 rounded bg-background"></div>
                    </div>
                </div>
            </div>

            <div
                v-else-if="error"
                class="rounded-2xl border border-border bg-surface p-5 text-sm text-text-muted"
            >
                {{ error }}
            </div>

            <div v-else-if="posts.length" class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <BlogCard
                    v-for="post in posts"
                    :key="post.slug"
                    :post="post"
                />
            </div>

            <div
                v-else
                class="rounded-2xl border border-border bg-surface p-5 text-sm text-text-muted"
            >
                Все още няма публикувани статии.
            </div>
        </section>
    </div>
</template>

<script setup>
import { onMounted, ref } from "vue";
import BlogCard from "@/components/blog/BlogCard.vue";
import {
    getBlogs,
    getCachedBlogs,
    useBlogPosts,
} from "@/composables/useBlogStore";

const posts = useBlogPosts();
const loading = ref(getCachedBlogs().length === 0);
const error = ref("");

async function loadPosts() {
    if (posts.value.length > 0) {
        loading.value = false;
    } else {
        loading.value = true;
    }

    error.value = "";

    try {
        await getBlogs();
    } catch (e) {
        console.error(e);
        error.value =
            "Възникна проблем при зареждането на блога. Моля, опитайте отново след малко.";
    } finally {
        loading.value = false;
    }
}

onMounted(loadPosts);
</script>
