<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'SpareHub') }}</title>

    <link rel="icon" type="image/png" href="{{ asset('favicon/favicon-96x96.png') }}" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon/favicon.svg') }}" />
    <link rel="shortcut icon" href="{{ asset('favicon/favicon.ico') }}" />
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicon/apple-touch-icon.png') }}" />
    <meta name="apple-mobile-web-app-title" content="SpareHub" />
    <link rel="manifest" href="{{ asset('favicon/site.webmanifest') }}" />

    @vite(['resources/scss/theme.scss', 'resources/assets/css/styles.css', 'resources/assets/css/icons.css', 'resources/js/app.js', 'resources/js/theme.js', 'resources/assets/js/main.js'])

    @yield('styles')
    @stack('styles')


</head>

<body>
    @include('layouts.shared.switcher')

    <div id="loader" class="d-none"> <img src="../assets/images/media/loader.svg" alt=""> </div>

    <div class="page">
        @include('layouts.shared.header')
        @include('layouts.shared.sidebar')

        <div class="main-content app-content">
            <div class="container-fluid">
                @yield('content')
            </div>
        </div>
    </div>

    @include('layouts.shared.footer')
    <x-toast />

    <script>
        // small shared script that appends a red * to any label.form-label[for] whose target field has required.
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('label.form-label[for]').forEach((label) => {
                const fieldId = label.getAttribute('for');
                if (!fieldId) {
                    return;
                }

                const field = document.getElementById(fieldId);
                if (!(field instanceof HTMLInputElement || field instanceof HTMLSelectElement ||
                        field instanceof HTMLTextAreaElement)) {
                    return;
                }

                if (!field.required) {
                    return;
                }

                const hasIndicator = Array.from(label.querySelectorAll('span')).some((span) => span
                    .textContent?.trim() === '*');
                if (hasIndicator) {
                    return;
                }

                const marker = document.createElement('span');
                marker.className = 'text-danger';
                marker.setAttribute('data-required-indicator', '1');
                marker.textContent = ' *';
                label.appendChild(marker);
            });
        });
    </script>

    @yield('scripts')
    @stack('scripts')
</body>

</html>
