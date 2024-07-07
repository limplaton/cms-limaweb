@if (Str::startsWith(config('app.url'), 'https://') && Str::startsWith(URL::current(), 'http://'))
    <i-alert variant="danger" class="{{ Auth::check() ? 'rounded-none' : '' }}">
        <i-alert-body>
            You must <a href="{{ config('app.url') }}"
                class="font-semibold text-danger-800 hover:text-danger-600">access</a>
            the installation URL with <span class="font-semibold">https</span>.
        </i-alert-body>
    </i-alert>
@elseif (Str::startsWith(config('app.url'), 'http://') && Str::startsWith(URL::current(), 'https://'))
    <i-alert variant="danger" class="{{ Auth::check() ? 'rounded-none' : '' }}">
        <i-alert-body>
            Incorrect application URL, update the <code class="rounded bg-danger-100 px-1">.env</code> file <span
                class="font-semibold">APP_URL</span> config
            value to start with <span class="font-semibold">https://</span> and delete all the <span
                class="font-semibold">.php</span> files in <span class="font-semibold">bootstrap/cache</span>
        </i-alert-body>
    </i-alert>
@endif
