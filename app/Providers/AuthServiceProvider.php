<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\AcademicTerm;
use App\Models\Branch;
use App\Models\Course;
use App\Models\ImportJob;
use App\Models\Program;
use App\Models\Section;
use App\Models\SectionEnrollment;
use App\Models\Student;
use App\Models\SupportRequest;
use App\Models\User;
use App\Modules\AcademicStructure\Policies\BranchPolicy;
use App\Modules\AcademicStructure\Policies\CoursePolicy;
use App\Modules\AcademicStructure\Policies\ProgramPolicy;
use App\Modules\AcademicTerms\Policies\TermPolicy;
use App\Modules\Imports\Policies\ImportJobPolicy;
use App\Modules\Sections\Policies\SectionPolicy;
use App\Modules\Students\Policies\EnrollmentPolicy;
use App\Modules\Students\Policies\StudentPolicy;
use App\Modules\Support\Policies\SupportRequestPolicy;
use App\Modules\UserManagement\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * T125 — policy registration.
 *
 * Explicit rather than relying on Laravel's convention-based discovery, because the policies live
 * under app/Modules/<Module>/Policies/ and the convention looks in app/Policies/. A policy that is
 * silently not discovered fails open on any `authorize()` call that has no other gate.
 */
final class AuthServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    private const POLICIES = [
        AcademicTerm::class => TermPolicy::class,
        Branch::class => BranchPolicy::class,
        Course::class => CoursePolicy::class,
        ImportJob::class => ImportJobPolicy::class,
        Program::class => ProgramPolicy::class,
        Section::class => SectionPolicy::class,
        SectionEnrollment::class => EnrollmentPolicy::class,
        Student::class => StudentPolicy::class,
        SupportRequest::class => SupportRequestPolicy::class,
        User::class => UserPolicy::class,
    ];

    public function boot(): void
    {
        foreach (self::POLICIES as $model => $policy) {
            Gate::policy($model, $policy);
        }
    }
}
