# TODO for Inventory Package (Improvements)

## 🚀 Core Functionality Enhancements

-   **Implement `InventoryService` Logic:**
    -   [ ] **Stock Adjustment (`adjustStock`):**
        -   Implement `adjustStock(Model $stockable, int $quantityChange, InventoryLocation $location, string $movementType, ?Model $reference = null, ?User $user = null)`:
            -   Find or create `Inventory` record for the stockable item at the location.
            -   Atomically update `Inventory->quantity` (use `DB::transaction` and potentially `lockForUpdate()`).
            -   Create a `StockMovement` record detailing the change, type (e.g., 'restock', 'sale', 'return', 'adjustment', 'damage'), reference (e.g., Order ID, Purchase Order ID), and user.
            -   Dispatch `StockReduced` or `StockRestocked` events.
            -   Check for low stock after adjustment and dispatch `LowStockAlert` if threshold is met.
    -   [ ] **Stock Reservation (`reserveStock`):**
        -   Implement `reserveStock(Model $stockable, int $quantity, InventoryLocation $location, string $reservationReference, ?Carbon $expiresAt = null)`:
            -   Check available quantity (`quantity - reserved_quantity`).
            -   If sufficient, increment `Inventory->reserved_quantity`.
            -   Log the reservation (perhaps a new `StockReservation` model or a specific `StockMovement` type).
            -   Return success/failure or reservation details.
    -   [ ] **Release Stock Reservation (`releaseStockReservation`):**
        -   Implement `releaseStockReservation(Model $stockable, int $quantity, InventoryLocation $location, string $reservationReference)`:
            -   Decrement `Inventory->reserved_quantity`.
    -   [ ] **Commit Reserved Stock (`commitReservedStock`):**
        -   Implement `commitReservedStock(Model $stockable, int $quantity, InventoryLocation $location, string $reservationReference, Model $orderReference)`:
            -   Decrement `Inventory->reserved_quantity`.
            -   Call `adjustStock` to formally deduct the committed quantity (e.g., movementType 'sale').
    -   [ ] **Check Stock Availability (`isStockAvailable`):**
        -   Implement `isStockAvailable(Model $stockable, int $requestedQuantity, InventoryLocation $location)`:
            -   Return boolean based on `quantity - reserved_quantity >= requestedQuantity`.
    -   [ ] **Get Stock Level (`getStockLevel`):**
        -   Implement `getStockLevel(Model $stockable, InventoryLocation $location)`: returns current actual quantity.
-   **Multi-Location Inventory Management:**
    -   [ ] Ensure all service methods correctly handle the `InventoryLocation` parameter.
    -   [ ] Add logic for transferring stock between locations (`transferStock` method in `InventoryService`).
-   **Low Stock Alerts:**
    -   [ ] Define how low stock thresholds are set (e.g., per product in `ijideals/catalog` or `ijideals/commerce`, or a global/per-category setting in `config/inventory.php`).
    -   [ ] Ensure `LowStockAlert` event is dispatched with relevant data (stockable item, location, current quantity, threshold).
    -   [ ] Consider creating a listener within this package or `ijideals/notifications-manager` to handle these alerts (e.g., send email to admin).
-   **Stockable Interface/Trait:**
    -   [ ] Create an `StockableInterface` (e.g., with methods like `getStockableIdentifier()`, `getStockableType()`).
    -   [ ] Models like `Product` and `Variant` (from `ijideals/commerce`) should implement this interface.
    -   [ ] Potentially a `HasStock` trait for these models to easily interact with `InventoryService`.

## 🔧 API & Configuration

-   **API Endpoints (Optional but Recommended for Admin):**
    -   [ ] `GET /inventory/{stockable_type}/{stockable_id}`: View stock levels across locations.
    -   [ ] `POST /inventory/adjust`: Manually adjust stock (admin action).
    -   [ ] `GET /inventory/locations`: List inventory locations.
    -   [ ] `POST /inventory/locations`: Create/update inventory locations.
    -   [ ] Create `InventoryController`, `InventoryLocationController`.
    -   [ ] Implement Form Requests and Policies.
-   **Refine `config/inventory.php`:**
    -   [ ] Add default low stock threshold value (can be overridden per product).
    -   [ ] Configure inventory reservation duration (if reservations expire automatically).
    -   [ ] Define default `InventoryLocation` if single-location is a common use-case or for fallback.
    -   [ ] List recognized `StockMovement` types (e.g., `sale`, `return`, `restock`, `adjustment`, `damage`, `transfer_in`, `transfer_out`).
    -   [ ] Configuration for event dispatching (e.g., enable/disable specific alerts).

## 🧹 Code Quality & Model Refinements

-   **Model `Inventory.php`:**
    -   [ ] Add `stockable()` morphTo relationship.
    -   [ ] Add `location()` belongsTo `InventoryLocation` relationship.
    -   [ ] Add accessor for `available_quantity` (`quantity - reserved_quantity`).
-   **Model `StockMovement.php`:**
    -   [ ] Add `stockable()` morphTo relationship.
    -   [ ] Add `location()` belongsTo `InventoryLocation` relationship.
    -   [ ] Add `reference()` morphTo relationship (e.g., to Order, PurchaseOrder).
    -   [ ] Add `user()` belongsTo `User` relationship (who initiated the movement).
-   **Model `InventoryLocation.php`:**
    -   [ ] Add `inventories()` hasMany relationship.
    -   [ ] Add `stockMovements()` hasMany relationship.
    -   [ ] Consider linking to `Address` model from `ijideals/location` if addresses are complex.
-   **Enums for Statuses/Types:**
    -   [ ] Create `StockMovementTypeEnum`. Update `StockMovement` model and service logic.

## 📚 Documentation & Testing

-   **README Update:**
    -   [ ] Correct the "Models" list.
    -   [ ] Document all features of `InventoryService`.
    -   [ ] Explain how to make a model "stockable" and integrate with the inventory system.
    -   [ ] Detail configuration options in `inventory.php`.
    -   [ ] Document any API endpoints if created.
-   **Testing Strategy:**
    -   [ ] Write unit tests for `InventoryService` methods (stock adjustments, reservations, availability checks, atomic operations).
    -   [ ] Test model relationships and scopes.
    -   [ ] Test event dispatching for low stock and stock changes.
    -   [ ] Test for race conditions in stock updates (e.g., using concurrent requests in feature tests if possible, though DB transactions should mitigate most direct DB issues).

## 💡 Remodularization Suggestions

*   **`WarehouseManagement` Module**: If managing multiple physical warehouses with complex layouts (bins, zones), receiving, put-away, picking, and packing logic becomes a requirement, this could be a more significant, distinct module or package. For now, `InventoryLocation` is a simpler concept.
*   **`StockReservationService`**: If reservation logic becomes very complex (e.g., different reservation types, tiered priorities, complex expiry rules), it could be extracted.

This list aims to make the Inventory package fully functional and robust.
