@php
    $formSelector = $formSelector ?? '#brands-search-form';
    $inputSelector = $inputSelector ?? '#search';
    $tableBodySelector = $tableBodySelector ?? '#brands-table tbody';
    $paginationSelector = $paginationSelector ?? '[data-brands-pagination]';
    $loadingSelector = $loadingSelector ?? '#brands-search-loading';
    $tableHeadSelector = $tableHeadSelector ?? null;
    $sortLinkSelector = $sortLinkSelector ?? '[data-ajax-sort-link]';
    $searchParam = $searchParam ?? 'search';
    $debounce = $debounce ?? '350';
    $minLoadingVisible = $minLoadingVisible ?? '220';
@endphp
data-ajax-table-search
data-form-selector="{{ $formSelector }}"
data-input-selector="{{ $inputSelector }}"
data-table-body-selector="{{ $tableBodySelector }}"
@if (filled($tableHeadSelector))
    data-table-head-selector="{{ $tableHeadSelector }}"
@endif
data-pagination-selector="{{ $paginationSelector }}"
data-loading-selector="{{ $loadingSelector }}"
data-sort-link-selector="{{ $sortLinkSelector }}"
data-search-param="{{ $searchParam }}"
data-debounce="{{ $debounce }}"
data-min-loading-visible="{{ $minLoadingVisible }}"
