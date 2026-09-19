@php
    /** @var \App\Models\BillingInvoice $invoice */
    $paid = $paidTotal ?? 0;
    $balance = $balanceDue ?? 0;
@endphp

<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
    <div>
        <h4 class="mb-1">{{ $invoice->invoice_number }}</h4>
        <div class="text-muted">
            {{ $invoice->patient?->full_name ?? '—' }}
            @if($invoice->patient?->cnp)
                · {{ $invoice->patient->cnp }}
            @endif
        </div>
        <div class="mt-2 d-flex flex-wrap gap-2">
            <span class="badge bg-info">{{ __('invoices.status.'.$invoice->status) }}</span>
            <span class="badge bg-secondary">{{ __('invoices.direction.'.$invoice->direction) }}</span>
            @if($invoice->locked_at)
                <span class="badge bg-warning">locked {{ $invoice->locked_at->format('Y-m-d') }}</span>
            @endif
        </div>
    </div>
    <div class="text-end">
        <div>{{ __('invoices.detail.totals.subtotal') }}: <strong>{{ number_format((float) $invoice->subtotal, 2) }}</strong></div>
        <div>{{ __('invoices.detail.totals.discount') }}: <strong>{{ number_format((float) $invoice->discount_amount, 2) }}</strong></div>
        <div>{{ __('invoices.detail.totals.tax') }}: <strong>{{ number_format((float) $invoice->tax_amount, 2) }}</strong></div>
        <div class="fs-5">{{ __('invoices.detail.totals.total') }}: <strong>{{ number_format((float) $invoice->total_amount, 2) }}</strong></div>
        <div>{{ __('invoices.detail.totals.paid') }}: <strong>{{ number_format((float) $paid, 2) }}</strong></div>
        <div>{{ __('invoices.detail.totals.balance') }}: <strong>{{ number_format((float) $balance, 2) }}</strong></div>
    </div>
</div>

<div class="d-flex flex-wrap gap-3 small text-muted">
    <span>{{ __('invoices.detail.lineage.clinic') }}: <strong>{{ $invoice->clinic?->name ?? __('invoices.detail.lineage.none') }}</strong></span>
    <span>{{ __('invoices.detail.lineage.doctor') }}: <strong>{{ ($invoice->doctor ? trim($invoice->doctor->first_name.' '.$invoice->doctor->last_name) : '') ?: __('invoices.detail.lineage.none') }}</strong></span>
    <span>{{ __('invoices.detail.lineage.appointment') }}: <strong>{{ $invoice->appointment_id ? '#'.$invoice->appointment_id : __('invoices.detail.lineage.none') }}</strong></span>
    <span>{{ __('invoices.detail.lineage.visit') }}: <strong>{{ $invoice->medical_visit_id ? '#'.$invoice->medical_visit_id : __('invoices.detail.lineage.none') }}</strong></span>
    <span>{{ __('invoices.list.date') }}: <strong>{{ $invoice->invoice_date?->format('Y-m-d') ?? '—' }}</strong></span>
    <span>{{ __('invoices.list.due') }}: <strong>{{ $invoice->due_date?->format('Y-m-d') ?? '—' }}</strong></span>
    @if($invoice->document_path)
        <span><a href="{{ $invoice->document_url }}" target="_blank" rel="noopener">{{ $invoice->document_name ?? $invoice->document_path }}</a></span>
    @endif
</div>
