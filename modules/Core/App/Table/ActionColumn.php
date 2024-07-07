<?php
 

namespace Modules\Core\App\Table;

class ActionColumn extends Column
{
    public bool $sortable = false;

    public bool $customizeable = false;

    public string $attribute = 'actions';

    public ?string $label = null;

    public ?string $minWidth = '48px';

    public string $width = '48px';

    /**
     * Initialize new ActionColumn instance.
     */
    public function __construct()
    {
        $this->centered();
    }
}
