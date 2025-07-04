# Guide d'Intégration des Packages IJIDeals dans un Projet Laravel

Ce guide détaille les étapes nécessaires pour intégrer et configurer les packages IJIDeals dans un nouveau projet Laravel, ainsi qu'une solution pour automatiser ce processus.

## 1. Analyse des Configurations Requises par Package

L'intégration de chaque package dans un projet Laravel implique généralement les étapes suivantes :

*   **Ajout aux dépendances Composer :** Chaque package doit être listé dans le fichier `composer.json` du projet Laravel principal.
*   **Exécution de `composer update` :** Pour télécharger les packages et leurs dépendances.
*   **Enregistrement du Service Provider :** Généralement automatique via la "Package Auto-Discovery" (Laravel 5.5+).
*   **Publication des fichiers de configuration :** Si un package a un fichier `config/*.php`, il doit être publié dans le répertoire `config/` du projet principal.
*   **Exécution des migrations :** Si un package définit des tables de base de données, ses migrations doivent être exécutées.
*   **Enregistrement des Facades :** (Si applicable) Souvent auto-découvertes.

Voici un aperçu des configurations spécifiques pour les packages modifiés/ajoutés :

| Package                     | Service Provider                                     | Migrations | Configuration (à publier)      | Facade (si applicable) |
| :-------------------------- | :--------------------------------------------------- | :--------- | :----------------------------- | :--------------------- |
| `pricing`                   | `IJIDeals\Pricing\Providers\PricingServiceProvider` | Oui        | `config/pricing.php`           |                        |
| `auction-system`            | `IJIDeals\AuctionSystem\Providers\AuctionSystemServiceProvider` | Oui        | `config/auction-system.php`    |                        |
| `analytics`                 | `IJIDeals\Analytics\Providers\AnalyticsServiceProvider` | Oui        | `config/analytics.php`         | `Analytics`            |
| `returns-management`        | `IJIDeals\ReturnsManagement\ReturnsManagementServiceProvider` | Oui        | `config/returns-management.php`|                        |
| `recommendation-engine`     | `IJIDeals\RecommendationEngine\RecommendationEngineServiceProvider` | Non        | `config/recommendation-engine.php`|                        |

## 2. Configuration des Dépôts de Chemins (pour les packages locaux)

Si vos packages `ijideals/*` ne sont pas publiés sur Packagist, vous devez indiquer à Composer où les trouver en configurant des "dépôts de chemins" (`path repositories`) dans le `composer.json` de votre projet Laravel principal.

**Ajoutez la section `repositories` à votre `composer.json` principal, AVANT d'exécuter `composer require` pour ces packages :**

```json
{
    "name": "laravel/laravel",
    "description": "The Laravel Framework.",
    "repositories": [
        {
            "type": "path",
            "url": "../packages-refactor/pricing"
        },
        {
            "type": "path",
            "url": "../packages-refactor/auction-system"
        },
        {
            "type": "path",
            "url": "../packages-refactor/analytics"
        },
        {
            "type": "path",
            "url": "../packages-refactor/returns-management"
        },
        {
            "type": "path",
            "url": "../packages-refactor/recommendation-engine"
        },
        {
            "type": "path",
            "url": "../packages-refactor/user-management"
        },
        {
            "type": "path",
            "url": "../packages-refactor/ijicommerce"
        },
        {
            "type": "path",
            "url": "../packages-refactor/ijiproductcatalog"
        },
        {
            "type": "path",
            "url": "../packages-refactor/notifications-manager"
        },
        {
            "type": "path",
            "url": "../packages-refactor/inventory"
        },
        {
            "type": "path",
            "url": "../packages-refactor/file-management"
        },
        {
            "type": "path",
            "url": "../packages-refactor/location"
        },
        {
            "type": "path",
            "url": "../packages-refactor/social"
        },
        {
            "type": "path",
            "url": "../packages-refactor/sponsorship"
        },
        {
            "type": "path",
            "url": "../packages-refactor/subscriptions"
        },
        {
            "type": "path",
            "url": "../packages-refactor/virtualcoin"
        },
        {
            "type": "path",
            "url": "../packages-refactor/ijilaurels"
        },
        {
            "type": "path",
            "url": "../packages-refactor/ijishoplistings"
        },
        {
            "type": "path",
            "url": "../packages-refactor/laravel-secure-messaging"
        },
        {
            "type": "path",
            "url": "../packages-refactor/internationalization"
        },
        {
            "type": "path",
            "url": "../packages-refactor/ijicommerce-productcollaboration"
        },
        {
            "type": "path",
            "url": "../packages-refactor/ijiordermanagement"
        }
    ],
    "require": {
        // ... vos dépendances Laravel habituelles ...
    }
}
```
**Note :** Les chemins (`../packages-refactor/pricing`, etc.) sont relatifs au répertoire racine de votre projet Laravel. Assurez-vous qu'ils correspondent à l'emplacement réel de vos packages.

