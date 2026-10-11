<?php

declare(strict_types=1);

namespace App\Modules\Imports\Modes;

/**
 * T346 — staff accounts, in bulk (FR-104, FR-105, decision C-05).
 *
 * Five columns: both names, email, role and — for a coordinator — the branches they oversee, several
 * separated by a comma, each written as the registrar writes a campus («<campus> (طلاب)»), so a
 * campus whose male and female branches share a name is never guessed between.
 *
 * ## Every account it creates is unusable until reset (FR-105)
 *
 * Exactly as FR-059 does for the central import's instructors: a password nobody knows, 64 random
 * characters hashed and discarded, and `first_login_at` null. Sign-in is refused until a Super Admin
 * resets the password — the deliberate moment someone hands the person their credentials — so no
 * row in a spreadsheet grants access by itself.
 *
 * ## Refused, each with its own reason
 *
 * An email any account holds, active or deactivated (FR-005); a coordinator with no branch (FR-004);
 * a role that is not a role row; a branch that resolves to none or to several. Never a create-or-
 * skip choice: an account is not something to invent half of.
 */
final class UserMode
{
    public const NAME = 'user';

    /** The role FR-004 requires a branch for. */
    public const COORDINATOR = 'BRANCH_COORDINATOR';

    /**
     * The roles a staff file may create: the staff FR-104 describes. Never SUPER_ADMIN — a Super
     * Admin account is created deliberately, on the form, not by a spreadsheet cell.
     *
     * @var list<string>
     */
    public const ROLES = [self::COORDINATOR, 'INSTRUCTOR'];

    /** @var array<string, bool> target => required */
    public const TARGETS = [
        'name_ar' => true,
        'name_en' => true,
        'email' => true,
        'role' => true,
        'branches' => false,
    ];

    /**
     * The English name before the Arabic, so the containment pass cannot give «الاسم» the English
     * column.
     *
     * @var array<string, list<string>>
     */
    public const ALIASES = [
        'name_en' => ['الاسم بالإنجليزية', 'الاسم الإنجليزي', 'name en', 'name_en', 'english name', 'name (english)'],
        'name_ar' => ['الاسم بالعربية', 'الاسم العربي', 'name ar', 'name_ar', 'arabic name', 'name (arabic)', 'الاسم'],
        'email' => ['البريد الإلكتروني', 'البريد', 'email', 'e-mail', 'email address'],
        'role' => ['الدور', 'الصلاحية', 'role'],
        'branches' => ['الفروع', 'الفرع', 'branches', 'branch'],
    ];

    /** An email appears once per file: one account, one address (FR-005). */
    public const IDENTITY = ['email'];

    /** @return list<string> */
    public static function required(): array
    {
        return array_keys(array_filter(self::TARGETS));
    }

    /** @return list<string> */
    public static function targets(): array
    {
        return array_keys(self::TARGETS);
    }
}
