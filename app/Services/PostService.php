<?php

namespace App\Services;

use App\Enums\Currency;
use App\Factories\OrderStorageFactory;
use App\Models\Post;

class PostService
{
    public function findPost($orderAlias)
    {
        return Post::query()->where('id', $orderAlias)->first();
    }
}
