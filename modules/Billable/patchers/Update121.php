<?php
 

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Billable\App\Models\BillableProduct;
use Modules\Core\App\Updater\UpdatePatcher;

return new class extends UpdatePatcher
{
    public function run(): void
    {
        if ($this->missingAmmountTaxExclColumn()) {
            Schema::table('billable_products', function (Blueprint $table) {
                if ($oldAmountIndexName = $this->getBillableProductsTableAmountIndexName()) {
                    $table->dropIndex($oldAmountIndexName);
                }

                $table->renameColumn('amount', 'amount_tax_exl');
                $table->index('amount_tax_exl');
            });

            Schema::table('billable_products', function (Blueprint $table) {
                $table->after('discount_total', function (Blueprint $table) {
                    $table->decimal('amount', 15, 3)->index()->default(0);
                });
            });

            BillableProduct::chunkById(250, function ($products) {
                foreach ($products as $product) {
                    $product->amount = $product->calculateAmount();
                    $product->amount_tax_exl = $product->calculateAmountBeforeTax();
                    $product->saveQuietly();
                }
            });
        }
    }

    public function shouldRun(): bool
    {
        return $this->missingAmmountTaxExclColumn();
    }

    protected function missingAmmountTaxExclColumn(): bool
    {
        return ! Schema::hasColumn('billable_products', 'amount_tax_exl');
    }

    protected function getBillableProductsTableAmountIndexName()
    {
        foreach ($this->getBillableProductAmountIndexes() as $index) {
            if (str_ends_with($index['name'], 'amount_index') && $index['type'] == 'btree') {
                return $index['name'];
            }
        }
    }

    protected function getBillableProductAmountIndexes()
    {
        return $this->getColumnIndexes('billable_products', 'amount');
    }
};
