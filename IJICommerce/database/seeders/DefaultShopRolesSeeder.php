<?php

namespace IJIDeals\IJICommerce\database\seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DefaultShopRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $defaultRoles = config('ijicommerce.default_roles', []);
        $teamKeyField = config('permission.column_names.team_foreign_key', 'team_id'); // Get from spatie's config

        if (empty($defaultRoles)) {
            $this->command->info('No default roles found in ijicommerce config. Skipping role seeding.');
            return;
        }

        foreach (array_keys($defaultRoles) as $roleName) {
            // Create roles with null team_id, making them global roles that can be assigned to teams.
            // Or, if you intend roles to be specific to a team type (not directly applicable here as Shop IS the team type)
            // you would handle it differently. For general purpose roles usable across any "Shop" team,
            // creating them globally (null team_id) is standard.
            // When assigning to a user for a shop: $user->assignRole($roleName, $shop);
            // Spatie will then correctly use the shop's ID in the pivot table.

            $role = Role::firstOrCreate([
                'name' => $roleName,
                // Ensure guard_name matches your application's default (usually 'web' or 'api')
                'guard_name' => config('auth.defaults.guard'),
                // $teamKeyField => null, // Explicitly setting team_id to null for global roles
            ]);
             $this->command->info("Created/Ensured role: {$roleName}");
        }

        $this->command->info('Default shop roles seeding/verification complete.');
        $this->command->info('Please ensure your config/permission.php has `teams` set to true and `team_foreign_key` set to `shop_id` (or your chosen key).');

        // Here you could also assign default permissions to these roles if defined.
        // Example:
        // $ownerRole = Role::findByName('Owner');
        // $ownerRole->givePermissionTo(config('ijicommerce.default_permissions')); // if permissions are just an array of names
    }
}
