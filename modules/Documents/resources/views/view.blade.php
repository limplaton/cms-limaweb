<x-core::layouts.skin :darkMode="false">
    @section('title', $title)

    @push('head')
        <meta name="robots" content="noindex">

        @foreach ($document->content->forPreview()->usedGoogleFonts() as $font)
            <link
                href="https://fonts.googleapis.com/css2?family={{ urlencode($font['name']) }}{{ $font['stylesQueryString'] }}&display=swap"
                rel="stylesheet" />
        @endforeach
    @endpush

    <div class="contenteditable-view h-full min-h-screen">
        <document-public-view :requires-signature="{{ $document->requires_signature ? 'true' : 'false' }}"
            title="{{ $document->title }}" content="{{ clean($document->content->forPreview()) }}"
            uuid="{{ $document->uuid }}" status="{{ $document->status->value }}" public-url="{{ $document->publicUrl }}"
            accept-thank-you-message="{{ clean($document->localizedBrandConfig('document.accepted_thankyou_message')) }}"
            sign-thank-you-message="{{ clean($document->localizedBrandConfig('document.signed_thankyou_message')) }}"
            signature-bound-text="{{ $document->brand->getLocalizedConfig('signature.bound_text', $document->locale) }}"
            navigation-background-color="{{ $document->brand->config['navigation']['background_color'] }}"
            logo="{{ $document->brand->logo_view ? $document->brand->logo_view_url : config('core.logo.dark') }}"
            brand-name="{{ $document->brand->display_name }}" view-type="{{ $document->view_type }}"
            :navigation="{{ Js::from($document->content->navigation()) }}"
            navigation-heading-tag-name="{{ $document->content::NAVIGATION_HEADING_TAG_NAME }} ">
        </document-public-view>
    </div>
</x-core::layouts.skin>
