<?php
 

namespace Modules\Core\App\Common\Placeholders;

class PrivacyPolicyPlaceholder extends UrlPlaceholder
{
    /**
     * Initialize new PrivacyPolicyPlaceholder instance.
     */
    public function __construct(string $tag = 'privacy_policy')
    {
        parent::__construct(null, $tag);

        $this->description(__('core::app.privacy_policy'));
    }

    /**
     * Format the placeholder
     *
     * @return string
     */
    public function format(?string $contentType = null)
    {
        return privacy_url();
    }
}
