<?php
 

namespace Modules\Billable\App\Services;

use Illuminate\Support\Arr;
use Modules\Billable\App\Enums\TaxType;
use Modules\Billable\App\Models\Billable;
use Modules\Billable\App\Models\BillableProduct;
use Modules\Billable\App\Models\Product;
use Modules\Core\App\Facades\ChangeLogger;

class BillableService
{
    /**
     * Save the billable data in storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $billableable
     */
    public function save(array $data, $billableable): Billable
    {
        $billable = $this->initializeBillable($data, $billableable);

        $this->saveProducts($data['products'] ?? [], $billable);

        if (count($data['removed_products'] ?? []) > 0) {
            $this->removeProducts($data['removed_products']);
        }

        return $this->updateTotalBillableableColumn($billable, $billableable);
    }

    protected function saveProducts(array $products, Billable $billable)
    {
        foreach ($this->setProductsDefaults($products) as $line) {
            // Remove the id from the line so it won't be passed to the firstOrCreate method.
            $id = Arr::pull($line, 'id');
            // Use only the needed attributes.
            $line = Arr::only($line, BillableProduct::formAttributes());
            $line['unit_price'] = $line['unit_price'] ?: 0;

            if ($id) {
                // Product update.
                $billable->products()
                    ->find($id)
                    ->fill(array_merge(
                        $line,
                        ['product_id' => $this->ensureProductAvailability($line['name'], $line)->getKey()]
                    ))
                    ->save();
            } elseif (! isset($line['product_id'])) {
                // New product creation.
                $billable->products()->create(array_merge($line, [
                    'product_id' => $this->ensureProductAvailability($line['name'], $line)->getKey(),
                ]));
            } else {
                // Regular product selected.
                $billable->products()->create($line);
            }
        }
    }

    /**
     * Initialize billable for save.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $billableable
     */
    protected function initializeBillable(array $data, $billableable): Billable
    {
        $billable = $billableable->billable()->firstOrNew();
        $taxType = $this->determineTaxType($data, $billable->exists);

        $billable->fill(array_filter([
            'note' => $data['note'] ?? null,
            'terms' => $data['terms'] ?? null,
            'tax_type' => $taxType !== false ? $taxType : null,
        ]))->save();

        return $billable;
    }

    /**
     * Ensure that the given product name is available to be stored in billable.
     *
     * In case the product exists with a given name will use the existing product instead.
     */
    protected function ensureProductAvailability(string $name, array $values): Product
    {
        $product = Product::withTrashed()->firstOrCreate(
            ['name' => $name],
            [...$values, ...['is_active' => true]]
        );

        if ($product->trashed()) {
            $product->restore();
        }

        return $product;
    }

    /**
     * Determine the billable tax type
     *
     * @return false|\Modules\Billable\App\Enums\TaxType
     */
    protected function determineTaxType(array $data, bool $exists)
    {
        $taxType = false;

        if ($exists && isset($data['tax_type']) && ! empty($data['tax_type'])) {
            $taxType = $data['tax_type'];
        } elseif (! $exists) {
            $taxType = empty($data['tax_type'] ?? null) ? Billable::defaultTaxType() : $data['tax_type'];
        }

        if (is_string($taxType)) {
            $taxType = TaxType::find($taxType);
        }

        return $taxType;
    }

    /**
     * Set the products defaults.
     */
    protected function setProductsDefaults(array $products): array
    {
        foreach ($products as $index => $line) {
            $products[$index] = array_merge($line, [
                'display_order' => $line['display_order'] ?? $index + 1,
                'discount_type' => $line['discount_type'] ?? BillableProduct::defaultDiscountType(),
                'tax_label' => $line['tax_label'] ?? BillableProduct::defaultTaxLabel(),
                'tax_rate' => $line['tax_rate'] ?? BillableProduct::defaultTaxRate(),
            ]);

            // When the product name is not set and the product_id exists
            // we will use the name from the actual product_id, useful when creating products via Zapier
            if (isset($line['product_id']) && ! isset($line['name'])) {
                $products[$index]['name'] = Product::find($line['product_id'])->name;
            }
        }

        return $products;
    }

    /**
     * Remove the given products id's from the products billable.
     */
    public function removeProducts(array $products): void
    {
        BillableProduct::with('billable')
            ->find($products)
            ->each(function (BillableProduct $product) {
                $product->delete();
            });
    }

    /**
     * Update the billable billableable total column (if using)
     *
     * @param  \Modules\Core\App\Models\Model  $billableable
     */
    protected function updateTotalBillableableColumn(Billable $billable, $billableable): Billable
    {
        $totalColumn = $billableable->totalColumn();

        if (! $totalColumn || ((int) $billableable->{$totalColumn} == 0 && $billable->rawTotal() == 0)) {
            return $billable;
        }

        $billableable
            ->forceFill([$totalColumn => $billable->rawTotal()])
            ->when(
                $billableable->wasRecentlyCreated,
                fn () => ChangeLogger::disabled(fn () => $billableable->save()),
                fn () => $billableable->save()
            );

        return $billable;
    }
}
