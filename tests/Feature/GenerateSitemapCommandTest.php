<?php

namespace Tests\Feature;

use App\Models\Blog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class GenerateSitemapCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_command_generates_static_and_blog_urls(): void
    {
        $baseUrl = rtrim(config('app.url'), '/');

        Blog::query()->create([
            'title' => 'Тестова статия',
            'slug' => 'testova-statiya',
            'excerpt' => 'Кратко описание.',
            'content' => 'Пълно съдържание на тестовата статия.',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->artisan('seo:generate-sitemap')
            ->assertSuccessful();

        $path = public_path('sitemap.xml');

        $this->assertFileExists($path);

        $contents = File::get($path);

        $this->assertStringContainsString("<loc>{$baseUrl}</loc>", $contents);
        $this->assertStringContainsString("<loc>{$baseUrl}/blog</loc>", $contents);
        $this->assertStringContainsString("<loc>{$baseUrl}/blog/testova-statiya</loc>", $contents);

        File::delete($path);
    }
}
