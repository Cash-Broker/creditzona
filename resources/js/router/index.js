import { createRouter, createWebHistory } from "vue-router";

const routes = [
    {
        path: "/",
        component: () => import("@/pages/HomePage.vue"),
    },
    {
        path: "/za-nas",
        component: () => import("@/pages/AboutPage.vue"),
    },
    {
        path: "/kontakti",
        component: () => import("@/pages/ContactPage.vue"),
    },
    {
        path: "/chesto-zadavani-vaprosi",
        component: () => import("@/pages/FaqPage.vue"),
    },
    {
        path: "/blog",
        component: () => import("@/pages/BlogPage.vue"),
    },
    {
        path: "/potrebitelski-kredit",
        component: () => import("@/pages/ConsumerPage.vue"),
    },
    {
        path: "/ipotechen-kredit",
        component: () => import("@/pages/MortgagePage.vue"),
    },
    {
        path: "/refinansirane",
        component: () => import("@/pages/RefinancePage.vue"),
    },
    {
        path: "/izkupuvane-na-zadalzheniya",
        component: () => import("@/pages/DebtBuyoutPage.vue"),
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior() {
        return { top: 0 };
    },
});

export default router;
