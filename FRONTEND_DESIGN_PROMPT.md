# Prompt pour la Conception et la Génération d'une Application Frontend Modulaire pour une Plateforme E-Commerce et Sociale

## Objectif Général :
Développer une application frontend moderne, réactive et conviviale qui exploite l'ensemble des fonctionnalités API fournies par une suite de packages backend Laravel. La plateforme est une solution e-commerce multi-vendeurs avec des aspects sociaux et communautaires.

## Acteurs et Rôles Utilisateurs :
Le frontend devra idéalement servir (ou être adaptable pour) les rôles suivants. Préciser si des interfaces/dashboards distincts sont nécessaires :
1.  **Client / Acheteur :** Utilisateur principal naviguant, achetant des produits, interagissant socialement.
2.  **Vendeur / Gestionnaire de Boutique (`Shop`) :** Utilisateur gérant sa boutique, ses produits, ses commandes, son programme de fidélité. (Probablement un dashboard dédié).
3.  **Administrateur de la Plateforme :** Utilisateur supervisant la plateforme, les utilisateurs, les produits maîtres, les configurations. (Probablement un dashboard dédié).

## Technologies Frontend Suggérées (à adapter selon les préférences) :
*   **Framework JS :** [Ex: Vue.js avec Nuxt.js, React avec Next.js, SvelteKit, ou Angular]
*   **Styling :** [Ex: Tailwind CSS, Bootstrap, Material Design Components]
*   **Gestion d'état :** [Ex: Pinia/Vuex, Redux/Zustand, Svelte Stores]
*   **Requêtes API :** Axios ou Fetch API.
*   **Authentification :** Gestion des tokens (Sanctum ou autre).

## Sections et Fonctionnalités Clés du Frontend (basées sur les packages backend) :

