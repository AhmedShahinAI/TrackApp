<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;

/**
 * T044 - the single college row the constitution requires from v1.
 *
 * One record, no university parent. The hierarchy column exists so a second college or a university
 * tier can be added without a data migration, but inventing a parent now would mean inventing an
 * organization that nothing in the platform refers to.
 */
class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        Organization::query()->firstOrCreate(
            ['type' => 'college', 'parent_id' => null],
            [
                'name_ar' => 'كلية المجتمع بالمجمعة',
                'name_en' => 'Majmaah Community College',
            ],
        );
    }
}
