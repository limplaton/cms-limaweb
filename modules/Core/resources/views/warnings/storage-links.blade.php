@if (Auth::user()->isSuperAdmin() &&
        app()->isProduction() &&
        collect(config('filesystems.links'))->filter(fn($link, $target) => !is_link($link))->isNotEmpty())
    @foreach (config('filesystems.links') as $link => $target)
        @if (is_link($link))
            @continue
        @endif

        <i-alert variant="danger" class="rounded-none">
            <i-alert-body>
                <p>
                    The <span class="font-semibold">{{ $link }}</span> filesystem link does not exist.
                </p>

                <p class="mt-2">
                    If you recently moved your installation to another server or location, the
                    <code><span
                            class="font-semibold">{{ str_replace(base_path() . DIRECTORY_SEPARATOR, '', $link) }}</span></code>
                    directory should not be copied into your new location.
                </p>

                <p class="mt-2">
                    After ensuring that the directory is not copied, navigate to <span
                        class="font-semibold">Settings->System->Tools</span> and run the <span
                        class="font-semibold">storage-link</span> command.
                </p>
            </i-alert-body>
        </i-alert>
    @endforeach
@endif
