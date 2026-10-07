<?php

declare(strict_types=1);

/* The English half of the audit action labels. Key for key with lang/ar/audit.php. */

return [
    'actions' => [
        /*
         * The actions Auditable generates on its own: {model}.{created|updated|deleted} for every
         * model carrying the trait. All twenty-three were missing, so the audit log and the landing
         * page read "unknown action" for most entries. AuditLabelsTest harvests the keys from the
         * source and fails when an action arrives without a label.
         */
        'academic_term.created' => 'Term created',
        'academic_term.updated' => 'Term updated',
        'academic_term.deleted' => 'Term deleted',
        'course.created' => 'Course created',
        'course.updated' => 'Course updated',
        'course.deleted' => 'Course deleted',
        'program.created' => 'Program created',
        'program.updated' => 'Program updated',
        'program.deleted' => 'Program deleted',
        'section.created' => 'Section created',
        'section.updated' => 'Section updated',
        'section.deleted' => 'Section deleted',
        'section_enrollment.created' => 'Student enrolled',
        'section_enrollment.updated' => 'Enrollment updated',
        'section_enrollment.deleted' => 'Enrollment deleted',
        'student.created' => 'Student created',
        'student.updated' => 'Student updated',
        'student.deleted' => 'Student deleted',
        'import_job.created' => 'Import started',
        'import_job.updated' => 'Import updated',
        'import_job.deleted' => 'Import deleted',
        'user.deleted' => 'Account deleted',
        'audit.exported' => 'Audit log exported',

        'unknown' => 'Unknown action',

        'auth.login' => 'Signed in',
        'auth.logout' => 'Signed out',
        'auth.login_failed' => 'Failed sign-in',
        'auth.login_blocked' => 'Sign-in blocked',

        'access.denied' => 'Access denied',

        'user.created' => 'Account created',
        'user.updated' => 'Account updated',
        'user.deactivated' => 'Account deactivated',
        'user.password_changed' => 'Password changed',
        'user.password_reset' => 'Password reset',
        'user.roles_changed' => 'Roles changed',
        'user.branches_changed' => 'Branches changed',

        'branch.created' => 'Branch added',
        'branch.updated' => 'Branch updated',
        'branch.deleted' => 'Branch deactivated',

        'term.set_current' => 'Current term set',
        'section.instructor_changed' => 'Section instructor changed',
        'section.access_granted' => 'Section access granted',
        'enrollment.withdrawn' => 'Withdrawn from section',
        'import.committed' => 'Import committed',
    ],
];
