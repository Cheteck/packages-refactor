<?php

namespace IJIDeals\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryLocation extends Model
{
    use HasFactory;

    protected $table = 'inventory_locations';

    protected $fillable = [
        'name',
        'address_details', // Could be JSON or separate address model relationship
        'is_active',
    ];

    protected $casts = [
        'address_details' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the inventory items at this location.
     */
    public function inventoryItems(): HasMany
    {
        return $this->hasMany(Inventory::class, 'location_id');
    }

    // TODO: Add a factory if not already created by ls check
    // protected static function newFactory()
    // {
    //     return \IJIDeals\Inventory\Database\factories\InventoryLocationFactory::new();
    // }
}
