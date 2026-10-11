{{--
    T131 — the accounts screen, built to the UsersScreen reference.

    Page header with a create action, a filter row, and the table. The filters submit with GET so
    the state lives in the URL: a Super Admin who narrows to one branch and then comes back lands on
    the same view, and can send someone the link.

    Row actions are gated on the permission the endpoint checks, not on the role. A row offering
    "deactivate" to someone whose next click would 403 is a worse screen than one that omits it.
--}}

@use('App\Modules\Shared\Support\Format')

@php
    $isEn = app()->isLocale('en');

    $name = fn ($u) => $isEn ? $u->name_en : $u->name_ar;
    $campus = fn ($b) => $isEn ? $b->campus_name_en : $b->campus_name_ar;

    /*
     * Rows carry the model alongside the display values, so a renderer can reach `is_active` and the
     * id without the table knowing what a user is.
     */
    $rows = $users->map(fn ($u) => [
        'model' => $u,
        'name' => $name($u),
        'email' => $u->email,
        'role' => $u->getRoleNames()->map(fn ($r) => __('users.roles.'.$r))->implode('، '),
        'branches' => $u->coordinatorBranches->map($campus)->implode('، ')
            ?: __('common.labels.none'),
        'status' => $u->is_active ? __('status.active') : __('status.inactive'),
        'last_login' => Format::dateTime($u->last_login_at, __('users.never_signed_in')),
        // Kept although the column no longer sorts in the browser (the server orders every page):
        // every date cell carries its timestamp, which DateColumnsSortTest holds the tables to.
        '_sort' => ['last_login' => $u->last_login_at?->getTimestamp() ?? 0],
    ])->all();

    /*
     * Per-column cell renderers, built here rather than in the component's attribute. A
     * `:renderers="[...]"` value containing a nested double-quoted string terminates the Blade
     * attribute early, and the remainder is parsed as template text — which produced an undefined
     * $status at runtime.
     */
    $renderers = [
        // Rendered through the real component tag, so FR-077 holds here too: the badge cannot be
        // produced without its text label.
        'status' => fn (array $row) => new Illuminate\Support\HtmlString(
            Illuminate\Support\Facades\Blade::render(
                '<x-track-status-badge :status=$s />',
                ['s' => $row['model']->is_active ? 'active' : 'inactive'],
            )
        ),

        'row_actions' => fn (array $row) => new Illuminate\Support\HtmlString(
            view('users._row-actions', ['user' => $row['model']])->render()
        ),
    ];
@endphp

<x-app-layout :title="__('users.title')">
    <x-track-first-login-banner />

    <x-track-page-header :title="__('users.title')" :subtitle="__('users.subtitle')">
        <x-slot:breadcrumbs>
            <x-track-breadcrumbs :items="[
                ['label' => __('nav.home'), 'url' => route('home')],
                ['label' => __('users.title')],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            @can('user.create')
                {{-- x-data, even though this button holds no state. $dispatch is an Alpine magic and
                     Alpine only processes directives on elements inside a component — without a
                     scope the x-on:click was never bound and the button did nothing at all. The
                     row-action buttons work because the data table around them has x-data. --}}
                <span x-data>
                    <x-track-button icon="user-plus"
                                    x-on:click="$dispatch('track-dialog-open', { id: 'user-dialog' })">
                        {{ __('users.create') }}
                    </x-track-button>
                </span>
            @endcan

            {{-- T349 — staff accounts in bulk (FR-104), beside creating one by hand. --}}
            @can('import.user.run')
                <x-track-button variant="secondary" icon="file-spreadsheet" :href="route('imports.create.users')">
                    {{ __('imports.actions.users') }}
                </x-track-button>
            @endcan
        </x-slot:actions>
    </x-track-page-header>

    <form method="GET" action="{{ route('users.index') }}" class="track-filters">
        <x-track-select
            name="role"
            :label="__('users.filters.role')"
            :placeholder="__('users.filters.all')"
            :selected="request('role')"
            :options="[
                ['value' => 'SUPER_ADMIN', 'label' => __('users.roles.SUPER_ADMIN')],
                ['value' => 'BRANCH_COORDINATOR', 'label' => __('users.roles.BRANCH_COORDINATOR')],
                ['value' => 'INSTRUCTOR', 'label' => __('users.roles.INSTRUCTOR')],
            ]"
        />

        <x-track-select
            name="branch_id"
            :label="__('users.filters.branch')"
            :placeholder="__('users.filters.all')"
            :selected="request('branch_id')"
            :options="$branches->map(fn ($b) => ['value' => $b->id, 'label' => $campus($b)])->all()"
        />

        <x-track-select
            name="is_active"
            :label="__('users.filters.status')"
            :placeholder="__('users.filters.all')"
            :selected="request('is_active')"
            :options="[
                ['value' => '1', 'label' => __('status.active')],
                ['value' => '0', 'label' => __('status.inactive')],
            ]"
        />

        {{-- Sorted by the server, across every page (UserController::index): sorting «آخر دخول» in
             the browser reordered only the 25 rows on screen, and never surfaced the dormant
             accounts on the other pages. --}}
        <x-track-select
            name="sort"
            :label="__('users.filters.sort')"
            :selected="request('sort', 'name')"
            :options="[
                ['value' => 'name', 'label' => __('users.sorts.name')],
                ['value' => 'last_login', 'label' => __('users.sorts.last_login')],
                ['value' => 'last_login_desc', 'label' => __('users.sorts.last_login_desc')],
            ]"
        />

        <div class="track-filters__search">
            <x-track-input
                name="q"
                :label="__('common.actions.search')"
                icon="search"
                :placeholder="__('users.filters.search')"
                :value="request('q')"
            />
        </div>

        <x-track-button type="submit" variant="secondary" icon="filter">
            {{ __('common.actions.filter') }}
        </x-track-button>
    </form>

    <x-track-card :padding="0">
        <x-track-data-table
            :caption="__('users.title')"
            :empty="__('users.empty.message')"
            empty-icon="user-cog"
            :columns="[
                ['key' => 'name', 'label' => __('users.fields.name')],
                ['key' => 'email', 'label' => __('users.fields.email')],
                ['key' => 'role', 'label' => __('users.fields.role')],
                ['key' => 'branches', 'label' => __('users.fields.branches'), 'sortable' => false],
                ['key' => 'status', 'label' => __('users.fields.status')],
                // Not sortable in the browser: the sort filter above orders every page. (No double
                // quotes in this comment: it sits inside a double-quoted attribute and would close it.)
                ['key' => 'last_login', 'label' => __('users.fields.last_login'), 'sortable' => false],
                // `actions.more`, not `labels.more`. The key lives under `actions` in lang/{ar,en}/common.php,
                // so the wrong path resolved to nothing and Laravel printed the key itself as a column
                // heading — in both locales, at every width, frozen into the baselines since Phase 4.
                ['key' => 'row_actions', 'label' => __('common.actions.more'), 'sortable' => false],
            ]"
            :rows="$rows"
            :renderers="$renderers"
        />
    </x-track-card>

    @if ($users->hasPages())
        <div style="margin-top: var(--space-4)">{{ $users->links() }}</div>
    @endif

    @can('user.create')
        @include('users._dialog')
    @endcan

    @can('user.deactivate')
        @include('users._deactivate')
    @endcan

    @can('user.reset_password')
        @include('users._reset')
    @endcan
</x-app-layout>
