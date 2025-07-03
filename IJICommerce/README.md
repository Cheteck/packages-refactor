# IJICommerce: The Multi-Vendor Marketplace Engine for Laravel

**by IJIDeals**

## Vision: Building a Sophisticated E-Commerce Marketplace

**IJICommerce** is architected to be more than just an e-commerce package; it's the foundational engine for transforming a Laravel application into a feature-rich, multi-vendor marketplace. When combined with `IJIDeals/UserManagement` and future IJIDeals modules like `IJILaurels` (gamification) and `IJICommerce-ProductCollaboration`, it aims to provide a comprehensive, scalable, and highly engaging online commerce platform.

The core philosophy is to empower individual **Shops** (vendors) with robust tools to manage their storefronts, products, orders, and teams, while providing **Platform Administrators** with overarching control and curation capabilities. Customers, in turn, experience a unified marketplace where they can discover and purchase products from a multitude of vendors.

## Core Pillars & Modules (Envisioned Final State)
(This section describes the full vision, parts of which are implemented in phases)

IJICommerce will be built upon several interconnected pillars:

### 1. Shop Management & Vendor Empowerment
   - **Shop Entity:** Each vendor operates a `Shop` with its own profile (name, logo, cover photo, description, story, contact details, social links, SEO settings).
   - **Team-Based Roles & Permissions:** Leveraging `spatie/laravel-permission` with a "teams" concept (each `Shop` is a team), allowing shop owners to invite members and assign granular roles (Owner, Administrator, Product Manager, Order Manager, Support Staff).
   - **Shop Settings:** Configurable options per shop.
   - **Shop Dashboard (API-driven):** Endpoints to power a vendor dashboard.
   - **Shop Status & Moderation:** Platform admins manage shop statuses.
   - **(Future) Brand Portal/Claiming:** `IJICommerce-BrandPortal` module for brands to manage their profiles.

### 2. User Integration & Roles
   - Builds upon `IJIDeals/UserManagement` for unified user accounts.
   - Customer profiles, order history, etc.
   - Vendor profiles with shop-specific roles.
   - Platform Administrator global roles.

### 3. Unified Product Catalog & Multi-Vendor Listings (Now primarily handled by `IJIDeals/IJIProductCatalog` and `IJIDeals/IJIShopListings`)
   - **Master Product Catalog (`MasterProduct`):** Central, curated catalog with rich details, images (Spatie MediaLibrary), specifications, brand, category. Platform Admin curated. **(Managed by `IJIDeals/IJIProductCatalog`)**
   - **Product Proposals (`ProductProposal`):** Shops propose new products for the master catalog, reviewed by Platform Admins. **(Managed by `IJIDeals/IJIProductCatalog`)**
   - **Shop-Specific Listings (`ShopProduct` - "Sell This"):** Shops list `MasterProduct`s, managing their own price, stock, sales, notes, and additional images (Spatie MediaLibrary). **(Managed by `IJIDeals/IJIShopListings`)**
   - **Product Variations & Options:** `MasterProduct`s support variations (Size, Color) via `ProductAttribute`, `ProductAttributeValue`, and `MasterProductVariation` (with own SKU, image). Shops list specific `MasterProductVariation`s through `ShopProductVariation` records, managing individual stock/price/sales. **(Master Product related components managed by `IJIDeals/IJIProductCatalog`, Shop Product related components managed by `IJIDeals/IJIShopListings`)**
   - **Brands & Categories:** Global, hierarchical `Category` and rich `Brand` management by platform admins. Brand pages can have rich content. **(Managed by `IJIDeals/IJIProductCatalog`)**
   - **(Future) Collaborative Product Data Enrichment (`IJICommerce-ProductCollaboration`):** Module for community/shop proposals for edits to existing `MasterProduct`s.

### 4. Inventory Management
   - **Shop-Specific Stock:** On `ShopProduct` (simple products) or `ShopProductVariation` (variants). **(Managed by `IJIDeals/IJIShopListings`)**
   - **Real-time Decrements:** Stock decremented on order completion.
   - **(Future) Granular Stock Movements & Low Stock Notifications.**

### 5. Order Management (Now primarily handled by `IJIDeals/IJIOrderManagement`)
   - **Customer Order Placement:** APIs for order creation. **(Managed by `IJIDeals/IJIOrderManagement`)**
   - **Shop Order Dashboard:** Shops view/manage their orders (update status, etc.). **(Managed by `IJIDeals/IJIOrderManagement`)**
   - **Platform Admin Oversight.**

### 6. Pricing & Promotions
   - **Shop-Determined Pricing:** For `ShopProduct`s and `ShopProductVariation`s. **(Managed by `IJIDeals/IJIShopListings`)**
   - **Sales & Discounts (Shop-Level):** `sale_price`, start/end dates on shop listings. **(Managed by `IJIDeals/IJIShopListings`)**
   - **(Future) Platform-Wide Promotions & Coupons.**

