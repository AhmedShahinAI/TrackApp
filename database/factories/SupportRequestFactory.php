<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AcademicTerm;
use App\Models\Student;
use App\Models\SupportRequest;
use App\Modules\Support\Services\ReferenceGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * T363 — a support request as the public form would have recorded it.
 *
 * The reference comes from ReferenceGenerator::candidate(), not a hand-written string, so every
 * factory row has a valid check character and a test that reads one back through normalise() is
 * testing the real format. No uniqueness query: at 50 random bits a clash in a test run will not
 * happen, and the unique index would say so if it did.
 *
 * Category is the first seeded value written out. A factory that read the setting would fail in any
 * test that had not seeded it (see StudentFactory).
 *
 * @extends Factory<SupportRequest>
 */
class SupportRequestFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'reference' => (new ReferenceGenerator)->candidate(),
            'student_id' => Student::factory(),
            'term_id' => AcademicTerm::factory(),
            'category' => 'incorrect_data',
            'description' => fake()->paragraph(),
            'contact' => null,
            'submitted_ip' => fake()->ipv4(),
        ];
    }
}
