<?php

namespace App\Support\Seo;

class SeoData
{
    /**
     * @param  array<int, string>  $keywords
     * @param  array<string, string>  $openGraph
     * @param  array<string, string>  $twitter
     * @param  array<int, array<string, mixed>>  $jsonLd
     * @param  array<int, string>  $preloadImages
     * @param  array<int, array{name: string, url: string}>  $breadcrumbs
     */
    public function __construct(
        public readonly string $pageKey,
        public readonly string $title,
        public readonly string $description,
        public readonly string $canonical,
        public readonly string $robots,
        public readonly array $keywords = [],
        public readonly array $openGraph = [],
        public readonly array $twitter = [],
        public readonly array $jsonLd = [],
        public readonly array $preloadImages = [],
        public readonly array $breadcrumbs = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'page_key' => $this->pageKey,
            'title' => $this->title,
            'description' => $this->description,
            'canonical' => $this->canonical,
            'robots' => $this->robots,
            'keywords' => $this->keywords,
            'open_graph' => $this->openGraph,
            'twitter' => $this->twitter,
            'json_ld' => $this->jsonLd,
            'preload_images' => $this->preloadImages,
            'breadcrumbs' => $this->breadcrumbs,
        ];
    }
}
