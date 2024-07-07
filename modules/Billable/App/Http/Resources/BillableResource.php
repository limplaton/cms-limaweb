<?php
 

namespace Modules\Billable\App\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\App\Http\Resources\JsonResource;

/** @mixin \Modules\Billable\App\Models\Billable */
class BillableResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return $this->withCommonData([
            'tax_type' => $this->tax_type->name,
            'subtotal' => $this->subtotal()->getValue(),
            'total' => $this->total()->getValue(),
            'total_tax' => $this->totalTax()->getValue(),
            'taxes' => collect($this->taxes())->map(function ($tax) {
                $tax['total'] = $tax['total']->getValue();

                return $tax;
            }),
            'has_discount' => $this->hasDiscount(),
            'total_discount' => $this->discountedAmount()->getValue(),
            // 'terms'    => $this->terms,
            // 'notes'    => $this->notes,
            'products' => BillableProductResource::collection(
                $this->whenLoaded('products', fn () => $this->products, [])
            ),
        ], $request);
    }
}
