<?php
 

namespace Modules\Documents\App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use Modules\Core\App\Models\CacheModel;
use Modules\Core\App\Resource\Resourceable;
use Modules\Documents\App\Content\FontsExtractor;
use Modules\Documents\App\Enums\DocumentViewType;
use Modules\Documents\Database\Factories\DocumentTemplateFactory;

class DocumentTemplate extends CacheModel
{
    use HasFactory,
        Resourceable;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_shared' => 'bool',
        'user_id' => 'int',
        'view_type' => DocumentViewType::class,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name', 'content', 'view_type', 'is_shared',
    ];

    /**
     * Perform any actions required after the model boots.
     */
    protected static function booted(): void
    {
        static::creating(function (DocumentTemplate $model) {
            $model->user_id = $model->user_id ?? Auth::id();
        });
    }

    /**
     * Get the template owner
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\Modules\Users\App\Models\User::class);
    }

    /**
     * Scope a query to only include shared templates.
     */
    public function scopeShared(Builder $query): void
    {
        $query->where('is_shared', true);
    }

    /**
     * Get all of the used Google fonts in the template content
     */
    public function usedGoogleFonts(): Collection
    {
        return (new FontsExtractor())->extractGoogleFonts($this->content ?: '');
    }

    /**
     * Clone the template.
     */
    public function clone(int $userId): DocumentTemplate
    {
        $newTemplate = $this->replicate();

        $newTemplate->name = clone_prefix($this->name);

        $newTemplate->user_id = $userId;
        $newTemplate->is_shared = false;

        $newTemplate->save();

        return $newTemplate;
    }

    /**
     * Name attribute accessor
     *
     * Supports translation from language file
     */
    public function name(): Attribute
    {
        return Attribute::get(function (string $value, array $attributes) {
            if (! array_key_exists('id', $attributes)) {
                return $value;
            }

            $customKey = 'custom.document_template.'.$attributes['id'];

            if (Lang::has($customKey)) {
                return __($customKey);
            } elseif (Lang::has($value)) {
                return __($value);
            }

            return $value;
        });
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): DocumentTemplateFactory
    {
        return DocumentTemplateFactory::new();
    }
}
