@extends('layout.headerFooter')
@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="card shadow-lg p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold text-primary mb-0">School Mood Overview</h3>
                    <a href="{{ route('school.index') }}" class="btn btn-secondary">
                        <i class="mdi mdi-arrow-left"></i> Back
                    </a>
                </div>

                {{-- Date Filter --}}
                <form method="GET" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Start Date</label>
                            <input type="date" name="start_date" value="{{ $startDate }}" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">End Date</label>
                            <input type="date" name="end_date" value="{{ $endDate }}" class="form-control">
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-outline-primary">Apply</button>
                            <a href="{{ route('school.moods.overview') }}" class="btn btn-outline-secondary">Clear</a>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Chart --}}
            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="card shadow-sm p-3">
                        <h5 class="mb-3 fw-semibold text-dark border-bottom pb-2 text-center">
                            Mood Distribution by School (% of Students)
                        </h5>
                        <div style="position: relative; height: 450px; width: 100%;">
                            <canvas id="moodOverviewChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const rawData    = @json($chartData);
        const labels     = rawData.map(d => d.school_name);
        const positives  = rawData.map(d => d.positive);
        const negatives  = rawData.map(d => d.negative);

        const allVals    = [...positives, ...negatives].filter(v => v > 0);
        const maxVal     = allVals.length > 0 ? Math.max(...allVals) : 10;
        const yMax       = Math.min(100, Math.ceil(maxVal / 5) * 5 + 5);
        const stepSize   = yMax <= 10  ? 1
                         : yMax <= 25  ? 5
                         : yMax <= 50  ? 10
                         : 20;

        new Chart(document.getElementById('moodOverviewChart'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Positive Mood',
                        data: positives,
                        backgroundColor: 'rgba(75, 192, 170, 0.75)',
                        borderColor:     'rgba(75, 192, 170, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Negative Mood',
                        data: negatives,
                        backgroundColor: 'rgba(255, 99, 132, 0.75)',
                        borderColor:     'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: yMax,
                        ticks: {
                            stepSize: stepSize,
                            callback: v => v + '%'
                        },
                        title: {
                            display: true,
                            text: 'Proportion of Students (%)',
                            font: { size: 13 }
                        }
                    },
                    x: {
                        ticks: {
                            maxRotation: 30,
                            minRotation: 0,
                            font: { size: 12 }
                        },
                        title: {
                            display: true,
                            text: 'Schools',
                            font: { size: 13 }
                        }
                    }
                },
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: ctx => `${ctx.dataset.label}: ${ctx.raw}%`
                        }
                    }
                }
            }
        });
    </script>
@endsection
