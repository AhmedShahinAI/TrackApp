<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * T049 - a Super Admin to sign in with while developing.
 *
 * Refuses to run in production, and the refusal is an exception rather than a silent return. A seeder
 * that quietly does nothing is indistinguishable from one that worked, and the failure mode here is
 * a known-password administrator account on a live system holding 30,000 students' records.
 */
class DevAdminSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException(
                'DevAdminSeeder creates an account with a known password and must never run in '
                .'production. Create the first Super Admin with the dedicated console command instead.',
            );
        }

        $organization = Organization::query()->where('type', 'college')->firstOrFail();

        // forceFill, because `password` is deliberately not mass-assignable - it is set through an
        // audited action in the application - while the column is NOT NULL.
        $admin = User::query()->firstWhere('email', 'admin@track.local') ?? new User;

        $admin->forceFill([
            'organization_id' => $organization->id,
            'name_ar' => 'مسؤول التطوير',
            'name_en' => 'Development Administrator',
            'email' => 'admin@track.local',
            'password' => Hash::make('password'),
            'locale' => 'ar',
            'is_active' => true,
        ])->saveQuietly();

        $admin->syncRoles(['SUPER_ADMIN']);
    }
}
