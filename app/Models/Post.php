<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    /** @use HasFactory<\Database\Factories\PostFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $table = 'posts';
    protected $fillable = [
        'title',
        'img_path',
        'content',
        'sort_order',
        'status',
        'is_pinned',
        'slug',
        'published_at',
        'finished_at',
    ];

    public function getImgUrlAttribute()
    {
        if ($this->img_path) {
            return asset('storage/' . $this->img_path);
        }
        return null;
    }
}
