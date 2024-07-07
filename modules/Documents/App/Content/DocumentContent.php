<?php
 

namespace Modules\Documents\App\Content;

use Illuminate\Support\Str;
use KubAT\PhpSimple\HtmlDomParser;
use Modules\Core\App\Models\Media;
use Modules\Core\App\Support\Makeable;
use Modules\Documents\App\Models\Document;
use Stringable;

class DocumentContent implements Stringable
{
    use Makeable;

    /**
     * Products section DOM selector
     */
    const PRODUCTS_SECTION_SELECTOR = '.products-section';

    /**
     * Signatures section DOM selector
     */
    const SIGNATURES_SECTION_SELECTOR = '.signatures-section';

    /**
     *  The heading tag name to build navigation from
     */
    const NAVIGATION_HEADING_TAG_NAME = 'h1';

    /**
     * @var \simple_html_dom|false
     */
    protected $dom;

    /**
     * Initialize new DocumentContent instance
     */
    public function __construct(protected ?string $value, protected Document $model)
    {
    }

    /**
     * Get the document content with products sections embedded
     */
    public function withProducts(): static
    {
        if (! $this->value) {
            return static::make($this->value, $this->model);
        }

        $dom = $this->dom();
        $sections = $dom->find(static::PRODUCTS_SECTION_SELECTOR);

        foreach ($sections as $section) {
            $section->outertext = $this->model->hasProducts() ? $this->productsHtml() : '';
        }

        return static::make($dom->save(), $this->model);
    }

    /**
     * Prepare the document content with embedded signatures
     */
    public function withSignatures(): static
    {
        if (! $this->value) {
            return static::make($this->value, $this->model);
        }

        $dom = $this->dom();

        $signaturesSections = $dom->find(static::SIGNATURES_SECTION_SELECTOR);
        $hasSignatureSections = count($signaturesSections) > 0;

        if ($hasSignatureSections) {
            foreach ($signaturesSections as $section) {
                $section->outertext = $this->model->atLeastOneSigned() ? $this->signaturesHtml() : '';
            }
        }

        $content = $dom->save();

        if (! $hasSignatureSections && $this->model->atLeastOneSigned()) {
            $content .= $this->signaturesHtml();
        }

        return static::make($content, $this->model);
    }

    /**
     * Get the document content intended for document preview
     */
    public function forPreview(): static
    {
        return $this->withProducts()->withPlaceholders();
    }

    /**
     * Get the document content with placeholders replaced with their values
     */
    public function withPlaceholders(): static
    {
        $content = $this->value;

        if ($content) {
            $content = $this->model->placeholders()->render($this->html());
        }

        return static::make($content, $this->model);
    }

    /**
     * Prepare the content intended to be displayed on PDF documents
     */
    public function forPdf(): static
    {
        $instance = $this->withProducts()->withSignatures()->withPlaceholders();

        if (! $this->value) {
            return $instance;
        }

        return $this->prepareHtmlForPdf($instance->html());
    }

