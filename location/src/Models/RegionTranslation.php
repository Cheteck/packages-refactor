<?php

namespace IJIDeals\Location\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegionTranslation extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
        // 'locale' and 'region_id' are handled by Astrotomic
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \IJIDeals\Location\Database\factories\RegionTranslationFactory::new();
    }
}
