<?php
 

namespace Modules\Users\App\Placeholders;

use Modules\Core\App\Common\Placeholders\Placeholder;

class UserPlaceholder extends Placeholder
{
    /**
     * Initialize new UserPlaceholder instance.
     *
     * @param  \Closure|mixed  $value
     */
    public function __construct($value = null, string $tag = 'user')
    {
        parent::__construct($tag, $value);

        $this->description(__('users::user.user'));
    }

    /**
     * Format the placeholder
     *
     * @return string
     */
    public function format(?string $contentType = null)
    {
        return is_a($this->value, \Modules\Users\App\Models\User::class) ?
            $this->value->name :
            $this->value;
    }
}