## 3. Commande Artisan d'Installation Centralisée

Pour automatiser la publication des configurations et l'exécution des migrations, une commande Artisan personnalisée est recommandée.

**1. Créez la commande Artisan :**
```bash
php artisan make:command InstallIJIDealsPackages
```

**2. Modifiez le fichier `app/Console/Commands/InstallIJIDealsPackages.php` :**

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class InstallIJIDealsPackages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ijideals:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Installs and configures all IJIDeals packages.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting IJIDeals packages installation...');

        // 1. Run composer update (assuming packages are already in composer.json)
        $this->comment('Running composer update...');
        $this->runProcess('composer update');

        // 2. Publish configurations for each package
        $this->comment('Publishing package configurations...');
        $this->publishConfig('IJIDeals\\Pricing\\Providers\\PricingServiceProvider');
        $this->publishConfig('IJIDeals\\AuctionSystem\\Providers\\AuctionSystemServiceProvider');
        $this->publishConfig('IJIDeals\\Analytics\\Providers\\AnalyticsServiceProvider');
        $this->publishConfig('IJIDeals\\ReturnsManagement\\ReturnsManagementServiceProvider');
        $this->publishConfig('IJIDeals\\RecommendationEngine\\RecommendationEngineServiceProvider');
        // Ajoutez ici les autres Service Providers de vos packages si nécessaire

        // 3. Run database migrations
        $this->comment('Running database migrations...');
        $this->runProcess('php artisan migrate');

        $this->info('IJIDeals packages installed and configured successfully!');

        return Command::SUCCESS;
    }

    /**
     * Helper to run shell commands.
     *
     * @param string $command
     * @return void
     */
    protected function runProcess(string $command): void
    {
        $process = Process::fromShellCommandline($command);
        $process->setTimeout(null); // No timeout
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if (!$process->isSuccessful()) {
            $this->error("Command failed: {$command}");
            throw new \RuntimeException($process->getErrorOutput());
        }
    }

    /**
     * Helper to publish package configurations.
     *
     * @param string $provider
     * @return void
     */
    protected function publishConfig(string $provider): void
    {
        $this->info("Publishing config for {$provider}...");
        $this->runProcess("php artisan vendor:publish --provider=\"{}\" --tag=\"config\"");
    }
}
```

**3. Exécution de la commande Artisan :**
Dans votre projet Laravel, exécutez :
```bash
php artisan ijideals:install
```

## 4. Script d'Automatisation pour l'Ajout à un Nouveau Projet

Ce script va ajouter les packages à votre `composer.json` et exécuter la commande Artisan `ijideals:install`.

**1. Créez un fichier `install_ijideals_suite.sh` (pour Linux/macOS/Git Bash) ou `install_ijideals_suite.bat` (pour Windows) :**

**`install_ijideals_suite.sh` (Linux/macOS/Git Bash) :**
```bash
#!/bin/bash

# Ce script automatise l'ajout et la configuration initiale des packages IJIDeals
# dans un nouveau projet Laravel.

# --- Configuration ---
# Définissez le chemin vers la racine de votre projet Laravel.
# Si vous exécutez ce script depuis la racine de votre projet Laravel, laissez "."
# Sinon, spécifiez le chemin absolu ou relatif, ex: "/path/to/your/laravel_project"
LARAVEL_PROJECT_PATH="."

