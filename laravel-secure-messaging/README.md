# Laravel Secure Messaging

[![Latest Version on Packagist](https://img.shields.io/packagist/v/acme/laravel-secure-messaging.svg?style=flat-square)](https://packagist.org/packages/acme/laravel-secure-messaging)
[![Total Downloads](https://img.shields.io/packagist/dt/acme/laravel-secure-messaging.svg?style=flat-square)](https://packagist.org/packages/acme/laravel-secure-messaging)

Laravel Secure Messaging est un package Laravel conçu pour intégrer facilement un système de messagerie sécurisée de bout en bout (E2EE) dans votre application Laravel. Il fournit une API RESTful robuste pour la gestion des utilisateurs, les messages individuels et de groupe, les notifications en temps réel, et plus encore, avec un accent sur la sécurité et la performance.

Ce package s'inspire des fonctionnalités de messageries modernes comme Telegram et Signal, en mettant l'accent sur le fait que le serveur ne devrait jamais avoir accès au contenu en clair des messages échangés.

**Note:** Ce package fournit l'infrastructure backend. L'implémentation client (pour le chiffrement E2EE effectif, la gestion des clés privées, et l'interface utilisateur) est à la charge de l'application hôte.

## Fonctionnalités Clés

*   **API RESTful Complète :** Pour toutes les opérations de messagerie.
*   **Chiffrement de Bout en Bout (Modèle Supporté) :** Les messages sont destinés à être chiffrés par le client avant d'être envoyés au serveur. Le serveur stocke uniquement les contenus chiffrés.
*   **Messagerie Individuelle et de Groupe.**
*   **Gestion des Utilisateurs :** Profils, gestion des clés publiques (via le modèle User de l'application).
*   **Notifications en Temps Réel :** Via Laravel Echo (supporte Pusher, Soketi/Laravel WebSockets, etc.) pour les nouveaux messages, statuts de lecture, indicateurs de saisie, etc.
*   **Statut de Lecture et Horodatage.**
*   **Indicateurs de Saisie.**
*   **Messages Éphémères.**
*   **Support des Pièces Jointes** (chiffrées par le client).
*   **Rate Limiting et Caching** pour la performance.
*   **Structure Modulaire et Extensible** via les événements Laravel.
*   **Compatible Laravel 10.x+**.

## Exigences

*   PHP ^8.1
*   Laravel ^10.0|^11.0
*   Extension PHP `sodium` (pour le chiffrement).
*   Une application Laravel existante avec un système d'authentification (Sanctum est recommandé pour les SPAs).
*   Un driver de diffusion configuré (Pusher, Soketi, etc.) pour les notifications en temps réel.
*   Un driver de cache supportant les tags (Redis, Memcached) pour une performance optimale du cache.

## Installation

1.  Installez le package via Composer :
    ```bash
    composer require acme/laravel-secure-messaging
    ```
    *(Note: Remplacez `acme/laravel-secure-messaging` par le nom réel du package une fois publié.)*

2.  Publiez le fichier de configuration :
    ```bash
    php artisan vendor:publish --provider="Acme\SecureMessaging\SecureMessagingServiceProvider" --tag="messaging-config"
    ```
    Cela créera un fichier `config/messaging.php`.

3.  Publiez les migrations :
    ```bash
    php artisan vendor:publish --provider="Acme\SecureMessaging\SecureMessagingServiceProvider" --tag="messaging-migrations"
    ```

4.  Exécutez les migrations pour créer les tables nécessaires :
    ```bash
    php artisan migrate
    ```

5.  Publiez le fichier des canaux de diffusion (si vous souhaitez le personnaliser ou pour référence) :
    ```bash
    php artisan vendor:publish --provider="Acme\SecureMessaging\SecureMessagingServiceProvider" --tag="messaging-channels"
    ```
    Cela publiera `channels_secure_messaging.php` dans votre dossier `routes/`. Vous devrez alors l'inclure dans la méthode `boot` de votre `App\Providers\BroadcastServiceProvider.php` :
    ```php
    // Dans app/Providers/BroadcastServiceProvider.php
    public function boot()
    {
        Broadcast::routes();

        require base_path('routes/channels.php'); // Votre fichier de canaux principal
        if (file_exists(base_path('routes/channels_secure_messaging.php'))) {
            require base_path('routes/channels_secure_messaging.php'); // Canaux du package
        }
    }
    ```

## Configuration

Après avoir publié les fichiers, examinez `config/messaging.php` et ajustez les paramètres selon vos besoins.

### Points Clés de Configuration :

*   **`user_model`**: Spécifiez le modèle Eloquent de votre application pour les utilisateurs.
    ```php
    'user_model' => App\Models\User::class,
    ```
    Assurez-vous que ce modèle User :
    *   Possède un attribut `public_key` (par exemple, une colonne `TEXT NULLABLE` dans votre table `users`). Ce champ est utilisé pour stocker la clé publique de chiffrement de l'utilisateur.
    *   Rend cet attribut `public_key` accessible (par exemple, en l'ajoutant à `$fillable` si vous permettez sa mise à jour via le profil, ou en utilisant des accesseurs/mutateurs).

*   **`auth_driver`**: `'sanctum'` (par défaut) ou `'jwt'` (nécessite une configuration supplémentaire).

*   **`notifications.driver`**: Driver de diffusion pour les notifications (ex: `pusher`, `redis`). Doit correspondre à votre configuration dans `config/broadcasting.php`.

*   **`routes`**: Préfixe et middlewares pour les routes du package. Le middleware d'authentification par défaut est `auth:sanctum`.

*   **`features`**: Activez/désactivez des fonctionnalités comme les messages éphémères ou les pièces jointes, et configurez leurs paramètres (TTL par défaut, taille max des fichiers, types MIME autorisés, disque de stockage pour les pièces jointes).

*   **`rate_limiting`**: Configurez les limites de débit pour les actions sensibles (envoi de message, création de groupe, upload). Les noms des limiteurs (`send_message`, `create_group`, `upload_attachment`) sont utilisés dans les routes du package.

*   **`caching`**: Activez/désactivez le cache et configurez le store et les TTLs. Pour une invalidation optimale (surtout pour les listes paginées), utilisez un driver de cache qui supporte les **tags** (ex: `redis`, `memcached`).

*   **`user_model_public_columns`**: Colonnes à sélectionner lors du chargement des données utilisateur publiques (participants, expéditeurs) pour éviter d'exposer des données sensibles.

*   **`pagination_limit` / `pagination_limit_conversations`**: Limites de pagination par défaut.

### Configuration de la Diffusion (Broadcasting)

Assurez-vous que votre application Laravel est configurée pour la diffusion. Installez le driver approprié (Pusher, Soketi, etc.) et configurez vos variables d'environnement (`BROADCAST_DRIVER`, `PUSHER_APP_KEY`, etc.).

Consultez la [documentation Laravel sur la Diffusion](https://laravel.com/docs/broadcasting) pour plus de détails.

## Utilisation de l'API

Toutes les routes de l'API sont préfixées par défaut par `/api/messaging` (configurable). L'authentification se fait via le driver configuré (Sanctum par défaut).

### Authentification

Pour Sanctum, assurez-vous que votre client (SPA, mobile) obtient un token API ou utilise l'authentification basée sur les cookies pour les SPA. Incluez le token comme Bearer token dans l'en-tête `Authorization` de vos requêtes.

### Endpoints Principaux

*(Les exemples de réponses sont simplifiés)*

#### Profil Utilisateur

*   **`GET /profile`**: Récupère le profil de l'utilisateur authentifié.
    *   Réponse : `{ "message": "...", "data": { "id": 1, "name": "John Doe", "email": "...", "public_key": "..." } }`
*   **`PUT /profile`**: Met à jour le profil de l'utilisateur authentifié.
    *   Payload : `{ "name": "Johnathan Doe", "public_key": "nouvelle_cle_publique_base64" }`
    *   Réponse : Profil mis à jour.
*   **`GET /users/{userId}/public-key`**: Récupère la clé publique d'un utilisateur spécifique.
    *   Réponse : `{ "message": "...", "data": { "user_id": 2, "public_key": "..." } }`

#### Messages

*   **`POST /messages`**: Envoie un nouveau message.
    *   Payload :
        ```json
        {
            "conversation_id": "uuid-de-la-conversation-existante", // OU "recipient_id": 2 pour une nouvelle conversation individuelle
            "encrypted_contents": {
                // Clé: ID de l'utilisateur, Valeur: Message chiffré pour CET utilisateur avec SA clé publique
                "1": "ciphertext_pour_user1_base64", // Pour le destinataire/membre 1
                "5": "ciphertext_pour_user_expediteur_base64" // Pour l'expéditeur lui-même
            },
            "type": "text", // ou "ephemeral_text", "image", "file"
            // Pour les messages éphémères de type "ephemeral_text":
            // "ttl_seconds": 3600, // Durée de vie en secondes
            // Pour les pièces jointes (type "image" ou "file"):
            // "attachment_path": "chemin/retourne/par/upload/endpoint.encrypted",
            // "attachment_original_name": "nom_original.jpg",
            // "attachment_mime_type": "image/jpeg"
        }
        ```
    *   Réponse : Détails du message créé.
*   **`GET /conversations/{conversationUuid}/messages`**: Récupère les messages d'une conversation (paginé).
    *   Réponse : Liste paginée de messages. Chaque message inclura `user_specific_content` qui est le contenu chiffré pour l'utilisateur authentifié.
*   **`PUT /messages/{messageId}/read`**: Marque un message comme lu.
    *   Réponse : Confirmation.
*   **`DELETE /messages/{messageId}`**: Supprime la copie du message pour l'utilisateur authentifié (soft delete de sa vue).
    *   Réponse : Confirmation.

#### Conversations

*   **`GET /conversations`**: Liste les conversations de l'utilisateur authentifié (paginé).
    *   Réponse : Liste paginée de conversations, triées par `last_message_at`.
*   **`GET /conversations/{conversationUuid}`**: Récupère les détails d'une conversation spécifique.
    *   Réponse : Détails de la conversation et de ses participants.

#### Groupes

*   **`POST /groups`**: Crée un nouveau groupe.
    *   Payload : `{ "name": "Nom du Groupe", "description": "...", "members": [2, 3] }` (members est optionnel)
    *   Réponse : Détails du groupe créé.
*   **`GET /groups/{groupUuid}`**: Affiche les détails d'un groupe.
*   **`PUT /groups/{groupUuid}`**: Met à jour un groupe (nom, description). (Admin requis)
*   **`DELETE /groups/{groupUuid}`**: Supprime un groupe. (Admin requis)
*   **`GET /groups/{groupUuid}/members`**: Liste les membres d'un groupe.
*   **`POST /groups/{groupUuid}/members/{userIdToAdd}`**: Ajoute un membre à un groupe. (Admin requis)
*   **`DELETE /groups/{groupUuid}/members/{userIdToRemove}`**: Retire un membre d'un groupe. (Admin requis, ou soi-même)
*   **`PUT /groups/{groupUuid}/members/{memberIdToUpdate}/role`**: Met à jour le rôle d'un membre. (Admin requis)

#### Pièces Jointes

*   **`POST /attachments`**: Uploade un fichier (qui doit être préalablement chiffré par le client).
    *   Payload : `multipart/form-data` avec un champ `attachment` contenant le fichier.
    *   Réponse : `{ "message": "...", "data": { "attachment_path": "...", "original_name": "...", "mime_type": "...", "size_kb": ... } }`
    *   Le `attachment_path` retourné est ensuite utilisé lors de l'envoi d'un message de type `image` ou `file`.

#### Indicateurs de Saisie

*   **`POST /conversations/{conversationUuid}/typing`**: Signale que l'utilisateur est en train d'écrire ou a arrêté.
    *   Payload : `{ "is_typing": true }` ou `{ "is_typing": false }`
    *   Réponse : Confirmation. L'événement est diffusé aux autres membres de la conversation.

## Chiffrement de Bout en Bout (E2EE)

Ce package est conçu pour faciliter le E2EE, mais la responsabilité principale du chiffrement et de la gestion des clés privées incombe au client.

*   **Génération des Clés :** Chaque client doit générer une paire de clés de chiffrement robuste (par exemple, en utilisant `libsodium` ou une bibliothèque cryptographique équivalente).
    *   La **clé publique** est envoyée au serveur et stockée (associée au profil utilisateur, via l'endpoint `PUT /profile`).
    *   La **clé privée** NE DOIT JAMAIS être envoyée au serveur en clair. Elle doit être stockée de manière sécurisée sur l'appareil du client, protégée par un mot de passe ou le système de stockage sécurisé de l'OS.
*   **Chiffrement :** Avant d'envoyer un message (via `POST /messages`), le client doit :
    1.  Identifier tous les destinataires du message (y compris lui-même pour pouvoir lire ses messages envoyés).
    2.  Pour chaque destinataire, récupérer sa clé publique (via `GET /users/{userId}/public-key` ou depuis un cache local).
    3.  Chiffrer le contenu du message (et les pièces jointes) séparément pour chaque destinataire en utilisant sa clé publique respective (par exemple, avec `sodium_crypto_box_seal`).
    4.  Envoyer les contenus chiffrés dans le champ `encrypted_contents` de la requête `POST /messages`.
*   **Déchiffrement :** Lorsqu'un client reçoit un message (via `GET /conversations/{uuid}/messages`), le champ `user_specific_content` contient le message chiffré pour cet utilisateur. Le client utilise sa clé privée pour déchiffrer ce contenu.
*   **Pièces Jointes :** Les pièces jointes doivent être chiffrées par le client *avant* d'être uploadées via `POST /attachments`. Le serveur stocke le fichier chiffré. Lors de la réception d'un message avec une pièce jointe, le client télécharge le fichier chiffré (en utilisant le `attachment_path`) et le déchiffre localement.

## Notifications en Temps Réel

Le package déclenche plusieurs événements qui sont diffusés via Laravel Echo. Votre client doit s'abonner aux canaux appropriés.

*   **Canal Principal :** `private-conversation.{conversationUuid}`
    *   Les clients doivent s'authentifier pour écouter ce canal. L'autorisation est gérée par le fichier `channels_secure_messaging.php` du package.
*   **Événements Diffusés sur `private-conversation.{conversationUuid}` :**
    *   `new.message` (Classe: `NewMessageSent`): Lorsqu'un nouveau message est envoyé. Contient les métadonnées du message (expéditeur, conversation, etc., mais pas le contenu chiffré spécifique qui doit être récupéré via API si nécessaire ou si le client est le destinataire).
    *   `message.read` (Classe: `MessageRead`): Lorsqu'un message est marqué comme lu. Contient l'ID du message et l'ID de l'utilisateur qui l'a lu.
    *   `group.user.joined` (Classe: `UserJoinedGroup`): Lorsqu'un utilisateur rejoint un groupe (et donc sa conversation).
    *   `group.user.left` (Classe: `UserLeftGroup`): Lorsqu'un utilisateur quitte un groupe.
    *   `typing.indicator` (Classe: `TypingIndicator`): Lorsqu'un utilisateur commence ou arrête d'écrire. Diffusé avec `toOthers()`.

**Exemple d'abonnement client avec Laravel Echo et PusherJS :**
```javascript
// Assurez-vous que Echo est configuré (voir documentation Laravel)
let conversationUuid = 'some-uuid'; // L'UUID de la conversation actuelle

Echo.private(`conversation.${conversationUuid}`)
    .listen('.new.message', (e) => {
        console.log('Nouveau message reçu:', e);
        // Mettre à jour l'UI, récupérer le contenu chiffré spécifique si nécessaire
    })
    .listen('.message.read', (e) => {
        console.log('Message lu:', e);
        // Mettre à jour l'UI pour indiquer le statut de lecture
    })
    .listen('.group.user.joined', (e) => {
        console.log('Utilisateur a rejoint:', e.user.name, 'dans le groupe:', e.group_name);
    })
    .listen('.group.user.left', (e) => {
        console.log('Utilisateur a quitté:', e.user_id, 'du groupe:', e.group_name);
    })
    .listenForWhisper('.typing.indicator', (e) => { // Note: .listenForWhisper est pour les client events, or .listen pour les server events
         // Correction: L'événement TypingIndicator est un événement serveur standard, pas un whisper.
         // Donc, c'est .listen('.typing.indicator', (e) => { ... });
         // Le broadcast(event)->toOthers() côté serveur empêche l'expéditeur de le recevoir.
        console.log('Typing:', e.user.name, e.is_typing);
        // Mettre à jour l'UI avec l'indicateur de saisie
    });

// Pour les indicateurs de saisie, si vous utilisez Presence Channels,
// vous pouvez aussi utiliser Echo.join(...).here(...).joining(...).leaving(...).listenForWhisper(...)
```
Correction pour l'écoute de `TypingIndicator`:
```javascript
Echo.private(`conversation.${conversationUuid}`)
    .listen('.typing.indicator', (e) => {
        // Assurez-vous que l'ID de l'utilisateur qui tape n'est pas l'ID de l'utilisateur actuel
        // window.Laravel.userId est un exemple, adaptez pour récupérer l'ID de l'utilisateur courant dans votre client
        // if (e.user.id !== window.Laravel.userId) {
        //     console.log('Typing:', e.user.name, 'is typing:', e.is_typing);
        // Mettre à jour l'UI
        // }
        // Le .toOthers() côté serveur devrait déjà gérer cela, donc le client peut juste écouter.
        console.log('Typing:', e.user.name, 'is typing:', e.is_typing);
        // Mettre à jour l'UI pour afficher que e.user.name tape...
    });
```


## Fonctionnalités Avancées (Détails)

### Messages Éphémères
*   Lors de l'envoi d'un message (`POST /messages`), si `type` est `ephemeral_text`, vous pouvez inclure `ttl_seconds` (par exemple, `3600` pour 1 heure).
*   Le serveur calculera `expires_at`.
*   Les clients sont responsables de ne pas afficher les messages dont `expires_at` est passé.
*   Une future tâche planifiée côté serveur pourrait nettoyer (hard delete) les messages éphémères expirés.

### Pièces Jointes
1.  Le client chiffre le fichier.
2.  Le client envoie le fichier chiffré à `POST /attachments`.
3.  Le serveur stocke le fichier et retourne un `attachment_path` et d'autres métadonnées.
4.  Le client envoie ensuite un message (type `image` ou `file`) via `POST /messages`, en incluant `attachment_path`, `attachment_original_name`, et `attachment_mime_type`.
5.  Les autres clients, en recevant le message, utilisent `attachment_path` pour (potentiellement via une route de téléchargement sécurisée non implémentée dans ce package de base) récupérer le fichier chiffré, puis le déchiffrent localement.

### Bots Programmables (Architecture Proposée)
L'intégration de bots programmables est une fonctionnalité avancée qui pourrait être ajoutée. L'architecture envisagée comprendrait :
*   **Modèle `Bot` :** Représentant un utilisateur bot, potentiellement lié à un `User` avec un rôle spécial.
*   **Authentification des Bots :** Mécanisme de token dédié pour les bots.
*   **API pour les Bots :**
    *   Endpoints pour que les bots puissent envoyer des messages.
    *   Webhooks pour que les bots reçoivent des messages (ceux qui leur sont adressés ou tous les messages d'une conversation à laquelle ils participent).
*   **Permissions :** Contrôle fin sur ce que les bots peuvent faire (lire les messages, envoyer des messages, ajouter/supprimer des utilisateurs des groupes, etc.).
*   **Découverte :** Comment les utilisateurs trouvent et interagissent avec les bots.

Cette fonctionnalité n'est pas incluse dans la version actuelle mais la structure du package permet son ajout ultérieur.

## Extensibilité

Le package déclenche plusieurs événements Laravel (voir section Notifications) que votre application peut écouter pour ajouter des fonctionnalités personnalisées ou intégrer avec d'autres systèmes.

Exemple :
```php
// Dans un Service Provider de votre application
use Acme\SecureMessaging\Events\NewMessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log; // N'oubliez pas d'importer Log

Event::listen(function (NewMessageSent $event) {
    // Logique personnalisée ici
    Log::info("Nouveau message envoyé dans la conversation {$event->conversation->uuid}");
});
```

## Tests

Pour exécuter les tests du package (PHPUnit) :
1.  Assurez-vous que les dépendances de développement sont installées : `composer install --dev`
2.  Depuis la racine de votre application Laravel (où le package est installé en dépendance) :
    ```bash
    php artisan test vendor/acme/laravel-secure-messaging/phpunit.xml.dist
    ```
    Ou, si vous avez cloné le package directement et qu'il a son propre `phpunit.xml` (ce qui n'est pas le cas ici car Testbench est utilisé) :
    ```bash
    # Depuis la racine du package cloné
    ./vendor/bin/phpunit
    ```
    La configuration de Testbench permet d'exécuter les tests dans le contexte d'une application Laravel minimale.

## Monitoring et Métriques

Pour surveiller la santé et l'utilisation de votre système de messagerie, considérez les points suivants :

*   **Logs Applicatifs :** Le package enregistre les erreurs critiques (échecs de chiffrement, problèmes de base de données, etc.) en utilisant le système de Log de Laravel. Assurez-vous que votre application est configurée pour collecter et analyser ces logs. Des logs d'information pour les actions d'administration de groupe (ajout/suppression de membre, changement de rôle, suppression de groupe) sont également émis.
*   **Performance des Requêtes :** Utilisez des outils comme Laravel Telescope, Debugbar (en développement), ou des services APM (New Relic, Datadog) pour surveiller les temps de réponse des API du package et identifier les requêtes de base de données lentes.
*   **Taux d'Erreur :** Surveillez le taux d'erreurs HTTP (4xx, 5xx) sur les endpoints de l'API du package.
*   **Utilisation des Files d'Attente (Queues) :** Si vous utilisez les files d'attente pour les notifications (en configurant les événements `ShouldQueue`), surveillez la longueur des files d'attente et le taux de traitement des jobs.
*   **Métriques Personnalisées via les Événements :** L'application hôte peut écouter les événements déclenchés par ce package pour collecter des métriques personnalisées. Exemples :
    *   `Acme\SecureMessaging\Events\NewMessageSent`: Pour compter le nombre de messages envoyés.
    *   `Acme\SecureMessaging\Events\UserJoinedGroup` / `UserLeftGroup`: Pour suivre l'activité des groupes.
    *   Vous pouvez créer des listeners dans votre application qui incrémentent des compteurs dans votre système de métriques (Prometheus, StatsD, etc.) lorsque ces événements se produisent.

## Compatibilité des Bases de Données

Ce package est conçu pour fonctionner avec les bases de données relationnelles supportées par Laravel Eloquent, notamment :

*   MySQL (5.7+ / 8.0+)
*   PostgreSQL (10.0+)
*   SQLite (3.8.8+) (principalement pour les tests et le développement local)
*   SQL Server (2017+)

Les migrations utilisent des types de champs standards pour assurer une large compatibilité.

### Considérations pour NoSQL (MongoDB)

La version actuelle du package est optimisée pour les bases de données relationnelles via Eloquent. Une adaptation pour des bases de données NoSQL comme MongoDB nécessiterait une refonte significative de la couche d'accès aux données (par exemple, en introduisant un motif Repository et des modèles/services agnostiques au type de base de données, ou en utilisant un ODM spécifique à MongoDB comme `mongodb/laravel-mongodb`). Bien que cela ne soit pas supporté nativement, la logique métier et de chiffrement pourrait être réutilisée.

## Plan pour l'Interface Utilisateur (Architecture Proposée)

Ce package fournit une API backend robuste pour un système de messagerie sécurisée. Pour construire une interface utilisateur (UI) complète, plusieurs approches sont possibles, en fonction des technologies et des préférences de l'application hôte.

**Approche Technologique (Exemples) :**

1.  **Single Page Application (SPA) :**
    *   *Frameworks :* Vue.js (avec Vuex/Pinia), React (avec Redux/Context API), ou Svelte.
    *   *Communication :* Consomme directement l'API RESTful fournie par ce package.
    *   *Avantages :* Expérience utilisateur riche et réactive, séparation claire frontend/backend.
    *   *Considérations :* Gestion de l'état client plus complexe, authentification API (Sanctum).

2.  **Application Laravel Traditionnelle avec Composants Réactifs :**
    *   *Base :* Vues Blade Laravel.
    *   *Réactivité :* Laravel Livewire ou Alpine.js.
    *   *Avantages :* Développement rapide si familier avec Laravel, bonne intégration.
    *   *Considérations :* Moins flexible pour des UI très complexes.

3.  **Application Mobile Native ou Hybride :**
    *   *Frameworks :* Swift/Kotlin, React Native, Flutter.
    *   *Communication :* Consomme l'API RESTful.

**Composants Clés de l'UI :**

*   **Authentification et Gestion du Profil :** Incluant la génération et la gestion sécurisée des clés E2EE côté client (la clé privée ne quitte jamais le client).
*   **Liste des Conversations :** Affichage des conversations, non-lus, démarrage de nouvelles conversations.
*   **Vue de Conversation :** Affichage des messages (déchiffrés localement), envoi de messages (chiffrés localement pour chaque destinataire), support des messages éphémères et pièces jointes (chiffrées/déchiffrées localement).
*   **Gestion des Groupes :** Création, affichage, gestion des membres et des rôles.
*   **Notifications :** Intégration avec Laravel Echo pour les mises à jour en temps réel.

**Logique Côté Client pour E2EE (Crucial) :**

*   **Bibliothèque Crypto :** `libsodium-wrappers` (pour JS) ou équivalent.
*   **Gestion Clé Privée :** Stockage local sécurisé (ex: IndexedDB chiffré, keychain/keystore de l'OS), jamais envoyée au serveur.
*   **Flux Chiffrement/Déchiffrement :**
    *   *Envoi :* Le client récupère les clés publiques des destinataires, chiffre le message pour chacun, et envoie les multiples versions chiffrées à l'API (`encrypted_contents`).
    *   *Réception :* Le client récupère son message chiffré (`user_specific_content`) et le déchiffre avec sa clé privée.

L'application hôte est responsable de l'implémentation de cette logique client pour garantir un véritable chiffrement de bout en bout.

## Licence

Ce package est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails (Ce fichier serait à créer).

---
