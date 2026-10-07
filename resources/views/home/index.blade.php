{{--
    T130 — the role landing page, built to the HomeScreen reference (FR-017).

    Six KPI tiles, a recent-activity table, and a quick-actions card. What each role sees is decided
    by the route, not here: the controller computes only the counts in that person's scope, and omits
    `users` and `imports` entirely for a role that cannot see them. So an instructor's page has four
    tiles, not six with two zeros — a zero would claim there are none, which is a different and
    false statement.

    The first-sign-in banner sits above everything (FR-012) and is dismissable.
--}}

@php
    $user = auth()->user();
    $locale = app()->getLocale();

    // Each tile's icon comes from the §7 concept map via config/icons.php, so the sidebar and the
    // dashboard name the same thing with the same glyph.
    $icons = [
        'branches' => 'branches',
        'sections' => 'sections',
        'students' => 'students',
        'users' => 'users',
        'enrollments' => 'clipboard-list',
        'imports' => 'imports',
    ];

    $tones = [
        'branches' => 'blue',
        'sections' => 'blue',
        'students' => 'green',
        'users' => 'dark',
        'enrollments' => 'green',
        'imports' => 'dark',
    ];
@endphp

<x-app-layout :title="__('home.title')">
    <x-track-first-login-banner />

    <x-track-page-header
        :title="__('home.welcome', ['name' => $locale === 'en' ? $user->name_en : $user->name_ar])"
        :subtitle="$currentTerm ? ($locale === 'en' ? $currentTerm->name_en : $currentTerm->name_ar) : null"
    />

    <div class="track-stats">
        @foreach ($stats as $key => $value)
            <x-track-stat-card
                :value="number_format((int) $value)"
                :label="__('home.stats.'.$key)"
                :tone="$tones[$key] ?? 'blue'"
                :icon="$icons[$key] ?? null"
            />
        @endforeach
    </div>

    <div class="track-home-grid">
        {{-- US2 — `who` reads through the `user` relation: this query moved from DB::table() to
             the AuditLog model so the branch scope applies, and the actor's name is no longer a
             joined column on the row. --}}
        <x-track-card id="recent-activity" :title="__('home.recent.title')" :padding="0">
            @if ($recent->isEmpty())
                <x-track-empty-state icon="inbox" :title="__('home.recent.empty')" :message="false" />
            @else
                <x-track-data-table
                    :caption="__('home.recent.title')"
                    :dense="true"
                    :columns="[
                        ['key' => 'when', 'label' => __('home.recent.when'), 'sortable' => false],
                        ['key' => 'what', 'label' => __('home.recent.what'), 'sortable' => false],
                        ['key' => 'who', 'label' => __('home.recent.who'), 'sortable' => false],
                    ]"
                    :rows="$recent->map(fn ($entry) => [
                        'when' => \Illuminate\Support\Carbon::parse($entry->created_at)->format('d/m/Y H:i'),
                        'what' => \App\Modules\Audit\Services\AuditLabels::for($entry->action),
                        'who' => $locale === 'en'
                            ? ($entry->user?->name_en ?? '—')
                            : ($entry->user?->name_ar ?? '—'),
                    ])->all()"
                />
            @endif
        </x-track-card>

        <x-track-card :title="__('home.quick.title')">
            <div style="display: flex; flex-direction: column; gap: var(--space-2)">
                {{-- Each action is gated on the permission that endpoint checks, so the card never
                     offers something the next click would refuse with a 403. --}}
                @can('user.create')
                    <x-track-button variant="secondary" icon="user-plus" :block="true" :href="route('users.index')">
                        {{ __('home.quick.new_user') }}
                    </x-track-button>
                @endcan

                <x-track-button variant="ghost" icon="user" :block="true" :href="route('profile')">
                    {{ __('home.quick.profile') }}
                </x-track-button>
            </div>
        </x-track-card>
    </div>
</x-app-layout>
