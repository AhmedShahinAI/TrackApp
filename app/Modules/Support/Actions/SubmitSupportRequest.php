<?php

declare(strict_types=1);

namespace App\Modules\Support\Actions;

use App\Models\SupportRequest;
use App\Modules\Shared\Actions\TransactionalAction;
use App\Modules\Support\Services\ReferenceGenerator;
use App\Modules\Support\Services\VerifyStudentIdentity;

/**
 * T390 — record a support request from the public form, or record nothing.
 *
 * Inside one transaction (constitution II): the student is resolved, the reference drawn, the request
 * created in `new` with the originating IP, and `support.request_created` written by the model's
 * creation hook — null actor, `channel: public`. A failed audit write throws, and the request goes
 * with it.
 *
 * Returns null when verification finds no enrolled student, and **nothing else about why**: the
 * controller answers every null with the same body (FR-119, research.md R-10). There is no exception
 * type per reason, no log line per reason, and no partial write — a verification failure leaves the
 * database exactly as it was, so not even a row count can tell the cases apart.
 *
 * @extends TransactionalAction<SupportRequest|null>
 */
final class SubmitSupportRequest extends TransactionalAction
{
    public function __construct(
        private readonly VerifyStudentIdentity $verify,
        private readonly ReferenceGenerator $references,
    ) {}

    /**
     * Called as execute($fields, $ip): the validated fields, and the originating address.
     */
    protected function handle(mixed ...$arguments): ?SupportRequest
    {
        /** @var array{university_id: string, category: string, description: string, contact?: string|null} $fields */
        [$fields, $ip] = $arguments;

        $student = $this->verify->resolve($fields['university_id']);

        if ($student === null) {
            return null;
        }

        $request = new SupportRequest([
            'student_id' => $student->studentId,
            'term_id' => $student->termId,
            'category' => $fields['category'],
            'description' => $fields['description'],
            'contact' => $fields['contact'] ?? null,
            'submitted_ip' => $ip,
        ]);

        // Not fillable: the reference is drawn here and never changes (T363).
        $request->forceFill(['reference' => $this->references->generate()])->save();

        return $request;
    }
}
