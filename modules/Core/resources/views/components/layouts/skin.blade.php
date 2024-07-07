<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />

    @includeWhen(config('core.favicon_enabled'), 'favicon')

    @includeWhen($darkMode ?? true, 'core::theme-change')

    <title>@yield('title')</title>

    <script>
        window.Innoclapps = {
            bootingCallbacks: [],
            booting: function(callback) {
                this.bootingCallbacks.push(callback)
            }
        }
    </script>

    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">

    <script src="{{ \Modules\Core\App\Facades\Innoclapps::vueSrc() }}"></script>

    @vite(['resources/js/app.js'])

    {!! \Modules\Core\App\Facades\Innoclapps::viteOutput() !!}

    <script>
        @if (($darkMode ?? true) === true)
            updateTheme();
        @endif

        var config = {!! Js::from(array_merge($config, ['csrfToken' => csrf_token()])) !!};
        var lang = {!! Js::from($lang) !!};
    </script>

    @stack('head')

    @includeIf('custom.includes.head')
</head>

<body>
    <div id="app" v-cloak>
        {{ $slot }}

        <teleport to="body">
            <the-float-notifications></the-float-notifications>
        </teleport>
    </div>

    @include('core::boot')

    <script>
        bootApplication(config, Innoclapps.bootingCallbacks);
    </script>
</body>

</html>
