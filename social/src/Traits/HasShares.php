<?php

namespace IJIDeals\Social\Traits;

use IJIDeals\Social\Models\Share;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasShares
{
    /**
     * Get all of the shares for the model.
     */
    public function shares(): MorphMany
    {
        return $this->morphMany(Share::class, 'shareable');
    }

    /**
     * Get the total share count for the model.
     */
    public function getSharesCountAttribute(): int
    {
        return $this->shares()->count();
    }
}
