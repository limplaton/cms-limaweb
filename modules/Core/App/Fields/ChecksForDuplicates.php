<?php
 

namespace Modules\Core\App\Fields;

trait ChecksForDuplicates
{
    /**
     * Add duplicates checker data
     */
    public function checkPossibleDuplicatesWith(string $url, array $params, string $langKey): static
    {
        $this->withMeta([
            'checkDuplicatesWith' => [
                'url' => $url,
                'params' => $params,
                'lang_keypath' => $langKey,
            ],
        ]);

        return $this;
    }

    /**
     * Disable the duplicate checks for the field.
     */
    public function disableDuplicateChecks(): static
    {
        unset($this->meta['checkDuplicatesWith']);

        return $this;
    }
}
