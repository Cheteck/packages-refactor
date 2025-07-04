<?php

namespace IJIDeals\Inventory\Services;

use Exception;
use IJIDeals\Inventory\Models\Inventory;
use IJIDeals\Inventory\Models\InventoryLocation;
use IJIDeals\Inventory\Models\StockMovement;
use IJIDeals\UserManagement\Models\User;
use Illuminate\Database\Eloquent\Model; // Assuming User model path
use Illuminate\Support\Facades\DB;
use IJIDeals\Analytics\Facades\Analytics;

class InventoryService
{
    /**
     * Adjust stock for a given stockable item at a specific location.
     *
     * @param  Model  $stockable  The stockable item (Product, Variant)
     * @param  InventoryLocation  $location  The location to adjust stock at
     * @param  int  $quantityChange  Positive to increase, negative to decrease
     * @param  string  $type  Type of movement (e.g., 'restock', 'sale', 'adjustment')
     * @param  string|null  $description  Optional description for the movement
     * @param  Model|null  $reference  Optional reference model (e.g., Order)
     * @param  User|null  $user  Optional user performing the action
     * @return Inventory The updated inventory record
     *
     * @throws Exception
     */
    public function adjustStock(
        Model $stockable,
        InventoryLocation $location,
        int $quantityChange,
        string $type,
        ?string $description = null,
        ?Model $reference = null,
        ?User $user = null
    ): Inventory {
        return DB::transaction(function () use ($stockable, $location, $quantityChange, $type, $description, $reference, $user) {
            $inventory = Inventory::firstOrCreate(
                [
                    'stockable_id' => $stockable->getKey(),
                    'stockable_type' => $stockable->getMorphClass(),
                    'location_id' => $location->getKey(),
                ],
                ['quantity' => 0, 'reserved_quantity' => 0]
            );

            $quantityBefore = $inventory->quantity;
            $newQuantity = $inventory->quantity + $quantityChange;

            // Fix: Only allow negative stock if business rules allow. Here, we do not allow negative available stock.
            if ($newQuantity - $inventory->reserved_quantity < 0) {
                throw new Exception("Insufficient stock to perform operation. Current available: {$inventory->available_quantity}, trying to decrease by ".abs($quantityChange));
            }

            if ($newQuantity < 0 && $type === 'sale') {
                throw new Exception('Cannot sell more items than available. Stock would become negative.');
            }

            $inventory->quantity = $newQuantity;
            $inventory->last_stock_update = now();
            $inventory->save();

            StockMovement::create([
                'stockable_id' => $stockable->getKey(),
                'stockable_type' => $stockable->getMorphClass(),
                'inventory_id' => $inventory->getKey(),
                'location_id' => $location->getKey(),
                'user_id' => $user ? $user->getKey() : (auth()->check() ? auth()->id() : null),
                'reference_id' => $reference ? $reference->getKey() : null,
                'reference_type' => $reference ? $reference->getMorphClass() : null,
                'type' => $type,
                'quantity_change' => $quantityChange,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $inventory->quantity,
                'description' => $description,
            ]);

            Analytics::track('stock_updated', [
                'stockable_id' => $stockable->getKey(),
                'stockable_type' => $stockable->getMorphClass(),
                'location_id' => $location->getKey(),
                'quantity_change' => $quantityChange,
                'new_quantity' => $inventory->quantity,
                'movement_type' => $type,
            ], $user ? $user->getKey() : (auth()->check() ? auth()->id() : null));

            if ($inventory->available_quantity <= 10) {
                event(new \IJIDeals\Inventory\Events\LowStockAlert($inventory));
            }

            return $inventory;
        });
    }

    /**
     * Reserve stock for a stockable item at a specific location.
     *
     * @throws Exception
     */
    public function reserveStock(Model $stockable, InventoryLocation $location, int $quantityToReserve): Inventory
    {
        if ($quantityToReserve <= 0) {
            throw new Exception('Quantity to reserve must be positive.');
        }

        return DB::transaction(function () use ($stockable, $location, $quantityToReserve) {
            $inventory = Inventory::firstOrCreate(
                [
                    'stockable_id' => $stockable->getKey(),
                    'stockable_type' => $stockable->getMorphClass(),
                    'location_id' => $location->getKey(),
                ],
                ['quantity' => 0, 'reserved_quantity' => 0]
            );

            if ($inventory->available_quantity < $quantityToReserve) {
                throw new Exception("Not enough available stock to reserve. Available: {$inventory->available_quantity}, trying to reserve: {$quantityToReserve}");
            }

            $inventory->reserved_quantity += $quantityToReserve;
            $inventory->save();

            Analytics::track('stock_reserved', [
                'stockable_id' => $stockable->getKey(),
                'stockable_type' => $stockable->getMorphClass(),
                'location_id' => $location->getKey(),
                'quantity_reserved' => $quantityToReserve,
                'new_reserved_quantity' => $inventory->reserved_quantity,
                'available_quantity' => $inventory->available_quantity,
            ], $user ? $user->getKey() : (auth()->check() ? auth()->id() : null));

            return $inventory;
        });
    }

