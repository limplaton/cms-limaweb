<?php
 

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Core\App\Updater\UpdatePatcher;

return new class extends UpdatePatcher
{
    public function run(): void
    {
        if ($this->missingAddressIndex()) {
            Schema::table('companies', function (Blueprint $table) {
                $table->index(['street', 'city', 'state', 'postal_code', 'country_id']);
            });
        }
    }

    public function shouldRun(): bool
    {
        return $this->missingAddressIndex();
    }

    protected function missingAddressIndex(): bool
    {
        $indexes = $this->getColumnIndexes('companies', 'street');

        if (count($indexes) === 0) {
            return true;
        }

        foreach ($indexes as $index) {
            if (str_contains($index['name'], 'street_city_state_postal_code_country_id')) {
                return false;
            }
        }

        return true;
    }
};
