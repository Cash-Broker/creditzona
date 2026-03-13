import { createRouter, createWebHistory } from "vue-router";
import BlogPage from "@/pages/BlogPage.vue";
import BlogDetailsPage from "@/pages/BlogDetailsPage.vue";
import { applyRouteSeo } from "@/seo";

const routes = [
    {
        path: "/",
        component: () => import("@/pages/HomePage.vue"),
        meta: { seoKey: "home" },
    },
    {
        path: "/about",
        component: () => import("@/pages/AboutPage.vue"),
        meta: { seoKey: "about" },
    },
    {
        path: "/contacts",
        component: () => import("@/pages/ContactPage.vue"),
        meta: { seoKey: "contact" },
    },
    {
        path: "/faq",
        component: () => import("@/pages/FaqPage.vue"),
        meta: { seoKey: "faq" },
    },
    {
        path: "/blog",
        component: BlogPage,
        meta: { seoKey: "blog" },
    },
    {
        path: "/blog/:slug",
        component: BlogDetailsPage,
        meta: { seoKey: "blog_show" },
    },
    {
        path: "/politika-za-poveritelnost",
        component: () => import("@/pages/PrivacyPolicyPage.vue"),
        meta: { seoKey: "privacy_policy" },
    },
    {
        path: "/politika-za-biskvitki",
        component: () => import("@/pages/CookiePolicyPage.vue"),
        meta: { seoKey: "cookie_policy" },
    },
    {
        path: "/obshti-usloviya",
        component: () => import("@/pages/TermsPage.vue"),
        meta: { seoKey: "terms" },
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior(to, from, savedPosition) {
        if (savedPosition) {
            return savedPosition;
        }

        if (to.hash) {
            return {
                el: to.hash,
                top: 96,
                behavior: "smooth",
            };
        }

        return { top: 0 };
    },
});

router.afterEach((to) => {
    applyRouteSeo(to);
});

export default router;
