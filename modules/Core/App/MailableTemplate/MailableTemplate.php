<?php
 

namespace Modules\Core\App\MailableTemplate;

use Illuminate\Mail\Mailable;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Modules\Core\App\Common\Placeholders\Placeholders as BasePlaceholders;
use Modules\Core\App\Models\MailableTemplate as MailableTemplateModel;
use Modules\Core\App\Resource\ResourcePlaceholders;
use Modules\Core\App\Support\Html2Text;

abstract class MailableTemplate extends Mailable
{
    /**
     * Holds the template model.
     */
    protected ?MailableTemplateModel $templateModel = null;

    /**
     * Provides the default mail template content to be is used when seeding the templates.
     */
    abstract public static function default(): DefaultMailable;

    /**
     * Get the mailable human readable name.
     */
    public static function name(): string
    {
        return Str::title(Str::snake(class_basename(get_called_class()), ' '));
    }

    /**
     * Build the view for the message.
     *
     * @return array
     */
    protected function buildView()
    {
        if (! $model = $this->getMailableTemplate()) {
            // Usually this may happen in tests where templates are not needed.
            return ['text' => new HtmlString('Template does not exists')];
        }

        $renderer = $this->getMailableTemplateRenderer($model);

        return array_filter([
            'html' => new HtmlString($renderer->renderHtmlLayout()),
            'text' => new HtmlString($renderer->renderTextLayout()),
        ]);
    }

    /**
     * Build the view data for the message.
     *
     * @return array
     */
    public function buildViewData()
    {
        return $this->placeholders()?->parse() ?: parent::buildViewData();
    }

    /**
     * Build the subject for the message.
     *
     * @param  \Illuminate\Mail\Message|\Modules\MailClient\App\Client\Client  $buildable
     * @return static
     */
    protected function buildSubject($buildable)
    {
        if ($model = $this->getMailableTemplate()) {
            $buildable->subject($this->getMailableTemplateRenderer($model)->renderSubject());
        } else {
            $buildable->subject('Template does not exists');
        }

        return $this;
    }

    /**
     * Get the mailable template subject.
     *
     * @return string|null
     */
    protected function getMailableTemplateSubject()
    {
        return $this->subject ?? $this->getMailableTemplate()->getSubject() ?? $this->name();
    }

    /**
     * Get the mailable template model.
     *
     * @return \Modules\Core\App\Models\MailableTemplate
     */
    public function getMailableTemplate()
    {
        if (! $this->templateModel) {
            $locale = $this->locale ?? config('app.fallback_locale');

            $this->templateModel = MailableTemplateModel::forLocale($locale, static::class)->first();

            if (! $this->templateModel) {
                $this->templateModel = MailableTemplateModel::forLocale(
                    config('app.fallback_locale'),
                    static::class
                )->first();
            }
        }

        return $this->templateModel;
    }

    /**
     * Creates alternative text message from the given HTML.
     *
     * @param  string  $html
     * @return string
     */
    protected static function altMessageFromHtml($html)
    {
        return Html2Text::convert($html);
    }

    /**
     * Get the mail template content rendered.
     */
    protected function getMailableTemplateRenderer(MailableTemplateModel $template): Renderer
    {
        return app(Renderer::class, [
            'htmlTemplate' => $template->getHtmlTemplate(),
            'subject' => $this->getMailableTemplateSubject(),
            'placeholders' => $this->placeholders(),
            'htmlLayout' => $this->getHtmlLayout(),
            'textTemplate' => $template->getTextTemplate() ?: static::altMessageFromHtml($template->getHtmlTemplate()),
            'textLayout' => $this->getTextLayout(),
        ]);
    }

    /**
     * Get the mailable HTML layout.
     *
     * @return string|null
     */
    public function getHtmlLayout()
    {
        $default = config('core.mailables.layout');

        if (file_exists($default)) {
            return file_get_contents($default);
        }
    }

    /**
     * Get the mailable text layout.
     *
     * @return string|null
     */
    public function getTextLayout()
    {
        //
    }

    /**
     * Provide the defined mailable template placeholders.
     */
    public function placeholders(): ResourcePlaceholders|BasePlaceholders|null
    {
        return null;
    }

    /**
     * The Mailable build method.
     *
     * @see  buildSubject, buildView, send
     *
     * @return static
     */
    public function build()
    {
        return $this;
    }

    /**
     * Seed the mailable in database as mail template.
     *
     * @param  string  $locale
     * @return \Modules\Core\App\Models\MailableTemplate
     */
    public static function seed($locale = 'en')
    {
        $default = static::default();
        $mailable = get_called_class();

        $template = MailableTemplateModel::firstOrNew([
            'locale' => $locale,
            'mailable' => $mailable,
        ],
            [
                'locale' => $locale,
                'subject' => $default->subject(),
                'html_template' => $default->htmlMessage(),
                'text_template' => $default->textMessage(),
            ]);

        if (! $template->exists) {
            $template->forceFill([
                'mailable' => $mailable,
                'name' => static::name(),
            ])->save();
        }

        return $template;
    }
}
