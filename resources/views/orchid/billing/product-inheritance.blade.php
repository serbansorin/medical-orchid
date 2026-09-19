@php
    /** @var array|null $effective */
@endphp

<div class="alert alert-info">
    <strong>{{ __('products.form.inheritance') }}</strong>
    <div>{{ __('products.form.effective') }}</div>
    @if(is_array($effective))
        <div class="mt-2">
            price: <strong>{{ number_format((float) ($effective['price'] ?? 0), 2) }}</strong>
            · level: <strong>{{ $effective['override_level'] ?? '—' }}</strong>
        </div>
    @endif
</div>
