<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Billing\Payment;

use App\Enums\PaymentMethod;
use App\Models\Payment;
use App\Orchid\Layouts\Billing\PaymentListLayout;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;

class PaymentListScreen extends Screen
{
    public function query(): iterable
    {
        $request = request();
        $qb = Payment::query()->with(['billing_invoice.patient']);

        if ($method = $request->query('f_method')) {
            $qb->where('method', $method);
        }

        if ($from = $request->query('f_from')) {
            $qb->whereDate('paid_at', '>=', $from);
        }

        if ($to = $request->query('f_to')) {
            $qb->whereDate('paid_at', '<=', $to);
        }

        if ($search = trim((string) $request->query('f_search', ''))) {
            $qb->where(function ($q) use ($search): void {
                $q->where('reference', 'ilike', '%'.$search.'%')
                    ->orWhereHas('billing_invoice.patient', function ($pq) use ($search): void {
                        $pq->where('first_name', 'ilike', '%'.$search.'%')
                            ->orWhere('last_name', 'ilike', '%'.$search.'%');
                    });
            });
        }

        $methodOptions = ['' => __('payments.filter.all')];
        foreach (PaymentMethod::cases() as $case) {
            $methodOptions[$case->value] = $case->label();
        }

        return [
            'payments' => $qb->orderByDesc('id')->paginate(25),
            'methodOptions' => $methodOptions,
        ];
    }

    public function name(): ?string
    {
        return __('payments.list.title');
    }

    public function description(): ?string
    {
        return __('payments.list.description');
    }

    public function permission(): ?iterable
    {
        return ['view_any_payment', 'view_any_cart_payment'];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('payments.list.create'))
                ->icon('bs.plus-circle')
                ->route('platform.payments.create'),
        ];
    }

    public function layout(): iterable
    {
        $methodOptions = ['' => __('payments.filter.all')];
        foreach (PaymentMethod::cases() as $case) {
            $methodOptions[$case->value] = $case->label();
        }

        return [
            Layout::rows([
                Select::make('f_method')
                    ->title(__('payments.filter.method'))
                    ->options($methodOptions)
                    ->value(request()->query('f_method', ''))
                    ->empty(),

                DateTimer::make('f_from')
                    ->title(__('payments.filter.from'))
                    ->format('Y-m-d')
                    ->value(request()->query('f_from')),

                DateTimer::make('f_to')
                    ->title(__('payments.filter.to'))
                    ->format('Y-m-d')
                    ->value(request()->query('f_to')),

                Input::make('f_search')
                    ->title(__('payments.filter.search'))
                    ->placeholder(__('payments.filter.search'))
                    ->value(request()->query('f_search', '')),

                Button::make(__('payments.filter.apply'))
                    ->method('applyFilters'),
            ]),

            PaymentListLayout::class,
        ];
    }

    public function applyFilters(Request $request): mixed
    {
        $params = array_filter([
            'f_method' => $request->input('f_method'),
            'f_from' => $request->input('f_from'),
            'f_to' => $request->input('f_to'),
            'f_search' => $request->input('f_search'),
        ]);

        return redirect()->route('platform.payments', $params);
    }
}
