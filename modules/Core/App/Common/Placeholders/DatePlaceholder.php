<?php
 

namespace Modules\Core\App\Common\Placeholders;

use Modules\Core\App\Support\Carbon;

class DatePlaceholder extends Placeholder
{
    /**
     * The user the date is intended for
     *
     * @var null|\Modules\Users\App\Models\User
     */
    protected $user;

    /**
     * Custom formatter callback
     *
     * @var null|callable
     */
    protected $formatCallback;

    /**
     * Format the placeholder
     *
     * @return string
     */
    public function format(?string $contentType = null)
    {
        if (is_callable($this->formatCallback)) {
            return call_user_func_array($this->formatCallback, [$this->value, $this->user]);
        }

        return $this->value ? Carbon::parse($this->value)->formatDateForUser($this->user) : '';
    }

    /**
     * Add custom format callback
     */
    public function formatUsing(callable $callback): static
    {
        $this->formatCallback = $callback;

        return $this;
    }

    /**
     * The user the date is intended for
     *
     * @param  \Modules\Users\App\Models\User  $user
     */
    public function forUser($user): static
    {
        $this->user = $user;

        return $this;
    }
}
