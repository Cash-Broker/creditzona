<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Faq;
use App\Support\Seo\SeoManager;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __construct(
        private readonly SeoManager $seoManager,
    ) {}

    public function home(Request $request): View
    {
        return $this->renderPage('home', $request);
    }

    public function about(Request $request): View
    {
        return $this->renderPage('about', $request);
    }

    public function contact(Request $request): View
    {
        return $this->renderPage('contact', $request);
    }

    public function faq(Request $request): View
    {
        $faqs = Faq::query()
            ->published()
            ->ordered()
            ->get(['question', 'answer']);

        return $this->renderPage('faq', $request, [
            'json_ld' => [$this->seoManager->faqSchema($faqs)],
            'initial_data' => [
                'faqs' => $faqs->values()->all(),
            ],
        ]);
    }

    public function blog(Request $request): View
    {
        $posts = Blog::query()
            ->published()
            ->latestPublished()
            ->get([
                'id',
                'title',
                'slug',
                'excerpt',
                'image_path',
                'published_at',
            ]);

        return $this->renderPage('blog', $request, [
            'initial_data' => [
                'blogs' => $posts->values()->all(),
            ],
        ]);
    }

    public function blogShow(Request $request, string $slug): View
    {
        $post = Blog::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        $description = $post->excerpt ?: Str::limit(Str::squish(strip_tags((string) $post->content)), 160);

        return $this->renderPage('blog_show', $request, [
            'title' => $post->title,
            'description' => $description,
            'canonical' => route('blog.show', $post->slug),
            'image' => Blog::getPublicImageUrl($post->image_path) ?: config('seo.site.default_image'),
            'open_graph' => [
                'og:type' => 'article',
            ],
            'breadcrumbs' => [
                ['label' => 'Начало', 'route' => 'home'],
                ['label' => 'Блог', 'route' => 'blog'],
                ['label' => $post->title, 'route' => 'blog.show', 'parameters' => ['slug' => $post->slug]],
            ],
            'json_ld' => [$this->seoManager->articleSchema($post)],
            'initial_data' => [
                'blogs' => [[
                    'id' => $post->id,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'excerpt' => $post->excerpt,
                    'image_path' => $post->image_path,
                    'published_at' => $post->published_at,
                ]],
                'blogPost' => [
                    'id' => $post->id,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'excerpt' => $post->excerpt,
                    'content' => $post->content,
                    'image_path' => $post->image_path,
                    'published_at' => $post->published_at,
                ],
            ],
        ]);
    }

    public function privacyPolicy(Request $request): View
    {
        return $this->renderPage('privacy_policy', $request);
    }

    public function cookiePolicy(Request $request): View
    {
        return $this->renderPage('cookie_policy', $request);
    }

    public function terms(Request $request): View
    {
        return $this->renderPage('terms', $request);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function renderPage(string $page, Request $request, array $overrides = []): View
    {
        $seo = $this->seoManager->forPage($page, $request, $overrides);

        return view('layouts.app', [
            'page' => $page,
            'seo' => $seo->toArray(),
            'appConfig' => $this->seoManager->browserPayload($overrides['initial_data'] ?? []),
        ]);
    }
}
