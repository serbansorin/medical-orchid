{{-- Doctor dashboard week strip: 7-day appointment counts. --}}
@if(empty($weekStrip))
    <p class="text-muted mb-0">{{ __('dashboard.doctor.week.empty') }}</p>
@else
    <ul class="list-group list-group-flush">
        @foreach($weekStrip as $day)
            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                <span>{{ e($day['label']) }}</span>
                <span class="badge bg-{{ $day['count'] > 0 ? 'primary' : 'secondary' }} rounded-pill">{{ $day['count'] }}</span>
            </li>
        @endforeach
    </ul>
@endif
