<?php
 

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Core\App\Updater\UpdatePatcher;

return new class extends UpdatePatcher
{
    public function run(): void
    {
        if (is_null($this->getDealsTableNameIndexName())) {
            Schema::table('deals', function (Blueprint $table) {
                $table->index('name');
            });
        }
    }

    public function shouldRun(): bool
    {
        return is_null($this->getDealsTableNameIndexName());
    }

    protected function getDealsTableNameIndexName()
    {
        foreach ($this->getDealsTableNameIndexes() as $index) {
            if (str_ends_with($index['name'], 'name_index') && $index['type'] == 'btree') {
                return $index['name'];
            }
        }
    }

    protected function getDealsTableNameIndexes()
    {
        return $this->getColumnIndexes('deals', 'name');
    }
};
