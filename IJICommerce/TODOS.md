# IJICommerce Development TODO List

This document outlines the planned features and tasks for the IJICommerce package.

## Phase 1: Shop Foundation & Team Roles (Completed)

-   **[X] Package Scaffolding:**
    -   [X] Create `composer.json` for `ijideals/ijicommerce`.
    -   [X] Set up basic directory structure (`src/`, `config/`, `database/migrations/`, `routes/`, `tests/`).
    -   [X] Create `IJICommerceServiceProvider`.
        -   [X] Implement `boot` method for loading migrations, routes, config.
        -   [X] Implement `register` method for basic bindings.
        -   [X] Define publishable assets (config, migrations).
-   **[X] Shop Model & Migration:**
    -   [X] Create `Shop` Eloquent model (`IJIDeals\IJICommerce\Models\Shop`).
        -   [X] Define `$fillable` attributes based on `README.md` field list.
        -   [X] Define `$casts` for `settings` (JSON) and `approved_at` (datetime).
    -   [X] Create migration for the `shops` table with all specified fields.
-   **[X] Spatie Laravel Permission Integration:**
    -   [X] Add `spatie/laravel-permission` as a dependency.
    -   [X] Configure Spatie permissions for "teams" (guidance in `ijicommerce.php`, user configures `permission.php`).
        -   [X] `teams = true`, `team_foreign_key = 'shop_id'`.
        -   [X] Ensure `Shop` model can be used as the "team" context.
    -   [X] Create `DefaultShopRolesSeeder` for roles ('Owner', 'Administrator', 'Editor', 'Support', 'Viewer').
        -   [X] Made seeder publishable.
-   **[X] Core Shop Logic & Authorization:**
    -   [X] `ShopController` for CRUD operations.
        -   [X] `store` method: Creates `Shop`, assigns 'Owner' role to creator for this shop.
        -   [X] `update`, `show`, `index`, `destroy` methods.
    -   [X] `ShopPolicy` for authorizing shop actions based on team roles.
    -   [X] API routes for shop management.
-   **[X] Team Management within Shops & Authorization:**
    -   [X] `ShopTeamController` for managing team members.
        -   [X] Methods: `index`, `addUser`, `updateUserRole`, `removeUser`.
    -   [X] Logic uses Spatie team functions for assigning/syncing roles within a shop context.
    -   [X] Authorization via `ShopPolicy@manageTeam` (or similar) for team management actions.
    -   [X] API routes for shop team management.