    /**
     * Wrap column content in div
     */
    protected function wrapColumnContentInDiv(string $html): string
    {
        $dom = $this->newDomInstance($html);

        $this->ensureAllColumnsAreWithWidth($dom);

        $reStylePadding = '/(padding-?(left|right|top|bottom)?)\s?:((([ ]*((-*\d+(px|em|%|cm|in|pc|pt|mm|ex)?|auto|inherit)*))*(!important)*));/';
        // $reStylePadding = '/(padding-?(left|right|top|bottom)?).*:(([ ]*((-*\d+(px|em|%|cm|in|pc|pt|mm|ex)?|auto|inherit)*))*(!important)*);/';
        // The content builder editor is modyfing the wrapper div .column class e.q. for padding, margins etc...
        // this is causing issues when the column is rendered via PDF, the column size is not properly calculated
        // in this case, we will transfer the .column div styles and classes that includes padding in a child wrapper div

        foreach ($dom->find('.column') as $element) {
            $wrapperClasses = '';
            $wrapperStyle = '';

            // First, we will check if any classes needs to be transfered to the wrapper div
            if ($classes = $element->getAttribute('class')) {

                $classes = array_map('trim', explode(' ', $classes));

                foreach ($classes as $class) {
                    if (Str::startsWith($class, ['padding-', 'p-'])) {
                        $element->removeClass($class);
                        $wrapperClasses .= $class.' ';
                    }
                }
            }

            // Next, we will check in inline styles to be fixed and transfered to the wrapper div
            if ($style = $element->getAttribute('style')) {
                // Next, we will transfer the inline styles to the wrapper div
                preg_match_all($reStylePadding, $style, $inlinePaddingMatches, PREG_SET_ORDER, 0);

                foreach ($inlinePaddingMatches as $stylePaddingMatch) {
                    $wrapperStyle .= $stylePaddingMatch[0].' ';
                    $element->setAttribute('style', str_replace($stylePaddingMatch[0], '', $element->getAttribute('style')));
                }
            }

            [$wrapperClasses, $wrapperStyle] = array_map('trim', [$wrapperClasses, $wrapperStyle]);

            $element->innertext = "<div class='$wrapperClasses column-inner' style='$wrapperStyle'>$element->innertext</div>";
        }

        return $dom->save();
    }

    /**
     * Ensure that all the columns are with width.
     *
     * @param  \simple_html_dom  $dom
     */
    protected function ensureAllColumnsAreWithWidth($dom): void
    {
        $inlineWidthStyleRegex = '/width:\s?([0-9.]+)%;?\s?/';

        // Prepare columns width for PDF, flexbox to float, supports old editor classes as well.
        foreach ($dom->find('.row') as $row) {
            $columns = $row->find('.column');
            $totalColumns = count($columns);

            if ($totalColumns <= 1) {
                continue;
            }

            $parsedColumns = collect();
            $allColumnsAreWithoutWidth = true;

            foreach ($columns as $column) {
                $parsedColumns->push([
                    'instance' => $column,
                    'style' => $style = rtrim($column->getAttribute('style') ?: '', ';'),
                    'hasWidth' => $hasWidth = str_contains($style, 'width'),
                ]);

                if ($hasWidth) {
                    $allColumnsAreWithoutWidth = false;
                }
            }

            // When all columns are without width, we will add equal width to all of them
            // so the PDF layout is consistent and they don't go out of the PDF document.
            if ($allColumnsAreWithoutWidth) {
                $parsedColumns->each(function (array $column) use ($totalColumns) {
                    $width = round(100 / $totalColumns);

                    $style = $column['style'];

                    if ($style) {
                        $style = rtrim($style, ';').';';
                    }

                    $column['instance']->setAttribute('style', $style.'width:'.$width.'%;');
                });

                continue;
            }

            $columnsWithWidth = $parsedColumns->filter(fn ($column) => $column['hasWidth']);
            $columnsWithoutWidth = $parsedColumns->reject(fn ($column) => $column['hasWidth']);
            $totalWidthInPercent = 0;

            // First, we will calculate the total used width in percent, so we can calculate the
            // remaining columns width that are left without width, this will ensure same width as flex provides.
            foreach ($columnsWithWidth as $column) {
                preg_match($inlineWidthStyleRegex, $column['style'], $matches);

                if ($matches) {
                    // It may happen the column to have 100% width, but other columns are with
                    // different width, but flex makes them properly aligned, in this case, we will
                    // cast this column as without with and later it's actual width will be calculated below.
                    if ($matches[1] != 100) {
                        $totalWidthInPercent += floatval($matches[1]);
                    } else {
                        $columnsWithoutWidth->push($column);
                    }
                }
            }

            foreach ($columnsWithoutWidth as $column) {
                // Remove width on columns with 100% width.
                $style = $column['style'];

                $style = preg_replace($inlineWidthStyleRegex, '', $style);

                if ($style) {
                    $style = rtrim($style, ';').';';
                }

                $percent = round((100 - $totalWidthInPercent) / count($columnsWithoutWidth));

                $column['instance']->setAttribute('style', $style.'width:'.$percent.'%;');
            }
        }
    }

