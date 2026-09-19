<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Billing;

use App\Models\BillingInvoiceItem;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class InvoiceItemsLayout extends Table
{
    protected $target = 'items';

    protected function columns(): iterable
    {
        return [
            TD::make('description', __('invoices.detail.description'))
                ->cantHide()
                ->render(fn (BillingInvoiceItem $item): string => e($item->description ?? $item->product?->name ?? '—')),

            TD::make('quantity', __('invoices.detail.quantity'))
                ->align(TD::ALIGN_RIGHT)
                ->render(fn (BillingInvoiceItem $item): string => e((string) $item->quantity)),

            TD::make('unit_price', __('invoices.detail.unit_price'))
                ->align(TD::ALIGN_RIGHT)
                ->render(fn (BillingInvoiceItem $item): string => e(number_format((float) $item->unit_price, 2))),

            TD::make('line_total', __('invoices.detail.line_total'))
                ->align(TD::ALIGN_RIGHT)
                ->render(fn (BillingInvoiceItem $item): string => e(number_format((float) $item->line_total, 2))),

            TD::make(__('invoices.detail.remove_item'))
                ->align(TD::ALIGN_CENTER)
                ->width('90px')
                ->canSee($this->query->get('editable', false))
                ->render(fn (BillingInvoiceItem $item) => Button::make(__('invoices.detail.remove_item'))
                    ->confirm()
                    ->method('removeItem', ['item' => $item->id])),
        ];
    }
}
