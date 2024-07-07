<?php
 

namespace Modules\Billable\App\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\App\Http\Resources\JsonResource;

/** @mixin \Modules\Billable\App\Models\BillableProduct */
class BillableProductResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return $this->withCommonData([
            'product_id' => $this->product_id,
            'name' => $this->name,
            'description' => $this->description,
            'unit_price' => $this->unitPrice()->getValue(),
            'qty' => $this->qty,
            'unit' => $this->unit,
            'tax_rate' => $this->tax_rate,
            'tax_label' => $this->tax_label,
            'discount_type' => $this->discount_type,
            'discount_total' => $this->discount_total,
            'sku' => $this->sku,
            'amount' => $this->amount()->getValue(),
            'note' => $this->note,
            'display_order' => $this->display_order,
        ], $request);
    }
}
