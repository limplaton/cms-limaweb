<?php
 

namespace Modules\Documents\App\Content;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use KubAT\PhpSimple\HtmlDomParser;

class FontsExtractor
{
    /**
     * Initialize new FontsExtractor instance.
     */
    public function __construct(protected array $extra = [])
    {
    }

    /**
     * Extract all the used Google fonts from the given html
     */
    public function extractGoogleFonts(string $html): Collection
    {
        $fonts = $this->extract($html, 'google');

        if ($fonts->isEmpty()) {
            return $fonts;
        }

        $dom = $this->newDomInstance($html);

        return $fonts->map(function ($font) use ($dom) {
            $elementsWithInlineFont = $dom->find('[style*="'.$font['name'].'"]');

            // When count is 0, only default font styles are used only
            $styles = $this->findUsedFontStylesForGoogleFont(
                count($elementsWithInlineFont) > 0 ? $elementsWithInlineFont : $dom->childNodes(),
            );

            $font['stylesQueryString'] = $this->createGoogleFontStylesQueryString($styles);

            return $font;
        });
    }

    /**
     * Get all the used fonts in the document content
     *
     * We need to include all the Google fonts manually on every view
     * where the content from the editor is display.
     *
     * The clean() function removes the <link> element that the editor is creating when
     * a font is selected, we can allow the link if we want via the purifier config
     * but this behavior is totally fine as the link should be removed because of security reasons
     *
     * The more bigger problem is the font styles that are used in the editor, for example
     * Bold, Italic, Normal, Bold Italic etc...
     *
     * We need to parse the styles manually and create new Google fonts list to embed them manually
     * If we don't do this and for example, user add Bold Italic with font Poppins and Polish characters
     * the "Bold 700 Italic" font won't be loaded and the special characters won't be displayed properly on PDF
     */
    public function extract(string $html, ?string $provider = null): Collection
    {
        if ($html === '') {
            return collect();
        }

        $dom = $this->newDomInstance($html);
        $fonts = $this->getFontsFromConfig();

        return collect($dom->find('[style*="font-family"]'))
            ->map(fn ($node) => $this->extractInlineStylePropertyValue('font-family', $node->getAttribute('style')))
            ->filter()
            ->unique()
            ->map(fn ($family) => $this->cleanUpFontName($family))
            ->map(function ($family) use ($fonts) {
                $font = $fonts[$family] ?? null;

                return array_merge($font ?? [], [
                    'name' => $font ? trim(explode(',', $family)[0]) : null,
                ]);
            })
            ->when(
                array_is_list($this->extra),
                fn ($collection) => $collection->merge($this->extra),
                fn ($collection) => $collection->push($this->extra)
            )
            ->reject(fn ($font) => empty($font['name']))
            ->unique('name')
            ->when(
                ! is_null($provider),
                fn ($collection) => $collection->filter(fn ($font) => $font['provider'] === $provider)
            )->values();
    }

    /**
     * Get the fonts from config prepared
     */
    public function getFontsFromConfig(): Collection
    {
        return collect(config('contentbuilder.fonts'))->mapWithKeys(function ($font) {
            $family = $this->cleanUpFontName($font['font-family']);

            return [$family => array_merge($font, ['font-family' => $family])];
        });
    }

    /**
     * Clean up the given font family name
     * If the font contains quotes e.q. 'Exo 2', serif
     */
    public function cleanUpFontName(string $name): string
    {
        return str_replace(['"', '\''], '', html_entity_decode($name));
    }

    /**
     * Create Google fonts styles query string
     */
    protected function createGoogleFontStylesQueryString(array $styles): string
    {
        $wghtParsed = implode(';', $styles['wght']);
        $italParsed = implode(';', array_map(fn ($weight) => '1,'.$weight, $styles['ital']));

        if ($wghtParsed && ! $italParsed) {
            // Only regular weight
            $stylesString = ':wght@'.$wghtParsed;
        } elseif ($italParsed && ! $wghtParsed) {
            // Only italic weight
            $stylesString = ':ital,wght@'.$italParsed;
        } elseif ($wghtParsed && $italParsed) {
            // With italic and with regular styles
            $wghtParsed = implode(';', array_map(fn ($weight) => '0,'.$weight, $styles['wght']));

            $stylesString = ':ital,wght@'.$wghtParsed.';'.$italParsed;
        }

        return $stylesString ?? '';
    }

