<?php

namespace App\Console\Commands;

use App\Models\Blog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Throwable;

class GenerateSitemapCommand extends Command
{
    protected $signature = 'seo:generate-sitemap';

    protected $description = 'Generate a sitemap.xml file in the public directory.';

    public function handle(): int
    {
        $items = collect([
            ['route' => 'home'],
            ['route' => 'about'],
            ['route' => 'contact'],
            ['route' => 'faq'],
            ['route' => 'blog'],
        ])
            ->filter(fn (array $item): bool => $this->isSitemapRouteEnabled($item['route']))
            ->map(fn (array $item): array => [
                'loc' => route($item['route']),
                'lastmod' => null,
            ]);

        try {
            if (Schema::hasTable('blogs')) {
                $items = $items->merge(
                    Blog::query()
                        ->published()
                        ->latestPublished()
                        ->get(['slug', 'updated_at'])
                        ->map(fn (Blog $post): array => [
                            'loc' => route('blog.show', $post->slug),
                            'lastmod' => optional($post->updated_at)->toAtomString(),
                        ]),
                );
            }
        } catch (Throwable $exception) {
            $this->warn('Published blog posts could not be added to the sitemap: '.$exception->getMessage());
        }

        $xml = view('sitemap', [
            'items' => $items->values(),
        ])->render();

        $target = public_path('sitemap.xml');
        $temporaryTarget = "{$target}.tmp";

        File::put($temporaryTarget, $xml);
        File::move($temporaryTarget, $target);

        $this->info("Sitemap generated at {$target}");

        return self::SUCCESS;
    }

    private function isSitemapRouteEnabled(string $routeName): bool
    {
        if (! app('router')->has($routeName)) {
            return false;
        }

        $pageMap = [
            'home' => 'home',
            'about' => 'about',
            'contact' => 'contact',
            'faq' => 'faq',
            'blog' => 'blog',
        ];

        $pageKey = $pageMap[$routeName] ?? null;

        return $pageKey !== null && (bool) config("seo.pages.{$pageKey}.sitemap", true);
    }
}
