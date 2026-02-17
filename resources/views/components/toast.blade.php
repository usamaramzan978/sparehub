@php
    $toastItems = [];
    $flashMap = [
        'success' => session('success'),
        'warning' => session('warning'),
        'error' => session('error'),
        'info' => session('info'),
        'status' => session('status'),
    ];

    foreach ($flashMap as $type => $message) {
        if (!$message) {
            continue;
        }
        $toastItems[] = [
            'type' => $type === 'status' ? 'success' : $type,
            'message' => $message,
        ];
    }

    if ($errors && $errors->any()) {
        $toastItems[] = [
            'type' => 'error',
            'message' => $errors->first(),
        ];
    }

    $titles = [
        'success' => 'Success',
        'warning' => 'Warning',
        'error' => 'Error',
        'info' => 'Info',
    ];
@endphp

<div class="app-toast-region" aria-live="polite" aria-atomic="true">
    <div class="app-toast-stack" id="app-toast-stack">
        @foreach ($toastItems as $toast)
            <div class="app-toast app-toast-{{ $toast['type'] }}" data-toast data-timeout="4500" role="status">
                <div class="app-toast-icon" aria-hidden="true">
                    @switch($toast['type'])
                        @case('success')
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M9.6 16.2 5.8 12.4l1.4-1.4 2.4 2.4 6.2-6.2 1.4 1.4z" />
                            </svg>
                            @break
                        @case('warning')
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 3 1.8 20.4h20.4zm0 5.1 3.7 6.4H8.3zM11.1 9.3h1.8v5.4h-1.8zm0 6.3h1.8v1.8h-1.8z" />
                            </svg>
                            @break
                        @case('error')
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 2.4a9.6 9.6 0 1 0 0 19.2 9.6 9.6 0 0 0 0-19.2zm3.3 12.3-1.2 1.2L12 13.2l-2.1 2.1-1.2-1.2L10.8 12 8.7 9.9l1.2-1.2 2.1 2.1 2.1-2.1 1.2 1.2L13.2 12z" />
                            </svg>
                            @break
                        @default
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 2.4a9.6 9.6 0 1 0 0 19.2 9.6 9.6 0 0 0 0-19.2zm0 4.2a1.2 1.2 0 1 1 0 2.4 1.2 1.2 0 0 1 0-2.4zm1.2 10.8h-2.4V10.2h2.4z" />
                            </svg>
                    @endswitch
                </div>
                <div class="app-toast-body">
                    <div class="app-toast-title">{{ $titles[$toast['type']] ?? 'Info' }}</div>
                    <div class="app-toast-message">{{ $toast['message'] }}</div>
                </div>
                <button class="app-toast-close" type="button" aria-label="Close" data-toast-close>×</button>
            </div>
        @endforeach
    </div>
</div>
