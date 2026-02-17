@props(['title', 'items' => []])

<div class="d-flex align-items-center justify-content-between my-4 page-header-breadcrumb flex-wrap gap-2">
    <div>
        <p class="fw-semibold fs-18 mb-1">{{ $title }}</p>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('tenant.dashboard') }}">{{ __('Dashboard') }}</a></li>
            @foreach ($items as $item)
                @php
                    $label = $item['label'] ?? '';
                    $url = $item['url'] ?? null;
                @endphp
                @if ($url)
                    <li class="breadcrumb-item"><a href="{{ $url }}">{{ $label }}</a></li>
                @else
                    <li class="breadcrumb-item active" aria-current="page">{{ $label }}</li>
                @endif
            @endforeach
        </ol>
    </div>
    @isset($actions)
        <div class="d-flex align-items-center gap-2 flex-wrap">
            {{ $actions }}
        </div>
    @endisset
</div>
