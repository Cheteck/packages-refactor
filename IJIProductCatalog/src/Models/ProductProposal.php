<?php

namespace IJIDeals\IJIProductCatalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;

class ProductProposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'name',
        'description',
        'proposed_brand_name',
        'proposed_category_name',
        'proposed_specifications',
        'proposed_images_payload',
        'status',
        'admin_notes',
        'approved_master_product_id',
        'proposed_variations_payload',
    ];

    protected $casts = [
        'proposed_specifications' => 'array',
        'proposed_images_payload' => 'array',
        'proposed_variations_payload' => 'array',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($proposal) {
            Log::info('Creating new product proposal.', ['name' => $proposal->name, 'shop_id' => $proposal->shop_id]);
        });

        static::updating(function ($proposal) {
            Log::info('Updating product proposal.', ['id' => $proposal->id, 'changes' => $proposal->getDirty()]);
        });

        static::deleting(function ($proposal) {
            Log::info('Deleting product proposal.', ['id' => $proposal->id]);
        });
    }

    /**
     * Get the table associated with the model.
     */
    public function getTable()
    {
        return config('ijiproductcatalog.tables.product_proposals', 'product_proposals');
    }

    /**
     * Get the shop that submitted this proposal.
     */
    public function shop(): BelongsTo
    {
        // This model will remain in the IJICommerce package
        return $this->belongsTo(\IJIDeals\IJICommerce\Models\Shop::class);
    }

    /**
     * Get the master product that was created from this proposal (if approved).
     */
    public function masterProduct(): BelongsTo
    {
        return $this->belongsTo(MasterProduct::class, 'approved_master_product_id');
    }
}
