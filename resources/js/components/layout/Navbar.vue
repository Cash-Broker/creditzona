<template>
    <header class="bg-white shadow sticky top-0 z-50">
        <div
            class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between"
        >
            <RouterLink to="/" class="text-xl font-bold text-yellow-600">
                КредитЗона
            </RouterLink>

            <!-- Desktop navigation -->
            <nav class="hidden lg:flex items-center gap-6 text-sm">
                <RouterLink
                    v-for="item in navItems"
                    :key="item.label"
                    :to="item.url"
                    :class="getLinkClasses(item.url)"
                >
                    {{ item.label }}
                </RouterLink>
            </nav>

            <!-- Mobile burger button -->
            <button
                type="button"
                class="lg:hidden inline-flex items-center justify-center rounded-xl border border-gray-300 p-2 text-gray-700 hover:bg-gray-50 transition"
                @click="isMobileMenuOpen = !isMobileMenuOpen"
                :aria-expanded="isMobileMenuOpen ? 'true' : 'false'"
                aria-label="Отвори менюто"
            >
                <svg
                    v-if="!isMobileMenuOpen"
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-6 w-6"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M4 6h16M4 12h16M4 18h16"
                    />
                </svg>

                <svg
                    v-else
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-6 w-6"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M6 18L18 6M6 6l12 12"
                    />
                </svg>
            </button>
        </div>

        <!-- Mobile navigation -->
        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-2"
        >
            <div
                v-if="isMobileMenuOpen"
                class="lg:hidden border-t border-gray-200 bg-white"
            >
                <nav class="max-w-6xl mx-auto px-4 py-4 flex flex-col gap-2">
                    <RouterLink
                        v-for="item in navItems"
                        :key="`mobile-${item.label}`"
                        :to="item.url"
                        :class="[
                            'rounded-xl px-4 py-3 text-sm transition',
                            isActive(item.url)
                                ? 'bg-yellow-50 text-yellow-700 font-semibold'
                                : 'text-gray-700 hover:bg-gray-50',
                        ]"
                        @click="closeMobileMenu"
                    >
                        {{ item.label }}
                    </RouterLink>
                </nav>
            </div>
        </transition>
    </header>
</template>

<script setup>
import { ref, watch } from "vue";
import { useRoute } from "vue-router";

const route = useRoute();
const isMobileMenuOpen = ref(false);

const navItems = [
    { label: "Потребителски", url: "/potrebitelski-kredit" },
    { label: "Ипотечен", url: "/ipotechen-kredit" },
    { label: "Рефинансиране", url: "/refinansirane" },
    { label: "Изкупуване", url: "/izkupuvane-na-zadalzheniya" },
    { label: "За нас", url: "/za-nas" },
    { label: "FAQ", url: "/chesto-zadavani-vaprosi" },
    { label: "Блог", url: "/blog" },
    { label: "Контакти", url: "/kontakti" },
];

function isActive(url) {
    return route.path === url;
}

function getLinkClasses(url) {
    return [
        "transition-colors hover:text-yellow-600",
        isActive(url) ? "text-yellow-600 font-semibold" : "text-gray-700",
    ];
}

function closeMobileMenu() {
    isMobileMenuOpen.value = false;
}

watch(
    () => route.path,
    () => {
        closeMobileMenu();
    },
);
</script>