-   **[X] `InstallIJICommerce` Artisan Command:**
    -   [X] Created command `ijicommerce:install`.
    -   [X] Publishes configs (`ijicommerce.php`, Spatie's `permission.php` if missing).
    -   [X] Publishes migrations.
    -   [X] Asks to run migrations (optional).
    -   [X] Publishes `DefaultShopRolesSeeder`.
    -   [X] Asks to run seeder (optional).
    -   [X] Outputs instructions for manual Spatie `config/permission.php` setup.
-   **[X] Testing (Phase 1):**
    -   [X] `TestCase.php` setup with Orchestra Testbench, Spatie config for teams.
    -   [X] Unit tests for `Shop` model.
    -   [X] Feature tests for `ShopController` (CRUD, ownership, authorization).
    -   [X] Feature tests for `ShopTeamController` (team management, roles, authorization).
-   **[X] Documentation (Phase 1 in README):**
    -   [X] `README.md` updated with installation, Spatie setup, `ijicommerce:install` command, core concepts, and API examples for Phase 1.

---

## Phase 2: E-commerce Core - Product Management Foundation (Refactored to IJIDeals/IJIProductCatalog)

This phase focused on establishing the product catalog system, now residing in `IJIDeals/IJIProductCatalog`. Platform admins curate "Master Products," shops can propose new master products, and then list these products for sale. Edits to active `MasterProducts` are solely by platform admins in this phase.

-   **[X] Product Proposal System (for *new* products by Shops):** (Moved to `IJIDeals/IJIProductCatalog`)
    -   [X] `ProductProposal` model and migration (with `approved_master_product_id` link).
    -   [X] `ProductProposalController` (shop-side: submit, list own, view own).
    -   [X] API routes for shop-side proposal actions.
    -   [X] `ProductProposalPolicy` (handling shop-side and admin-side actions via distinct methods).
-   **[X] Brand & Category Management (Platform Admin Controlled):** (Moved to `IJIDeals/IJIProductCatalog`)
    -   [X] `Brand` model and migration (enhanced with fields for rich frontend pages: cover_photo, website, social_links, story, is_featured, status, meta fields).
    -   [X] `Category` model and migration (with `parent_id` for simple hierarchy).
    -   [X] `Admin\BrandController` & `Admin\CategoryController` for CRUD by platform admins.
    -   [X] API routes for admin CRUD of Brands and Categories.
    -   [X] `Admin\BrandPolicy` & `Admin\CategoryPolicy` for platform admin authorization.
-   **[X] Master Product System (Initial - Simple Products, Admin Controlled):** (Moved to `IJIDeals/IJIProductCatalog`)
    -   [X] `MasterProduct` model and migration (links to Brand, Category, originating ProductProposal).
    -   [X] `Admin\ProductProposalController` updated for admin actions: list all proposals, view specific, approve (creates `MasterProduct`, links it to proposal), reject.
    -   [X] `Admin\MasterProductController` for platform admins for direct CRUD on `MasterProduct`s.
    -   [X] API routes for these admin actions.
    -   [X] `Admin\MasterProductPolicy` for platform admin authorization for MasterProducts.
-   **[X] Shop Product Listing ("Sell This" - Simple Products):** (Moved to `IJIDeals/IJIShopListings`)
    -   [X] `ShopProduct` model and migration (links to Shop & MasterProduct; includes price, stock, visibility, notes, `master_version_hash`, `needs_review_by_shop`, `shop_images_payload` (JSON)). Unique constraint on `shop_id` + `master_product_id`.
    -   [X] `ShopProductController` for shops to:
        -   [X] List available `MasterProduct`s to sell (`indexMasterProducts`).
        -   [X] List their own `ShopProduct`s (`indexShopProducts`).
        -   [X] Create a `ShopProduct` from a `MasterProduct` ("Sell This" - `store` method).
        -   [X] View, Update, and Delete (de-list) their `ShopProduct` listings.
    -   [X] API routes for these shop-specific actions.
    -   [X] `ShopProductPolicy` for shop team authorization.
-   **[X] Master Product Update Notification Logic (Admin Edits):** (Logic in `Admin\MasterProductController` moved to `IJIDeals/IJIProductCatalog`, flagging `ShopProduct`s in `IJIDeals/IJIShopListings`)
    -   [X] Implemented in `Admin\MasterProductController@update`: significant changes to active `MasterProduct` flag linked `ShopProduct`s (`needs_review_by_shop = true`, `is_visible_in_shop = false`, `master_version_hash` updated).
    -   [X] `ShopProductController` has `acknowledgeMasterProductUpdate` endpoint for shops to re-activate their listing.
    -   [ ] Event/Notification dispatch for shop admins. *(Deferred to specific notification system implementation)*
-   **[X] Roles & Permissions for Core Product Management:**
    -   [X] Policies created/updated assuming 'Platform Admin' global role and shop-level roles for relevant actions.
-   **[X] Testing (Phase 2 - Foundational):** (Marked as complete for this phase's scope)
    -   [X] `TestCase.php` setup with Orchestra Testbench, Spatie config for teams.
    -   [X] Unit tests for `ProductProposal`, `Brand`, `Category`, `MasterProduct`, `ShopProduct` models.
    -   [X] Feature tests for `ProductProposalController` (shop-side), `Admin\BrandController`, `Admin\CategoryController`, `Admin\ProductProposalController`, `Admin\MasterProductController` (including update notification), and `ShopProductController`.

---

## Phase 3: Advanced Product Features & E-commerce Operations (Refactored to IJIDeals/IJIProductCatalog & IJIDeals/IJIShopListings, and IJIDeals/IJIOrderManagement)

-   **[X] Product Variations & Options:** (Master Product related components moved to `IJIDeals/IJIProductCatalog`, Shop Product related components moved to `IJIDeals/IJIShopListings`)
    -   [X] Models & Migrations: `ProductAttribute`, `ProductAttributeValue`, `MasterProductVariation`, `master_product_variation_options` pivot, `ShopProductVariation`.
    -   [X] `ProductProposal` updated for `proposed_variations_payload`.
    -   [X] Admin - `ProductAttributeController` for attributes & values (CRUD).
    -   [X] Admin - `MasterProductVariationController` for CRUD of master variations.
    -   [X] `Admin\ProductProposalController@approve` handles `proposed_variations_payload`.
    -   [X] Shop - `ShopProductController@store` handles listing products with variations (creating `ShopProductVariation`s).
    -   [X] Shop - `ShopProductVariationController` created for updating individual `ShopProductVariation` (price, stock).
    -   [X] API routes and Policies for these.
-   **[X] Full Image Management Integration (using Spatie MediaLibrary):** (Brand, Category, MasterProduct, MasterProductVariation image handling moved to `IJIDeals/IJIProductCatalog`; ShopProduct image handling moved to `IJIDeals/IJIShopListings`)
    -   [X] Added `spatie/laravel-medialibrary` dependency.
    -   [X] Config `ijicommerce.php` updated with MediaLibrary notes & collection names.
    -   [X] Models (`Brand`, `Category`, `MasterProduct`, `MasterProductVariation`, `ShopProduct`) updated:
        -   [X] Implement `HasMedia`, `InteractsWithMedia`.
        -   [X] Removed old `*_path`/`*_payload` image fields.
        -   [X] Registered media collections and basic conversions.
    -   [X] New migrations created to drop old image columns.
    -   [X] Controllers (`Admin\BrandController`, `Admin\CategoryController`, `Admin\MasterProductController`, `Admin\MasterProductVariationController`, `ShopProductController`) updated:
        -   [X] Handle file uploads using MediaLibrary.
        -   [X] Validation rules for image files.
        -   [X] API responses include media URLs.
    -   [X] `ProductProposal.proposed_images_payload` remains informational; admin handles uploads.
-   **[X] Inventory Management (Shop-Level - Granular for `ShopProduct` & `ShopProductVariation`):** (Moved to `IJIDeals/IJIShopListings`)
    -   [X] `stock_quantity` on `ShopProduct` and `ShopProductVariation` is the current source of truth.
    -   [X] `ShopProductController` & `ShopProductVariationController` allow direct stock updates.
    -   [ ] `StockMovement` model and detailed logging. *(Deferred to Phase 3.1 or later)*
-   **[X] Basic Order Management (Shop-Level - Initial API Structure):** (Moved to `IJIDeals/IJIOrderManagement`)
    -   [X] `Order` model and migration (shop, customer, order_number, status, totals, addresses (JSON), payment info, notes, timestamps).
    -   [X] `OrderItem` model and migration (links to Order, products/variants; snapshots of name, price, SKU, variant details).
    -   [X] `OrderController` (customer-facing): `store` (simulated order, basic stock decrement), `show`, `index`.
    -   [X] `Shop\OrderController` (shop-facing): `index`, `show`, `updateStatus`.
    -   [X] Relationships added to models.
    -   [X] API routes for customer and shop order actions.
    -   [X] `OrderPolicy` for basic authorization.
    -   [ ] Full cart/checkout logic, payment integration. *(Deferred)*
    -   [ ] Detailed stock decrement events/observers. *(Deferred)*
-   **[X] Pricing Strategies (Basic Sales on `ShopProduct` / `ShopProductVariation`):** (Moved to `IJIDeals/IJIShopListings`)
    -   [X] Added `sale_price`, `sale_start_date`, `sale_end_date` to `ShopProduct` & `ShopProductVariation` models/migrations.
    -   [X] Implemented `getEffectivePriceAttribute()` and `getIsOnSaleAttribute()` accessors.
    -   [X] Updated relevant controllers (`ShopProductController`, `ShopProductVariationController`) to manage these sales fields.
    -   [X] API responses updated to include effective price and sale status.
-   **[X] Search & Filtering for Products (Basic API Level):**
    -   [X] `Admin\MasterProductController@index`: Filtering by name, status, brand, category. **(Now part of `IJIDeals/IJIProductCatalog`)**
    -   [X] `ShopProductController@indexMasterProducts`: Filtering by name, brand, category. **(Now part of `IJIDeals/IJIShopListings`)**
    -   [X] `ShopProductController@indexShopProducts`: Filtering by master product name. **(Now part of `IJIDeals/IJIShopListings`)**
-   **[X] Testing (Phase 3 - Foundational):** (Marked as complete for this phase's scope)
    -   [X] `TestCase.php` updated for MediaLibrary.
    -   [X] Unit tests updated/created for `Brand`, `Category`, `MasterProduct`, `ShopProduct`, `MasterProductVariation`, `ShopProductVariation`, `Order`, `OrderItem`.
    -   [X] Feature tests created/updated for `AdminProductAttributeController`, `AdminMasterProductController` (MediaLibrary, variation creation from proposal), `AdminMasterProductVariationController` (MediaLibrary), `ShopProductController` (MediaLibrary, variations, sales), `ShopProductVariationController` (sales), basic `OrderController` & `Shop\OrderController` tests, and filtering.
    -   [ ] Comprehensive testing for all edge cases and deeper permission scenarios. *(Ongoing/Future Refinement)*

---

## Phase 4: Full Marketplace & Scaling (IJICommerce Core / Potential Extensions)
(Renamed from Phase 3 in previous TODOS to reflect current progress)

-   **[ ] Inventory Management (Shop-Level - Granular Logging):**
    -   [ ] `StockMovement` model and detailed logging for all stock changes.
    -   [ ] Low stock notifications for shops.
-   **[ ] Order Management Enhancements:**
    -   [ ] Robust stock decrement (e.g., using events/observers, handling race conditions).
    -   [ ] Order status transition logic and events.
    -   [ ] Customer and Shop notifications for order updates.
    -   [ ] Partial fulfillment, returns, refunds (basic structure).
-   **[ ] Advanced Pricing & Promotions:**
    -   [ ] Platform-wide promotions.
    -   [ ] Coupon/Voucher system.
-   **[ ] Search & Filtering Enhancements:**
    -   [ ] Search by attributes, specifications.
    -   [ ] More advanced filtering options.
-   **[ ] Payment Gateway Integration for Shops.**
-   **[ ] Shipping & Fulfillment Logic per Shop.**
-   **[ ] Platform Commissions & Payouts.**
-   **[ ] Shop Reviews & Ratings.**
-   [ ] Customer Communication Tools.
-   [ ] Advanced Reporting & Analytics.
-   [ ] Full Integration with `UserManagement` package.

## Potential Future Separate Packages / Advanced Modules

-   **[ ] IJICommerce - Product Collaboration Module:**
    -   [ ] Feature: Allow shop admins to edit `MasterProduct` details if they are the sole seller.
    -   [ ] Feature: System for any authenticated user/shop to propose edits to *existing* `MasterProduct`s, with an admin moderation workflow.
    -   [ ] Feature: Community engagement/rewards for contributions to product data.
-   **[ ] IJICommerce - Brand Portal / Brand Claiming Module:**
    -   [ ] Allow brand representatives to claim and manage their rich brand profile pages.
    -   [ ] Workflow for verification and approval of brand claims.
-   **[ ] IJICommerce - Advanced Promotions Module.**
-   **[ ] IJICommerce - Affiliate System.**

## General/Ongoing

-   **[ ] Refine Tests:** Ensure comprehensive test coverage for all features.
-   **[ ] Documentation:** Keep `README.md` and other documentation up-to-date.
-   **[ ] Code Quality:** Adhere to PSR standards, code reviews, refactoring.
-   **[ ] User Experience:** Consider the UX for both end-customers and shop vendors.

---
*This TODO list is a living document and will be updated as the project progresses.*