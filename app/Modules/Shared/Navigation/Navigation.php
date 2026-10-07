<?php

declare(strict_types=1);

namespace App\Modules\Shared\Navigation;

use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * T153 — builds the sidebar for the signed-in caller from config/navigation.php.
 *
 * ## Permission first, role second
 *
 * The role decides the *order and curation*; the permission decides whether an entry appears at
 * all. Running it in that order is what keeps FR-020 true — an invented role with `audit.view`
 * gets an audit entry in its sidebar without anyone editing this class — and it also closes the
 * smaller gap where a role's curated list names something that role can no longer do, which happens
 * every time a permission set is narrowed and a config file is not.
 *
 * ## Stubbed entries
 *
 * Three of the eleven destinations have no screen in this feature. They render as `#` and are
 * marked, rather than being hidden, so the sidebar matches the approved design today instead of
 * growing entry by entry over six phases — and so the visual baselines captured now stay valid.
 */
final class Navigation
{
    /**
     * @return list<array{key: string, icon: string, url: string, active: bool, stub: bool}>
     */
    public static function forUser(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        /** @var array<string, array<string, mixed>> $items */
        $items = config('navigation.items', []);

        /** @var array<string, list<string>> $roles */
        $roles = config('navigation.roles', []);

        $keys = self::keysFor($user, $items, $roles);

        $built = [];

        foreach ($keys as $key) {
            $item = $items[$key] ?? null;

            if ($item === null) {
                continue;
            }

            /*
             * The permission check is here and not in the config, so an entry a role's list names
             * but its permissions do not allow simply does not render. The sidebar never offers a
             * destination whose route would refuse the next click.
             */
            if (! $user->can((string) $item['permission'])) {
                continue;
            }

            $route = $item['route'] ?? null;
            $url = is_string($route) && Route::has($route) ? route($route) : '#';

            $built[] = [
                'key' => $key,
                'icon' => (string) $item['icon'],
                'url' => $url,
                'active' => $url !== '#' && request()->url() === $url,
                'stub' => (bool) ($item['stub'] ?? false),
            ];
        }

        return $built;
    }

    /**
     * The ordered entry keys for this caller.
     *
     * A role the map does not know falls back to every entry, in declaration order, filtered by
     * permission above. That fallback is the mechanism FR-020 rests on: a fourth role seeded next
     * month gets a working sidebar with no edit here.
     *
     * @param  array<string, array<string, mixed>>  $items
     * @param  array<string, list<string>>  $roles
     * @return list<string>
     */
    private static function keysFor(User $user, array $items, array $roles): array
    {
        foreach ($user->roles->pluck('name') as $name) {
            if (isset($roles[$name])) {
                return $roles[$name];
            }
        }

        /*
         * `mysections` is dropped from the fallback. It points at the same /sections route as
         * `sections` and differs only in wording, so an unknown role would get two identical links
         * one above the other. The curated per-role lists choose between them; the fallback cannot,
         * so it keeps the neutral one.
         */
        return array_values(array_filter(
            array_keys($items),
            static fn (string $key): bool => $key !== 'mysections',
        ));
    }
}
