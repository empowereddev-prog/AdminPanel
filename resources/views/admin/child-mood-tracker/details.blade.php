@extends('layout.headerFooter')

@section('title', $title)

@section('content')
    <div class="container mt-4">

        {{-- Back Button at Top --}}
        <div class="mb-3 text-right">
            <a href="{{ route('child-mood-tracker') }}" class="btn btn-secondary">
                <i class="mdi mdi-arrow-left"></i> Back
            </a>
        </div>

        <h2 class="mb-4 text-center">{{ $title }}</h2>

        {{-- Filter Form --}}
        <form method="GET" class="mb-4 d-flex justify-content-center align-items-end">
            <div class="col-sm-3 form-group mx-2">
                <label for="month">Month</label>
                <select name="month" id="month" class="form-control">
                    @foreach (range(1, 12) as $m)
                        <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group col-sm-3 mx-2">
                <label for="year">Year</label>
                <select name="year" id="year" class="form-control">
                    @for ($y = now()->year; $y >= now()->year - 5; $y--)
                        <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>

            <button type="submit" class="btn btn-success mx-2">
                <i class="mdi mdi-filter"></i> Filter
            </button>
            <a href="{{ route('child-mood-tracker.detail', [
                'user_id' => $user_id,
                'month' => now()->month,
                'year' => now()->year,
            ]) }}"
                class="btn btn-secondary mx-2">
                <i class="mdi mdi-refresh"></i> Reset
            </a>
        </form>
        {{-- Mood Calendar --}}
        <div class="card mb-4">
            <div class="text-center">
                <h4>
                    Mood Calendar ({{ \Carbon\Carbon::create($year, $month)->format('F Y') }})
                </h4>
            </div>
            <div class="card-body">
                <div class="row text-center font-weight-bold">
                    @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $day)
                        <div class="col border py-2">{{ $day }}</div>
                    @endforeach
                </div>
                @php
                    $calendar = collect($calendarData);
                    $startDate = \Carbon\Carbon::create($year, $month)->startOfMonth();
                    $paddingDays = $startDate->dayOfWeekIso - 1;

                    $calendarDays = [];
                    for ($i = 0; $i < $paddingDays; $i++) {
                        $calendarDays[] = null;
                    }
                    foreach ($calendar as $date => $data) {
                        $calendarDays[] = ['date' => $date] + $data;
                    }
                    $weeks = array_chunk($calendarDays, 7);
                @endphp

                @foreach ($weeks as $week)
                    <div class="row">
                        @foreach ($week as $day)
                            @if ($day)
                                <div class="col border text-center p-2"
                                    style="min-height: 80px; background-color: #f8f9fa;">

                                    {{-- style="min-height: 80px; background-color: {{ $day['color'] ?? '#f8f9fa' }}"> --}}
                                    <div class="text-muted small">{{ \Carbon\Carbon::parse($day['date'])->day }}</div>
                                    @if ($day['image'])
                                        <img src="{{ $day['image'] }}" alt="" width="40">
                                    @endif
                                </div>
                            @else
                                <div class="col border p-2" style="min-height: 80px; background-color: #f8f9fa"></div>
                            @endif
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Mood Ring Chart --}}
        <div class="card mb-4">
            <div class="text-center">
                <h4>Mood Ring</h4>
            </div>
            <div class="card-body text-center">
                @if (count($moodRing) > 0)
                    <div style="max-width: 300px; margin: 0 auto;">
                        <canvas id="moodRingChart"></canvas>
                    </div>
                @else
                    <p class="text-muted">No mood data available for this month.</p>
                @endif
            </div>
        </div>

        {{-- Top 3 Emotions --}}
        <div class="card mb-4">
            <div class="text-center">
                <h4>Top 3 Emotions</h4>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    @forelse ($topEmotions as $emotion)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                @if ($emotion['image'])
                                    <img src="{{ $emotion['image'] }}" width="30" class="mr-2">
                                @endif
                                <strong>{{ $emotion['mood_name'] }}</strong>
                            </div>
                            <span class="badge badge-primary badge-pill">{{ $emotion['count'] }} days</span>
                        </li>
                    @empty
                        <li class="list-group-item text-center">No mood data found</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const moodRing = @json($moodRing);

        if (Array.isArray(moodRing) && moodRing.length > 0) {
            const ctx = document.getElementById('moodRingChart');

            const labels = moodRing.map(item => {
                const moodNames = item.moods.map(m => m.mood_name).join(', ');
                return `${moodNames} (${item.total_count} days)`;
            });

            const data = moodRing.map(item => item.total_count);
            const colors = moodRing.map(item => item.color || '#cccccc');

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: colors,
                        borderColor: '#ffffff',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    return `${label}: ${value} days`;
                                }
                            }
                        }
                    }
                }
            });
        }
    </script>
@endpush
