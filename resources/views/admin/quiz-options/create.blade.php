@extends('layout.headerFooter')
@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="title_left">
                <h2>Add Options</h2>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <ul class="admin-breadcrumb">
                        <li>
                            <a href="{{ route('quizCategory.index') }}">Quiz Category</a>
                        </li>
                        <li>
                            <a href="{{ route('quiz.index', ['id' => $category_id]) }}">
                                Quiz </a>
                        </li>
                        <li>
                            <a href="{{ route('quiz-questions.index', ['id' => $quiz_id, 'category_id' => $category_id]) }}">
                                Quiz Question</a>
                        </li>
                        
                        <li>
                            <a
                                href="{{ route('question-options', ['id' => $question_id, 'category_id' => $category_id, 'quiz_id' => $quiz_id]) }}">Quiz
                                Options</a>
                        </li>
                        <li class="active">
                            <a href="#">Add Options</a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="row">
                <div class="col-xl-12">
                    <div class="card card-default">
                        <div id="page-loader"
                            style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
                            background:rgba(255,255,255,0.7); z-index:9999; text-align:center; padding-top:20%;">
                            <div class="spinner-border text-primary" role="status" style="width:3rem; height:3rem;">
                                <span class="sr-only">Loading...</span>
                            </div>
                            <p class="mt-2 text-dark">Please wait...</p>
                        </div>
                        <div class="card-body">
                            <form id="bannerForm"
                                action="{{ route('question-options.store', ['category_id' => $category_id, 'quiz_id' => $quiz_id]) }}"
                                enctype="multipart/form-data" method="post">
                                @csrf
                                <input type="hidden" name="question_id" value="{{ $question_id }}">
                                <input type="hidden" name="quiz_id" value="{{ $quiz_id }}">
                                <input type="hidden" name="marks" id="marks"value="{{ $marks }}">
                                <div class="row">
                                    <div class="col-md-8">
                                        <label for="options">Enter Options</label>
                                        @for ($i = 1; $i <= 4; $i++)
                                            <div class="form-group align-items-center mt-2">
                                                <input type="text" class="form-control mr-2" name="options[]"
                                                    placeholder="Enter Option {{ $i }}"
                                                    value="{{ old('options.' . ($i - 1)) }}">
                                                <input type="radio" name="is_correct" value="{{ $i - 1 }}"
                                                    {{ old('is_correct') == $i - 1 ? 'checked' : '' }} class="mt-2">
                                                Correct Answer
                                            </div>
                                            @error('options.' . ($i - 1))
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        @endfor
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-8">
                                        <label for="description">Enter Explanation</label>
                                        <textarea name="description" id="description" class="form-control" rows="4" placeholder="Enter explanation">{{ old('description') }}</textarea>
                                        @error('description')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                {{-- <div class="row">
                                <div class="col-md-8">
                                    <label for="description">Enter Explanation (In Chinese )</label>
                                    <textarea name="description_chinese" id="description_chinese" class="form-control" rows="4" placeholder="Enter explanation">{{ old('description_chinese') }}</textarea>
                                    @error('description_chinese')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div> --}}


                                <div class="form-footer mt-6">
                                    <button type="submit" class="btn btn-primary btn-pill mr-2">Submit</button>
                                    <a
                                        href="{{ route('question-options', ['id' => $question_id, 'category_id' => $category_id, 'quiz_id' => $quiz_id]) }}">
                                        <button type="button" class="btn btn-primary btn-pill mr-2">Cancel</button>
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        $(document).ready(function() {
            // Global Ajax Start/Stop loader
            $(document).ajaxStart(function() {
                $("#page-loader").fadeIn();
            }).ajaxStop(function() {
                $("#page-loader").fadeOut();
            });
            // Agar form submit par loader chahiye
            $("form").on("submit", function() {
                $("#page-loader").fadeIn();
            });
        });
    </script>
@endpush
