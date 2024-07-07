<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-v="{{ \Modules\Core\App\Application::VERSION }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />

    @includeWhen(config('core.favicon_enabled'), 'favicon')

    @include('core::theme-change')

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

    <script>
        updateTheme();
        var config = {!! Js::from($config) !!};
        var lang = {!! Js::from($lang) !!};
    </script>

    <!-- Add all of the custom registered styles -->
    @foreach (\Modules\Core\App\Facades\Innoclapps::styles() as $name => $path)
        @if (\Illuminate\Support\Str::startsWith($path, ['http://', 'https://']))
            <link rel="stylesheet" href="{!! $path !!}">
        @else
            <link rel="stylesheet" href="{{ url("styles/$name") }}">
        @endif
    @endforeach

    {{-- Head Flag --}}
</head>

<body>
    <div id="app">
        <div class="flex min-h-screen flex-col justify-center bg-neutral-50 py-12 dark:bg-neutral-900 sm:px-6 lg:px-8">
            <div class="sm:mx-auto sm:w-full sm:max-w-md">
                @include('core::brand')

                <h2 class="mt-6 text-center text-3xl font-extrabold text-neutral-900 dark:text-neutral-100">
                    @yield('title')
                </h2>
                <p class="mt-1 text-center text-base text-neutral-600 dark:text-neutral-300">
                    @yield('subtitle')
                </p>
            </div>

            <div class="mt-8 px-2 sm:mx-auto sm:w-full sm:max-w-md sm:px-0">
                <div class="rounded-lg bg-white px-6 py-8 shadow dark:bg-neutral-800 sm:px-10">
                    <div class="space-y-4">
                        @include('core::warnings.www-url-prefix')
                        @include('core::warnings.incorrect-url')
                        {{-- Fake div for spacing when only 1 alert exists --}}
                        <div></div>
                    </div>
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>

    @include('core::boot')

    <script>
        bootApplication(config, Innoclapps.bootingCallbacks);
    </script>
</body>

</html>
