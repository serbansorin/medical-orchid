<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Billing\Invoice;

use App\Models\BillingInvoice;
use App\Orchid\Layouts\Billing\InvoiceListLayout;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;

class BillingInvoiceListScreen extends Screen
{
    public function query(): iterable
    {
        $request = request();
        $qb = BillingInvoice::query()->with(['patient', 'clinic', 'doctor']);

        if ($status = $request->query('f_status')) {
            $qb->where('status', $status);
        }

        if ($from = $request->query('f_from')) {
            $qb->whereDate('invoice_date', '>=', $from);
        }

        if ($to = $request->query('f_to')) {
            $qb->whereDate('invoice_date', '<=', $to);
        }

        if ($search = trim((string) $request->query('f_search', ''))) {
            $qb->where(function ($q) use ($search): void {
                $q->where('invoice_number', 'ilike', '%'.$search.'%')
                    ->orWhereHas('patient', function ($pq) use ($search): void {
                        $pq->where('first_name', 'ilike', '%'.$search.'%')
                            ->orWhere('last_name', 'ilike', '%'.$search.'%')
                            ->orWhere('cnp', 'ilike', '%'.$search.'%');
                    });
            });
        }

        return [
            'invoices' => $qb->orderByDesc('id')->paginate(25),
        ];
    }

    public function name(): ?string
    {
        return __('invoices.list.title');
    }

    public function description(): ?string
    {
        return __('invoices.list.description');
    }

    public function permission(): ?iterable
    {
        return ['view_any_billing_invoice'];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('invoices.list.create'))
                ->icon('bs.plus-circle')
                ->route('platform.invoices.create'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::rows([
                Select::make('f_status')
                    ->title(__('invoices.filter.status'))
                    ->options(['' => __('invoices.filter.all')] + array_combine(
                        ['draft', 'sent', 'paid', 'overdue', 'cancelled'],
                        array_map(fn (string $s): string => __('invoices.status.'.$s), ['draft', 'sent', 'paid', 'overdue', 'cancelled'])
                    ))
                    ->value(request()->query('f_status', ''))
                    ->empty(),

                DateTimer::make('f_from')
                    ->title(__('invoices.filter.from'))
                    ->format('Y-m-d')
                    ->value(request()->query('f_from')),

                DateTimer::make('f_to')
                    ->title(__('invoices.filter.to'))
                    ->format('Y-m-d')
                    ->value(request()->query('f_to')),

                Input::make('f_search')
                    ->title(__('invoices.filter.search'))
                    ->placeholder(__('invoices.filter.search'))
                    ->value(request()->query('f_search', '')),

                Button::make(__('invoices.filter.apply'))
                    ->method('applyFilters'),
            ]),

            InvoiceListLayout::class,
        ];
    }

    public function applyFilters(Request $request): mixed
    {
        $params = array_filter([
            'f_status' => $request->input('f_status'),
            'f_from' => $request->input('f_from'),
            'f_to' => $request->input('f_to'),
            'f_search' => $request->input('f_search'),
        ]);

        return redirect()->route('platform.invoices', $params);
    }
}
