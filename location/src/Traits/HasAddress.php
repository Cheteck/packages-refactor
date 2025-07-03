<?php

namespace IJIDeals\Location\Traits;

use IJIDeals\Location\Models\Address;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasAddress
{
    /**
     * Get all of the addresses for the model.
     */
    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    /**
     * Get the primary address for the model.
     * Assumes an 'is_primary' column exists on the addresses table.
     */
    public function primaryAddress(): ?Address
    {
        return $this->addresses()->where('is_primary', true)->first();
    }

    /**
     * Add an address to the model.
     */
    public function addAddress(array $attributes): Address
    {
        return $this->addresses()->create($attributes);
    }

    /**
     * Update an existing address for the model.
     */
    public function updateAddress(Address $address, array $attributes): bool
    {
        // Ensure the address belongs to this model before updating
        if ($address->addressable_id !== $this->getKey() || $address->addressable_type !== $this->getMorphClass()) {
            return false;
        }

        return $address->update($attributes);
    }

    /**
     * Remove an address from the model.
     */
    public function removeAddress(Address $address): ?bool
    {
        // Ensure the address belongs to this model before deleting
        if ($address->addressable_id !== $this->getKey() || $address->addressable_type !== $this->getMorphClass()) {
            return false;
        }

        return $address->delete();
    }
}
