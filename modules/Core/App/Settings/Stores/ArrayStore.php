<?php
 

namespace Modules\Core\App\Settings\Stores;

class ArrayStore extends AbstractStore
{
    /**
     * Fire the post options to customize the store.
     */
    protected function postOptions(array $options)
    {
        // Do nothing...
    }

    /**
     * Read the data from the store.
     */
    protected function read(): array
    {
        return $this->data;
    }

    /**
     * Write the data into the store.
     */
    protected function write(array $data): void
    {
        // Nothing to do...
    }
}
