<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />

    @includeWhen(config('core.favicon_enabled'), 'favicon')

    @include('core::theme-change')

    <title>{{ config('app.name') }}</title>

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

    @vite(['resources/js/app.js', 'resources/css/contentbuilder/theme.css'])

    {!! \Modules\Core\App\Facades\Innoclapps::viteOutput() !!}

    <script>
        updateTheme();

        var config = {!! Js::from(array_merge($config, ['csrfToken' => csrf_token()])) !!};
        var lang = {!! Js::from($lang) !!};
    </script>

    {{-- Add all of the custom registered styles --}}
    @foreach (\Modules\Core\App\Facades\Innoclapps::styles() as $name => $path)
        @if (\Illuminate\Support\Str::startsWith($path, ['http://', 'https://']))
            <link rel="stylesheet" href="{!! $path !!}">
        @else
            <link rel="stylesheet" href="{{ url("styles/$name") }}">
        @endif
    @endforeach

    @includeIf('custom.includes.head')

    {{-- Head Flag --}}
</head>

<body>
    <div class="flex h-screen overflow-hidden bg-neutral-100 dark:bg-neutral-800" id="app" v-cloak>
        <the-sidebar></the-sidebar>

        <div class="flex w-0 flex-1 flex-col overflow-hidden">

            @include('core::warnings.dashboard')

            <the-navbar></the-navbar>

            {{-- Navbar End Flag --}}

            @if ($alert = get_current_alert())
                <i-alert variant="{{ $alert['variant'] }}" dismissible>
                    <i-alert-body>
                        {{ $alert['message'] }}
                    </i-alert-body>
                </i-alert>
            @endif

            @if (auth()->user()->can('use voip') && config('voip.client') !== null)
                <call-component></call-component>
            @endif

            <router-view></router-view>

            <the-floating-resource-modal></the-floating-resource-modal>

            <teleport
                :to="confirmationDialog.value && confirmationDialog.value._teleport ? confirmationDialog.value._teleport :
                    'body'">
                <i-confirmation-dialog v-if="confirmationDialog.value" :dialog="confirmationDialog.value">
                </i-confirmation-dialog>
            </teleport>

            <teleport to="body">
                <the-float-notifications></the-float-notifications>
            </teleport>
        </div>
    </div>

    <script src="{{ asset('static/tinymce/tinymce.min.js?v=' . \Modules\Core\App\Application::VERSION) }}"></script>

    {{-- Add all of the custom registered scripts --}}
    @foreach (\Modules\Core\App\Facades\Innoclapps::scripts() as $name => $path)
        @if (\Illuminate\Support\Str::startsWith($path, ['http://', 'https://']))
            <script src="{!! $path !!}"></script>
        @else
            <script src="{{ url("scripts/$name") }}"></script>
        @endif
    @endforeach

    @include('core::boot')

    <script>
        bootApplication(config, Innoclapps.bootingCallbacks);
    </script>
</body>

</html>
