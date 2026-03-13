import { getSeoConfig } from "@/utils/appConfig";

function cloneSchema(schema) {
    return schema ? JSON.parse(JSON.stringify(schema)) : null;
}

function upsertMeta(attribute, key, content) {
    const selector = `meta[${attribute}="${key}"]`;
    let element = document.head.querySelector(selector);

    if (!content) {
        element?.remove();
        return;
    }

    if (!element) {
        element = document.createElement("meta");
        element.setAttribute(attribute, key);
        element.dataset.seoManaged = "true";
        document.head.appendChild(element);
    }

    element.setAttribute("content", content);
}

function upsertCanonical(href) {
    let element = document.head.querySelector('link[rel="canonical"]');

    if (!href) {
        element?.remove();
        return;
    }

    if (!element) {
        element = document.createElement("link");
        element.setAttribute("rel", "canonical");
        element.dataset.seoManaged = "true";
        document.head.appendChild(element);
    }

    element.setAttribute("href", href);
}

function replaceStructuredData(schemas) {
    document.head
        .querySelectorAll("script[data-seo-schema='true']")
        .forEach((node) => node.remove());

    schemas.forEach((schema) => {
        if (!schema || typeof schema !== "object") {
            return;
        }

        const script = document.createElement("script");
        script.type = "application/ld+json";
        script.dataset.seoSchema = "true";
        script.textContent = JSON.stringify(schema);
        document.head.appendChild(script);
    });
}

function toAbsoluteUrl(path) {
    try {
        return new URL(path, window.location.origin).toString();
    } catch {
        return window.location.origin;
    }
}

export function buildBreadcrumbSchema(breadcrumbs) {
    return {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        itemListElement: breadcrumbs.map((breadcrumb, index) => ({
            "@type": "ListItem",
            position: index + 1,
            name: breadcrumb.name,
            item: breadcrumb.url,
        })),
    };
}

export function buildFaqSchema(faqs) {
    return {
        "@context": "https://schema.org",
        "@type": "FAQPage",
        mainEntity: faqs
            .filter((faq) => faq?.question && faq?.answer)
            .map((faq) => ({
                "@type": "Question",
                name: faq.question,
                acceptedAnswer: {
                    "@type": "Answer",
                    text: faq.answer,
                },
            })),
    };
}

export function buildArticleSchema(post) {
    const seoConfig = getSeoConfig();
    const site = seoConfig.site ?? {};
    const canonical = toAbsoluteUrl(`/blog/${encodeURIComponent(post.slug)}`);

    return {
        "@context": "https://schema.org",
        "@type": "BlogPosting",
        "@id": `${canonical}#article`,
        headline: post.title,
        description:
            post.excerpt ||
            "Практична статия от блога на Кредит Зона.",
        mainEntityOfPage: canonical,
        datePublished: post.published_at || undefined,
        image: post.image_path
            ? toAbsoluteUrl(post.image_path)
            : site.defaultImage,
        author: {
            "@type": "Organization",
            name: site.name,
        },
        publisher: {
            "@type": "Organization",
            name: site.name,
            logo: {
                "@type": "ImageObject",
                url:
                    site.schemas?.organization?.logo ||
                    site.defaultImage,
            },
        },
    };
}

export function applyRouteSeo(route, overrides = {}) {
    const seoConfig = getSeoConfig();
    const site = seoConfig.site ?? {};
    const pages = seoConfig.pages ?? {};
    const pageKey = route?.meta?.seoKey ?? "home";
    const basePage = pages[pageKey] ?? {};

    const page = {
        ...basePage,
        ...overrides,
    };

    const title = page.title || site.defaultTitle || document.title;
    const description =
        page.description || site.defaultDescription || "";
    const robots = page.robots || "index,follow";
    const canonical =
        page.canonical ||
        toAbsoluteUrl(route?.fullPath || window.location.pathname);
    const image = page.image
        ? toAbsoluteUrl(page.image)
        : site.defaultImage;
    const ogType = page.ogType || "website";

    document.title = title;
    upsertMeta("name", "description", description);
    upsertMeta(
        "name",
        "keywords",
        Array.isArray(page.keywords) && page.keywords.length
            ? page.keywords.join(", ")
            : "",
    );
    upsertMeta("name", "robots", robots);
    upsertCanonical(canonical);

    upsertMeta("property", "og:locale", "bg_BG");
    upsertMeta("property", "og:type", ogType);
    upsertMeta("property", "og:site_name", site.name || "");
    upsertMeta("property", "og:url", canonical);
    upsertMeta("property", "og:title", title);
    upsertMeta("property", "og:description", description);
    upsertMeta("property", "og:image", image || "");
    upsertMeta("property", "og:image:alt", site.name || "");

    upsertMeta(
        "name",
        "twitter:card",
        site.twitterCard || "summary_large_image",
    );
    upsertMeta("name", "twitter:title", title);
    upsertMeta("name", "twitter:description", description);
    upsertMeta("name", "twitter:image", image || "");

    const schemas = [
        cloneSchema(site.schemas?.organization),
        cloneSchema(site.schemas?.website),
    ].filter(Boolean);

    if (Array.isArray(page.breadcrumbs) && page.breadcrumbs.length) {
        schemas.push(buildBreadcrumbSchema(page.breadcrumbs));
    }

    if (Array.isArray(overrides.structuredData)) {
        schemas.push(...overrides.structuredData.filter(Boolean));
    }

    replaceStructuredData(schemas);
}
