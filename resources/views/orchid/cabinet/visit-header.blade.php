<div class="d-flex align-items-center gap-3 flex-wrap">
    <div>
        <h4 class="mb-0">{{ $patientName }}</h4>
        <div class="text-muted small">
            {{ __('visits.doctor') }}: {{ $doctorName }}
            · {{ __('visits.clinic') }}: {{ $clinicName }}
            · {{ $visitDate }}
            @if(!empty($appointmentId))
                · <a href="{{ route('platform.appointments.detail', $appointmentId) }}">{{ __('visits.appointment') }} #{{ $appointmentId }}</a>
            @endif
        </div>
    </div>
    <div class="ms-auto d-flex gap-2 align-items-center">
        <span class="badge bg-info">{{ $statusLabel }}</span>
        @if(!empty($episodeLabel))
            <span class="badge bg-secondary">{{ $episodeLabel }}</span>
        @endif
    </div>
</div>
@if(!empty($allergyAlert))
    <div class="alert alert-warning mt-2 mb-0">{{ $allergyAlert }}</div>
@endif
