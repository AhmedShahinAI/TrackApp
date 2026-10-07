<?php

declare(strict_types=1);

namespace App\Modules\Audit\Services;

/**
 * The human label for an audit action key.
 *
 * ## Why this is not just `__('audit.actions.'.$action)`
 *
 * Every action key contains a dot — `auth.login`, `section.access_granted` — and a dot is how
 * Laravel separates path segments in a translation key. `__('audit.actions.auth.login')` therefore
 * looks for `$audit['actions']['auth']['login']`, while `lang/{ar,en}/audit.php` defines `'auth.login'`
 * as a flat key inside `actions`. The lookup misses, Lang::has() returns false, and every row falls
 * through to "unknown".
 *
 * The effect was the entire recent-activity panel on the landing page reading "إجراء غير معروف" on
 * every line — eight rows of nothing, in both locales, for every role. It survived Phase 4's whole
 * test suite and sat frozen in the visual baselines, because a screenshot of eight identical wrong
 * labels looks exactly like a screenshot of eight correct ones unless you read them.
 *
 * Fetching the array once and indexing it sidesteps the path parsing entirely, and keeps the lang
 * file's flat shape — which is worth keeping, because it can be diffed directly against the action
 * keys in `Auditor::record()` calls and against contracts/audit.md.
 */
final class AuditLabels
{
    public static function for(string $action): string
    {
        /** @var array<string, string> $labels */
        $labels = (array) __('audit.actions');

        return $labels[$action] ?? (string) __('audit.actions.unknown');
    }
}
