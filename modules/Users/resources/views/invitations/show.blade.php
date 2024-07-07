<x-core::layouts.skin>
    @section('title', __('users::user.accept_invitation'))

    @push('head')
        <meta name="robots" content="noindex">
    @endpush

    <div class="h-screen min-h-screen bg-neutral-100 dark:bg-neutral-800">
        <div class="pt-20">
            <div class="mx-auto max-w-2xl">
                @include('core::brand')
                <div class="px-2 sm:px-0">
                    <i-card class="mt-6">
                        <user-invitation-accept-form :invitation="{{ Js::from($invitation) }}"
                            :timezones="{{ Js::from(tz()->all()) }}" date-format="{{ config('core.date_format') }}"
                            time-format="{{ config('core.time_format') }}">
                        </user-invitation-accept-form>
                    </i-card>
                </div>
            </div>
        </div>
    </div>
</x-core::layouts.skin>
