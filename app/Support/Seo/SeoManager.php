<?php

namespace App\Support\Seo;

use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class SeoManager
{
    /**
     * @param  array<string, mixed>  $overrides
     */
    public function forPage(string $pageKey, Request $request, array $overrides = []): SeoData
    {
        /** @var array<string, mixed> $page */
        $page = config("seo.pages.{$pageKey}", []);

        $title = $this->formatTitle((string) ($overrides['title'] ?? $page['title'] ?? config('seo.site.default_title')));
        $description = (string) ($overrides['description'] ?? $page['description'] ?? config('seo.site.default_description'));
        $canonical = (string) ($overrides['canonical'] ?? $this->resolveCanonical($page, $request));
        $robots = (string) ($overrides['robots'] ?? $page['robots'] ?? 'index,follow');
        $keywords = array_values(array_filter((array) ($overrides['keywords'] ?? $page['keywords'] ?? config('seo.site.default_keywords', []))));
        $image = $this->absoluteUrl((string) ($overrides['image'] ?? $page['image'] ?? config('seo.site.default_image')));
        $breadcrumbs = $this->resolveBreadcrumbs((array) ($overrides['breadcrumbs'] ?? $page['breadcrumbs'] ?? []));

        $openGraph = array_merge([
            'og:locale' => (string) config('seo.site.locale', 'bg_BG'),
            'og:type' => (string) ($overrides['og_type'] ?? $page['og_type'] ?? 'website'),
            'og:site_name' => (string) config('seo.site.name'),
            'og:url' => $canonical,
            'og:title' => $title,
            'og:description' => $description,
            'og:image' => $image,
            'og:image:alt' => (string) config('seo.site.name'),
        ], (array) ($overrides['open_graph'] ?? []));

        $twitter = array_merge([
            'twitter:card' => (string) config('seo.site.twitter_card', 'summary_large_image'),
            'twitter:title' => $title,
            'twitter:description' => $description,
            'twitter:image' => $image,
        ], (array) ($overrides['twitter'] ?? []));

        $jsonLd = [
            $this->organizationSchema(),
            $this->websiteSchema(),
        ];

        if ($breadcrumbs !== []) {
            $jsonLd[] = $this->breadcrumbSchema($breadcrumbs);
        }

        foreach ((array) ($overrides['json_ld'] ?? []) as $schema) {
            if (is_array($schema) && $schema !== []) {
                $jsonLd[] = $schema;
            }
        }

        return new SeoData(
            pageKey: $pageKey,
            title: $title,
            description: $description,
            canonical: $canonical,
            robots: $robots,
            keywords: $keywords,
            openGraph: $openGraph,
            twitter: $twitter,
            jsonLd: $jsonLd,
            preloadImages: array_values(array_filter((array) ($overrides['preload_images'] ?? $page['preload_images'] ?? []))),
            breadcrumbs: $breadcrumbs,
        );
    }

    public function articleSchema(Blog $post): array
    {
        $url = route('blog.show', $post->slug);
        $description = $post->excerpt ?: Str::limit(Str::squish(strip_tags((string) $post->content)), 160);
        $image = Blog::getPublicImageUrl($post->image_path) ?: $this->absoluteUrl((string) config('seo.site.default_image'));
        $logo = $this->absoluteUrl((string) config('seo.site.logo'));

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            '@id' => "{$url}#article",
            'headline' => $post->title,
            'description' => $description,
            'mainEntityOfPage' => $url,
            'datePublished' => optional($post->published_at)->toAtomString(),
            'dateModified' => optional($post->updated_at)->toAtomString(),
            'author' => [
                '@type' => 'Organization',
                'name' => (string) config('seo.site.name'),
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => (string) config('seo.site.name'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $logo,
                ],
            ],
            'image' => $image,
        ];
    }

    /**
     * @param  iterable<int, array<string, mixed>|object>  $faqItems
     * @return array<string, mixed>
     */
    public function faqSchema(iterable $faqItems): array
    {
        $entities = [];

        foreach ($faqItems as $item) {
            $question = trim((string) data_get($item, 'question'));
            $answer = trim((string) data_get($item, 'answer'));

            if ($question === '' || $answer === '') {
                continue;
            }

            $entities[] = [
                '@type' => 'Question',
                'name' => $question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $answer,
                ],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $entities,
        ];
    }

    /**
     * @param  array<string, mixed>  $initialData
     * @return array<string, mixed>
     */
    public function browserPayload(array $initialData = []): array
    {
        return [
            'routes' => [
                'home' => Route::has('home') ? route('home') : '/',
                'about' => Route::has('about') ? route('about') : '/about',
                'contact' => Route::has('contact') ? route('contact') : '/contacts',
                'faq' => Route::has('faq') ? route('faq') : '/faq',
                'blog' => Route::has('blog') ? route('blog') : '/blog',
            ],
            'business' => $this->businessPayload(),
            'seo' => [
                'site' => [
                    'name' => (string) config('seo.site.name'),
                    'titleSeparator' => (string) config('seo.site.title_separator', '|'),
                    'defaultTitle' => $this->formatTitle((string) config('seo.site.default_title')),
                    'defaultDescription' => (string) config('seo.site.default_description'),
                    'defaultKeywords' => array_values((array) config('seo.site.default_keywords', [])),
                    'defaultImage' => $this->absoluteUrl((string) config('seo.site.default_image')),
                    'twitterCard' => (string) config('seo.site.twitter_card', 'summary_large_image'),
                    'schemas' => [
                        'organization' => $this->organizationSchema(),
                        'website' => $this->websiteSchema(),
                    ],
                ],
                'pages' => $this->pagePayloads(),
            ],
            'initialData' => $initialData,
        ];
    }

    public function organizationSchema(): array
    {
        $siteUrl = (string) config('seo.site.url');
        $logo = $this->absoluteUrl((string) config('seo.site.logo'));
        $sameAs = array_values(array_filter((array) config('seo.business.same_as', [])));

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => (string) config('seo.business.type', 'FinancialService'),
            '@id' => "{$siteUrl}#organization",
            'name' => (string) config('seo.business.name'),
            'legalName' => (string) config('seo.business.legal_name'),
            'url' => $siteUrl,
            'logo' => $logo,
            'image' => $logo,
            'email' => (string) config('seo.business.email'),
            'telephone' => (string) config('seo.business.phone'),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => (string) config('seo.business.street_address'),
                'addressLocality' => (string) config('seo.business.address_locality'),
                'addressRegion' => (string) config('seo.business.address_region'),
                'postalCode' => (string) config('seo.business.postal_code'),
                'addressCountry' => (string) config('seo.business.country_code', 'BG'),
            ],
            'areaServed' => [
                '@type' => 'City',
                'name' => (string) config('seo.business.address_locality'),
            ],
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'contactType' => 'customer service',
                'telephone' => (string) config('seo.business.phone'),
                'email' => (string) config('seo.business.email'),
                'areaServed' => (string) config('seo.business.country_code', 'BG'),
                'availableLanguage' => ['bg'],
            ],
            'openingHoursSpecification' => $this->openingHoursSpecification(),
        ];

        if ($sameAs !== []) {
            $schema['sameAs'] = $sameAs;
        }

        $latitude = config('seo.business.latitude');
        $longitude = config('seo.business.longitude');

        if ($latitude && $longitude) {
            $schema['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => (float) $latitude,
                'longitude' => (float) $longitude,
            ];
        }

        return $schema;
    }

    public function websiteSchema(): array
    {
        $siteUrl = (string) config('seo.site.url');

        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => "{$siteUrl}#website",
            'url' => $siteUrl,
            'name' => (string) config('seo.site.name'),
            'inLanguage' => (string) config('seo.site.language', 'bg'),
            'publisher' => [
                '@id' => "{$siteUrl}#organization",
            ],
        ];
    }

    /**
     * @param  array<int, array{name: string, url: string}>  $breadcrumbs
     */
    public function breadcrumbSchema(array $breadcrumbs): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($breadcrumbs)->values()->map(
                fn (array $breadcrumb, int $index): array => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $breadcrumb['name'],
                    'item' => $breadcrumb['url'],
                ],
            )->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function businessPayload(): array
    {
        $openingHours = collect((array) config('seo.business.opening_hours', []));
        $primaryHours = $openingHours->first();

        $days = collect((array) data_get($primaryHours, 'days', []))
            ->map(fn (string $day): string => $this->translateDay($day))
            ->values()
            ->all();

        return [
            'name' => (string) config('seo.business.name'),
            'legalName' => (string) config('seo.business.legal_name'),
            'email' => (string) config('seo.business.email'),
            'phone' => (string) config('seo.business.phone'),
            'phones' => array_values((array) config('seo.business.phones', [])),
            'streetAddress' => (string) config('seo.business.street_address'),
            'addressLocality' => (string) config('seo.business.address_locality'),
            'addressRegion' => (string) config('seo.business.address_region'),
            'postalCode' => (string) config('seo.business.postal_code'),
            'country' => (string) config('seo.business.country'),
            'googleMapsUrl' => (string) config('seo.business.google_maps_url'),
            'latitude' => config('seo.business.latitude'),
            'longitude' => config('seo.business.longitude'),
            'workingDays' => $days === [] ? '' : implode(' - ', [$days[0], $days[array_key_last($days)]]),
            'workingHours' => $primaryHours ? sprintf('%s - %s', data_get($primaryHours, 'opens'), data_get($primaryHours, 'closes')) : '',
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function pagePayloads(): array
    {
        /** @var array<string, array<string, mixed>> $pages */
        $pages = config('seo.pages', []);

        return collect($pages)->mapWithKeys(function (array $page, string $pageKey): array {
            $title = (string) ($page['title'] ?? config('seo.site.default_title'));

            return [
                $pageKey => [
                    'title' => $this->formatTitle($title),
                    'description' => (string) ($page['description'] ?? config('seo.site.default_description')),
                    'keywords' => array_values((array) ($page['keywords'] ?? [])),
                    'robots' => (string) ($page['robots'] ?? 'index,follow'),
                    'ogType' => (string) ($page['og_type'] ?? 'website'),
                    'image' => $this->absoluteUrl((string) ($page['image'] ?? config('seo.site.default_image'))),
                    'canonical' => null,
                    'breadcrumbs' => $this->resolveBreadcrumbs((array) ($page['breadcrumbs'] ?? [])),
                ],
            ];
        })->all();
    }

    /**
     * @param  array<string, mixed>  $page
     */
    private function resolveCanonical(array $page, Request $request): string
    {
        $canonicalRoute = Arr::get($page, 'canonical_route');

        if (is_string($canonicalRoute) && Route::has($canonicalRoute)) {
            return route($canonicalRoute);
        }

        return $request->url();
    }

    private function formatTitle(string $title): string
    {
        $siteName = (string) config('seo.site.name');

        if ($title === '' || Str::contains($title, $siteName)) {
            return $title === '' ? $siteName : $title;
        }

        return trim(sprintf('%s %s %s', $title, config('seo.site.title_separator', '|'), $siteName));
    }

    /**
     * @param  array<int, array<string, mixed>>  $breadcrumbs
     * @return array<int, array{name: string, url: string}>
     */
    private function resolveBreadcrumbs(array $breadcrumbs): array
    {
        return collect($breadcrumbs)
            ->map(function (array $breadcrumb): ?array {
                $label = trim((string) ($breadcrumb['label'] ?? ''));
                $routeName = $breadcrumb['route'] ?? null;
                $parameters = is_array($breadcrumb['parameters'] ?? null) ? $breadcrumb['parameters'] : [];

                if ($label === '' || ! is_string($routeName) || ! Route::has($routeName)) {
                    return null;
                }

                return [
                    'name' => $label,
                    'url' => route($routeName, $parameters),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function openingHoursSpecification(): array
    {
        return collect((array) config('seo.business.opening_hours', []))
            ->map(function (array $slot): array {
                return [
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => collect((array) ($slot['days'] ?? []))
                        ->map(fn (string $day): string => "https://schema.org/{$day}")
                        ->values()
                        ->all(),
                    'opens' => (string) ($slot['opens'] ?? ''),
                    'closes' => (string) ($slot['closes'] ?? ''),
                ];
            })
            ->all();
    }

    private function absoluteUrl(string $path): string
    {
        if ($path === '') {
            return (string) config('seo.site.url');
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return url($path);
    }

    private function translateDay(string $day): string
    {
        return match ($day) {
            'Monday' => 'Понеделник',
            'Tuesday' => 'Вторник',
            'Wednesday' => 'Сряда',
            'Thursday' => 'Четвъртък',
            'Friday' => 'Петък',
            'Saturday' => 'Събота',
            'Sunday' => 'Неделя',
            default => $day,
        };
    }
}
