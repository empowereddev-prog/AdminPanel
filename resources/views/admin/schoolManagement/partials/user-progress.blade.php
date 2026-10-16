@php
    // Quiz progress is only set for student accounts.
    $progressSections = ['Video' => $progressUser->categoryProgress ?? []];
    if (isset($progressUser->quizProgress)) {
        $progressSections['Quiz'] = $progressUser->quizProgress;
    }
@endphp
@foreach ($progressSections as $kind => $items)
    <div class="card p-3 mt-3 mb-4 bg-light" style="border: 1px solid #7c7e80;">
        <h5 class="mb-3 text-primary">{{ $kind }} Progress by Category</h5>
        <div class="row">
            @forelse ($items as $progress)
                @php($percentage = min(100, max(0, $progress['percentage'])))
                <div class="col-md-6 mb-3">
                    <strong>{{ $progress['category_name'] }}</strong>
                    <div class="d-flex align-items-center justify-content-between">
                        <span>{{ round($percentage, 2) }}%</span>
                        <div class="progress flex-grow-1 ml-2" style="height: 8px;">
                            <div class="progress-bar {{ $kind === 'Quiz' ? 'bg-success' : 'bg-primary' }}"
                                role="progressbar" style="width: {{ $percentage }}%;"
                                aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
            @empty
                <p class="col-12 text-muted mb-0">No {{ strtolower($kind) }} progress data available.</p>
            @endforelse
        </div>
    </div>
@endforeach
