<?php

namespace App\Models;

use Spatie\Sluggable\HasSlug;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Post extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, HasSlug;

    protected $fillable = ['title', 'slug', 'content', 'user_id', 'post_category_id'];

    protected $appends = ['image_url'];

    public function getSlugOptions() : SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }

    public function getImageUrlAttribute()
    {
        return $this->getFirstMediaUrl('posts');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function postCategories(): BelongsToMany
    {
        return $this->belongsToMany(PostCategory::class, 'post_category_pivot');
    }

    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class);
    }
}
