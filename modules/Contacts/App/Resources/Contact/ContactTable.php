<?php
 

namespace Modules\Contacts\App\Resources\Contact;

use Modules\Contacts\App\Models\Contact;
use Modules\Core\App\Table\Table;

class ContactTable extends Table
{
    /**
     * Whether the table has actions column.
     */
    public bool $withActionsColumn = true;

    /**
     * Indicates whether the user can customize columns orders and visibility
     */
    public bool $customizeable = true;

    /**
     * Prepare the searchable columns for the model from the table defined columns.
     */
    public function prepareSearchableColumns(): array
    {
        return array_merge(
            parent::prepareSearchableColumns(),
            ['full_name' => [
                'column' => Contact::nameQueryExpression(),
                'condition' => 'like',
            ]],
        );
    }

    /**
     * Boot table
     */
    public function boot(): void
    {
        $this->orderBy('created_at', 'desc');
    }
}
