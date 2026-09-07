@extends('layout.headerFooter')

@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="title_left">
                <h2>User Attempt Quiz</h2>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <ul class="admin-breadcrumb">
                        <li>
                            <a href="{{ route('users-attempt-quizzes') }}">User Attempt Quiz</a>
                        </li>
                        <li class="active">
                            <a href="#">User Attempt Quiz Details</a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-12">
                    <div class="card card-default">
                        <div class="card-body">
                            @foreach ($quizzes as $quiz)
                                <div class="quiz-section mb-4">
                                    <div class="card p-3 mb-2">
                                        <h6 class="quiz-title">
                                            Quiz: {{ $quiz['quiz_name'] }}
                                            <span class="text-muted">(Category:
                                                {{ $quiz['category_name'] ?? 'N/A' }})</span>
                                        </h6>
                                        <div class="total-score">
                                            <label for="score" class="col-form-label">
                                                Score: {{ $quiz['marks_obtained'] }}/{{ $quiz['total_marks'] }}
                                            </label>
                                        </div>
                                    </div>

                                    @if (!empty($quiz['questions']))
                                        <div class="card p-3">
                                            <h5>Attempted Questions:</h5>
                                            @foreach ($quiz['questions'] as $question)
                                                <div class="question-box mb-3 p-3 border rounded">
                                                    <p><strong>Question:</strong> {{ $question['question'] }}</p>
                                                    <p><strong>Marks Obtained:</strong> {{ $question['marks'] }} /
                                                        {{ $question['total_marks'] }}</p>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach

                            <div class="row mt-2">
                                <div class="col-12">
                                    <button type="button" class="btn btn-default mb-2"
                                        onclick="window.history.back()">Back</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('inlinescript')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
@endpush
