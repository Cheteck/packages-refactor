<?php

namespace IJIDeals\IJIProductCatalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;

class ProductAttributeValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_attribute_id',
        'value', // e.g., "Red", "XL", "Cotton"
        'meta',  // JSON: For extra info like color hex for 'Red', specific sort order, etc.
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($value) {
            Log::info('Creating new product attribute value.', ['value' => $value->value, 'attribute_id' => $value->product_attribute_id]);
        });

        static::updating(function ($value) {
            Log::info('Updating product attribute value.', ['id' => $value->id, 'changes' => $value->getDirty()]);
        });

        static::deleting(function ($value) {
            Log::info('Deleting product attribute value.', ['id' => $value->id]);
        });
    }

    /**
     * Get the table associated with the model.
     */
    public function getTable()
    {
        return config('ijiproductcatalog.tables.product_attribute_values', 'product_attribute_values');
    }

    /**
     * Get the attribute that this value belongs to.
     * Example: "Red" value belongs to "Color" attribute.
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(ProductAttribute::class, 'product_attribute_id');
    }
}
