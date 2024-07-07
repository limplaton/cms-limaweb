<x-core::layouts.skin :darkMode="false">
    @push('head')
        <meta name="robots" content="noindex">
    @endpush

    @section('title', $title)

    @if (Auth::check() && !$form->isActive())
        <i-alert variant="warning" class="rounded-none">
            <i-alert-body>
                {{ __('webforms::form.inactive_info') }}
            </i-alert-body>
        </i-alert>
    @endif

    <div class="sm:px-5">
        <web-form-public-view :sections="{{ Js::from($form->sections) }}" :styles="{{ Js::from($form->styles) }}"
            :submit-data="{{ Js::from($form->submit_data) }}" public-url="{{ $form->publicUrl }}"
            logo="{{ $form->logo() }}"></web-form-public-view>
    </div>
</x-core::layouts.skin>
