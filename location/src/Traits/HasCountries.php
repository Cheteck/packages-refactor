<?php

namespace IJIDeals\Location\Traits;

use IJIDeals\Location\Models\Country;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasCountries
{
    /**
     * Get the countries associated with the model.
     */
    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class);
    }
}
