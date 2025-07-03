<?php

namespace IJIDeals\Social\Traits;

use IJIDeals\Social\Models\Post;

trait HasPosts
{
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}
