<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Section access grants
|--------------------------------------------------------------------------
|
| contracts/auth-users.md requires the recipient of a section grant to "hold the INSTRUCTOR role".
| That is a real constraint — granting a coordinator access to one section would contradict the
| branch scope they already have — but writing the role name into a controller would make a fourth
| role a code change, which FR-020 forbids and NewRoleNeedsNoCodeChangeTest fails the build over.
|
| So it lives here, as configuration (constitution IV). A college that later introduces, say, a
| TEACHING_ASSISTANT role makes it grantable by adding one line to this list, and nothing in
| app/ learns a new role name.
|
*/

return [
    'grantable_roles' => ['INSTRUCTOR'],

    /*
     * The roles for which a branch assignment is meaningful. contracts/auth-users.md requires
     * PUT /users/{id}/branches to refuse a target that is not a coordinator, for the same reason
     * and with the same escape hatch: a role name in configuration, not in a controller.
     */
    'branch_scoped_roles' => ['BRANCH_COORDINATOR'],
];
