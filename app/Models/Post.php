<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Post extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'locale',
        'excerpt',
        'content',
        'featured_image',
        'status',
        'published_at',
        'seo_title',
        'seo_description',
    ];

   protected $casts = [
        'title' => 'array',
        'excerpt' => 'array',
        'content' => 'array',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Post $post) {
            if (blank($post->slug) && filled($post->title)) {
                $title = is_array($post->title)
                    ? ($post->title['en'] ?? $post->title['es'] ?? reset($post->title))
                    : $post->title;

                $post->slug = Str::slug($title);
            }

            if ($post->status === 'published' && blank($post->published_at)) {
                $post->published_at = now();
            }
        });
    }
}
