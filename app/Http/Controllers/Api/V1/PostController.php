<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $lang = $request->get('locale') ?? $request->get('lang', 'en');
        $perPage = (int) $request->get('per_page', 10);

        $posts = Post::query()
            ->where('status', 'published')
            ->where('locale', $lang)
            ->orderByDesc('published_at')
            ->paginate($perPage);

        $posts->getCollection()->transform(function ($post) {
            return $this->transformPost($post, false);
        });

        return response()->json($posts);
    }

    public function show(Request $request, string $slug)
    {
        $lang = $request->get('locale') ?? $request->get('lang', 'en');

        $post = Post::query()
            ->where('slug', $slug)
            ->where('locale', $lang)
            ->where('status', 'published')
            ->firstOrFail();

        return response()->json([
            'data' => $this->transformPost($post, true),
        ]);
    }

    private function transformPost(Post $post, bool $withContent = false): array
    {
        return [
            'id' => $post->id,
            'slug' => $post->slug,
            'locale' => $post->locale,
            'title' => $post->title,
            'excerpt' => $post->excerpt,
            'content' => $withContent ? $post->content : null,
            'featured_image_url' => $this->imageUrl($post->featured_image),
            'published_at' => optional($post->published_at)?->toISOString(),
            'status' => $post->status,
            'seo_title' => $post->seo_title,
            'seo_description' => $post->seo_description,
        ];
    }

    private function imageUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        try {
            return Storage::disk('public')->url($path);
        } catch (\Throwable $e) {
            return $path;
        }
    }
}