# Notes d'Analyse des Packages IJIDeals

Ce document centralise les notes et observations issues de l'analyse croisée des packages de l'écosystème IJIDeals, en particulier concernant leur interaction potentielle avec les nouveaux packages `IJISettings` et `IJIUserSettings`.

## Objectifs de l'Analyse :

1.  Identifier les paramètres globaux de chaque package pouvant être gérés par `IJISettings`.
2.  Identifier les préférences utilisateur spécifiques à chaque package pouvant être gérées par `IJIUserSettings`.
3.  Déterminer comment chaque package pourrait déclarer ses paramètres utilisateur.
4.  Utiliser ces informations pour affiner la conception des packages `IJISettings` et `IJIUserSettings`.

## Packages Analysés et Suggestions :

### 1. `virtualcoin`
*   **Revue Approfondie Effectuée.**
*   **IJISettings Potentiels:**
    *   `virtualcoin.enabled` (boolean, group: 'features', label: 'Enable Virtual Coin System')
    *   `virtualcoin.currency_name` (string, group: 'general', label: 'Virtual Currency Name', default: 'Coins') - Actuellement `default_currency_code` mais le nom pourrait être plus descriptif.
    *   `virtualcoin.min_transfer_amount` (decimal, group: 'transfers', label: 'Minimum Transfer Amount')
    *   `virtualcoin.max_transfer_amount` (decimal, group: 'transfers', label: 'Maximum Transfer Amount')
    *   `virtualcoin.transfer_fee_fixed` (decimal, group: 'transfers', label: 'Fixed Transfer Fee')
    *   `virtualcoin.transfer_fee_percentage` (decimal, group: 'transfers', label: 'Percentage Transfer Fee', min:0, max:100)
    *   `virtualcoin.allow_transfers_between_users` (boolean, group: 'features', label: 'Allow User-to-User Transfers')
    *   `virtualcoin.log_channel` (string, group: 'developer', label: 'Log Channel for VirtualCoin')
*   **IJIUserSettings Potentiels:**
    *   `virtualcoin.notifications.on_receive` (boolean, group: 'notifications.virtualcoin', label: 'Notify on Receiving Coins', default: true)
    *   `virtualcoin.transfer.min_amount_for_notification` (integer, group: 'notifications.virtualcoin', label: 'Minimum Transfer Amount for Notification', default: 1)
*   **Déclaration pour `IJIUserSettings` (Exemple):**
    ```php
    // Dans config/user_settings_declarations/virtualcoin.php
    return [
        'virtualcoin.notifications.on_receive' => [
            'label' => 'Notify on Receiving Coins',
            'type' => 'boolean',
            'group' => 'notifications.virtualcoin',
            'default' => true,
            'rules' => ['boolean'],
        ],
        // ... autres ...
    ];
    ```
*   **Impact sur Settings:**
    *   Nécessite des types `decimal` ou une gestion de la précision si on utilise `integer` pour stocker des centimes.
    *   Les frais de transfert pourraient nécessiter une logique de calcul dans `WalletService` qui lit ces paramètres depuis `IJISettings`.

---

*(Cette structure sera répétée pour chaque package analysé)*

### 2. `SocialLinkManager`
*   **IJISettings Potentiels:**
    *   `sociallinkmanager.platforms.{platform_key}.is_enabled` (boolean, group: 'social_platforms', label: 'Enable [PlatformName]') : Permettre à l'admin d'activer/désactiver globalement certaines plateformes sociales.
    *   `sociallinkmanager.max_links_per_model` (integer, group: 'limits', label: 'Max Social Links per Entity', default: 10)
*   **IJIUserSettings Potentiels:**
    *   `sociallinkmanager.profile.display_publicly.{platform_key}` (boolean, group: 'profile.social_visibility', label: 'Display [PlatformName] link publicly on my profile', default: true) - *Alternative à la colonne `is_public` sur la table `social_links` si la visibilité doit être une préférence utilisateur plutôt qu'une propriété du lien lui-même.* Si `is_public` reste sur le lien, pas besoin de paramètre utilisateur ici. Le README actuel suggère que `is_public` est une propriété du lien.
*   **Déclaration pour `IJIUserSettings`:** (Peu probable si `is_public` est sur le lien)
*   **Impact sur Settings:**
    *   La gestion dynamique de `sociallinkmanager.platforms.{platform_key}.is_enabled` dans `IJISettings` nécessiterait que le `SocialLinkManager` vérifie ce paramètre avant de proposer une plateforme.

---

### 3. `recommendation-engine`
*   **IJISettings Potentiels:**
    *   `recommendation.engine.enabled` (boolean, group: 'features', label: 'Enable Recommendation Engine')
    *   `recommendation.strategy.popular_products.event_types` (array/json, group: 'recommendations', label: 'Event Types for Popularity', default: ['product_viewed', 'order_item_added']) - *Permettrait de configurer les événements analytics qui contribuent à la popularité.*
    *   `recommendation.strategy.popular_products.lookback_days` (integer, group: 'recommendations', label: 'Lookback Days for Popularity', default: 30)
    *   `recommendation.strategy.user_history.min_interactions` (integer, group: 'recommendations', label: 'Min Interactions for User-Based Recs', default: 5)
    *   `recommendation.cache_ttl_minutes` (integer, group: 'performance', label: 'Recommendation Cache TTL (minutes)', default: 60)
*   **IJIUserSettings Potentiels:**
    *   `recommendation.user.allow_tracking` (boolean, group: 'privacy', label: 'Allow use of my activity for personalized recommendations', default: true)
    *   `recommendation.user.preferred_categories` (array/json, group: 'preferences.recommendations', label: 'My Preferred Categories for Recommendations') - *L'utilisateur pourrait explicitement indiquer ses préférences.*
*   **Déclaration pour `IJIUserSettings`:**
    ```php
    return [
        'recommendation.user.allow_tracking' => [
            'label' => 'Allow personalized recommendations',
            'type' => 'boolean',
            'group' => 'privacy',
            'default' => true,
            'rules' => ['boolean'],
        ],
        // ...
    ];
    ```
*   **Impact sur Settings:**
    *   `IJIUserSettings` doit pouvoir stocker et caster des types `array`/`json` (pour `preferred_categories`).
    *   Le `RecommendationService` lirait ces paramètres pour ajuster son comportement.

---
*(L'analyse se poursuivra pour les autres packages...)*
