{{--
    US7 — the pagination every list uses, registered as the paginator's default view.

    Laravel's stock view was rendering instead: styled for Tailwind, which this app does not load;
    worded from a lang/pagination.php the app never had, so Arabic pages read "« Previous"; and
    with the disabled "Previous" as a bare span carrying aria-label, which axe rejects (T265).

    Previous and Next are links when they lead somewhere and plain text when they do not — a disabled
    link is still announced as a link. Their chevrons follow the reading direction, as the
    breadcrumbs' do (FR-083). Serves the simple paginator too: without a total, it shows no summary
    and no page numbers.
--}}

@php
    $rtl = app()->isLocale('ar');
    $back = $rtl ? 'chevron-right' : 'chevron-left';
    $forward = $rtl ? 'chevron-left' : 'chevron-right';
    $counted = $paginator instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator;
@endphp

@if ($paginator->hasPages())
    <nav class="track-pager" aria-label="{{ __('common.pagination.label') }}">
        @if ($counted)
            <p class="track-pager__summary">
                {{ __('common.pagination.summary', [
                    'from' => \App\Modules\Shared\Support\Format::number($paginator->firstItem()),
                    'to' => \App\Modules\Shared\Support\Format::number($paginator->lastItem()),
                    'total' => \App\Modules\Shared\Support\Format::number($paginator->total()),
                ]) }}
            </p>
        @endif

        <ul class="track-pager__pages">
            <li>
                @if ($paginator->onFirstPage())
                    <span class="track-pager__step track-pager__step--off">
                        <x-track-icon :name="$back" :size="16" />{{ __('common.pagination.previous') }}
                    </span>
                @else
                    <a class="track-pager__step" href="{{ $paginator->previousPageUrl() }}" rel="prev">
                        <x-track-icon :name="$back" :size="16" />{{ __('common.pagination.previous') }}
                    </a>
                @endif
            </li>

            @foreach ($elements ?? [] as $element)
                @if (is_string($element))
                    <li><span class="track-pager__gap" aria-hidden="true">…</span></li>
                @else
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page === $paginator->currentPage())
                                <span class="track-pager__page track-pager__page--current" aria-current="page">{{ $page }}</span>
                            @else
                                <a class="track-pager__page" href="{{ $url }}" aria-label="{{ __('common.pagination.page', ['page' => $page]) }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            <li>
                @if ($paginator->hasMorePages())
                    <a class="track-pager__step" href="{{ $paginator->nextPageUrl() }}" rel="next">
                        {{ __('common.pagination.next') }}<x-track-icon :name="$forward" :size="16" />
                    </a>
                @else
                    <span class="track-pager__step track-pager__step--off">
                        {{ __('common.pagination.next') }}<x-track-icon :name="$forward" :size="16" />
                    </span>
                @endif
            </li>
        </ul>
    </nav>
@endif