    /**
     * Find used from styles from element
     *
     * @param  \simple_html_dom\simple_html_dom_node[]  $elements
     */
    protected function findUsedFontStylesForGoogleFont($elements): array
    {
        $styles = ['ital' => [], 'wght' => []];

        foreach ($elements as $element) {
            $this->parseFontStylesByElement($element, $styles);
        }

        $styles['ital'] = array_unique($styles['ital']);
        $styles['wght'] = array_unique($styles['wght']);

        // Google requires to be properly sorted
        sort($styles['ital']);
        sort($styles['wght']);

        return $styles;
    }

    /**
     * Find font styles for Google fonts
     *
     * @param  \simple_html_dom\simple_html_dom_node  $element
     * @return void
     */
    protected function parseFontStylesByElement($element, array &$styles)
    {
        $weight = $this->getElementFontWeight($element);

        if ($this->elementIsItalicOrHasChildItalicElements($element)) {
            $styles['ital'][] = $weight;
        } else {
            $styles['wght'][] = $weight;
        }

        foreach ($element->childNodes() as $child) {
            $this->parseFontStylesByElement($child, $styles);
        }
    }

    /**
     * Get the given DOM element font weight
     *
     * @param  \simple_html_dom\simple_html_dom_node  $element
     */
    protected function getElementFontWeight($element): int
    {
        $elementStyle = $element->getAttribute('style') ?: '';
        $elementClass = $element->getAttribute('class') ?: '';

        // First inline styles
        if ($weight = $this->extractInlineStylePropertyValue('font-weight', $elementStyle)) {
            return $this->determineFontWeightFromInlineStyle($weight, $element->parentNode());
        }

        // Classes
        $weightClassMap = $this->getFontWeightByClassMap();
        if (Str::contains($elementClass, array_keys($weightClassMap))) {
            foreach ($weightClassMap as $class => $weight) {
                if (Str::contains($elementClass, $class)) {
                    return $weight;
                }
            }
        }

        if ($element->tag === 'b' || $element->tag === 'strong') {
            return 700;
        }

        return 400; // normal
    }

    /**
     * Check if the given dom element is italic or has italic child elements
     *
     * @param  \simple_html_dom\simple_html_dom_node  $element
     */
    protected function elementIsItalicOrHasChildItalicElements($element): bool
    {
        $style = $element->getAttribute('style') ?: '';
        $class = $element->getAttribute('class') ?: '';

        if (str_contains($style, 'italic')) {
            return true;
        }

        if (str_contains($style, 'italic') || str_contains($class, 'italic')) {
            return true;
        }

        if ($element->tag === 'i') {
            return true;
        }

        foreach ($element->childNodes() as $child) {
            if ($this->elementIsItalicOrHasChildItalicElements($child)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine the font weight from inline style
     *
     * @param  mixed  $weight
     * @param  \simple_html_dom\simple_html_dom_node  $parentElement
     */
    protected function determineFontWeightFromInlineStyle($weight, $parentElement): int
    {
        if (is_numeric($weight)) {
            return (int) $weight;
        }

        if ($weight == 'normal') {
            return 400;
        }

        if ($weight == 'bold') {
            return 700;
        }

        // https://developer.mozilla.org/en-US/docs/Web/CSS/font-weight
        // Keyword values relative to the parent
        if ($weight === 'lighter') {
            return match ($this->getElementFontWeight($parentElement)) {
                100, 200, 300, 400, 500 => 100,
                600, 700 => 400,
                800, 900 => 700,
                // fallback
                default => 400,
            };
        }

        if ($weight === 'bolder') {
            return match ($this->getElementFontWeight($parentElement)) {
                100, 200, 300 => 400,
                400, 500 => 700,
                600, 700, 800, 900 => 900,
                // fallback
                default => 400,
            };
        }

        // fallback
        return 400;
    }

    /**
     * Extract property value for the given inline style string
     * We do't use regex in order to allow non correct HTML
     *
     * e.q. style="font-family: Poppins" notice there is no ending ; in the style, it's hard to catch this with REGEX
     *
     * @param  string  $property
     * @param  string  $style
     * @return string|false
     */
    protected function extractInlineStylePropertyValue($property, $style)
    {
        $parts = explode($property.':', $style);

        if (! array_key_exists(1, $parts)) {
            return false;
        }

        return trim(Str::before(str_replace('"', '', html_entity_decode($parts[1])), ';'));
    }

    /**
     * Get mapping for font weight by class
     */
    protected function getFontWeightByClassMap(): array
    {
        return [
            'font-thin' => 100,
            'font-extralight' => 200,
            'font-light' => 300,
            'font-normal' => 400,
            'font-medium' => 500,
            'font-semibold' => 600,
            'font-bold' => 700,
            'font-extrabold' => 800,
            'display' => 800,
            'font-black' => 900,
        ];
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
}
