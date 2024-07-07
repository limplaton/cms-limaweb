<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex">

    <title>{{ $title ?? ' CRM - Installation' }}</title>

    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">

    @php
        $viteManifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
        $cssFilePath = $viteManifest['resources/js/app.js']['css'][0] ?? null;
    @endphp

    @if ($cssFilePath)
        <link rel="stylesheet" href="{{ asset('build/' . $cssFilePath) }}">
    @endif
</head>

<body>
    <div id="app">
        <div class="flex min-h-screen flex-col justify-center bg-neutral-100 py-12 sm:px-6 lg:px-8">
            <div class="sm:mx-auto sm:w-full sm:max-w-md">
                @if (file_exists(public_path('logo.png')))
                    <div class="mb-5 mt-5 text-center">
                        <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}" class="mx-auto h-12 w-auto">
                    </div>
                @endif
            </div>
            <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-5xl">
                <div class="border border-neutral-200 bg-white p-7 shadow-sm sm:rounded-lg">
                    @if (!isset($withSteps) || (isset($withSteps) && $withSteps !== false))
                        @include('installer.menu')
                    @endif

                    @error('general')
                        <div class="mt-5 rounded-md bg-warning-50 p-4">
                            <div class="flex">
                                <div class="shrink-0">
                                    <!-- Heroicon name: solid/exclamation -->
                                    <svg class="size-5 text-warning-400" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd"
                                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-warning-800">
                                        {!! nl2br($message) !!}
                                    </h3>
                                </div>
                            </div>
                        </div>
                    @enderror

                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</body>

</html>