# Définissez les packages IJIDeals à installer
IJIDEALS_PACKAGES=(
    "ijideals/pricing"
    "ijideals/auction-system"
    "ijideals/analytics"
    "ijideals/returns-management"
    "ijideals/recommendation-engine"
    "ijideals/user-management"
    "ijideals/ijicommerce"
    "ijideals/ijiproductcatalog"
    "ijideals/notifications-manager"
    "ijideals/inventory"
    "ijideals/file-management"
    "ijideals/location"
    "ijideals/social"
    "ijideals/sponsorship"
    "ijideals/subscriptions"
    "ijideals/virtualcoin"
    "ijideals/ijilaurels"
    "ijideals/ijishoplistings"
    "ijideals/laravel-secure-messaging"
    "ijideals/internationalization"
    "ijideals/ijicommerce-productcollaboration"
    "ijideals/ijiordermanagement"
)

# Définissez la contrainte de version pour les packages.
PACKAGE_VERSION="*" # Utilisez "*" pour n'importe quelle version, ou une version spécifique comme "^1.0"

# --- Logique du Script ---

echo "--- Installation de la Suite de Packages IJIDeals ---"

# Vérifier si le chemin du projet Laravel existe
if [ ! -d "$LARAVEL_PROJECT_PATH" ]; then
    echo "Erreur : Le chemin du projet Laravel '$LARAVEL_PROJECT_PATH' n'existe pas."
    exit 1
fi

# Naviguer vers le répertoire du projet Laravel
echo "Navigation vers le répertoire du projet Laravel : $LARAVEL_PROJECT_PATH"
cd "$LARAVEL_PROJECT_PATH" || { echo "Échec du changement de répertoire. Sortie."; exit 1; }

# Vérifier si composer est disponible
if ! command -v composer &> /dev/null
then
    echo "Erreur : Composer n'est pas installé ou n'est pas dans votre PATH. Veuillez installer Composer d'abord."
    exit 1
fi

# Rappel pour la configuration des dépôts de chemins
echo "IMPORTANT : Assurez-vous que les 'path repositories' sont configurés dans votre composer.json avant de continuer."
read -p "Appuyez sur Entrée pour continuer après avoir configuré les dépôts de chemins..."

# Ajouter chaque package IJIDeals
for package in "${IJIDEALS_PACKAGES[@]}"; do
    echo "Ajout du package : $package:$PACKAGE_VERSION"
    composer require "$package:$PACKAGE_VERSION"
    if [ $? -ne 0 ]; then
        echo "Erreur : Échec de l'ajout de $package. Veuillez vérifier votre composer.json et votre connexion réseau."
        exit 1
    fi
done

echo "Tous les packages IJIDeals spécifiés ont été ajoutés à composer.json."

# Exécuter la commande Artisan personnalisée pour la configuration
if [ -f "artisan" ]; then
    echo "Exécution de la commande d'installation Artisan personnalisée IJIDeals..."
    php artisan ijideals:install
    if [ $? -ne 0 ]; then
        echo "Avertissement : La commande d'installation Artisan personnalisée a échoué. Veuillez vérifier la sortie ci-dessus."
    fi
else
    echo "Avertissement : La commande artisan n'a pas été trouvée. Ignorons la commande d'installation personnalisée IJIDeals."
fi

echo "--- Installation Terminée ---"
echo "Vous devrez peut-être exécuter 'php artisan migrate' et 'php artisan db:seed' manuellement si cela n'est pas géré par la commande artisan personnalisée."
echo "Vérifiez également votre fichier .env et les configurations des packages."
```

**`install_ijideals_suite.bat` (Windows) :**
```batch
@echo off
REM Ce script automatise l'ajout et la configuration initiale des packages IJIDeals
REM dans un nouveau projet Laravel.

REM --- Configuration ---
REM Définissez le chemin vers la racine de votre projet Laravel.
REM Si vous exécutez ce script depuis la racine de votre projet Laravel, laissez "."
REM Sinon, spécifiez le chemin absolu ou relatif, ex: "C:\path\to\your\laravel_project"
SET LARAVEL_PROJECT_PATH=.

REM Définissez les packages IJIDeals à installer
SET "IJIDEALS_PACKAGES=ijideals/pricing ijideals/auction-system ijideals/analytics ijideals/returns-management ijideals/recommendation-engine ijideals/user-management ijideals/ijicommerce ijideals/ijiproductcatalog ijideals/notifications-manager ijideals/inventory ijideals/file-management ijideals/location ijideals/social ijideals/sponsorship ijideals/subscriptions ijideals/virtualcoin ijideals/ijilaurels ijideals/ijishoplistings ijideals/laravel-secure-messaging ijideals/internationalization ijideals/ijicommerce-productcollaboration ijideals/ijiordermanagement"

