{{-- Doctor dashboard Now bar: current + next appointment today. --}}
<div class="row g-3">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">{{ __('dashboard.doctor.now.current') }}</div>
            <div class="card-body">
                @if($nowCurrent)
                    <h5 class="card-title mb-1">{{ e(trim(($nowCurrent->patient?->first_name ?? '').' '.($nowCurrent->patient?->last_name ?? '')) ?: '—') }}</h5>
                    <p class="card-text text-muted mb-0">
                        {{ $nowCurrent->appointment_time ? \Illuminate\Support\Carbon::parse($nowCurrent->appointment_time)->format('H:i') : '—' }}
                        · {{ e($nowCurrent->appointment_type ?? '—') }}
                        · {{ e($nowCurrent->status ?? '—') }}
                    </p>
                @else
                    <p class="card-text text-muted mb-0">{{ __('dashboard.doctor.now.none') }}</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">{{ __('dashboard.doctor.now.next') }}</div>
            <div class="card-body">
                @if($nowNext)
                    <h5 class="card-title mb-1">{{ e(trim(($nowNext->patient?->first_name ?? '').' '.($nowNext->patient?->last_name ?? '')) ?: '—') }}</h5>
                    <p class="card-text text-muted mb-0">
                        {{ $nowNext->appointment_time ? \Illuminate\Support\Carbon::parse($nowNext->appointment_time)->format('H:i') : '—' }}
                        · {{ e($nowNext->appointment_type ?? '—') }}
                        · {{ e($nowNext->status ?? '—') }}
                    </p>
                @else
                    <p class="card-text text-muted mb-0">{{ __('dashboard.doctor.now.none') }}</p>
                @endif
            </div>
        </div>
    </div>
</div>
