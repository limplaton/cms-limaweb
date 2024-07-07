<x-mail.layout>
    {!! $document->data['send_mail_body'] !!}

    <x-mail.button>
        <a href="{{ $document->publicUrl }}" class="button"
            style="background-color: {{ $primaryColor }}; border-bottom: 8px solid {{ $primaryColor }}; border-left: 18px solid {{ $primaryColor }}; border-right: 18px solid {{ $primaryColor }}; border-top: 8px solid {{ $primaryColor }}; color:#fff;"
            target="_blank" rel="noopener">
            {{ $document->localizedBrandConfig('document.mail_button_text') }}
        </a>
    </x-mail.button>

    <x-slot:header>
        <span style="display: inline-block;">
            <img src="{{ $logo }}" class="logo" alt="{{ $document->brand->display_name }} Logo" />
        </span>
    </x-slot:header>

    <x-slot:footer>
        © {{ date('Y') }} {{ $document->brand->display_name }}.
    </x-slot:footer>

</x-mail.layout>
