<template>
    <div class="min-h-screen flex flex-col bg-background">
        <Navbar />

        <main class="flex-1">
            <RouterView />
        </main>

        <Footer />
        <CookieConsentManager />
    </div>
</template>

<script setup>
import { onMounted } from "vue";
import Navbar from "./components/layout/Navbar.vue";
import Footer from "./components/layout/Footer.vue";
import CookieConsentManager from "./components/layout/CookieConsentManager.vue";
import { getBlogs } from "@/composables/useBlogStore";

onMounted(() => {
    // Warm the blog cache in the background to reduce first navigation latency.
    getBlogs().catch(() => {
        // Ignore prefetch errors to keep app boot non-blocking.
    });
});
</script>
