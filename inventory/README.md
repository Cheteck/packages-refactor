# Inventory Package

The Inventory package provides a comprehensive solution for stock management and inventory tracking across the IJIDeals platform. It ensures that stock levels are accurate and synchronized, preventing overselling and providing clear visibility into inventory.

## Core Features

-   **Real-time Stock Tracking**: Accurately track inventory levels for every product and variant.
-   **Multi-location Inventory**: Manage stock across multiple warehouses or physical locations.
-   **Stock Movements**: Full history of all stock movements (e.g., `sale`, `return`, `restock`, `damage`).
-   **Low Stock Alerts**: Automatically trigger events when stock levels for a product fall below a configurable threshold.
-   **Inventory Reservation**: Temporarily reserve stock when an item is added to a cart to prevent it from being sold to someone else.
-   **Atomic Operations**: All stock updates are atomic to ensure data integrity, especially during high-traffic sales events.

## Key Components

### Models

-   `Inventory`: Stores the quantity on hand for a specific product (`stockable`) at a specific `Location`.
-   `StockMovement`: Records every change in inventory, providing a complete audit trail.
-   `Location`: Represents a physical location where stock is held (e.g., a warehouse).

### Services

-   `InventoryService`: The central service for all inventory operations. It handles stock adjustments, reservations, and checks for availability.

### Events

-   `LowStockAlert`: Fired when a product's inventory is low.
-   `StockReduced`: Fired after a sale, for logging or other integrations.
-   `StockRestocked`: Fired when new inventory is added.

## How It Works

Every time an order is placed, the `InventoryService` is called to decrease the stock for the purchased items. When an item is returned, the service increases the stock. This ensures that the `Inventory` model always reflects the true quantity available for sale.

## Dependencies

-   **`ijideals/catalog`**: To associate inventory with products and their variants.
-   **`ijideals/commerce`**: To update stock levels when orders are placed or returned.

## Structure

```
src/
├── Models/           # Stock, Variant, and related models
├── Database/
│   ├── factories/    # Model factories for testing
│   └── migrations/   # Database migrations
├── Providers/        # Service providers
└── Config/          # Package configuration
```

## Models

- Stock
- Variant
- VariantTranslation
- VariantSpecification
- VariantOptionValue
- StockMovement
- StockAdjustment
- Warehouse

## Installation

```bash
composer require ijideals/inventory
```

## Configuration

Publish the configuration:

```bash
php artisan vendor:publish --tag=inventory-config
``` 
