<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BlogTag extends Model
{
    protected $fillable = ['name', 'slug'];

    public function posts()
    {
        return $this->belongsToMany(BlogPost::class, 'blog_post_tag');
    }

    /** Find an existing tag by name (case-insensitive) or create it. */
    public static function findOrCreateByName(string $name): self
    {
        $name = trim($name);
        $slug = Str::slug($name);

        return static::firstOrCreate(['slug' => $slug], ['name' => $name]);
    }
}
