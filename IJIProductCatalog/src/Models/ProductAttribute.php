<?php

namespace IJIDeals\IJIProductCatalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;

class ProductAttribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', // e.g., "Color", "Size", "Material"
        'type', // e.g., 'select', 'radio', 'color_swatch', 'text' (for custom values)
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($attribute) {
            Log::info('Creating new product attribute.', ['name' => $attribute->name, 'type' => $attribute->type]);
        });

        static::updating(function ($attribute) {
            Log::info('Updating product attribute.', ['id' => $attribute->id, 'changes' => $attribute->getDirty()]);
        });

        static::deleting(function ($attribute) {
            Log::info('Deleting product attribute.', ['id' => $attribute->id]);
        });
    }

    /**
     * Get the table associated with the model.
     */
    public function getTable()
    {
        return config('ijiproductcatalog.tables.product_attributes', 'product_attributes');
    }

    /**
     * Get the attribute values for this attribute.
     * Example: "Color" attribute has values "Red", "Blue", "Green".
     */
    public function values(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }
}
