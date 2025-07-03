<?php

namespace IJIDeals\IJICommerce\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

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
        $this->info('Installing IJICommerce Package...');

        // 1. Publish ijicommerce.php config file
        $this->comment('Publishing IJICommerce configuration...');
        $this->callSilent('vendor:publish', [
            '--provider' => 'IJIDeals\IJICommerce\IJICommerceServiceProvider',
            '--tag' => 'ijicommerce-config',
            '--force' => $this->confirm('Overwrite ijicommerce.php config if it already exists?', false)
        ]);

        // 2. Publish Spatie's permission.php config (if not already published)
        $spatieConfigPath = config_path('permission.php');
        if (!File::exists($spatieConfigPath)) {
            $this->comment('Publishing Spatie Laravel Permission configuration...');
            $this->callSilent('vendor:publish', [
                '--provider' => "Spatie\Permission\PermissionServiceProvider",
                // Spatie does not use tags for config, it publishes directly or use --tag=config for all configs
            ]);
        } else {
            $this->info('Spatie permission.php config already exists. Skipping publish.');
        }

        // 3. Publish package migrations
        $this->comment('Publishing IJICommerce migrations...');
        $this->callSilent('vendor:publish', [
            '--provider' => 'IJIDeals\IJICommerce\IJICommerceServiceProvider',
            '--tag' => 'ijicommerce-migrations',
            '--force' => $this->confirm('Overwrite existing IJICommerce migrations if they exist in the database/migrations/vendor folder?', false)
        ]);

        // 4. Ask user if they want to run migrations
        if ($this->confirm('Run database migrations now?', true)) {
            $this->comment('Running migrations...');
            $this->call('migrate');
        }

        // 5. Publish DefaultShopRolesSeeder.php
        $this->comment('Publishing DefaultShopRolesSeeder...');
        $this->callSilent('vendor:publish', [
            '--provider' => 'IJIDeals\IJICommerce\IJICommerceServiceProvider',
            '--tag' => 'ijicommerce-seeders',
            '--force' => $this->confirm('Overwrite DefaultShopRolesSeeder if it already exists in database/seeders/vendor folder?', false)
        ]);

        // 6. Ask user if they want to run the DefaultShopRolesSeeder
        $seederClassName = '\\Database\\Seeders\\Vendor\\IJIDeals\\IJICommerce\\DefaultShopRolesSeeder'; // Default namespaced path
        if (class_exists($seederClassName)) {
            if ($this->confirm('Run DefaultShopRolesSeeder to create default roles (Owner, Administrator etc.)?', true)) {
                $this->comment('Seeding default shop roles...');
                $this->call('db:seed', ['--class' => $seederClassName]);
            }
        } else {
            $this->warn("{$seederClassName} not found. Skipping role seeding. Ensure it was published correctly.");
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

        $this->info('IJICommerce installation process complete!');
        return Command::SUCCESS;
    }
}
