<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'POS') }}</title>

    <link rel="icon" type="image/png" href="{{ 'favicon/favicon-96x96.png' }}" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="{{ 'favicon/favicon.svg' }}" />
    <link rel="shortcut icon" href="{{ 'favicon/favicon.ico' }}" />
    <link rel="apple-touch-icon" sizes="180x180" href="{{ 'favicon/apple-touch-icon.png' }}" />
    <meta name="apple-mobile-web-app-title" content="POS" />
    <link rel="manifest" href="{{ 'favicon/site.webmanifest' }}" />

    @vite(['resources/scss/theme.scss', 'resources/assets/css/styles.css', 'resources/assets/css/icons.css', 'resources/js/theme.js', 'resources/assets/js/main.js'])

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

    @yield('scripts')
    @stack('scripts')
</body>

</html>
