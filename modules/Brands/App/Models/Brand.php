<?php
 

namespace Modules\Brands\App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Modules\Brands\App\Services\BrandLogoService;
use Modules\Brands\Database\Factories\BrandFactory;
use Modules\Core\App\Common\Media\HasMedia;
use Modules\Core\App\Common\VisibilityGroup\HasVisibilityGroups;
use Modules\Core\App\Common\VisibilityGroup\RestrictsModelVisibility;
use Modules\Core\App\Concerns\HasInitialAttributes;
use Modules\Core\App\Models\CacheModel;
use Modules\Documents\App\Content\FontsExtractor;

class Brand extends CacheModel implements HasVisibilityGroups
{
    use HasFactory,
        HasInitialAttributes,
        HasMedia,
        RestrictsModelVisibility;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name', 'display_name', 'config', 'is_default',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'config' => 'array',
        'is_default' => 'boolean',
    ];

    /**
     * Perform any actions required after the model boots.
     */
    protected static function booted(): void
    {
        static::deleting(function (Brand $model) {
            $model->purge();
        });
    }

    /**
     * Get the documents that are using the brand
     */
    public function documents(): HasMany
    {
        return $this->hasMany(\Modules\Documents\App\Models\Document::class);
    }

    /**
     * Get the brand logo url when the document is viewed
     */
    public function logoViewUrl(): Attribute
    {
        return Attribute::get(
            fn () => $this->logo_view ? url('storage/'.$this->logo_view) : null
        );
    }

    /**
     * Get the brand logo url when the document is sent to mail
     */
    public function logoMailUrl(): Attribute
    {
        return Attribute::get(
            fn () => $this->logo_mail ? url('storage/'.$this->logo_mail) : null
        );
    }

    /**
     * Get the brand PDF font
     */
    public function pdfFont(): array
    {
        $fontsExtractor = new FontsExtractor;
        $family = $fontsExtractor->cleanUpFontName($this->config['pdf']['font']);
        $fonts = $fontsExtractor->getFontsFromConfig();

        $font = array_merge($fonts->where('font-family', $family)->first(), [
            'name' => trim(explode(',', $family)[0]),
        ]);

        return $font;
    }

    /**
     * Get localized config value from the brand configuration.
     */
    public function getLocalizedConfig(string $key, string $locale): ?string
    {
        $value = Arr::get($this->config, $key.'.'.$locale);

        if (! $value) {
            return Arr::get($this->config, $key.'.'.config('app.fallback_locale'));
        }

        return $value;
    }

    /**
     * Get the model initial attributes with dot notation
     */
    public static function getInitialAttributes(): array
    {
        return [
            'config.pdf.font' => 'DejaVu Sans',
            'config.pdf.size' => 'a4',
            'config.pdf.orientation' => 'portrait',
            'config.navigation.background_color' => '#f3f4f6',

            'config.document.mail_subject.en' => 'Your document is ready',
            'config.document.mail_message.en' => 'Your document is ready',
            'config.document.mail_button_text.en' => 'Read Your Document',
            'config.document.signed_mail_subject.en' => 'Thank you for signing our document.',
            'config.document.signed_mail_message.en' => 'You can view the document at any time by clicking the button below. You can also download it as a PDF to save it.',
            'config.document.signed_thankyou_message.en' => 'Thank you for signing our document. We will be in touch shortly so that we can get started.',
            'config.document.accepted_thankyou_message.en' => 'Thank you for accepting our document. We will be in touch shortly so that we can get started.',
            'config.signature.bound_text.en' => 'I, {{ signerName }}, agree to the terms of this agreement and I agree that my typed name below can be used as a digital representation of my signature to that fact',
        ];
    }

    /**
     * Eager load the relations that are required for the front end response.
     */
    public function scopeWithCommon(Builder $query): void
    {
        $query->with('visibilityGroup');
    }

    /**
     * Purge the model data.
     */
    public function purge(): void
    {
        if ($this->documents()->withTrashed()->count() > 0) {
            abort(409, __('brands::brand.delete_documents_usage_warning'));
        }

        $logoService = new BrandLogoService();

        $logoService->delete($this, 'mail');
        $logoService->delete($this, 'view');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): BrandFactory
    {
        return BrandFactory::new();
    }
}
