<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Illuminate\Http\JsonResponse;

class BlogController extends Controller
{
    public function index(): JsonResponse
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

        return response()->json($posts);
    }

    public function show(string $slug): JsonResponse
    {
        $post = Blog::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail([
                'id',
                'title',
                'slug',
                'excerpt',
                'content',
                'image_path',
                'published_at',
            ]);

        return response()->json($post);
    }
}
