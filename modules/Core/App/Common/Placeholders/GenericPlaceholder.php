<?php
 

namespace Modules\Core\App\Common\Placeholders;

use Exception;
use Modules\Core\App\Contracts\Presentable;

class GenericPlaceholder extends Placeholder
{
    /**
     * Format the placeholder
     *
     * @return string
     */
    public function format(?string $contentType = null)
    {
        if ($this->value instanceof Presentable) {
            return $this->value->displayName();
        }

        return $this->value;
    }

    /**
     * Serialize the placeholder for the front end
     */
    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();

        if (! $data['tag']) {
            throw new Exception('"tag" not provided for generic placeholder.');
        }

        return $data;
    }
}
