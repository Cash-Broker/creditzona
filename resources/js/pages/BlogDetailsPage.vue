<template>
    <div class="mx-auto max-w-4xl px-4 py-10 md:py-12">
        <RouterLink
            to="/blog"
            class="inline-flex items-center gap-2 text-sm font-semibold text-accent-darkened transition-colors hover:text-accent-ink"
        >
            <span aria-hidden="true">←</span>
            Към блога
        </RouterLink>

        <div v-if="loading && !post" class="mt-6 animate-pulse space-y-4">
            <div class="h-5 w-1/3 rounded bg-background"></div>
            <div class="h-10 w-4/5 rounded bg-background"></div>
            <div class="h-72 w-full rounded-3xl bg-background"></div>
            <div class="h-4 w-full rounded bg-background"></div>
            <div class="h-4 w-11/12 rounded bg-background"></div>
            <div class="h-4 w-10/12 rounded bg-background"></div>
        </div>

        <div
            v-if="error"
            class="mt-6 rounded-2xl border border-border bg-surface p-6 text-sm text-text-muted"
        >
            <p>{{ error }}</p>
        </div>

        <article v-if="post" class="mt-6">
            <header class="rounded-3xl border border-border bg-surface p-6 shadow-sm sm:p-8">
                <p
                    class="text-xs font-medium uppercase tracking-[0.12em] text-text-subtle"
                >
                    {{ formattedDate }}
                </p>

                <h1
                    class="mt-3 text-3xl font-extrabold tracking-tight text-text md:text-4xl"
                >
                    {{ post.title }}
                </h1>

                <p
                    v-if="post.excerpt"
                    class="mt-4 text-sm leading-7 text-text-muted sm:text-base"
                >
                    {{ post.excerpt }}
                </p>

                <img
                    :src="post.image_path || '/images/credit-consultation.jpg'"
                    :alt="post.title"
                    class="mt-6 aspect-[3/2] w-full rounded-2xl border border-border object-cover"
                    loading="eager"
                    fetchpriority="high"
                    decoding="async"
                />
            </header>

            <section
                class="mt-8 rounded-3xl border border-border bg-surface p-6 shadow-sm sm:p-8"
            >
                <div
                    v-if="contentParagraphs.length"
                    class="space-y-5 text-base leading-8 text-text-muted"
                >
                    <p
                        v-for="(paragraph, index) in contentParagraphs"
                        :key="`${index}-${paragraph.slice(0, 20)}`"
                    >
                        {{ paragraph }}
                    </p>
                </div>

                <p v-else class="text-sm text-text-muted">
                    Зареждане на пълното съдържание...
                </p>
            </section>
        </article>
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { useRoute } from "vue-router";
import { getBlogBySlug, getCachedBlogBySlug } from "@/composables/useBlogStore";
import { applyRouteSeo, buildArticleSchema } from "@/seo";

const route = useRoute();

const post = ref(null);
const loading = ref(true);
const error = ref("");

const formattedDate = computed(() => {
    if (!post.value?.published_at) {
        return "Без дата";
    }

    return new Intl.DateTimeFormat("bg-BG", {
        day: "2-digit",
        month: "long",
        year: "numeric",
    }).format(new Date(post.value.published_at));
});

const contentParagraphs = computed(() => {
    const content = post.value?.content ?? "";

    return content
        .split(/\n\s*\n/)
        .map((paragraph) => paragraph.trim())
        .filter(Boolean);
});

function syncSeo() {
    if (!post.value) {
        if (error.value) {
            applyRouteSeo(route, {
                title: "Статията не беше намерена | Кредит Зона",
                description:
                    "Търсената статия не е налична или вече не е публикувана.",
                robots: "noindex,follow",
                canonical: `${window.location.origin}/blog`,
                breadcrumbs: [
                    { name: "Начало", url: `${window.location.origin}/` },
                    { name: "Блог", url: `${window.location.origin}/blog` },
                ],
                structuredData: [],
            });
        }

        return;
    }

    applyRouteSeo(route, {
        title: `${post.value.title} | Кредит Зона`,
        description:
            post.value.excerpt ||
            "Практична статия от блога на Кредит Зона.",
        canonical: `${window.location.origin}/blog/${post.value.slug}`,
        image:
            post.value.image_path || "/images/credit-consultation.jpg",
        ogType: "article",
        breadcrumbs: [
            { name: "Начало", url: `${window.location.origin}/` },
            { name: "Блог", url: `${window.location.origin}/blog` },
            {
                name: post.value.title,
                url: `${window.location.origin}/blog/${post.value.slug}`,
            },
        ],
        structuredData: [buildArticleSchema(post.value)],
    });
}

async function loadPost(slug) {
    const safeSlug = typeof slug === "string" ? slug.trim() : "";

    if (!safeSlug) {
        error.value = "Невалиден адрес на статия.";
        loading.value = false;
        post.value = null;
        syncSeo();
        return;
    }

    error.value = "";

    const cached = getCachedBlogBySlug(safeSlug);

    if (cached) {
        post.value = cached;
        loading.value = !Boolean(cached.content);
        syncSeo();
    } else {
        post.value = null;
        loading.value = true;
    }

    try {
        const payload = await getBlogBySlug(safeSlug);
        post.value = payload;
        syncSeo();
    } catch (e) {
        if (e?.status === 404) {
            error.value = "Статията не беше намерена или не е публикувана.";
            post.value = null;
            syncSeo();
        } else if (!post.value) {
            error.value =
                "Възникна проблем при зареждането на статията. Моля, опитайте отново след малко.";
        }
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    loadPost(route.params.slug);
});

watch(
    () => route.params.slug,
    (slug) => {
        loadPost(slug);
    },
);
</script>