### 7. Payments & Payouts (Future Major Phase)
### 8. Shipping & Fulfillment (Future Major Phase)
### 9. Reviews & Ratings (Future Major Phase)
### 10. Gamification & Engagement (`IJILaurels` Integration - Future)

## Implemented Features

-   **Shop Management:** Full CRUD, Team Roles via Spatie.
-   **Product Proposal System:** Shops propose, Admins approve/reject. **(Now part of `IJIDeals/IJIProductCatalog`)**
-   **Brand & Category Management:** Admin CRUD for rich Brand & Category pages. **(Now part of `IJIDeals/IJIProductCatalog`)**
-   **Master Product System:** Admin CRUD for Master Products (simple products initially). **(Now part of `IJIDeals/IJIProductCatalog`)**
-   **Shop Product Listing ("Sell This"):** Shops link Master Products, set price/stock. **(Now part of `IJIDeals/IJIShopListings`)**
-   **Master Product Update Notifications:** Shops review admin changes to Master Products. **(Logic for flagging in `IJIDeals/IJIProductCatalog`, handling in `IJIDeals/IJIShopListings`)**
-   **Product Variations & Options (Foundation):** Models for Attributes, Values, MasterProductVariations, ShopProductVariations. Admin can manage Master Product Attributes & Variations. Shops can list products with variations. **(Master Product related components now part of `IJIDeals/IJIProductCatalog`, Shop Product related components now part of `IJIDeals/IJIShopListings`)**
-   **Image Management (Spatie MediaLibrary):** Integrated for Brands, Categories, MasterProducts, MasterProductVariations, and ShopProducts (additional images). Old path fields removed. **(Brand, Category, MasterProduct, MasterProductVariation image handling now part of `IJIDeals/IJIProductCatalog`, ShopProduct image handling now part of `IJIDeals/IJIShopListings`)**
-   **Basic Order Management (API Structure):** `Order` and `OrderItem` models. Customer API to place (simulated) orders with stock decrement. Shop API to view orders and update status. **(Now part of `IJIDeals/IJIOrderManagement`)**
-   **Sales Pricing:** `sale_price`, start/end dates on `ShopProduct` and `ShopProductVariation`. `effective_price` accessor. **(Now part of `IJIDeals/IJIShopListings`)**
-   **API Filtering:** Basic filtering on index endpoints for products.
    -   `Admin\MasterProductController@index`: Filtering by name, status, brand, category. **(Now part of `IJIDeals/IJIProductCatalog`)**
    -   `ShopProductController@indexMasterProducts`: Filtering by name, brand, category. **(Now part of `IJIDeals/IJIShopListings`)**
    -   `ShopProductController@indexShopProducts`: Filtering by master product name. **(Now part of `IJIDeals/IJIShopListings`)**
-   **Artisan Install Command:** `ijicommerce:install` for easy setup.
-   **Policies & Authorization:** For all implemented actions.
-   **Testing:** Unit and Feature tests for implemented functionalities.

## Installation

1.  **Require the package & Spatie Packages:**
    ```bash
    composer require ijideals/ijicommerce ijideals/ijiproductcatalog ijideals/ijishoplistings ijideals/ijiordermanagement
    ```
    *Note: `spatie/laravel-permission` and `spatie/laravel-medialibrary` are now dependencies of `ijideals/ijiproductcatalog` and `ijideals/ijishoplistings`.*
2.  **Run the Install Command:**
    ```bash
    php artisan ijicommerce:install
    ```
    This command will guide you through publishing assets and offers to run migrations and seeders.
3.  **Configure Spatie Laravel Permission for Teams:**
    In `config/permission.php`:
    ```php
    'teams' => true,
    'team_foreign_key' => 'shop_id',
    ```
4.  **Configure Spatie MediaLibrary:**
    Publish its config and migration if not done by the install command:
    ```bash
    php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="config"
    php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="migrations"
    ```
    Ensure your `config/filesystems.php` is set up for image storage (e.g., a public disk).
5.  **Ensure User Model Uses `HasRoles`:**
    Your `App\Models\User` must use `Spatie\Permission\Traits\HasRoles`.
6.  **Run Migrations:**
    ```bash
    php artisan migrate
    ```

## Configuration
-   **`config/ijicommerce.php`**: Review for table names, route prefix/middleware, default roles.
-   **`config/ijiproductcatalog.php`**: New configuration file for product catalog specific settings (table names, media collections, routes).
-   **`config/ijishoplistings.php`**: New configuration file for shop listings specific settings (table names, media collections, routes).
-   **`config/ijiordermanagement.php`**: New configuration file for order management specific settings (table names, routes).
-   **`config/permission.php`**: Ensure Spatie teams setup.
-   **`config/media-library.php`**: Configure Spatie MediaLibrary as needed.

