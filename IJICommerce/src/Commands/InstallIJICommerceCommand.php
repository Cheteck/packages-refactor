<?php

namespace IJIDeals\IJICommerce\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class InstallIJICommerceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ijicommerce:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install IJICommerce package: publish assets, run migrations (optional), and seed roles (optional).';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Log::info('IJICommerce installation process started.');
        $this->info('Installing IJICommerce Package...');

        // 1. Publish ijicommerce.php config file
        $this->comment('Publishing IJICommerce configuration...');
        $forceConfig = $this->confirm('Overwrite ijicommerce.php config if it already exists?', false);
        Log::debug("Attempting to publish IJICommerce configuration.", ['force' => $forceConfig]);
        $this->callSilent('vendor:publish', [
            '--provider' => 'IJIDeals\IJICommerce\IJICommerceServiceProvider',
            '--tag' => 'ijicommerce-config',
            '--force' => $forceConfig
        ]);
        Log::info('IJICommerce configuration publish command executed.');

        // 2. Publish Spatie's permission.php config (if not already published)
        $spatieConfigPath = config_path('permission.php');
        if (!File::exists($spatieConfigPath)) {
            $this->comment('Publishing Spatie Laravel Permission configuration...');
            Log::debug("Attempting to publish Spatie Laravel Permission configuration.");
            $this->callSilent('vendor:publish', [
                '--provider' => "Spatie\Permission\PermissionServiceProvider",
                // Spatie does not use tags for config, it publishes directly or use --tag=config for all configs
            ]);
            Log::info('Spatie Laravel Permission configuration publish command executed.');
        } else {
            $this->info('Spatie permission.php config already exists. Skipping publish.');
            Log::info('Spatie permission.php config already exists. Skipping publish.');
        }

        // 3. Publish package migrations
        $this->comment('Publishing IJICommerce migrations...');
        $forceMigrations = $this->confirm('Overwrite existing IJICommerce migrations if they exist in the database/migrations/vendor folder?', false);
        Log::debug("Attempting to publish IJICommerce migrations.", ['force' => $forceMigrations]);
        $this->callSilent('vendor:publish', [
            '--provider' => 'IJIDeals\IJICommerce\IJICommerceServiceProvider',
            '--tag' => 'ijicommerce-migrations',
            '--force' => $forceMigrations
        ]);
        Log::info('IJICommerce migrations publish command executed.');

        // 4. Ask user if they want to run migrations
        if ($this->confirm('Run database migrations now?', true)) {
            $this->comment('Running migrations...');
            Log::info("User confirmed to run database migrations.");
            $this->call('migrate');
            Log::info('Database migrations command executed.');
        } else {
            Log::info('User skipped running database migrations.');
        }

        // 5. Publish DefaultShopRolesSeeder.php
        $this->comment('Publishing DefaultShopRolesSeeder...');
        $forceSeeder = $this->confirm('Overwrite DefaultShopRolesSeeder if it already exists in database/seeders/vendor folder?', false);
        Log::debug("Attempting to publish DefaultShopRolesSeeder.", ['force' => $forceSeeder]);
        $this->callSilent('vendor:publish', [
            '--provider' => 'IJIDeals\IJICommerce\IJICommerceServiceProvider',
            '--tag' => 'ijicommerce-seeders',
            '--force' => $forceSeeder
        ]);
        Log::info('DefaultShopRolesSeeder publish command executed.');

        // 6. Ask user if they want to run the DefaultShopRolesSeeder
        $seederClassName = '\\Database\\Seeders\\Vendor\\IJIDeals\\IJICommerce\\DefaultShopRolesSeeder'; // Default namespaced path
        if (class_exists($seederClassName)) {
            if ($this->confirm('Run DefaultShopRolesSeeder to create default roles (Owner, Administrator etc.)?', true)) {
                $this->comment('Seeding default shop roles...');
                Log::info("User confirmed to run DefaultShopRolesSeeder.", ['seeder_class' => $seederClassName]);
                $this->call('db:seed', ['--class' => $seederClassName]);
                Log::info('DefaultShopRolesSeeder command executed.');
            } else {
                Log::info('User skipped running DefaultShopRolesSeeder.');
            }
        } else {
            $this->warn("{$seederClassName} not found. Skipping role seeding. Ensure it was published correctly.");
            Log::warning("DefaultShopRolesSeeder class not found. Skipping role seeding.", ['seeder_class' => $seederClassName]);
        }

        // 7. Output clear instructions for manual Spatie configuration
        $this->info('--------------------------------------------------------------------------');
        $this->info('IMPORTANT: Manual Configuration Required for Spatie Permissions!');
        $this->info('--------------------------------------------------------------------------');
        $this->line("To enable team permissions for shops, you MUST update your application's `config/permission.php` file:");
        $this->line("1. Set `'teams' => true,`");
        $this->line("2. Set `'team_foreign_key' => 'shop_id',` (or your chosen foreign key name for shops)");
        $this->line("This step is crucial for roles and permissions to be scoped to individual shops.");
        $this->info('--------------------------------------------------------------------------');
        Log::info("Displayed manual configuration instructions for Spatie Permissions.");

        $this->info('IJICommerce installation process complete!');
        Log::info('IJICommerce installation process completed successfully.');
        return Command::SUCCESS;
    }
}
