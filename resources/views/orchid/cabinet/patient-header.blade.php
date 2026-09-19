<div class="d-flex align-items-center gap-3 flex-wrap">
    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:56px;height:56px;font-size:1.4rem;">
        {{ mb_strtoupper(mb_substr($patient->first_name ?? '', 0, 1).mb_substr($patient->last_name ?? '', 0, 1)) }}
    </div>
    <div>
        <h4 class="mb-0">{{ $patient->first_name }} {{ $patient->last_name }}</h4>
        <div class="text-muted small">
            {{ __('patients.cnp') }}: {{ $patient->cnp ?? '—' }}
            · {{ __('patients.age') }}: {{ $patient->date_of_birth ? $patient->date_of_birth->age : '—' }}
            · {{ $patient->phone ?? __('patients.no_phone') }}
        </div>
    </div>
    @if(!empty($alerts))
        <div class="ms-auto">
            @foreach($alerts as $alert)
                <span class="badge bg-warning text-dark me-1">{{ $alert }}</span>
            @endforeach
        </div>
    @endif
</div>