## Core Concepts Summary
-   **Shops as Teams:** Permissions are scoped to shops.
-   **Platform Admin Role:** A global role (e.g., 'Platform Admin') is needed for managing global entities.
-   **Product Flow:** `ProductProposal` (shop) -> Admin Review -> `MasterProduct` (admin curated) (+ `ProductAttributes`, `MasterProductVariations` by admin) -> `ShopProduct` ("Sell This" by shop, linking to `MasterProduct`) + `ShopProductVariation`s (shop sets price/stock for variants they sell).
    *Note: `ProductProposal`, `MasterProduct`, `ProductAttributes`, `MasterProductVariations` are now managed by `IJIDeals/IJIProductCatalog`. `ShopProduct` and `ShopProductVariation` are now managed by `IJIDeals/IJIShopListings`.*
-   **Image Handling:** Uses Spatie MediaLibrary. Images are associated with their respective models (Brand logos, MasterProduct base images, ShopProduct additional images, etc.).

## API Usage Examples (Illustrative - check `api-documentation.yaml` for more)

(All endpoints typically prefixed with `api/ijicommerce` and require authentication)

**Shops & Teams (Phase 1 - Covered Previously)**

**Product Catalog (Now handled by `IJIDeals/IJIProductCatalog`)
**
- **Shop-side Product Proposals**
  - `POST /api/v1/shop/product-proposals` (Submit new product idea)
  - `GET /api/v1/shop/product-proposals` (List user's shop proposals)
- **Admin: Brands, Categories, Attributes**
  - `GET, POST, PUT, DELETE /api/v1/admin/brands/{id}`
  - `GET, POST, PUT, DELETE /api/v1/admin/categories/{id}`
  - `GET, POST, PUT, DELETE /api/v1/admin/product-attributes/{id}`
  - `POST, PUT, DELETE /api/v1/admin/product-attributes/{attribute_id}/values/{value_id}` (Manage attribute values)
- **Admin: Master Products & Variations**
  - `GET, POST, PUT, DELETE /api/v1/admin/master-products/{id}` (Handle `base_images` array for uploads)
  - `GET, POST, PUT, DELETE /api/v1/admin/master-products/{master_product_id}/variations/{id}` (Shallow route: `/admin/variations/{id}` for update/delete. Handle `variant_image` upload)
- **Admin: Product Proposal Review**
  - `GET /api/v1/admin/product-proposals`
  - `POST /api/v1/admin/product-proposals/{id}/approve` (Payload includes master product details, potentially variation data)
  - `POST /api/v1/admin/product-proposals/{id}/reject`
  - `POST /api/v1/admin/product-proposals/{id}/needs-revision`

**Shop Product Listings (Now handled by `IJIDeals/IJIShopListings`)
**
- `GET /api/v1/shops/{shop_id}/shop-products/available-master` (Filter by name, brand, category)
- `GET /api/v1/shops/{shop_id}/shop-products` (List own products, filter by name)
- `POST /api/v1/shops/{shop_id}/shop-products` ("Sell This")
  ```json
  // For simple product:
  { "master_product_id": 1, "price": 99.99, "stock_quantity": 10, "sale_price": 79.99, "sale_start_date": "..." }
  // For product with variations:
  {
    "master_product_id": 2,
    // Optional: main ShopProduct price/stock if relevant as a parent
    "variations": [
      { "master_product_variation_id": 5, "price": 109.99, "stock_quantity": 5, "sale_price": 99.00, "sale_start_date": "..." },
      { "master_product_variation_id": 6, "price": 119.99, "stock_quantity": 8 }
    ],
    "shop_images": [ /* file uploads */ ]
  }
  ```
- `PUT /api/v1/shops/{shop_id}/shop-products/{shop_product_id}` (Update main listing, including sales, add/remove `shop_images`)
- `DELETE /api/v1/shops/{shop_id}/shop-products/{shop_product_id}` (De-list)
- `POST /api/v1/shops/{shop_id}/shop-products/{shop_product_id}/acknowledge-update`

**Shop Product Variation Management (Now handled by `IJIDeals/IJIShopListings`)
**
- `PUT /api/v1/shops/{shop_id}/shop-products/{shop_product_id}/variations/{shop_product_variation_id}` (Update specific variant's price, stock, sales info)

**Order Management (Now handled by `IJIDeals/IJIOrderManagement`)
**
- **Customer-side Orders**
  - `POST /api/v1/orders`
    ```json
    {
      "shop_id": 1,
      "items": [
        { "type": "shopproduct", "id": 101, "quantity": 1 }, // For simple ShopProduct
        { "type": "shopproductvariation", "id": 205, "quantity": 2 } // For specific ShopProductVariation
      ],
      "billing_address": { "...": "..." },
      "shipping_address": { "...": "..." },
      "payment_method_token": "tok_xyz"
    }
    ```
  - `GET /api/v1/orders`, `GET /api/v1/orders/{id}`

- **Shop-side Orders**
  - `GET /api/v1/shops/{shop_id}/orders`
  - `GET /api/v1/shops/{shop_id}/orders/{id}`
  - `PUT /api/v1/shops/{shop_id}/orders/{id}/status` (Payload: `{"status": "shipped", "tracking_number": "123xyz"}`)


---
*This README will continue to evolve with the package.*