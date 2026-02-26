@props([
    'label',
    'column',
    'currentSortBy' => null,
    'currentSortDirection' => 'asc',
])

@php
    $isCurrentColumn = $currentSortBy === $column;
    $normalizedDirection = $currentSortDirection === 'desc' ? 'desc' : 'asc';
    $shouldClearSort = $isCurrentColumn && $normalizedDirection === 'desc';
    $nextSortDirection = $isCurrentColumn && $normalizedDirection === 'asc' ? 'desc' : 'asc';

    $query = request()->query();
    if ($shouldClearSort) {
        unset($query['sort_by'], $query['sort_direction']);
    } else {
        $query['sort_by'] = $column;
        $query['sort_direction'] = $nextSortDirection;
    }
    unset($query['page']);

    $sortUrl = request()->url().(!empty($query) ? '?'.http_build_query($query) : '');

    if (!$isCurrentColumn) {
        $sortIcon = 'ri-arrow-up-down-line';
        $ariaSort = 'none';
    } elseif ($normalizedDirection === 'asc') {
        $sortIcon = 'ri-arrow-up-line';
        $ariaSort = 'ascending';
    } else {
        $sortIcon = 'ri-arrow-down-line';
        $ariaSort = 'descending';
    }
@endphp

<th scope="col" aria-sort="{{ $ariaSort }}">
    <a href="{{ $sortUrl }}" class="text-body text-decoration-none d-inline-flex align-items-center gap-1"
        data-ajax-sort-link data-sort-column="{{ $column }}">
        <span>{{ $label }}</span>
        <i class="{{ $sortIcon }}" aria-hidden="true"></i>
    </a>
</th>
