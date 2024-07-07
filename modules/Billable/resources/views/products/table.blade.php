<table style="width: 100%; border-collapse: collapse;">
    <thead>
        <tr>
            <th
                style="font-size: 13.5px; padding: 12px; border-bottom: 1px solid #f0f0f0 ;text-align:left;font-weight: normal;">
                @lang('billable::product.table_heading')
            </th>
            <th
                style="font-size: 13.5px; padding: 12px; border-bottom: 1px solid #f0f0f0; text-align: right;font-weight: normal;">
                @lang('billable::product.qty')
            </th>
            <th
                style="font-size: 13.5px; padding: 12px; border-bottom: 1px solid #f0f0f0; text-align: right;font-weight: normal; white-space: nowrap;">
                @lang('billable::product.unit_price')
            </th>
            @if ($billable->isTaxable())
                <th
                    style="font-size: 13.5px; padding: 12px; border-bottom: 1px solid #f0f0f0; text-align: right;font-weight: normal;">
                    @lang('billable::product.tax')
                </th>
            @endif

            @if ($billable->hasDiscount())
                <th
                    style="font-size: 13.5px; padding: 12px; border-bottom: 1px solid #f0f0f0; text-align: right;font-weight: normal;">
                    @lang('billable::product.discount')
                </th>
            @endif
            <th
                style="font-size: 13.5px; padding: 12px; border-bottom: 1px solid #f0f0f0; text-align: right;font-weight: normal;">
                @lang('billable::product.amount')
            </th>
        </tr>
    </thead>
    <tbody>
        @foreach ($billable->products as $product)
            <tr>
                <td style="border-bottom: 1px solid #f0f0f0; text-align:left; font-weight: bold;">
                    {{ ($product->sku ? $product->sku . ': ' : '') . $product->name }}

                    @if ($product->description)
                        <div style="color: #64748b; font-size: 13.5px; line-height: 13px; font-weight: normal;">
                            {!! nl2br(e($product->description)) !!}
                        </div>
                    @endif
                </td>

                <td style="border-bottom: 1px solid #f0f0f0; text-align: right;">
                    {{ $product->qty }} {{ $product->unit ?: '' }}
                </td>

                <td style="border-bottom: 1px solid #f0f0f0; text-align: right;">
                    {{ $product->unitPrice()->format() }}
                </td>

                @if ($billable->isTaxable())
                    <td style="border-bottom: 1px solid #f0f0f0; text-align: right;">
                        {{ $product->tax_label }} <span
                            style="font-size: 13px; color: #64748b;">({{ $product->tax_rate }}%)</span>
                    </td>
                @endif

                @if ($billable->hasDiscount())
                    <td style="border-bottom: 1px solid #f0f0f0; text-align: right;">
                        @if ($product->discount_type === 'fixed')
                            <span>
                                {{ $product->discountedAmount()->format() }}
                            </span>
                        @endif
                        @if ($product->discount_type === 'percent')
                            <span>
                                {{ $product->discount_total }}%
                            </span>
                        @endif
                    </td>
                @endif

                <td style="border-bottom: 1px solid #f0f0f0; text-align: right;">
                    {{ $product->amount()->format() }}
                </td>
            </tr>
        @endforeach
    </tbody>
    @php
        $colspans = 3;
        if ($billable->hasDiscount() && $billable->isTaxable()) {
            $colspans = 5;
        } elseif ($billable->hasDiscount() || $billable->isTaxable()) {
            $colspans = 4;
        }

    @endphp
    <tfoot>
        <tr>
            <th style="font-size: 14px; text-align: right; font-weight: bold; padding-right: 10px;"
                colspan="{{ $colspans }}">
                @lang('billable::billable.sub_total')

                @if ($billable->hasDiscount())
                    <p style="font-style: italic; margin:0; font-weight: normal; font-size: 13px; color: #64748b;">
                        (
                        @lang('billable::billable.includes_discount', [
                            // format
                            'amount' => $billable->discountedAmount()->format(),
                        ])
                        )
                    </p>
                @endif
            </th>

            <td style="text-align: right; font-size: 14px; white-space: nowrap;">
                {{ $billable->subtotal()->format() }}
            </td>
        </tr>
        @if ($billable->tax_type !== 'no_tax')
            @foreach ($billable->taxes() as $tax)
                <tr>
                    <th style="font-size: 14px; text-align: right; font-weight: bold; padding-right: 10px;"
                        colspan="{{ $colspans }}">
                        {{ $tax['label'] }} ({{ $tax['rate'] }}%)
                    </th>
                    <td style="text-align: right; font-size: 14px; white-space: nowrap;">
                        @if ($billable->isTaxInclusive())
                            @lang('billable::billable.tax_amount_is_inclusive')
                        @endif
                        {{ $tax['total']->format() }}
                    </td>
                </tr>
            @endforeach
        @endif
        <tr>
            <th style="font-size: 14px; text-align: right; font-weight: bold; padding-right: 10px;"
                colspan="{{ $colspans }}">
                @lang('billable::billable.total')
            </th>
            <td style="text-align: right; font-size: 14px; white-space: nowrap;">
                {{ $billable->total()->format() }}
            </td>
        </tr>
    </tfoot>
</table>
