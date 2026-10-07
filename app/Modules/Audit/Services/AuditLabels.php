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
    /**
     * Every action key the platform defines, in the order the lang file lists them.
     *
     * The lang file is the source because AuditLabelsTest already pins it to contracts/audit.md in
     * both directions — every contract key has a label, and no label exists for a key the contract
     * does not define. Reading it here means the filter dropdown cannot offer an action the system
     * never emits, and cannot omit one it does.
     *
     * @return list<string>
     */
    public static function contractActions(): array
    {
        /** @var array<string, string> $labels */
        $labels = (array) __('audit.actions');

        return array_values(array_filter(
            array_keys($labels),
            static fn (string $key): bool => $key !== 'unknown',
        ));
    }

    public static function for(string $action): string
    {
        /** @var array<string, string> $labels */
        $labels = (array) __('audit.actions');

        return $labels[$action] ?? (string) __('audit.actions.unknown');
    }
}
