import { createRouter, createWebHistory } from "vue-router";
import BlogPage from "@/pages/BlogPage.vue";
import BlogDetailsPage from "@/pages/BlogDetailsPage.vue";

const siteName = document.title.includes("|")
    ? document.title.split("|").pop().trim()
    : document.title;

const routes = [
    {
        path: "/",
        component: () => import("@/pages/HomePage.vue"),
        meta: { title: "Кредит Зона" },
    },
    {
        path: "/about",
        component: () => import("@/pages/AboutPage.vue"),
        meta: { title: "За нас" },
    },
    {
        path: "/contacts",
        component: () => import("@/pages/ContactPage.vue"),
        meta: { title: "Контакти" },
    },
    {
        path: "/faq",
        alias: ["/chesto-zadavani-vaprosi"],
        component: () => import("@/pages/FaqPage.vue"),
        meta: { title: "Често задавани въпроси" },
    },
    {
        path: "/blog",
        component: BlogPage,
        meta: { title: "Блог" },
    },
    {
        path: "/blog/:slug",
        component: BlogDetailsPage,
        meta: { title: "Блог" },
    },
    {
        path: "/politika-za-poveritelnost",
        component: () => import("@/pages/PrivacyPolicyPage.vue"),
        meta: { title: "Политика за поверителност" },
    },
    {
        path: "/politika-za-biskvitki",
        component: () => import("@/pages/CookiePolicyPage.vue"),
        meta: { title: "Политика за бисквитки" },
    },
    {
        path: "/obshti-usloviya",
        component: () => import("@/pages/TermsPage.vue"),
        meta: { title: "Общи условия" },
    },
    // {
    //     path: "/potrebitelski-kredit",
    //     component: () => import("@/pages/ConsumerPage.vue"),
    // },
    // {
    //     path: "/ipotechen-kredit",
    //     component: () => import("@/pages/MortgagePage.vue"),
    // },
    // {
    //     path: "/refinansirane",
    //     component: () => import("@/pages/RefinancePage.vue"),
    // },
    // {
    //     path: "/izkupuvane-na-zadalzheniya",
    //     component: () => import("@/pages/DebtBuyoutPage.vue"),
    // },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior() {
        return { top: 0 };
    },
});

router.afterEach((to) => {
    document.title = to.meta?.title
        ? `${to.meta.title} | ${siteName}`
        : siteName;
});

export default router;
