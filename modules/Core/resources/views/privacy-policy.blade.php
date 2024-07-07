<x-core::layouts.skin>
    @section('title', config('app.name') . ' - ' . __('core::app.privacy_policy'))

    <div class="h-full min-h-screen dark:bg-neutral-800">
        <div class="w-full border-b border-neutral-200 bg-neutral-100 dark:border-neutral-500/30 dark:bg-neutral-900">
            <div class="m-auto max-w-6xl">
                <div class="p-4">
                    <h5 class="text-lg font-semibold text-neutral-800 dark:text-neutral-200">
                        {{ config('app.name') . ' - ' . __('core::app.privacy_policy') }}
                    </h5>
                </div>
            </div>
        </div>
        <div class="m-auto max-w-6xl">
            <div class="wysiwyg-text p-4">
                {!! $content !!}
            </div>
        </div>
    </div>
</x-core::layouts.skin>
