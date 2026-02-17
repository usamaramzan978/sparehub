<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" data-vertical-style="overlay" data-theme-mode="light"
    data-header-styles="light" data-menu-styles="light" data-toggled="close">

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

    @vite(['resources/css/app.css', 'resources/scss/theme.scss', 'resources/assets/css/styles.css', 'resources/assets/css/icons.css', 'resources/js/app.js', 'resources/js/theme.js', 'resources/js/auth.js', 'resources/assets/js/main.js'])

    @yield('styles')
    @stack('styles')

</head>

<body class="authenticationcover-background bg-primary-transparent position-relative" id="particles-js">

    @yield('content')

    @stack('scripts')
</body>

</html>
