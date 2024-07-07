<?php
 

return [
    'products' => 'Products',
    'product' => 'Product',
    'create' => 'Create Product',
    'edit' => 'Edit Product',
    'export' => 'Export products',
    'import' => 'Import Products',
    'created' => 'Product successfully created',
    'updated' => 'Product successfully updated',
    'deleted' => 'Product successfully deleted',
    'related_products' => 'Related Products',
    'manage' => 'Manage Products',
    'name' => 'Name',
    'description' => 'Description',
    'table_heading' => 'Product',
    'tax' => 'Tax',
    'quantity' => 'Quantity',
    'qty' => 'QTY',
    'unit_price' => 'Unit Price',
    'direct_cost' => 'Direct Cost',
    'unit' => 'Unit (kg, lots)',
    'sku' => 'SKU',

    'is_active' => 'Active',
    'tax_rate' => 'Tax Rate',
    'tax_label' => 'Tax Label',
    'tax_percent' => 'Tax Percent',
    'discount' => 'Discount',
    'amount' => 'Amount',
    'discount_percent' => 'Discount Percent',
    'discount_amount' => 'Discount Amount',
    'will_be_added_as_new' => ':name will be added as new product',

    'total_products' => 'Total Products',
    'total_sold' => 'Total Sold',
    'sold_amount_exc_tax' => 'Sold Amount (tax exl.)',
    'interest_in_product' => 'Interest In Product',
    'resource_has_no_products' => 'No products added, start by selecting a product',

    'exists_in_trash_by_name' => 'Product with the same name already exists in the trash, would you like to restore the trashed product?',

    'choose_or_enter' => 'Choose or enter a product',

    'cards' => [
        'performance' => 'Product performance',
        'performance_info' => '"Interest in product" column reflects all products that are added to deals, but the "Total Sold" column reflects the products that are added to deals and the deals are marked as won',
    ],

    'count' => '0 products | 1 product | :count products',

    'settings' => [
        'default_tax_type' => 'Do you sell your products at rates inclusive of Tax?',
        'default_discount_type' => 'Default discount type',
    ],

    'actions' => [
        'mark_as_active' => 'Mark as active',
        'mark_as_inactive' => 'Mark as inactive',
        'update_unit_price' => 'Update price',
        'update_tax_rate' => 'Update tax rate',
        'update_tax_label' => 'Update tax label',
    ],

    'validation' => [
        'sku' => [
            'unique' => 'A product with this SKU already exists.',
        ],
    ],

    'empty_state' => [
        'title' => 'You have not created any products.',
        'description' => 'Save time by using predefined products.',
    ],
];
