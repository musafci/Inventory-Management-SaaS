<?php

namespace App\Http\Livewire\Concerns;

trait MapsFormValidationAttributes
{
    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return array_merge($this->sharedFormValidationAttributes(), $this->customValidationAttributes());
    }

    /**
     * @return array<string, string>
     */
    protected function customValidationAttributes(): array
    {
        return [];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return array_merge($this->sharedFormValidationMessages(), $this->customValidationMessages());
    }

    /**
     * @return array<string, string>
     */
    protected function customValidationMessages(): array
    {
        return [];
    }

    /**
     * @return array<string, string>
     */
    protected function sharedFormValidationAttributes(): array
    {
        return [
            'form.name' => 'name',
            'form.email' => 'email',
            'form.phone' => 'phone',
            'form.address' => 'address',
            'form.symbol' => 'symbol',
            'form.slug' => 'slug',
            'form.parent_id' => 'parent category',
            'form.is_default' => 'default warehouse',
            'form.is_active' => 'active status',
            'form.contact_person' => 'contact person',
            'form.role' => 'role',
            'form.password' => 'password',
            'form.category_id' => 'category',
            'form.unit_id' => 'unit',
            'form.sku' => 'SKU',
            'form.barcode' => 'barcode',
            'form.cost_price' => 'cost price',
            'form.selling_price' => 'selling price',
            'form.tax_rate' => 'tax rate',
            'form.reorder_point' => 'reorder point',
            'form.supplier_id' => 'supplier',
            'form.customer_id' => 'customer',
            'form.warehouse_id' => 'warehouse',
            'form.product_id' => 'product',
            'form.order_date' => 'order date',
            'form.expected_date' => 'expected date',
            'form.type' => 'movement type',
            'form.quantity' => 'quantity',
            'form.note' => 'note',
            'form.items' => 'line items',
            'form.items.*.product_id' => 'product',
            'form.items.*.quantity' => 'quantity',
            'form.items.*.quantity_ordered' => 'quantity ordered',
            'form.items.*.unit_cost' => 'unit cost',
            'form.items.*.unit_price' => 'unit price',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function sharedFormValidationMessages(): array
    {
        return [
            'form.items.required' => 'Add at least one line item.',
            'form.items.min' => 'Add at least one line item.',
            'form.items.*.product_id.required' => 'Select a product for each line item.',
            'form.items.*.quantity.required' => 'Enter a quantity for each line item.',
            'form.items.*.quantity_ordered.required' => 'Enter a quantity for each line item.',
            'form.items.*.unit_cost.required' => 'Enter a unit cost for each line item.',
            'form.items.*.unit_price.required' => 'Enter a unit price for each line item.',
        ];
    }
}
