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
        $lang = $request->get('lang', 'en');
        $perPage = (int) $request->get('per_page', 10);

        $posts = Post::query()
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->paginate($perPage);

        $posts->getCollection()->transform(function ($post) use ($lang) {
            return $this->transformPost($post, $lang, false);
        });

        return response()->json($posts);
    }

    public function show(Request $request, string $slug)
    {
        $lang = $request->get('lang', 'en');

        $post = Post::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        return response()->json([
            'data' => $this->transformPost($post, $lang, true),
        ]);
    }

    private function transformPost(Post $post, string $lang, bool $withContent = false): array
    {
        return [
            'id' => $post->id,
            'slug' => $post->slug,
            'locale' => $post->locale,
            'title' => $this->translateField($post->title, $lang),
            'excerpt' => $this->translateField($post->excerpt, $lang),
            'content' => $withContent ? $this->translateField($post->content, $lang) : null,
            'featured_image_url' => $this->imageUrl($post->featured_image),
            'published_at' => optional($post->published_at)?->toISOString(),
            'status' => $post->status,
            'seo_title' => $post->seo_title,
            'seo_description' => $post->seo_description,
        ];
    }

    private function translateField($value, string $lang): ?string
    {
        if (is_null($value)) {
            return null;
        }

        if (is_array($value)) {
            return $value[$lang] ?? $value['en'] ?? $value['es'] ?? reset($value) ?: null;
        }

        return (string) $value;
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