REM Définissez la contrainte de version pour les packages.
SET PACKAGE_VERSION=*

REM --- Logique du Script ---

ECHO --- Installation de la Suite de Packages IJIDeals ---

REM Vérifier si le chemin du projet Laravel existe
IF NOT EXIST "%LARAVEL_PROJECT_PATH%" (
    ECHO Erreur : Le chemin du projet Laravel '%LARAVEL_PROJECT_PATH%' n'existe pas.
    EXIT /B 1
)

REM Naviguer vers le répertoire du projet Laravel
ECHO Navigation vers le répertoire du projet Laravel : %LARAVEL_PROJECT_PATH%
CD "%LARAVEL_PROJECT_PATH%" || ( ECHO Échec du changement de répertoire. Sortie. && EXIT /B 1 )

REM Vérifier si composer est disponible
WHERE composer >NUL 2>NUL
IF %ERRORLEVEL% NEQ 0 (
    ECHO Erreur : Composer n'est pas installé ou n'est pas dans votre PATH. Veuillez installer Composer d'abord.
    EXIT /B 1
)

REM Rappel pour la configuration des dépôts de chemins
ECHO IMPORTANT : Assurez-vous que les 'path repositories' sont configurés dans votre composer.json avant de continuer.
PAUSE

REM Ajouter chaque package IJIDeals
FOR %%P IN (%IJIDEALS_PACKAGES%) DO (
    ECHO Ajout du package : %%P:%PACKAGE_VERSION%
    composer require %%P:%PACKAGE_VERSION%
    IF %ERRORLEVEL% NEQ 0 (
        ECHO Erreur : Échec de l'ajout de %%P. Veuillez vérifier votre composer.json et votre connexion réseau.
        EXIT /B 1
    )
)

ECHO Tous les packages IJIDEals spécifiés ont été ajoutés à composer.json.

REM Exécuter la commande Artisan personnalisée pour la configuration
IF EXIST "artisan" (
    ECHO Exécution de la commande d'installation Artisan personnalisée IJIDeals...
    php artisan ijideals:install
    IF %ERRORLEVEL% NEQ 0 (
        ECHO Avertissement : La commande d'installation Artisan personnalisée a échoué. Veuillez vérifier la sortie ci-dessus.
    )
) ELSE (
    ECHO Avertissement : La commande artisan n'a pas été trouvée. Ignorons la commande d'installation personnalisée IJIDeals.
)

ECHO --- Installation Terminée ---
ECHO Vous devrez peut-être exécuter 'php artisan migrate' et 'php artisan db:seed' manuellement si cela n'est pas géré par la commande artisan personnalisée.
ECHO Vérifiez également votre fichier .env et les configurations des packages.
PAUSE
```

**2. Comment utiliser le script :**

1.  **Créez un nouveau projet Laravel :**
    ```bash
    laravel new mon-nouveau-projet
    cd mon-nouveau-projet
    ```
2.  **Configurez les dépôts de chemins :**
    *   Ouvrez le fichier `composer.json` de votre nouveau projet Laravel.
    *   Ajoutez la section `repositories` comme indiqué dans la section "2. Configuration des Dépôts de Chemins" ci-dessus, en ajustant les chemins si nécessaire pour qu'ils pointent vers vos packages locaux.
3.  **Placez le script :**
    *   Copiez le contenu du script (`.sh` ou `.bat`) dans un fichier nommé `install_ijideals_suite.sh` (ou `.bat`) à la racine de votre nouveau projet Laravel.
4.  **Rendez le script exécutable (Linux/macOS/Git Bash) :**
    ```bash
    chmod +x install_ijideals_suite.sh
    ```
5.  **Exécutez le script :**
    ```bash
    ./install_ijideals_suite.sh
    ```
    (Pour Windows, double-cliquez sur le fichier `.bat` ou exécutez-le depuis l'invite de commande).

Ce guide complet devrait faciliter grandement l'intégration de vos packages dans de nouveaux projets Laravel.
