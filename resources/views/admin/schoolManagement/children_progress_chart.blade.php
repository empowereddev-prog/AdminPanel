@extends('layout.headerFooter')
@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="card shadow-lg p-4">
                <!-- Heading + Back Button -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold text-primary mb-0">
                        Children Progress Chart - {{ $data->name }}
                    </h3>
                    <a href="{{ route('school.show', $data->school_id ?? '') }}" class="btn btn-secondary">
                        <i class="mdi mdi-arrow-left"></i> Back
                    </a>
                </div>
                <!-- Filter Form -->
                <form method="GET" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Start Date</label>
                            <input type="date" name="start_date" value="{{ $startDate }}"
                                class="form-control shadow-sm">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">End Date</label>
                            <input type="date" name="end_date" value="{{ $endDate }}"
                                class="form-control shadow-sm">
                        </div>
                        <div class="col-sd-1"></div>
                        <div class="col-md-6 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-outline-primary w-50 shadow-sm">
                                <i class="mdi mdi-filter"></i> Apply Filter
                            </button>
                            &nbsp;
                            <a href="{{ route('school.children.progress', $data->school_id ?? '') }}"
                                class="btn btn-outline-secondary w-50 shadow-sm">
                                <i class="mdi mdi-backspace"></i> Clear Filter
                            </a>
                        </div>
                    </div>
                </form>
            </div>
            <div class="card mt-2">
                <!-- Chart Section -->
                <div class="p-3">
                    <h5 class="mb-3 fw-semibold text-dark">Overall Progress</h5>
                    <canvas id="childrenChart" height="120"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('childrenChart');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json(array_column($chartData, 'name')),
                datasets: [{
                        label: 'Video Progress (%)',
                        data: @json(array_column($chartData, 'video_progress')),
                        backgroundColor: 'rgba(54, 162, 235, 0.8)',
                        borderRadius: 6,
                    },
                    {
                        label: 'Quiz Progress (%)',
                        data: @json(array_column($chartData, 'quiz_progress')),
                        backgroundColor: 'rgba(255, 99, 132, 0.8)',
                        borderRadius: 6,
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: {
                                size: 14,
                                weight: 'bold'
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.raw + '%';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            font: {
                                size: 13,
                                weight: '600'
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            stepSize: 20,
                            font: {
                                size: 13
                            }
                        }
                    }
                }
            }
        });
    </script>
@endsection
