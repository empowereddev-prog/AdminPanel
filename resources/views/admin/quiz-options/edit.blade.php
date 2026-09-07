@extends('layout.headerFooter')
@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="title_left">
                <h2>Edit Options</h2>
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
                                href="{{ route('question-options', ['id' => $question_id, 'category_id' => $category_id, 'quiz_id', $quiz_id]) }}">Quiz
                                Options</a>
                        </li>
                        <li class="active">
                            <a href="#">Edit Options</a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="row">
                <div class="col-xl-12">
                    <div class="card card-default">
                        <div class="card-body">
                            <form id="optionForm"
                                action="{{ route('question-options.update', ['id' => $question_id, 'category_id' => $category_id, 'quiz_id' => $quiz_id]) }}"
                                enctype="multipart/form-data" method="post">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="question_id" value="{{ $question_id }}">
                                <input type="hidden" name="quiz_id" value="{{ $quiz_id }}">
                                <input type="hidden" name="category_id"  value="{{  $category_id }}">
                                <input type="hidden" name="marks" value="{{ $marks }}">


                                <div class="row">
                                    <div class="col-md-8">
                                        <label for="options">Edit Options</label>
                                        @foreach ($options as $index => $option)
                                            <div class="form-group align-items-center">
                                                <input type="text" class="form-control mr-2" name="options[]"
                                                    placeholder="Enter Option {{ $index + 1 }}"
                                                    value="{{ old('options.' . $index, $option->option_text) }}" required>

                                                <input type="radio" name="is_correct" value="{{ $index }}"
                                                    {{ old('is_correct', $correct_option_index) == $index ? 'checked' : '' }}>
                                                Correct Answer
                                            </div>
                                            @error('options.' . $index)
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror

                                            <div class="form-group mb-3 option-description description-english"
                                                id="description-container-{{ $index }}" style="display: none;">
                                                <label>Explanation for Option {{ $index + 1 }}</label>
                                                <textarea name="descriptions[{{ $index }}]" class="form-control" rows="4">{{ old('descriptions.' . $index, $option->description ?? '') }}</textarea>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="form-footer mt-6">
                                    <button type="submit" class="btn btn-primary btn-pill mr-2">Update</button>
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
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            function toggleDescriptions() {
                const selectedRadio = document.querySelector('input[name="is_correct"]:checked');
                const selectedIndex = selectedRadio ? selectedRadio.value : null;

                // Hide all descriptions and remove required attribute
                document.querySelectorAll('.option-description').forEach(container => {
                    container.style.display = 'none';
                    const textarea = container.querySelector('textarea');
                    if (textarea) textarea.required = false;
                });

                // Show and set required for selected descriptions
                if (selectedIndex !== null) {
                    const englishContainer = document.getElementById(`description-container-${selectedIndex}`);
                    const chineseContainer = document.getElementById(
                        `description-chinese-container-${selectedIndex}`);

                    if (englishContainer) {
                        englishContainer.style.display = 'block';
                        const textarea = englishContainer.querySelector('textarea');
                        if (textarea) textarea.required = true;
                    }

                    if (chineseContainer) {
                        chineseContainer.style.display = 'block';
                        const textarea = chineseContainer.querySelector('textarea');
                        if (textarea) textarea.required = true;
                    }
                }
            }

            document.querySelectorAll('input[name="is_correct"]').forEach(rb => {
                rb.addEventListener('change', toggleDescriptions);
            });

            // Initial display setup
            toggleDescriptions();
        });
    </script>
@endsection
