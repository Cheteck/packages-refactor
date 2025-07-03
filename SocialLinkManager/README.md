# SocialLinkManager - Flexible Social Link Management for Laravel Eloquent Models

**Package Name (Composer):** `ijideals/sociallinkmanager`
**Version:** 1.0.0 (MVP)

## Introduction

SocialLinkManager is a Laravel package designed to provide a seamless and configurable way to associate social media links with any Eloquent model. Whether it's a user profile, a shop page, a brand identity, or any other entity in your application, this package allows you to easily manage a collection of social links through a simple trait.

The package is built with flexibility in mind, allowing developers to define supported social platforms, customize validation, and control features like link visibility and ordering. Furthermore, it's architected to support future extensions where specific functionalities could be gated based on application-level logic, such as user subscription plans.

## Core Functionalities (MVP)

*   **`HasSocialLinks` Trait:** Easily attach social link management capabilities to any Eloquent model.
*   **Polymorphic Storage:** Social links are stored in a dedicated polymorphic table (`social_links` by default), allowing multiple links of different platforms per model instance.
*   **Configurable Platforms:** Define a list of supported social media platforms (e.g., Facebook, Twitter, Instagram, LinkedIn, GitHub) via a configuration file. Each platform can have associated metadata like a display name, validation patterns, base URL patterns, and icon classes.
*   **CRUD Operations:** The trait provides simple methods to add, update, retrieve (single or all), and remove social links for a model.
*   **Link Visibility:** Control whether individual social links are public or private (e.g., for internal reference) via an `is_public` flag.
*   **Link Ordering:** Define a `sort_order` for displaying social links.
*   **Basic Validation:** Includes basic URL validation and ensures that added links belong to a configured platform. Platform-specific URL regex validation is also supported via configuration.
*   **`syncSocialLinks()`:** A convenient method to manage all social links for a model in a single operation, adding new ones, updating existing ones (based on `platform_key`), and removing those not provided in the sync array.

## Installation

1.  **Require the package via Composer:**
    ```bash
    composer require ijideals/sociallinkmanager
    ```

2.  **Publish Assets:**
    Publish the configuration file and migration:
    ```bash
    php artisan vendor:publish --provider="IJIDeals\SocialLinkManager\SocialLinkManagerServiceProvider"
    ```
    You can also publish them separately using tags:
    *   Config: `php artisan vendor:publish --provider="IJIDeals\SocialLinkManager\SocialLinkManagerServiceProvider" --tag="socialinkmanager-config"`
    *   Migrations: `php artisan vendor:publish --provider="IJIDeals\SocialLinkManager\SocialLinkManagerServiceProvider" --tag="socialinkmanager-migrations"`

3.  **Run Migrations:**
    ```bash
    php artisan migrate
    ```
    This will create the `social_links` table (or the name you configure).

## Configuration

After publishing, the configuration file will be located at `config/socialinkmanager.php`.

Key configuration options:

*   **`table_name`**: The database table name for storing social links (default: `social_links`).
*   **`social_link_model`**: The Eloquent model used for social links (default: `IJIDeals\SocialLinkManager\Models\SocialLink::class`).
*   **`platforms`**: An array defining the social media platforms your application supports.
    *   **Key:** A unique string key for the platform (e.g., `facebook`, `twitter_x`, `personal_blog`).
    *   **`name`**: The human-readable display name (e.g., "Facebook", "X (formerly Twitter)", "Personal Blog").
    *   **`icon_class`**: (Optional) CSS class for an icon (e.g., 'fab fa-facebook', 'fas fa-link').
    *   **`base_url_pattern`**: (Optional) A pattern like `https://twitter.com/{username}`. The trait doesn't automatically use this for URL generation yet, but it's good for reference or future helpers.
    *   **`validation_regex`**: (Optional) A specific regex pattern to validate URLs for this platform. If not provided, general URL validation applies.
        Example for Twitter: `'/^https?:\/\/(www\.)?(twitter|x)\.com\/([a-zA-Z0-9_]{1,15})(\/)?$/'`
    *   **`input_prepend`**: (Optional) Text to prepend to an input field in a form, e.g., `https://twitter.com/`.
*   **`features`**: Array for enabling/disabling features or mapping them to Gate abilities.
    *   `basic_crud`: (Default `true`) Enables core add/update/delete functionality.
    *   `link_ordering`: (Default `true`) Enables usage of `sort_order`.
    *   `link_visibility`: (Default `true`) Enables usage of `is_public`.
    *   *(Future features like `click_analytics` or `link_verification` will be added here, potentially mapped to Gate ability strings for premium feature control by the consuming application.)*
*   **`defaults`**: Default values for new links.
    *   `is_public`: (Default `true`)
    *   `sort_order`: (Default `0`)

