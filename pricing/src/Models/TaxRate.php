<?php

namespace IJIDeals\Pricing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model
{
    use HasFactory;

    protected $table = 'tax_rates';

    protected $fillable = [
        'name',         // e.g., "VAT", "Sales Tax"
        'rate_percentage', // The tax rate as a percentage, e.g., 20.00 for 20%
        'is_active',
        'priority',     // For compound taxes or multiple taxes applicable
        'country_code', // ISO 3166-1 alpha-2 country code, nullable for global tax
        'region',       // State, province, nullable
        'city',         // Nullable
        'zip_code',     // Postal code, nullable
        'description',
    ];

    protected $casts = [
        'rate_percentage' => 'decimal:4',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * Scope a query to only include active tax rates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \IJIDeals\Pricing\Database\factories\TaxRateFactory::new();
    }
}
