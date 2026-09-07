@extends('layout.headerFooter')
@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="card shadow-lg p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold text-primary mb-0">School - {{ $school->name }}</h3>
                    <a href="{{ route('school.show', $school->id) }}" class="btn btn-secondary">
                        <i class="mdi mdi-arrow-left"></i> Back
                    </a>
                </div>
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
                        <!-- <div class="col-md-3">
                            <label class="form-label fw-bold">Age Range</label>
                            <select name="age_range" class="form-control">
                                <option value="">All Ages</option>
                                <option value="11-14" {{ $ageFilter == '11-14' ? 'selected' : '' }}>Age(11-14)</option>
                                <option value="15-18" {{ $ageFilter == '15-18' ? 'selected' : '' }}>Age(15-18)</option>
                                <option value="11-18" {{ $ageFilter == '11-18' ? 'selected' : '' }}>Age(11-18)</option>
                            </select>
                        </div> -->
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-outline-primary">Apply</button>
                            <a href="{{ route('school.children.progress', $school->id) }}"
                                class="btn btn-outline-secondary">Clear</a>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span style="color: #000000;font-weight: bold; font-size: 20px;">Overall Avg Video Progress: </span>
                        <strong
                            style="color: #000000;font-weight: bold; font-size: 20px;">{{ number_format((float) $averageVideoProgress, 2) }}%</strong>
                        <span class="ml-4" style="color: #000000;font-weight: bold; font-size: 20px;">Overall Avg Mood
                            Progress: </span>
                        <strong style="color: #000000;font-weight: bold; font-size: 20px;">{{ $avgMoodPercent }}%</strong>
                    </div>
                </form>
            </div>


            {{-- Quiz Progress - Full Width Row --}}
            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="card shadow-sm p-3">
                        <h5 class="mb-3 fw-semibold text-dark border-bottom pb-2 text-center">Quiz Progress (%)</h5>
                        <div style="position: relative; height: 400px; width: 100%;">
                            <!-- <canvas id="quizPieChart"></canvas> -->
                            <canvas id="quizBarChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Video Progress - Full Width Row --}}
            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="card shadow-sm p-3">
                        <h5 class="mb-3 fw-semibold text-dark border-bottom pb-2 text-center">Video Progress (%)</h5>
                        <div style="position: relative; height: 400px; width: 100%;">
                            <!-- <canvas id="videoPieChart"></canvas> -->
                            <canvas id="videoBarChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Mood Distribution - Full Width Row --}}
            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="card shadow-sm p-3">
                        <h5 class="mb-3 fw-semibold text-dark border-bottom pb-2 text-center">Mood Distribution</h5>
                        <div style="position: relative; height: 400px; width: 100%;">
                            <canvas id="moodBarChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const colorPalette = ['#36A2EB', '#FF6384', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#63FF84'];
        const pieOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        };

        // 1. Quiz Pie Chart
        /*
        const quizRaw = @json($chartData);
        new Chart(document.getElementById('quizPieChart'), {
            type: 'pie',
            data: {
                labels: quizRaw.map(d => d.name),
                datasets: [{
                    data: quizRaw.map(d => d.quiz_progress),
                    backgroundColor: colorPalette
                }]
            },
            options: pieOptions
        });
        */
        // 1. Quiz Bar Chart (replaces Quiz Pie Chart)
        const quizBarRaw = @json($quizBarData);
        const quizBarLabels = quizBarRaw.map(d => d.category_name);
        const quizBar1114  = quizBarRaw.map(d => d['11-14']);
        const quizBar1518  = quizBarRaw.map(d => d['15-18']);

        const quizMaxVal = Math.max(...quizBar1114, ...quizBar1518);
        const quizYMax   = quizMaxVal <= 0 ? 10 : Math.min(100, Math.ceil(quizMaxVal / 5) * 5 + 5);
        const quizStepSize = quizYMax <= 10  ? 1
                   : quizYMax <= 25  ? 5
                   : quizYMax <= 50  ? 10
                   : 20;

        new Chart(document.getElementById('quizBarChart'), {
            type: 'bar',
            data: {
                labels: quizBarLabels,
                datasets: [
                    {
                        label: '11-14 yrs',
                        data: quizBar1114,
                        backgroundColor: 'rgba(153, 102, 255, 0.75)',
                        borderColor: 'rgba(153, 102, 255, 1)',
                        borderWidth: 1
                    },
                    {
                        label: '15-18 yrs',
                        data: quizBar1518,
                        backgroundColor: 'rgba(75, 192, 170, 0.75)',
                        borderColor: 'rgba(75, 192, 170, 1)',
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
                        max: quizYMax,
                        ticks: {
                            stepSize: quizStepSize,
                            callback: v => v + '%'
                        }
                    },
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 30,
                            font: { size: 11 }
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

        // 2. Video Pie Chart
        /*
        const videoRaw = @json($videoChartData);
        new Chart(document.getElementById('videoPieChart'), {
            type: 'pie',
            data: {
                labels: videoRaw.map(d => d.child_name),
                datasets: [{
                    data: videoRaw.map(d => d.percentage),
                    backgroundColor: colorPalette
                }]
            },
            options: pieOptions
        });
        */

        // 2. Video Bar Graph
        const videoBarRaw = @json($videoBarData);
        const barLabels = videoBarRaw.map(d => d.category_name);
        const bar1114   = videoBarRaw.map(d => d['11-14']);
        const bar1518   = videoBarRaw.map(d => d['15-18']);

        const maxVal = Math.max(...bar1114, ...bar1518);
        const yMax   = maxVal <= 0 ? 10 : Math.min(100, Math.ceil(maxVal / 5) * 5 + 5);

        new Chart(document.getElementById('videoBarChart'), {
            type: 'bar',
            data: {
                labels: barLabels,
                datasets: [
                    {
                        label: '11-14 yrs',
                        data: bar1114,
                        backgroundColor: 'rgba(153, 102, 255, 0.75)',
                        borderColor: 'rgba(153, 102, 255, 1)',
                        borderWidth: 1
                    },
                    {
                        label: '15-18 yrs',
                        data: bar1518,
                        backgroundColor: 'rgba(75, 192, 170, 0.75)',
                        borderColor: 'rgba(75, 192, 170, 1)',
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
                        ticks: { callback: v => v + '%' }
                    },
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 30,
                            font: { size: 11 }
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

        // 3. Mood Pie Chart
        /*
        new Chart(document.getElementById('moodPieChart'), {
            type: 'pie',
            data: {
                labels: @json($moodLabels),
                datasets: [{
                    data: @json($moodCounts),
                    backgroundColor: colorPalette
                }]
            },
            options: {
                ...pieOptions,
                plugins: {
                    ...pieOptions.plugins,
                    tooltip: {
                        callbacks: {
                            label: (ctx) => `${ctx.label}: ${ctx.raw} (${@json($moodChildMap)[ctx.label]})`
                        }
                    }
                }
            }
        });
        */

        // 3. Mood Bar Chart (replaces Mood Pie Chart)
        const moodLabels = @json($moodLabels);
        const moodCounts = @json($moodCounts);
        const moodChildMap = @json($moodChildMap);

        const moodMaxVal   = moodCounts.length > 0 ? Math.max(...moodCounts) : 1;
        const moodYMax     = moodMaxVal <= 0 ? 5 : moodMaxVal + Math.ceil(moodMaxVal * 0.2);
        const moodStepSize = moodYMax <= 5  ? 1
                        : moodYMax <= 20 ? 2
                        : moodYMax <= 50 ? 5
                        : 10;

        const moodColors = [
            'rgba(54,  162, 235, 0.75)',
            'rgba(255, 99,  132, 0.75)',
            'rgba(255, 206, 86,  0.75)',
            'rgba(75,  192, 192, 0.75)',
            'rgba(153, 102, 255, 0.75)',
            'rgba(255, 159, 64,  0.75)',
            'rgba(99,  255, 132, 0.75)',
        ];
        const moodBorderColors = moodColors.map(c => c.replace('0.75', '1'));

        new Chart(document.getElementById('moodBarChart'), {
            type: 'bar',
            data: {
                labels: moodLabels,
                datasets: [{
                    label: 'Mood Count',
                    data: moodCounts,
                    backgroundColor: moodLabels.map((_, i) => moodColors[i % moodColors.length]),
                    borderColor:     moodLabels.map((_, i) => moodBorderColors[i % moodBorderColors.length]),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: moodYMax,
                        ticks: {
                            stepSize: moodStepSize,
                            callback: v => v
                        }
                    },
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 30,
                            font: { size: 11 }
                        }
                    }
                },
                plugins: {
                    legend: { display: false },   // no legend needed — bar colors = moods
                    tooltip: {
                        callbacks: {
                            label: ctx => {
                                const label  = ctx.label;
                                const count  = ctx.raw;
                                const names  = moodChildMap[label] ?? '';
                                return names
                                    ? `${count} entries — ${names}`
                                    : `${count} entries`;
                            }
                        }
                    }
                }
            }
        });

    </script>
@endsection