**Example `platforms` configuration:**
```php
// config/socialinkmanager.php
'platforms' => [
    'facebook' => [
        'name' => 'Facebook',
        'icon_class' => 'fab fa-facebook',
        'validation_regex' => '/^https?:\/\/(www\.)?facebook\.com\/([a-zA-Z0-9._-]+)(\/?)$/',
    ],
    'twitter' => [
        'name' => 'X (Twitter)',
        'icon_class' => 'fab fa-twitter', // or 'fab fa-x-twitter'
        'validation_regex' => '/^https?:\/\/(www\.)?(twitter|x)\.com\/([a-zA-Z0-9_]{1,15})(\/)?$/',
    ],
    // ... other platforms
],
```

## Usage

1.  **Use the `HasSocialLinks` Trait in your Eloquent Model:**
    ```php
    namespace App\Models; // Or your model's namespace

    use Illuminate\Database\Eloquent\Model;
    use IJIDeals\SocialLinkManager\Traits\HasSocialLinks;

    class YourModel extends Model // E.g., User, Shop, Brand
    {
        use HasSocialLinks;
        // ... your model code
    }
    ```

2.  **Managing Social Links:**

    ```php
    $model = YourModel::find(1);

    // Add or Update a social link (uses updateOrCreate logic based on platform_key)
    $twitterLink = $model->addSocialLink('twitter', 'https://twitter.com/yourhandle', [
        'label' => 'Follow me on X',
        'is_public' => true,
        'sort_order' => 1
    ]);

    $facebookLink = $model->addSocialLink('facebook', 'https://facebook.com/yourpage'); // Uses defaults for label, is_public, sort_order

    // Get a specific link
    $fetchedTwitterLink = $model->getSocialLink('twitter');
    if ($fetchedTwitterLink) {
        echo $fetchedTwitterLink->url; // https://twitter.com/yourhandle
        echo $fetchedTwitterLink->platform_display_name; // "X (Twitter)" (from config)
    }

    // Get all public social links (ordered by sort_order, then platform_key)
    $publicLinks = $model->getSocialLinks(); // Default true for publicOnly
    foreach ($publicLinks as $link) {
        // $link->platform_key, $link->url, $link->label, $link->platform_display_name, $link->platform_icon_class
    }

    // Get all links (public and private)
    $allLinks = $model->getSocialLinks(false);

    // Check if a link exists
    if ($model->hasSocialLink('instagram')) {
        // ...
    }

    // Update a link by its ID (more flexible for changing visibility/order)
    $linkToUpdate = $model->getSocialLink('twitter');
    if ($linkToUpdate) {
        $model->updateSocialLinkById($linkToUpdate->id, [
            'url' => 'https://x.com/newhandle',
            'is_public' => false,
            'sort_order' => 0
        ]);
    }

    // Remove a link by platform key
    $model->removeSocialLink('facebook');

    // Remove a link by its ID
    // $linkToRemove = $model->getSocialLink('twitter');
    // if ($linkToRemove) {
    //     $model->removeSocialLinkById($linkToRemove->id);
    // }

    // Sync all links (adds/updates provided, removes others)
    $linksToSync = [
        ['platform_key' => 'linkedin', 'url' => 'https://linkedin.com/in/profile', 'label' => 'My LinkedIn', 'sort_order' => 0],
        ['platform_key' => 'github', 'url' => 'https://github.com/profile', 'is_public' => true, 'sort_order' => 1],
        // Any existing links for this $model not in this array (e.g., 'twitter') will be removed.
    ];
    $model->syncSocialLinks($linksToSync);

    // Accessing the relationship directly
    foreach ($model->socialLinks as $link) { // Returns a Collection of SocialLink models
        // ...
    }
    ```

## Validation

-   The `addSocialLink`, `updateSocialLinkById` (if URL is passed), and `syncSocialLinks` methods automatically validate:
    -   That the `platform_key` is defined in your `config/socialinkmanager.php` file.
    -   That the `url` is a valid URL format.
    -   If a `validation_regex` is provided for a platform in the config, the URL must also match this regex.
-   A `Illuminate\Validation\ValidationException` will be thrown if validation fails.

## Future Vision & Extensibility

While the MVP focuses on core link management, SocialLinkManager is envisioned to grow with more advanced features, potentially including:

*   **Click Analytics:** Tracking engagement with social links.
*   **Link Verification:** A system to mark certain links as "official" or "verified."
*   **Advanced Premium Feature Gating:** Deeper integration points for applications to enable/disable specific social link features based on user subscriptions or other business logic (e.g., limiting the number of links, enabling analytics for premium users via Laravel Gates).
*   **Enhanced Frontend Helpers:** More sophisticated Blade components or helpers for rendering social links with icons and themes.

This package aims to be the go-to solution for robust and adaptable social media link management within the Laravel and IJIDeals ecosystem.

---
*(This README will be further developed with more detailed examples and advanced feature documentation as the package evolves).*