    /**
     * Prepare the given html content for PDF
     */
    protected function prepareHtmlForPdf(string $html): static
    {
        $dom = $this->newDomInstance(
            $this->wrapColumnContentInDiv($html)
        );

        foreach ($dom->find('img,source') as $element) {
            if ($src = $element->getAttribute('src')) {
                if ($realPath = $this->convertUrlToAbsolutePath($src)) {
                    $element->setAttribute('src', $realPath);
                }
            }
        }

        return static::make(
            $this->convertInlineBackgroundImagesToAbsolutePath($dom->save()),
            $this->model
        );
    }

    /**
     * Get the document navigation from the content
     */
    public function navigation(): array
    {
        $headings = [];

        $dom = $this->dom();

        if (! $dom) {
            return $headings;
        }

        foreach ($dom->find(static::NAVIGATION_HEADING_TAG_NAME) as $element) {
            $id = ($element->getAttribute('id') ?: Str::slug($element->plaintext));
            $name = $element->getAttribute('data-name') ?: Str::title($element->plaintext);

            $headings[] = [
                'id' => $id,
                'href' => '#'.$id,
                'name' => $name,
            ];
        }

        return $headings;
    }

    protected function convertInlineBackgroundImagesToAbsolutePath($content)
    {
        $bgImageMediaRegex = '/background\-image:(?: {1,}|)url(?: {1,}|)\([\'|"](.*)[\'|"]\)/';

        preg_match_all($bgImageMediaRegex, html_entity_decode($content, ENT_QUOTES), $matches, PREG_SET_ORDER, 0);

        foreach ($matches as $match) {
            if ($realPath = $this->convertUrlToAbsolutePath($match[1])) {
                $content = str_replace($match[1], $realPath, $content);
            }
        }

        return $content;
    }

    /**
     * Convert the given URL to local real path
     *
     * When possible use local path instead of url if the source is from the installation files
     * Will help resolve allow_url_fopen issues
     *
     * @link https://stackoverflow.com/questions/15153139/dompdf-remote-image-is-not-displaying-in-pdf
     */
    protected function convertUrlToAbsolutePath(string $src): ?string
    {
        // Absolute but from installation URL?
        $appUrl = config('app.url');

        if (str_starts_with($src, $appUrl)) {
            $path = rtrim(Str::after($src, $appUrl), '/');

            if (file_exists(public_path($path))) {
                return public_path($path);
            }
        }

        // Relative src?
        if (! str_starts_with($src, 'http') && file_exists(public_path($src))) {
            return public_path($src);
        }

        // Check if is really media by checking the uuid in the image or video src
        $uuidRegexMatch = '/[\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12}/m';
        preg_match($uuidRegexMatch, $src, $matches);

        if (count($matches) === 1) {
            if ($media = Media::byToken($matches[0])->first()) {
                if ($media->disk === 'local') {
                    return $media->getAbsolutePath();
                }

                return $media->getPreviewUrl();
            }
        }

        return null;
    }

    /**
     * Get the document content
     */
    public function html(): string
    {
        return $this->value ?? '';
    }

    /**
     * Get the products html
     */
    protected function productsHtml(): string
    {
        return view('billable::products.table')->with([
            'billable' => $this->model->billable,
        ])->render();
    }

    /**
     * Get the products html
     *
     * @return string
     */
    protected function signaturesHtml()
    {
        return view('documents::signatures')->with([
            'document' => $this->model,
        ])->render();
    }

    /**
     * Get all the used Google fonts in the document content
     *
     * @return \Illuminate\Support\Collection
     */
    public function usedGoogleFonts()
    {
        return (new FontsExtractor(
            $this->model->pdfFont()
        ))->extractGoogleFonts($this->html());
    }

    /**
     * Get dom instance
     *
     * @return \simple_html_dom|false
     */
    protected function dom()
    {
        return $this->dom ??= $this->newDomInstance($this->value);
    }

    /**
     * Create new dom instance
     *
     * @return \simple_html_dom|false
     */
    protected function newDomInstance($value)
    {
        return HtmlDomParser::str_get_html($value);
    }

    /**
     * __toString
     */
    public function __toString(): string
    {
        return $this->html();
    }
}
