<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Authorization and scope — US2
|--------------------------------------------------------------------------
|
| The refusal copy is deliberately vague. A refusal message is itself a disclosure channel
| (FR-024): "you lack permission" reveals nothing, while "that section belongs to Zulfi" reveals
| both that the section exists and where it is.
|
*/

return [
    'errors' => [
        'not_grantable' => 'This user cannot be granted access to a section.',
        'already_granted' => 'This user already has access to this section.',
        'not_branch_scoped' => 'Branches cannot be assigned to a user who is not a branch coordinator.',
        'last_unscoped_role' => 'This role cannot be removed: nobody would be left with full oversight.',
    ],

    'refusal' => [
        'forbidden_title' => 'You do not have access',
        'forbidden_body' => 'This page requires a permission you do not hold. If you believe this is a mistake, contact your system administrator.',
        'missing_title' => 'Page not found',
        'missing_body' => 'We could not find what you asked for. It may have been removed, or the link may be wrong.',
        'back' => 'Back to the home page',
    ],

    'shared_access' => 'Shared access',
];