### 1. Interface Client / Acheteur :

    *   **Navigation et Découverte Produits (`IJIProductCatalog`, `IJIShopListings`, `Pricing`) :**
        *   Homepage avec sections (nouveautés, promotions, produits populaires).
        *   Pages de catégories de produits.
        *   Pages de marques.
        *   Moteur de recherche de produits (filtrage par nom, catégorie, marque, prix, attributs).
        *   Page de détail d'un `MasterProduct` : affichage des informations, images, variations (avec sélection), et liste des `ShopProduct` (vendeurs) le proposant avec leurs prix et stocks.
        *   Affichage des prix (avec gestion multi-devises si applicable via `Pricing`).
        *   Affichage des promotions et prix barrés.
    *   **Gestion de Panier et Commande (`IJIOrderManagement`, `Pricing`, `Inventory`) :**
        *   Ajout au panier depuis page produit/listing.
        *   Visualisation et modification du panier.
        *   Processus de checkout (adresses, options de livraison - si gérées, récapitulatif, sélection de paiement).
        *   Affichage des coûts de livraison (si `Pricing` le gère).
        *   Suivi des commandes passées.
        *   Gestion des retours (`ReturnsManagement`) : initier une demande de retour, suivre le statut.
    *   **Compte Utilisateur (`UserManagement`, `SocialLinkManager`) :**
        *   Inscription, connexion, déconnexion, récupération de mot de passe.
        *   Gestion du profil (informations personnelles, adresses, photo).
        *   Gestion des liens sociaux du profil.
        *   Préférences de notification (`NotificationsManager`).
    *   **Fonctionnalités Sociales (`Social`, `FileManagement` pour les uploads) :**
        *   Fil d'actualité principal (agrégation de posts).
        *   Visualisation des posts (texte, image, vidéo).
        *   **`Shoppable Posts` :** Affichage des produits tagués sur les posts avec redirection vers la page produit/achat.
        *   Création de posts (texte, upload d'images/vidéos).
        *   Interactions : liker, commenter, partager des posts.
        *   Profils utilisateurs publics (avec leurs posts, followers, etc.).
        *   Système de follow/unfollow.
        *   Notifications sociales (nouveau follower, like, commentaire, mention).
    *   **Messagerie (`LaravelSecureMessaging`) :**
        *   Interface de messagerie pour conversations individuelles et de groupe.
        *   Notifications de nouveaux messages en temps réel.
        *   (Note: Le chiffrement E2EE doit être géré côté client).
    *   **Programme de Fidélité (`IJILaurels` ou `LoyaltyPlatform`, `ShopLoyalty`) :**
        *   Visualisation du statut de fidélité global (points, palier, avantages).
        *   Sur les pages de boutiques : visualisation du statut de fidélité spécifique à cette boutique.
        *   Indication des points gagnés lors des achats ou interactions.
    *   **Enchères (`AuctionSystem`) :**
        *   Liste des enchères actives/programmées/terminées.
        *   Page de détail d'une enchère (produit, prix actuel, historique des offres, temps restant).
        *   Interface pour placer une offre (avec mises à jour en temps réel).
    *   **Autres :**
        *   Consultation du solde et de l'historique `VirtualCoin` (si applicable pour le client).
        *   Gestion des abonnements (`Subscriptions`) si le client peut s'abonner à des plans.
        *   Alertes de retour en stock (`IJIShopListings` / `Inventory`).

### 2. Dashboard Vendeur / Boutique (`IJICommerce`, `IJIShopListings`, `IJIProductCatalog`, `IJIOrderManagement`, `Inventory`, `ShopLoyalty`, `Analytics` pour sa boutique) :

    *   **Gestion de la Boutique :**
        *   Mise à jour des informations de la boutique (nom, description, logo, etc.).
        *   Gestion des membres de l'équipe de la boutique et de leurs rôles.
    *   **Gestion des Produits de la Boutique (`IJIShopListings`) :**
        *   Lister les `MasterProduct` disponibles pour la vente.
        *   "Vendre ce produit" : créer un `ShopProduct` à partir d'un `MasterProduct`, définir son propre prix, stock, images additionnelles, promotions.
        *   Gestion des variations de `ShopProduct` (prix/stock par variation).
        *   Mise à jour des `ShopProduct` (prix, stock, détails).
        *   Retirer un `ShopProduct` de la vente.
    *   **Propositions de Produits (`IJIProductCatalog`) :**
        *   Soumettre des propositions pour de nouveaux `MasterProduct` si la fonctionnalité est utilisée.
        *   Suivre le statut des propositions.
    *   **Gestion des Commandes (`IJIOrderManagement`) :**
        *   Liste des commandes reçues par la boutique.
        *   Détail d'une commande.
        *   Mise à jour du statut d'une commande (en préparation, expédiée, etc.).
        *   Gestion des retours (`ReturnsManagement`) pour les produits de sa boutique.
    *   **Gestion de l'Inventaire (`Inventory` - via API dédiée si créée) :**
        *   Visualisation des niveaux de stock pour ses `ShopProduct` / `ShopProductVariation`.
        *   (Optionnel) Ajustements manuels de stock (si l'API le permet).
        *   Visualisation des mouvements de stock pour ses produits.
    *   **Programme de Fidélité de la Boutique (`ShopLoyalty` - via API dédiée) :**
        *   Configuration de son programme de fidélité (paliers, points, récompenses).
        *   Activation/désactivation du programme.
        *   Visualisation des clients membres et de leur statut.
    *   **Analytics de la Boutique (`Analytics` - via API dédiée si créée) :**
        *   Statistiques de ventes, vues produits, performance des promotions pour sa boutique.
    *   **Campagnes de Sponsoring (`Sponsorship` - via API dédiée si créée) :**
        *   Créer/gérer des campagnes pour sponsoriser ses posts ou produits.
        *   Suivre la performance des campagnes.
        *   Gestion du budget (interaction avec `VirtualCoin`).

### 3. Dashboard Administrateur de la Plateforme (Accès à toutes les API de gestion) :

    *   **Gestion des Utilisateurs (`UserManagement`) :** Lister, voir, modifier, bannir des utilisateurs.
    *   **Gestion des Boutiques (`IJICommerce`) :** Approuver, suspendre, gérer les boutiques.
    *   **Gestion du Catalogue Maître (`IJIProductCatalog`) :**
        *   CRUD pour `MasterProduct`, `Brand`, `Category`, `ProductAttribute`.
        *   Gestion et approbation des `ProductProposal`.
    *   **Supervision des Commandes (`IJIOrderManagement`) :** Vue globale des commandes.
    *   **Gestion des Enchères (`AuctionSystem`) :** Supervision.
    *   **Gestion de l'Inventaire (`Inventory`) :** Vue globale, gestion des localisations.
    *   **Gestion des Mouvements de Stock (`Inventory`).**
    *   **Analytics Plateforme (`Analytics`) :** Statistiques globales.
    *   **Configuration des Programmes de Fidélité Plateforme (`IJILaurels` / `LoyaltyPlatform`).**
    *   **Gestion des Retours (`ReturnsManagement`).**
    *   **Gestion du Sponsoring (`Sponsorship`).**
    *   **Gestion des Abonnements (`Subscriptions`).**
    *   **Gestion des `VirtualCoin`.**
    *   **Modération de Contenu (`Social`).**
    *   **Configuration Générale de la Plateforme.**

## Points d'Attention Spécifiques :

*   **Modularité :** Le frontend devrait être conçu de manière modulaire, idéalement en reflétant la structure des packages backend pour faciliter la maintenance et l'évolution.
*   **Performance :** Optimiser les chargements, utiliser le lazy loading, et gérer efficacement l'état.
*   **Sécurité :** Bonnes pratiques pour la gestion des tokens, protection XSS, etc.
*   **UX/UI :** Interface intuitive, claire, et esthétiquement agréable. Design responsive pour mobile et desktop.
*   **Internationalisation (`Internationalization`) :** Le frontend doit être conçu pour supporter plusieurs langues si le backend le permet.
*   **Gestion des Erreurs :** Affichage clair des erreurs API et des messages de validation.
