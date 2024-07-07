<?php
 

namespace Modules\Core\App\Models;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\App\Common\Media\HasMedia;
use Modules\Core\App\Common\Placeholders\Placeholders as BasePlaceholders;
use Modules\Core\App\Resource\ResourcePlaceholders;

class MailableTemplate extends CacheModel
{
    use HasMedia;

    /**
     * Indicates if the model has timestamps
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'subject', 'html_template', 'text_template', 'locale',
    ];

    /**
     * Get the mail template mailable class
     *
     * @return \Modules\Core\App\MailableTemplate\MailableTemplate
     */
    public function mailable()
    {
        return resolve($this->mailable);
    }

    /**
     * Get mailable template HTMl layout
     */
    public function getHtmlLayout(): ?string
    {
        return null;
    }

    /**
     * Get mailable template text layout.
     */
    public function getTextLayout(): ?string
    {
        return null;
    }

    /**
     * Get the mailable template placeholders.
     */
    public function getPlaceholders(): ResourcePlaceholders|BasePlaceholders
    {
        if (! class_exists($this->mailable)) {
            return new BasePlaceholders([]);
        }

        $reflection = new \ReflectionClass($this->mailable);

        /** @var \Modules\Core\App\MailableTemplate\MailableTemplate */
        $mailable = $reflection->newInstanceWithoutConstructor();

        return $mailable->placeholders() ?: new BasePlaceholders([]);
    }

    /**
     * Get mailable template subject.
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Get mailable template HTML.
     *
     * @return string
     */
    public function getHtmlTemplate()
    {
        return $this->html_template;
    }

    /**
     * Get mailable template TEXT.
     *
     * @return string
     */
    public function getTextTemplate()
    {
        return $this->text_template;
    }

    /**
     * Get the attributes that may contain pending media.
     */
    public function textAttributesWithMedia(): string
    {
        return 'html_template';
    }

    /**
     * Scope a query to only include templates of a given locale.
     */
    public function scopeForLocale(Builder $query, string $locale, ?string $mailable = null): void
    {
        $query->where('locale', $locale);

        if ($mailable) {
            $query->forMailable($mailable);
        }
    }

    /**
     * Scope a query to only include templates of a given mailable.
     */
    public function scopeForMailable(Builder $query, string $mailable): void
    {
        $query->where('mailable', $mailable);
    }
}
