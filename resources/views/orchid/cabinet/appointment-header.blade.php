<div class="d-flex align-items-center gap-3 flex-wrap">
    <div>
        <h4 class="mb-0">{{ $patientName }}</h4>
        <div class="text-muted small">
            {{ __('appointments.doctor') }}: {{ $doctorName }}
            · {{ __('appointments.clinic') }}: {{ $clinicName }}
            · {{ $slot }}
        </div>
    </div>
    <div class="ms-auto d-flex gap-2 align-items-center">
        <span class="badge bg-info">{{ $statusLabel }}</span>
        <span class="badge {{ $isPaid ? 'bg-success' : 'bg-secondary' }}">{{ $isPaid ? __('appointments.paid') : __('appointments.unpaid') }}</span>
        @if(!empty($visitId))
            <a href="{{ route('platform.visits.detail', $visitId) }}" class="badge bg-primary text-decoration-none">{{ __('appointments.open_visit') }}</a>
        @endif
    </div>
</div>
@if(!empty($allergyAlert))
    <div class="alert alert-warning mt-2 mb-0">{{ $allergyAlert }}</div>
@endif
