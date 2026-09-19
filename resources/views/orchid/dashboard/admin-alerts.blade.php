{{-- Admin dashboard alert strip: low stock + pending fulfillment counts. --}}
@if(($alerts['low_stock_count'] ?? 0) > 0 || ($alerts['pending_fulfillments'] ?? 0) > 0)
    <div class="alert alert-warning mb-0" role="alert">
        <ul class="mb-0">
            @if(($alerts['low_stock_count'] ?? 0) > 0)
                <li>{{ __('dashboard.admin.alerts.low_stock', ['count' => $alerts['low_stock_count']]) }}</li>
            @endif
            @if(($alerts['pending_fulfillments'] ?? 0) > 0)
                <li>{{ __('dashboard.admin.alerts.pending_fulfillments', ['count' => $alerts['pending_fulfillments']]) }}</li>
            @endif
        </ul>
    </div>
@else
    <div class="alert alert-success mb-0" role="alert">
        {{ __('dashboard.admin.alerts.all_clear') }}
    </div>
@endif
