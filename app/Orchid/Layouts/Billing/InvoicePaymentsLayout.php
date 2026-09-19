<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Billing;

use App\Enums\PaymentMethod;
use App\Models\Payment;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class InvoicePaymentsLayout extends Table
{
    protected $target = 'payments';

    protected function columns(): iterable
    {
        return [
            TD::make('paid_at', __('payments.list.date'))
                ->cantHide()
                ->render(fn (Payment $p): string => e($p->paid_at?->format('Y-m-d H:i') ?? $p->created_at?->format('Y-m-d H:i') ?? '—')),

            TD::make('amount', __('payments.list.amount'))
                ->align(TD::ALIGN_RIGHT)
                ->render(fn (Payment $p): string => e(number_format((float) $p->amount, 2))),

            TD::make('method', __('payments.list.method'))
                ->render(function (Payment $p): string {
                    try {
                        return e(PaymentMethod::from((string) $p->method)->label());
                    } catch (\ValueError) {
                        return e((string) ($p->method ?? '—'));
                    }
                }),

            TD::make('reference', __('payments.list.reference'))
                ->render(fn (Payment $p): string => e($p->reference ?? '—')),
        ];
    }
}
