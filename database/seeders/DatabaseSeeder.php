<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * The production-safe seed: structure the platform cannot run without.
 *
 * Deliberately excludes DevAdminSeeder and UiFixtureSeeder. Those two create accounts with known
 * passwords and demonstration data, and a `db:seed` that quietly included them would put both on
 * whatever database it was pointed at. They are run by name instead - see `db:seed --class=...` in
 * quickstart.md.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            OrganizationSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            SettingSeeder::class,
        ]);
    }
}
