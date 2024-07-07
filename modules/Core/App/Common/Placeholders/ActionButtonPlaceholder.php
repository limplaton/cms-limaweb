<?php
 

namespace Modules\Core\App\Common\Placeholders;

class ActionButtonPlaceholder extends UrlPlaceholder
{
    /**
     * Indicates the starting interpolation
     */
    public string $interpolationStart = '{{{';

    /**
     * Indicates the ending interpolation
     */
    public string $interpolationEnd = '}}}';

    /**
     * Initialize new ActionButtonPlaceholder instance.
     *
     * @param  \Closure|mixed  $value
     */
    public function __construct($value = null, string $tag = 'action_button')
    {
        parent::__construct($value, $tag);

        $this->description('Formatted action button.');
    }

    /**
     * Format the placeholder
     *
     * @return \Closure
     */
    public function format(?string $contentType = null)
    {
        // $text and $mustache are empty because of versions compatibility
        // previous this was {{{ action_button }}} only now it's {{#action_button}}Text{{/action_button}}
        return function ($text = '', $mustache = null) use ($contentType) {
            if ($contentType === 'text') {
                return parent::format();
            }

            return view('core::mail.action', [
                'url' => parent::format(),
                'text' => $text ?: __('core::mail_template.placeholders.view_record'),
            ])->render();
        };
    }
}
