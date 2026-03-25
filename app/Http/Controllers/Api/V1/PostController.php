<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $lang = $request->get('lang', 'en');
        $perPage = (int) $request->get('per_page', 10);

        $posts = Post::query()
            ->where('is_published', true)
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
            ->where('is_published', true)
            ->firstOrFail();

        return response()->json([
            'data' => $this->transformPost($post, $lang, true),
        ]);
    }

    private function transformPost(Post $post, string $lang, bool $withContent = false): array
    {
        $title = $this->translateField($post->title, $lang);
        $excerpt = $this->translateField($post->excerpt ?? null, $lang);
        $content = $this->translateField($post->content ?? null, $lang);

        return [
            'id' => $post->id,
            'slug' => $post->slug,
            'title' => $title,
            'excerpt' => $excerpt,
            'content' => $withContent ? $content : null,
            'featured_image_url' => $post->featured_image_url ?? $post->image_url ?? null,
            'published_at' => optional($post->published_at)?->toISOString(),
            'author_name' => $post->author_name ?? 'Admin',
        ];
    }

    private function translateField($value, string $lang): ?string
    {
        if (is_null($value)) {
            return null;
        }

        if (is_array($value)) {
            return $value[$lang] ?? $value['en'] ?? reset($value) ?: null;
        }

        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded[$lang] ?? $decoded['en'] ?? reset($decoded) ?: null;
        }

        return (string) $value;
    }
}