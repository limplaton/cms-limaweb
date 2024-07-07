@if ($logo = config('core.logo.dark'))
    <img src="{{ $logo }}" alt="{{ config('app.name') }}" class="mx-auto h-12 w-auto">
@endif