    /**
     * Release reserved stock for a stockable item at a specific location.
     *
     * @throws Exception
     */
    public function releaseStockReservation(Model $stockable, InventoryLocation $location, int $quantityToRelease): Inventory
    {
        if ($quantityToRelease <= 0) {
            throw new Exception('Quantity to release must be positive.');
        }

        return DB::transaction(function () use ($stockable, $location, $quantityToRelease) {
            $inventory = Inventory::where('stockable_id', $stockable->getKey())
                ->where('stockable_type', $stockable->getMorphClass())
                ->where('location_id', $location->getKey())
                ->first();

            if (! $inventory) {
                throw new Exception('No inventory record found to release stock from.');
            }

            if ($inventory->reserved_quantity < $quantityToRelease) {
                throw new Exception("Cannot release more stock than reserved. Reserved: {$inventory->reserved_quantity}, trying to release: {$quantityToRelease}");
            }

            $inventory->reserved_quantity -= $quantityToRelease;
            $inventory->save();

            Analytics::track('stock_released', [
                'stockable_id' => $stockable->getKey(),
                'stockable_type' => $stockable->getMorphClass(),
                'location_id' => $location->getKey(),
                'quantity_released' => $quantityToRelease,
                'new_reserved_quantity' => $inventory->reserved_quantity,
                'available_quantity' => $inventory->available_quantity,
            ], $user ? $user->getKey() : (auth()->check() ? auth()->id() : null));

            return $inventory;
        });
    }

    /**
     * Get the available quantity for a stockable item at a specific location.
     */
    public function getAvailableQuantity(Model $stockable, InventoryLocation $location): int
    {
        $inventory = Inventory::where('stockable_id', $stockable->getKey())
            ->where('stockable_type', $stockable->getMorphClass())
            ->where('location_id', $location->getKey())
            ->first();

        return $inventory ? $inventory->available_quantity : 0;
    }

    /**
     * Check if stock is available for a given quantity.
     */
    public function isStockAvailable(Model $stockable, InventoryLocation $location, int $quantityNeeded): bool
    {
        return $this->getAvailableQuantity($stockable, $location) >= $quantityNeeded;
    }

    /**
     * Perform bulk stock adjustments.
     *
     * @param  array  $adjustments  An array of arrays, each containing:
     *                              [stockable, location, quantityChange, type, description, reference, user]
     * @return array An array of updated Inventory records
     *
     * @throws Exception
     */
    public function bulkAdjustStock(array $adjustments): array
    {
        $results = [];
        DB::transaction(function () use ($adjustments, &$results) {
            foreach ($adjustments as $adjustment) {
                $stockable = $adjustment[0];
                $location = $adjustment[1];
                $quantityChange = $adjustment[2];
                $type = $adjustment[3];
                $description = $adjustment[4] ?? null;
                $reference = $adjustment[5] ?? null;
                $user = $adjustment[6] ?? null;

                $results[] = $this->adjustStock(
                    $stockable, $location, $quantityChange, $type, $description, $reference, $user
                );
            }
        });

        return $results;
    }

    /**
     * Transfer stock of a stockable item from one location to another.
     *
     * @param  Model  $stockable  The stockable item (Product, Variant)
     * @param  InventoryLocation  $fromLocation  The location to transfer stock from
     * @param  InventoryLocation  $toLocation  The location to transfer stock to
     * @param  int  $quantity  The quantity to transfer
     * @param  string|null  $description  Optional description for the movement
     * @param  Model|null  $reference  Optional reference model (e.g., TransferOrder)
     * @param  User|null  $user  Optional user performing the action
     * @return array An array containing the updated Inventory records for both locations
     *
     * @throws Exception
     */
    public function transferStock(
        Model $stockable,
        InventoryLocation $fromLocation,
        InventoryLocation $toLocation,
        int $quantity,
        ?string $description = null,
        ?Model $reference = null,
        ?User $user = null
    ): array {
        if ($quantity <= 0) {
            throw new Exception('Quantity to transfer must be positive.');
        }

        if ($fromLocation->getKey() === $toLocation->getKey()) {
            throw new Exception('Cannot transfer stock to the same location.');
        }

        return DB::transaction(function () use ($stockable, $fromLocation, $toLocation, $quantity, $description, $reference, $user) {
            // Decrease stock from the source location
            $fromInventory = $this->adjustStock(
                $stockable,
                $fromLocation,
                -$quantity, // Negative quantity to decrease
                'transfer_out',
                $description ?: "Stock transfer out from {$fromLocation->name} to {$toLocation->name}",
                $reference,
                $user
            );

            // Increase stock at the destination location
            $toInventory = $this->adjustStock(
                $stockable,
                $toLocation,
                $quantity, // Positive quantity to increase
                'transfer_in',
                $description ?: "Stock transfer in from {$fromLocation->name} to {$toLocation->name}",
                $reference,
                $user
            );

            return [
                'from' => $fromInventory,
                'to' => $toInventory,
            ];
        });
    }
}
