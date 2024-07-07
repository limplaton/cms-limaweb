<?php
 

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Core\App\Updater\UpdatePatcher;

return new class extends UpdatePatcher
{
    public function run(): void
    {
        if (! $this->getPhonesTableNumberColumnIndexName()) {
            Schema::table('phones', function (Blueprint $table) {
                $table->index('number');
            });
        }
    }

    public function shouldRun(): bool
    {
        return ! $this->getPhonesTableNumberColumnIndexName();
    }

    protected function getPhonesTableNumberColumnIndexName()
    {
        foreach ($this->getColumnIndexes('phones', 'number') as $index) {
            if (str_ends_with($index['name'], 'number_index') && $index['type'] == 'btree') {
                return $index['name'];
            }
        }
    }
};
