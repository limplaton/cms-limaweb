<?php
 

namespace Modules\Translator\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\Core\App\Rules\SupportedLocaleRule;
use Modules\Translator\App\Translator;

class TranslationController extends ApiController
{
    /**
     * Initialize new TranslationController instance.
     */
    public function __construct(protected Translator $translator)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(string $locale): JsonResponse
    {
        $source = $this->translator->source($locale);
        $current = $this->translator->current($locale);

        return $this->response([
            'source' => [
                'groups' => $this->toDot($source['groups']),
                'namespaces' => collect($source['namespaces'])->mapWithKeys(function ($translations, $namespace) {
                    return [$namespace => $this->toDot($translations)];
                }),
            ],
            'current' => [
                'groups' => $this->toDot($current['groups']),
                'namespaces' => collect($current['namespaces'])->mapWithKeys(function ($translations, $namespace) {
                    return [$namespace => $this->toDot($translations)];
                }),
            ],
        ]);
    }

    /**
     * Create new language locale.
     */
    public function store(Request $request, Translator $translator): JsonResponse
    {
        $payload = $request->validate([
            'name' => [
                'required',
                'string',
                new SupportedLocaleRule,
                Rule::notIn(Innoclapps::locales()),
            ],
        ]);

        $created = $translator->createLocale($payload['name'], $request->boolean('namespaces', false));

        return $this->response($created ? [
            'locale' => $payload['name'],
        ] : ['message' => 'Failed to create new locale.'], $created ? JsonResponse::HTTP_CREATED : 500);
    }

    /**
     * Update locale group translations.
     */
    public function update(string $locale, string $group, Request $request): JsonResponse
    {
        // Save translations flag

        $this->translator->save(
            $locale,
            $group,
            $this->fixDottedKeysContainsDot($request->input('translations')),
            $request->input('namespace')
        );

        return $this->response('', JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Convert translation to dot notation
     *
     * @return \Illuminate\Support\Collection
     */
    protected function toDot(array $data)
    {
        return collect($data)->mapWithKeys(fn ($translations, $group) => [$group => Arr::dot($translations)]);
    }

    protected function replaceDotFlagsWithOriginalDots(array $arr): array
    {
        foreach ($arr as $key => $translation) {
            if (preg_match('/@(\d)@/', $key)) {
                unset($arr[$key]);

                $key = preg_replace_callback('/@(\d)@/', function ($matches) {
                    return str_repeat('.', (int) $matches[1]);
                }, $key);

                $arr[$key] = $translation;
            }

            if (is_array($translation)) {
                $arr[$key] = $this->replaceDotFlagsWithOriginalDots($translation);
            }
        }

        return $arr;
    }

    protected function fixDottedKeysContainsDot(array $dotted): array
    {
        // Find all keys that ends with or contains dot
        // Replace the dots with temporary flag
        // Undot them
        // Replace the flags with the original dots
        foreach ($dotted as $key => $translation) {
            if (str_ends_with($key, '.')) {
                $newTmpKey = preg_replace_callback('/\.+(?!.*\.)/', function ($matches) {
                    return '@'.strlen($matches[0]).'@';
                }, $key);

                if (str_contains($newTmpKey, '. ')) {
                    $newTmpKey = str_replace('. ', '@1@ ', $newTmpKey);
                }

                unset($dotted[$key]);
                $dotted[$newTmpKey] = $translation;
            } elseif (str_contains($key, '. ')) {
                $newTmpKey = str_replace('. ', '@1@ ', $key);
                unset($dotted[$key]);
                $dotted[$newTmpKey] = $translation;
            }
        }

        return $this->replaceDotFlagsWithOriginalDots(Arr::undot($dotted));
    }
}
