import { ref } from "vue";
import { getInitialData } from "@/utils/appConfig";

const posts = ref([]);
const postsLastFetchedAt = ref(0);
const postDetails = new Map();

let postsRequest = null;
const detailRequests = new Map();

const CACHE_TTL_MS = 5 * 60 * 1000;

function isCacheFresh(timestamp) {
    return Number.isFinite(timestamp) && Date.now() - timestamp < CACHE_TTL_MS;
}

function normalizeArray(payload) {
    return Array.isArray(payload) ? payload : [];
}

function seedInitialData() {
    const initialPosts = normalizeArray(getInitialData("blogs", []));
    const initialBlogPost = getInitialData("blogPost", null);

    if (initialPosts.length > 0) {
        posts.value = initialPosts;
        postsLastFetchedAt.value = Date.now();

        for (const post of initialPosts) {
            if (post?.slug) {
                postDetails.set(post.slug, {
                    data: post,
                    fetchedAt: postsLastFetchedAt.value,
                });
            }
        }
    }

    if (initialBlogPost?.slug) {
        postDetails.set(initialBlogPost.slug, {
            data: initialBlogPost,
            fetchedAt: Date.now(),
        });

        const existingIndex = posts.value.findIndex(
            (post) => post?.slug === initialBlogPost.slug,
        );

        if (existingIndex >= 0) {
            posts.value[existingIndex] = {
                ...posts.value[existingIndex],
                ...initialBlogPost,
            };
        } else {
            posts.value.unshift(initialBlogPost);
        }
    }
}

async function fetchJson(url) {
    const response = await fetch(url, {
        headers: {
            Accept: "application/json",
        },
    });

    if (!response.ok) {
        const error = new Error(`HTTP ${response.status}`);
        error.status = response.status;
        throw error;
    }

    return response.json();
}

export function getCachedBlogs() {
    return posts.value;
}

export function useBlogPosts() {
    return posts;
}

export async function getBlogs({ force = false } = {}) {
    if (
        !force &&
        posts.value.length > 0 &&
        isCacheFresh(postsLastFetchedAt.value)
    ) {
        return posts.value;
    }

    if (!force && postsRequest) {
        return postsRequest;
    }

    postsRequest = fetchJson("/api/blogs")
        .then((payload) => {
            posts.value = normalizeArray(payload);
            postsLastFetchedAt.value = Date.now();

            for (const post of posts.value) {
                if (post?.slug) {
                    postDetails.set(post.slug, {
                        data: post,
                        fetchedAt: postsLastFetchedAt.value,
                    });
                }
            }

            return posts.value;
        })
        .finally(() => {
            postsRequest = null;
        });

    return postsRequest;
}

export function getCachedBlogBySlug(slug) {
    if (typeof slug !== "string" || !slug.trim()) {
        return null;
    }

    const cached = postDetails.get(slug);

    if (cached && isCacheFresh(cached.fetchedAt)) {
        return cached.data;
    }

    return null;
}

export async function getBlogBySlug(slug, { force = false } = {}) {
    if (typeof slug !== "string" || !slug.trim()) {
        const error = new Error("Invalid blog slug");
        error.status = 400;
        throw error;
    }

    if (!force) {
        const cached = getCachedBlogBySlug(slug);

        if (cached?.content) {
            return cached;
        }
    }

    if (!force && detailRequests.has(slug)) {
        return detailRequests.get(slug);
    }

    const request = fetchJson(`/api/blogs/${encodeURIComponent(slug)}`)
        .then((payload) => {
            postDetails.set(slug, {
                data: payload,
                fetchedAt: Date.now(),
            });

            const index = posts.value.findIndex((post) => post?.slug === slug);

            if (index >= 0) {
                posts.value[index] = {
                    ...posts.value[index],
                    ...payload,
                };
            } else {
                posts.value.unshift(payload);
            }

            return payload;
        })
        .finally(() => {
            detailRequests.delete(slug);
        });

    detailRequests.set(slug, request);

    return request;
}

export function prefetchBlogBySlug(slug) {
    if (typeof slug !== "string" || !slug.trim()) {
        return;
    }

    const cached = getCachedBlogBySlug(slug);
    if (cached?.content || detailRequests.has(slug)) {
        return;
    }

    getBlogBySlug(slug).catch(() => {
        // Ignore prefetch errors.
    });
}

seedInitialData();
