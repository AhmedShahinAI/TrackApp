<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Validation messages
|--------------------------------------------------------------------------
|
| This file used to hold only the platform's own rules and leave Laravel's defaults in place. The
| Arabic file did the same, so an Arabic page fell back to the English defaults — the Arabic sign-in
| screen said "The email field is required" (T263, FR-078). Both files now carry every rule the
| platform uses and the field names a reader recognises, with identical keys (LocalizationParityTest).
|
*/

return [
    'accepted' => 'The :attribute must be confirmed.',
    'after' => 'The :attribute must be after :date.',
    'after_or_equal' => 'The :attribute must be on or after :date.',
    'array' => 'The :attribute must be a list.',
    'before' => 'The :attribute must be before :date.',
    'before_or_equal' => 'The :attribute must be on or before :date.',
    'boolean' => 'The :attribute must be yes or no.',
    'confirmed' => 'The :attribute confirmation does not match.',
    'current_password' => 'The current password is incorrect.',
    'date' => 'The :attribute is not a valid date.',
    'different' => 'The :attribute and :other must be different.',
    'digits' => 'The :attribute must be :digits digits.',
    'email' => 'The :attribute must be a valid email address.',
    'exists' => 'The selected :attribute does not exist.',
    'file' => 'The :attribute must be a file.',
    'in' => 'The selected :attribute is not allowed.',
    // InSetting (T304): a value checked against a list the Super Admin maintains in settings.
    'in_setting' => 'The selected :attribute is not one of the configured options.',
    'integer' => 'The :attribute must be a whole number.',
    'max' => [
        'array' => 'The :attribute must not have more than :max items.',
        'file' => 'The :attribute must not be larger than :max kilobytes.',
        'numeric' => 'The :attribute must not be greater than :max.',
        'string' => 'The :attribute must not be longer than :max characters.',
    ],
    'min' => [
        'array' => 'The :attribute must have at least :min items.',
        'file' => 'The :attribute must be at least :min kilobytes.',
        'numeric' => 'The :attribute must be at least :min.',
        'string' => 'The :attribute must be at least :min characters.',
    ],
    'numeric' => 'The :attribute must be a number.',
    'present' => 'The :attribute must be sent.',
    'prohibited' => 'The :attribute is not allowed here.',
    'regex' => 'The :attribute format is invalid.',
    'required' => 'The :attribute is required.',
    'required_if' => 'The :attribute is required when :other is :value.',
    'required_with' => 'The :attribute is required when :values is entered.',
    'same' => 'The :attribute must match :other.',
    'size' => [
        'array' => 'The :attribute must contain :size items.',
        'file' => 'The :attribute must be :size kilobytes.',
        'numeric' => 'The :attribute must be :size.',
        'string' => 'The :attribute must be :size characters.',
    ],
    'string' => 'The :attribute must be text.',
    'unique' => 'This :attribute is already in use.',
    'url' => 'The :attribute format is invalid.',

    /*
     * T183 — the message names the expected shape, because "invalid format" tells a registrar
     * nothing while they are looking at a spreadsheet cell. The shape is derived from the
     * configured pattern, so it stays true when the setting changes.
     */
    'university_id' => 'That is not a valid university ID. Expected format: :format.',
    'university_id_digits' => ':count digits',

    /*
     * Field names as a reader knows them. Without these the message names the column:
     * "The name_ar is required."
     */
    'attributes' => [
        'academic_year' => 'academic year',
        'activity' => 'activity',
        'branch_id' => 'branch',
        'branch_ids' => 'branches',
        'campus_name_ar' => 'campus name in Arabic',
        'campus_name_en' => 'campus name in English',
        'code' => 'code',
        'confirm' => 'confirmation',
        'course_id' => 'course',
        'current_password' => 'current password',
        'degree' => 'degree',
        'email' => 'email',
        'end_date' => 'end date',
        'file' => 'file',
        'gender' => 'gender',
        'instructor_id' => 'instructor',
        'locale' => 'language',
        'mapping' => 'column mapping',
        'name_ar' => 'Arabic name',
        'name_en' => 'English name',
        'options' => 'options',
        'password' => 'password',
        'password_confirmation' => 'password confirmation',
        'program_id' => 'program',
        'reason' => 'reason',
        'role' => 'role',
        'roles' => 'roles',
        'section_number' => 'section number',
        'semester' => 'semester',
        'start_date' => 'start date',
        'status' => 'status',
        'term_id' => 'term',
        'university_id' => 'University ID',
        'user_id' => 'user',
    ],
];
