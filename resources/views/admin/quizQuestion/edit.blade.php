@extends('layout.headerFooter')
@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="title_left">
                <h2>Edit Question</h2>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <ul class="admin-breadcrumb">
                        <li>
                            <a href="{{ route('quizCategory.index') }}">Quiz
                                Quiz {{ $categoryName }} Category</a>
                        </li>
                        <li>
                            <a href="{{ route('quiz.index', ['id' => $question->quiz_category_id]) }}">Quiz</a>
                        </li>
                        <li><a href="{{ route('quiz-questions.index', ['id' => $question->quiz_id,'category_id' => $question->quiz_category_id]) }}"> Question</a></li>
                        <li class="active">
                            <a href="#">Edit question</a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="row">
                <div class="col-xl-12">
                    <div class="card card-default">
                        <div class="card-body">
                            <form id="bannerForm" 
                            action="{{ route('quiz-questions.update', ['id' => $id, 'category_id' => $category_id]) }}"
                            enctype="multipart/form-data" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="category" value="{{ $question->quiz_category_id }}">
                                <input type="hidden" name="quiz_id" value="{{ $question->quiz_id }}">
                              
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="group_name">Question</label>
                                            <input type="text" class="form-control" name="question" id="question"
                                                placeholder="Enter Category Name"
                                                value="{{ $question->question ? $question->question : '' }}"
                                                maxlength="100">
                                            @error('question')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="group_name">Battery (%)</label>
                                            <input type="text" class="form-control" name="marks" id="marks"
                                                placeholder="Enter Points"
                                                value="{{ $question->marks ? $question->marks : '' }}" maxlength="100">
                                            @error('marks')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="exampleFormControlFile1">Status</label>
                                            <select name="status" class="form-control" id="optionSelect">
                                                <option value="">Select Status</option>
                                                <option value="active"
                                                    {{ $question->status == 'active' ? 'selected' : '' }}>Active</option>
                                                <option value="inactive"
                                                    {{ $question->status == 'inactive' ? 'selected' : '' }}>Inactive
                                                </option>
                                            </select>
                                            @if ($errors->has('status'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('status') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                {{-- <div class="row mt-2">
                                    <div class="col-md-6">
                                        <label for="age">Select Age Range</label>
                                        <select class="form-control" id="age" name="age">
                                            <option value="">Select Age Range</option>
                                            <option value="11-14"
                                                {{ old('age', $question->age) == '11-14' ? 'selected' : '' }}>Age(11-14)
                                            </option>
                                            <option value="15-18"
                                                {{ old('age', $question->age) == '15-18' ? 'selected' : '' }}>Age(15-18)
                                            </option>
                                          
                                        </select>
                                    </div>

                                    <!-- ✅ Banner Image upload + preview -->
                                    <div class="col-md-6">
                                        <label for="exampleFormControlFile1">Banner Image</label>
                                        <input type="file" class="form-control-file" name="image"
                                            id="exampleFormControlFile1">

                                        @if ($question->image)
                                            <img id="imagePreview" src="{{ asset('assets/images/' . $question->image) }}"
                                                style="display:block; max-width: 200px; margin-top: 10px; border: 1px solid #ddd; border-radius: 5px;" />
                                        @else
                                            <img id="imagePreview" src="#"
                                                style="display:none; max-width: 200px; margin-top: 10px; border: 1px solid #ddd; border-radius: 5px;" />
                                        @endif
                                    </div>
                                </div> --}}
                                <div class="form-footer mt-6">
                                    <button type="submit" class="btn btn-primary btn-pill mr-2">Update</button>
                                    <a href="{{ route('quiz-questions.index', ['id' => $question->quiz_id,'category_id' => $question->quiz_category_id]) }}">
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

    @push('scripts')
        <script>
            $("#exampleFormControlFile1").on("change", function(event) {
                let input = event.target;
                if (input.files && input.files[0]) {
                    let reader = new FileReader();
                    reader.onload = function(e) {
                        $("#imagePreview").attr("src", e.target.result).fadeIn();
                    }
                    reader.readAsDataURL(input.files[0]);
                } else {
                    $("#imagePreview").hide();
                }
            });
        </script>
    @endpush
@endsection
