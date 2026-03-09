<template>
    <div class="min-h-screen flex flex-col bg-gray-50">
        <Navbar />

        <main class="flex-1">
            <RouterView />
        </main>

        <Footer />
    </div>
</template>

<script setup>
import Navbar from "./components/layout/Navbar.vue";
import Footer from "./components/layout/Footer.vue";
</script>
