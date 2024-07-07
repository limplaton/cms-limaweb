@if (
    (Str::contains(config('app.url'), 'www') && !Str::contains(Request::url(), 'www')) ||
        (Str::contains(Request::url(), 'www') && !Str::contains(config('app.url'), 'www')))
    <i-alert variant="danger" class="{{ Auth::check() ? 'rounded-none' : '' }}">
        <i-alert-body>
            You must <a href="{{ config('app.url') }}"
                class="font-semibold text-danger-800 hover:text-danger-600">access</a>
            the installation URL
            {{ Str::contains(config('app.url'), 'www') && !Str::contains(Request::url(), 'www') ? 'with' : 'without' }}
            <b>www</b>.
        </i-alert-body>
    </i-alert>
@endif
