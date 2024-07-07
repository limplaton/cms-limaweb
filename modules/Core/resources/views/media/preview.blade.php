<x-core::layouts.skin>
    @section('title', __('core::app.file_preview'))

    @push('head')
        <meta name="robots" content="noindex">
    @endpush

    <div class="h-screen min-h-screen bg-neutral-50 dark:bg-neutral-800">
        <div class="w-full border-b border-neutral-200 bg-neutral-100 dark:border-neutral-500/30 dark:bg-neutral-900">
            <div class="m-auto max-w-6xl">
                <div class="flex items-center p-4">
                    <div class="flex grow items-center space-x-3">
                        <i-button-copy icon="Share" text="{{ $media->getViewUrl() }}"
                            success-message="{{ __('core::media.link_copied') }}"
                            v-i-tooltip.bottom="'{{ __('core::app.copy') }}'">
                        </i-button-copy>
                        <i-text-display>
                            {{ __('core::app.file_preview') }}
                        </i-text-display>
                    </div>
                    <div>
                        <i-link href="{{ $media->getDownloadUrl() }}" target="_self"
                            text="{{ __('core::app.download') }}" download></i-link>
                    </div>
                </div>
            </div>
        </div>
        <div class="m-auto max-w-6xl">
            <div class="flex w-full flex-col p-4">
                @if ($media->aggregate_type === 'image')
                    <img src="{{ $media->previewPath() }}" class="mx-auto rounded" alt="{{ $media->basename }}">
                @elseif($media->aggregate_type === 'pdf')
                    <div class="aspect-h-9 aspect-w-16">
                        <iframe src="{{ $media->previewPath() }}" name="{{ $media->filename }}" allowfullscreen>
                        </iframe>
                    </div>
                @elseif($media->mime_type === 'text/plain')
                    <div class="whitespace-normal text-left">
                        {{ $media->contents() }}
                    </div>
                @elseif($media->aggregate_type === 'video' && $media->isHtml5SupportedVideo())
                    <div class="aspect-h-9 aspect-w-16">
                        <video autoplay controls>
                            <source src="{{ $media->previewPath() }}" type="{{ $media->mime_type }}">
                            Your browser does not support the video tag.
                        </video>
                    </div>
                @elseif($media->aggregate_type === 'audio' && $media->isHtml5SupportedAudio())
                    <audio autoplay controls>
                        <source src="{{ $media->previewPath() }}" type="{{ $media->mime_type }}">
                        Your browser does not support the audio tag.
                    </audio>
                @else
                    <i-text class="text-center">
                        {{ __('core::media.no_preview_available') }}
                    </i-text>
                @endif
                <div class="mt-5 text-center">
                    <i-link href="{{ $media->getDownloadUrl() }}" target="_self">
                        {{ $media->basename }}
                    </i-link>
                </div>
            </div>
        </div>
    </div>
</x-core::layouts.skin>
