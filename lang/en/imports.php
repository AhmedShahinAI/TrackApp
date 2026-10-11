<?php

declare(strict_types=1);

/*
 * T247 — the import wizard. Wording follows design-system/ui_kits/web-app/i18n.js wherever the
 * reference has a string; the queue-waiting state and the commit-time reasons have no reference
 * counterpart (C-13) and are written to match its voice.
 */

return [
    'title' => 'Data import',
    'subtitle' => 'Load rosters from Excel or CSV files',

    'modes' => [
        'central' => 'Central import',
        'section' => 'Section import',
        'registrar' => 'Registration-unit export',
        'course' => 'Course import',
        'user' => 'Account import',
    ],

    'steps' => [
        'upload' => 'Upload',
        'map' => 'Map columns',
        'check' => 'Check',
        'preview' => 'Preview',
        'import' => 'Import',
        'report' => 'Report',
        'label' => 'Import steps',
    ],

    'status' => [
        'uploaded' => 'Uploaded',
        'mapped' => 'Mapped',
        'validating' => 'Checking',
        'validated' => 'Ready to preview',
        'committing' => 'Importing',
        'completed' => 'Completed',
        'failed' => 'Failed',
    ],

    'fields' => [
        'file' => 'File',
        'mode' => 'Mode',
        'ran_by' => 'Run by',
        'date' => 'Date',
        'rows' => 'Rows',
        'processed' => 'Processed',
        'imported' => 'Imported',
        'duplicates' => 'Duplicates',
        'errors' => 'Errors',
        'status' => 'Status',
        'term' => 'Target term',
        'actions' => 'Actions',
    ],

    'targets' => [
        'term' => 'Term',
        'branch' => 'Branch',
        'gender' => 'Gender',
        'course_code' => 'Course code',
        'course_name' => 'Course name',
        'section_number' => 'Section no.',
        'instructor_name' => 'Instructor name',
        'instructor_email' => 'Instructor email',
        'university_id' => 'University ID',
        'student_name' => 'Student name',
        'program_code' => 'Program code',
        // Registrar mode (T332): the two columns the other modes do not have, and the block's course.
        'sequence' => 'Sequence',
        'academic_status' => 'Academic status',
        'course' => 'Course',
        // US8 — the course and staff modes (T350).
        'name_ar' => 'Name (Arabic)',
        'name_en' => 'Name (English)',
        'programs' => 'Programs',
        'email' => 'Email',
        'role' => 'Role',
        'branches' => 'Branches',
    ],

    'actions' => [
        'central' => 'Central import',
        'registrar' => 'Import a registration-unit export',
        'courses' => 'Import courses',
        'users' => 'Import staff accounts',
        'resume' => 'Resume',
        'download_errors' => 'Download error report (xlsx)',
        'download_short' => 'xlsx',
        'choose' => 'Choose file',
        'upload' => 'Upload and map columns',
        'start_check' => 'Check',
        'confirm_import' => 'Confirm import',
        'new_import' => 'Import another file',
        'template' => 'Download template',
        'open_section' => 'Open section',
        'back_to_list' => 'Back to imports',
        'remap' => 'Change mapping',
        'home' => 'Home',
    ],

    'empty' => [
        'title' => 'No imports yet',
        'message' => 'Start a central import to load the term roster from one file.',
        'instructor' => 'You have not imported a list yet. A section’s student list is imported from that section’s own page.',
    ],

    'upload' => [
        'drop' => 'Drop a file here or choose from your device',
        'drop_hint' => 'xlsx or csv · up to :mb MB · up to :rows rows',
        'selected' => 'Selected file',
        'section_columns' => 'The file needs two columns only: University ID and student name. Students are added to this section alone.',
        'required' => 'Choose a file to upload.',
        'bad_type' => 'Unsupported file type. Accepted: xlsx, csv.',
        'too_large' => 'File exceeds :mb MB.',
        'too_many_rows' => 'File exceeds :rows rows.',
        'unreadable' => 'The file could not be read. Make sure it is a valid xlsx or csv file.',
        'unpacked_too_large' => 'The workbook unpacks to far more data than an import can hold. Save it again from Excel and upload the new copy.',
        'previously_imported' => 'This file was already imported for this term. Continuing will create no new records.',
    ],

    'mapping' => [
        'help' => 'Mapping proposed from the header row. Review and correct it. Fields marked * are required.',
        'unmapped' => '— Ignore —',
        'target' => 'Field',
        'source' => 'File column',
        'required' => 'The required field :column must be mapped.',
        'not_in_header' => 'The column “:column” is not in the header row.',
        'need_all' => 'All required fields must be mapped to continue.',
        'wrong_shape' => 'This file does not look like a central roster. If it is a registration-unit export, use the registration-unit import; if it is one section’s list, import it from that section’s page.',
        'options' => 'When the file names something unknown',
        'create_courses' => 'Create unknown courses',
        'create_instructors' => 'Create instructor accounts (unusable until password reset)',
        'skip_hint' => 'When off, the affected rows are skipped and listed in the error report.',
        'new_course_program' => 'Programme for new courses',
        'new_course_program_hint' => 'Every section belongs to one programme. New sections of a new course join this programme unless the file names a programme code.',
        // US8 — the course mode's choices.
        'create_programs' => 'Create programs that do not exist yet',
        'new_program_unit' => 'Specialty unit for new programs',
        'associate_inactive' => 'Also associate deactivated programs the file names',
        'programs_hint' => 'When off, a row naming an unknown program is skipped and listed in the error report, and a deactivated program is left out of the row.',
    ],

    'checking' => [
        'title' => 'Checking rows in the background…',
        'hint' => 'You can keep working on other screens; the result will appear here.',
        'nothing_saved' => 'Nothing has been saved yet.',
        'counter' => ':processed of :total rows',
    ],

    'queue' => [
        'waiting_title' => 'Import waiting',
        'stalled_title' => 'Processing stopped',
        'stalled_validation' => 'Background processing started and then stopped without finishing. Nothing was saved; review the mapping to check the file again.',
        'stalled_commit' => 'The import started in the background and then stopped without finishing. Completed chunks are kept, and Resume continues from where it stopped.',
        'waiting' => 'Background processing is not available right now. The import is saved and will be completed and reported when it finishes.',
    ],

    'preview' => [
        'nothing_saved' => 'Nothing has been saved yet.',
        'new_sections' => 'New sections',
        'new_students' => 'New students',
        'new_enrollments' => 'New enrollments',
        'new_courses' => 'New courses',
        'new_instructors' => 'New instructor accounts',
        'matched' => 'Matched records',
        'warnings' => 'Warnings',
        'errors' => 'Errors',
        'will_skip' => ':count rows will be skipped as you chose.',
        'first_rows' => 'First 100 rows',
        'no_rows' => 'The file contains no importable rows.',
        'row' => 'Row',
        'student' => 'Student',
        'course_section' => 'Course / Section',
        'reasons' => 'Reason',
        'options_chosen' => 'Unknown courses: :courses · Instructors without accounts: :instructors',
        // US8.
        'matched_courses' => 'Existing courses',
        'new_programs' => 'New programs',
        'new_accounts' => 'New accounts',
        'course_options_chosen' => 'Unknown programs: :programs · Deactivated programs: :inactive',
        'option_associate' => 'associate',
        'option_leave_out' => 'leave out',
        'accounts_unusable' => 'Every account this import creates cannot sign in until its password is reset.',
        'confirm_courses' => ':courses courses and :programs programs will be created, and :matched existing courses linked. :errors rows with errors will not be imported.',
        'confirm_accounts' => ':accounts accounts will be created, each unusable until its password is reset. :errors rows with errors will not be imported.',
        'option_create' => 'Create',
        'option_skip' => 'Skip rows',
        'confirm_title' => 'Confirm import',
        'confirm_body' => ':sections sections, :students students and :enrollments enrollments will be created, and :matched existing records linked. :errors rows with errors will not be imported.',
        'confirm_duplicate' => 'This file was already imported for this term; rows will be matched and no new record created.',
    ],

    'committing' => [
        'title' => 'Importing in chunks of 500 rows…',
        'chunk' => 'Chunk :current of :total',
    ],

    'report' => [
        'done' => 'Import completed',
        'failed' => 'Import stopped',
        'failed_hint' => 'Completed chunks are kept. Resume continues from the chunk that stopped.',
        'validation_failed' => 'The check could not finish',
        'columns' => [
            'row' => 'Row',
            'column' => 'Column',
            'value' => 'Submitted value',
            'reason' => 'Reason',
        ],
    ],

    'reasons' => [
        'duplicate_in_file' => 'Duplicate within file (row :row)',
        'invalid_university_id' => 'University ID does not match the format (:format)',
        'missing_required' => 'Missing required value: :column',
        'unknown_branch' => 'Unknown branch: :value',
        'unknown_course' => 'Unknown course: :value',
        'unknown_instructor' => 'Instructor has no account: :value',
        'name_mismatch' => 'Name differs from stored (:stored) — stored name kept',
        'unresolvable_section' => 'Section cannot be resolved: :value',
        'existing_student' => 'Existing student — will be linked',
        'name_mismatch_private' => 'Name differs from the one on record — the recorded name is kept',
        'student_unavailable' => 'This student’s record is not available for import; contact the system administrator',
        'invalid_email' => 'Instructor email is not valid: :value',
        'skipped_by_choice' => 'Row skipped as you chose',
        'commit_conflict' => 'Conflicted with a record created during the import — row not imported',
        'unspecified' => 'Row rejected',
        // contracts/imports.md — the registrar mode's three categories (T335).
        'ambiguous_course' => 'Course name matches several courses: :value — choose one at the confirmation step, or the row is skipped',
        'unknown_academic_status' => 'Academic status not recognised (:value) — stored empty, row still imports',
        'invalid_sequence' => 'Sequence missing or not a number — stored empty, row still imports',
        'duplicate_sequence' => 'Sequence repeats row :row — kept as the file gives it',
        // contracts/imports.md — the course and staff modes' categories (T350).
        'unknown_program' => 'Program does not exist: :value — created or the row skipped, as you chose',
        'ambiguous_program' => 'Program name matches more than one program: :value — use its code',
        'inactive_program' => 'Program is deactivated: :value — associated only if you confirmed it',
        'email_taken' => 'Email already belongs to an account: :value',
        'coordinator_needs_branch' => 'A branch coordinator needs at least one branch',
        'unknown_role' => 'Unknown role: :value',
        'too_long' => ':column is longer than :max characters',
        'existing_course' => 'Existing course — linked; its stored names are kept',
    ],

    /*
     * T350 — the course and staff uploads (US8).
     */
    'bulk' => [
        'courses' => [
            'columns' => 'Columns: course code, Arabic name, programs (required), and English name (optional). A course can belong to several programs — separate them with a comma. An existing code is linked, not duplicated.',
        ],
        'users' => [
            'columns' => 'Columns: Arabic name, English name, email, role (required), and branches — required for a branch coordinator, written as «campus (طلاب)» and separated by commas.',
            'unusable' => 'Every account created here cannot sign in until a Super Admin resets its password.',
        ],
    ],

    /*
     * T335 — the registration unit's own export (FR-096 to FR-101).
     */
    'registrar' => [
        'not_registrar_export' => 'This file does not look like a registration-unit export: it has no block of campus, degree, course, activity and section above its header row. For a ten-column roster use the central import; for one section’s list, import it from that section’s page.',
        'metadata_missing' => 'The block above the header row is missing: :fields.',
        'campus_unknown' => 'The campus “:value” matches no active branch. Check the branch names, or add the branch first.',
        'campus_ambiguous' => 'The campus “:value” matches more than one branch. The file cannot be imported until it names one.',
        'course_unknown' => 'The course “:value” matches no course in the college. Add the course first, or check its Arabic name.',
        'course_ambiguous' => 'The course name “:value” matches several courses. Choose the one this section belongs to; otherwise its rows are skipped and listed in the report.',
        'course_to_choose' => 'Several courses — choose below',
        'instructor_invalid' => 'Choose an active instructor of the college.',
        'upload_help' => 'The registration unit’s export describes one section in the lines above its header. Choose the term and the instructor; the file carries neither.',
        'instructor_hint' => 'The section is created under this instructor if it does not exist yet. An existing section keeps its own.',
        'choose_instructor' => 'Choose an instructor…',
        'confirm_title' => 'What the file says',
        'confirm_lead' => 'Each value from the file, and what it was matched to. Confirm to check the rows; nothing is saved until you confirm the import at the preview.',
        'confirm_and_check' => 'Confirm and check the rows',
        'adjust_columns' => 'Adjust the columns',
        'skip_rows' => 'Do not choose — skip these rows',
        'program_hint' => 'Every section belongs to one programme. Used only for a new section whose course has none or several.',
        'program_from_course' => 'The course’s own programme',
        'section_new' => 'will be created',
        'section_existing' => 'existing section',
        'default_value' => 'Not in the list — the default is used',
        // Review M3 — about the job's one section, shown on the preview.
        'notices' => [
            'degree_unknown' => 'The degree “:value” is not in the settings list. A new section is given “:default”.',
            'activity_unknown' => 'The activity “:value” is not in the settings list. A new section is given “:default”.',
            'degree_differs' => 'The file gives the degree “:value”; the existing section keeps its own (:stored).',
            'activity_differs' => 'The file gives the activity “:value”; the existing section keeps its own (:stored).',
        ],
        'fields' => [
            'campus' => 'Campus',
            'degree' => 'Degree',
            'course' => 'Course',
            'activity' => 'Activity',
            'section' => 'Section',
            'instructor' => 'Instructor',
            'program' => 'Programme for a new section',
        ],
    ],

    'conflicts' => [
        'not_mappable' => 'The mapping cannot be changed at this stage of the import.',
        'not_mapped' => 'Confirm the column mapping before starting the check.',
        'not_validated' => 'The file has not finished being checked.',
        'preview_not_seen' => 'Open the preview before confirming the import.',
        'not_failed' => 'Only an import that stopped while importing can be resumed.',
        'options_not_in_section_mode' => 'A section list creates no courses or accounts, so it takes no create-or-skip choices.',
        'options_not_in_user_mode' => 'A staff file takes no create-or-skip choices: every problem in it is refused with its reason.',
        'term_archived' => 'This section’s term is archived and takes no new list.',
        'options_at_mapping' => 'The create-or-skip choices are set at the mapping step. Change the mapping so the file is checked again with the new choices.',
    ],

    'failure' => [
        'validation' => 'The file could not be read during the check. Upload it again, and if it happens again tell technical support.',
        'chunk' => 'The import stopped at the chunk starting at row :row.',
        'owner_revoked' => 'The import stopped because the person who ran it no longer has permission to. Completed chunks are kept; someone who holds the permission can import the remaining rows.',
    ],
];
