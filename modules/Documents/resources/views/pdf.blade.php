<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <title>{{ $document->title }}</title>

    {{-- Define default PDF font family --}}
    <style>
        body {
            font-family: {{ $document->pdfFont()['font-family'] }};
        }

        @page {
            margin: 0px;
        }

        html,
        body {
            margin: 0px;
        }

        @if ($document->data['pdf']['padding'] ?? null)
            .contentbuilder {
                padding: {{ $document->data['pdf']['padding'] }};
            }
        @endif
    </style>

    {{-- Add signature font --}}
    <style>
        @if ($document->atLeastOneSigned())
            @font-face {
                font-family: 'Dancing Script';
                font-style: normal;
                font-weight: 400;
                src: url("{{ public_path('fonts/DancingScript-Regular.ttf') }}") format("truetype");
            }

            .font-signature {
                font-family: 'Dancing Script' !important;
            }
        @endif
    </style>

    {{-- Add all the used Google fonts --}}
    @foreach ($document->content->forPdf()->usedGoogleFonts() as $font)
        <link
            href="https://fonts.googleapis.com/css2?family={{ urlencode($font['name']) }}{{ $font['stylesQueryString'] }}&display=swap"
            rel="stylesheet" />
    @endforeach

    {{-- Add the ionicons font CSS --}}
    @if (is_file($iconsPath = public_path('static/contentbuilder/assets/ionicons/css/ionicons.css')))
        <style>
            {!! str_replace('/fonts', public_path('/fonts'), file_get_contents($iconsPath)) !!}
        </style>
    @endif

    {{-- Content builder CSS for for PDF --}}
    @if (is_file($pdfPath = base_path('resources/css/contentbuilder/pdf.css')))
        <style type="text/css">
            {!! file_get_contents($pdfPath) !!};
        </style>
    @endif
</head>

<body>
    <div class="contentbuilder">
        {!! $document->content->forPdf() !!}
    </div>
</body>

</html>
