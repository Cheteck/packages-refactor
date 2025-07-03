<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Social Links Table Name
    |--------------------------------------------------------------------------
    |
    | This is the name of the database table that will store your social links.
    | You can change this if you prefer a different name.
    |
    */
    'table_name' => 'social_links',

    /*
    |--------------------------------------------------------------------------
    | SocialLink Model
    |--------------------------------------------------------------------------
    |
    | This is the model class that will be used to represent a social link.
    | You can change this if you have a custom SocialLink model.
    |
    */
    'social_link_model' => \IJIDeals\SocialLinkManager\Models\SocialLink::class,

    /*
    |--------------------------------------------------------------------------
    | Supported Social Media Platforms
    |--------------------------------------------------------------------------
    |
    | Define the social media platforms your application will support.
    | Each platform is identified by a unique key.
    |
    | - 'name': The display name of the platform (e.g., "Facebook", "X (Twitter)").
    | - 'base_url_pattern': (Optional) A pattern to help construct full URLs if users
    |                       provide usernames/handles. Use {username} as placeholder.
    |                       Example: 'https://twitter.com/{username}'
    | - 'validation_regex': (Optional) A regex pattern to validate the URL structure
    |                       specific to this platform. If not provided, general URL
    |                       validation will be used.
    |                       Example for Twitter: '/^https?:\/\/(www\.)?twitter\.com\/([a-zA-Z0-9_]{1,15})(\/)?$/'
    | - 'icon_class': (Optional) A CSS class for an icon representing the platform
    |                 (e.g., from Font Awesome: 'fab fa-facebook').
    | - 'input_prepend': (Optional) Text or HTML to prepend to the input field when
    |                    editing this platform's link, e.g., "https://twitter.com/"
    |
    */
    'platforms' => [
        'facebook' => [
            'name' => 'Facebook',
            'icon_class' => 'fab fa-facebook',
            'base_url_pattern' => 'https://facebook.com/{username}',
            'validation_regex' => '/^https?:\/\/(www\.)?facebook\.com\/([a-zA-Z0-9._-]+)(\/?)$/',
            'input_prepend' => 'https://facebook.com/',
        ],
        'twitter' => [ // Or 'x_twitter'
            'name' => 'X (Twitter)',
            'icon_class' => 'fab fa-twitter', // Or fab fa-x-twitter
            'base_url_pattern' => 'https://x.com/{username}',
            'validation_regex' => '/^https?:\/\/(www\.)?(twitter|x)\.com\/([a-zA-Z0-9_]{1,15})(\/)?$/',
            'input_prepend' => 'https://x.com/',
        ],
        'instagram' => [
            'name' => 'Instagram',
            'icon_class' => 'fab fa-instagram',
            'base_url_pattern' => 'https://instagram.com/{username}',
            'validation_regex' => '/^https?:\/\/(www\.)?instagram\.com\/([a-zA-Z0-9._]{1,30})(\/?)$/',
            'input_prepend' => 'https://instagram.com/',
        ],
        'linkedin' => [
            'name' => 'LinkedIn',
            'icon_class' => 'fab fa-linkedin',
            'base_url_pattern' => 'https://linkedin.com/in/{profile_id_or_vanity_url}', // More complex
            'validation_regex' => '/^https?:\/\/(www\.)?linkedin\.com\/(in|company)\/([a-zA-Z0-9-._~:\/?#\[\]@!$&\'()*+,;=]+)(\/?)$/',
        ],
        'youtube' => [
            'name' => 'YouTube',
            'icon_class' => 'fab fa-youtube',
            'base_url_pattern' => 'https://youtube.com/channel/{channel_id}', // or /c/CustomURL or /user/Username
            'validation_regex' => '/^https?:\/\/(www\.)?youtube\.com\/(channel\/|c\/|user\/|@)?([a-zA-Z0-9_-]+)(\/?.*)?$/',
        ],
        'github' => [
            'name' => 'GitHub',
            'icon_class' => 'fab fa-github',
            'base_url_pattern' => 'https://github.com/{username}',
            'validation_regex' => '/^https?:\/\/(www\.)?github\.com\/([a-zA-Z0-9_-]{1,39})(\/?)$/',
            'input_prepend' => 'https://github.com/',
        ],
        'tiktok' => [
            'name' => 'TikTok',
            'icon_class' => 'fab fa-tiktok',
            'base_url_pattern' => 'https://tiktok.com/@{username}',
            'validation_regex' => '/^https?:\/\/(www\.)?tiktok\.com\/@([a-zA-Z0-9._-]+)(\/?.*)?$/',
            'input_prepend' => 'https://tiktok.com/@',
        ],
        'website' => [ // Generic website
            'name' => 'Website',
            'icon_class' => 'fas fa-globe', // Example Font Awesome icon
        ],
        // Add more platforms as needed
        // 'pinterest' => [ ... ],
        // 'snapchat' => [ ... ],
        // 'reddit' => [ ... ],
        // 'discord' => [ ... ],
        // 'custom_platform' => [
        //     'name' => 'My Custom Site',
        //     'icon_class' => 'fas fa-link',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific features of the SocialLinkManager.
    | Some features might be tied to Gate abilities for premium access,
    | which would be defined by the consuming application.
    |
    | Values can be:
    |   true: Feature is enabled for all.
    |   false: Feature is disabled for all.
    |   string (e.g., 'gate:can-use-feature'): Feature is enabled if the Gate::allows('can-use-feature', $model) passes.
    |                                          The Gate must be defined in the consuming application.
    |
    */
    'features' => [
        // MVP features, typically always true or controlled by basic auth on who can edit the parent model
        'basic_crud' => true,           // Can models add/update/delete links at all?
        'link_ordering' => true,        // Is sort_order field used and editable?
        'link_visibility' => true,      // Is is_public field used and editable?

        // Examples of future features that could be gated
        // 'click_analytics' => false, // or 'gate:can-use-social-link-analytics'
        // 'link_verification' => false, // or 'gate:can-verify-social-links'
        // 'max_links_per_model' => 5, // or true for unlimited (if true, no limit enforced by package)
                                     // or 'gate:get-max-social-links' (gate returns integer limit or null for unlimited)
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Values for New Links
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'is_public' => true,
        'sort_order' => 0,
    ],

];
