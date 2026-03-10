import { createRouter, createWebHistory } from "vue-router";
import BlogPage from "@/pages/BlogPage.vue";
import BlogDetailsPage from "@/pages/BlogDetailsPage.vue";

const routes = [
    {
        path: "/",
        component: () => import("@/pages/HomePage.vue"),
    },
    {
        path: "/about",
        component: () => import("@/pages/AboutPage.vue"),
    },
    {
        path: "/contacts",
        component: () => import("@/pages/ContactPage.vue"),
    },
    {
        path: "/faq",
        alias: ["/chesto-zadavani-vaprosi"],
        component: () => import("@/pages/FaqPage.vue"),
    },
    {
        path: "/blog",
        component: BlogPage,
    },
    {
        path: "/blog/:slug",
        component: BlogDetailsPage,
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
