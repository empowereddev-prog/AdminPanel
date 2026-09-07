@extends('layout.headerFooter')
@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="title_left">
                <h2>Add Question</h2>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <ul class="admin-breadcrumb">
                        <li>
                            <a href="{{ route('quizCategory.index') }}">Quiz Category -> {{ $categoryName }} </a>
                        </li>
                        <li>
                            <a href="{{ route('quiz.index', ['id' => $category_id]) }}">Quiz</a>
                        </li>
                        <li> <a href="{{ route('quiz-questions.index', ['id' => $id, 'category_id' => $category_id]) }}">
                                Question</a></li>
                        <li class="active">
                            <a href="#">Add Question</a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="row">
                <div class="col-xl-12">
                    <div class="card card-default">

                        <div class="card-body">
                            <form id="bannerForm"
                                action="{{ route('quiz-questions.store', ['id' => $id, 'category_id' => $category_id]) }}"
                                enctype="multipart/form-data" method="post">
                                @csrf

                                <input type="hidden" name="category" value="{{ $category_id }}">
                                <input type="hidden" name="quiz_id" value="{{ $id }}">
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="group_name">Question</label>
                                            <input type="text" id="question" class="form-control" name="question"
                                                value="{{ old('question') }}" placeholder="Enter Question">
                                            @error('question')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="group_name">Battery (%)</label>
                                            <input type="text" id="marks" class="form-control" name="marks"
                                                value="{{ old('marks') }}" placeholder="Enter Points">
                                            @error('marks')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                {{-- <div class="row mt-2">
                                    <div class="col-md-6" id="ageRangeWrapper">
                                        <label for="age">Select Age Range</label>
                                        <select class="form-control" id="age" name="age">
                                            <option value="">Select Age Range</option>
                                            <option value="11-14" {{ old('age') == '11-14' ? 'selected' : '' }}>Age(11-14)
                                            </option>
                                            <option value="15-18" {{ old('age') == '15-18' ? 'selected' : '' }}>Age(15-18)
                                            </option>
                                           
                                        </select>
                                        @error('age')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="exampleFormControlFile1">Banner Image</label>
                                        <input type="file" class="form-control-file" name="image"
                                            id="exampleFormControlFile1" value="{{ old('image') }}">
                                        <canvas id="canvas" style="display:none;"></canvas>

                                        <!-- Image preview element -->
                                        <img id="imagePreview" src="#" alt="Image Preview"
                                            style="display:none; max-width: 200px; margin-top: 10px; border: 1px solid #ddd; border-radius: 5px;" />

                                        @if ($errors->has('image'))
                                            <div class="text-danger small mt-1">
                                                {{ $errors->first('image') }}
                                            </div>
                                        @endif
                                    </div>

                                </div> --}}
                                <div class="form-footer mt-6">
                                    <button type="submit" class="btn btn-primary btn-pill mr-2">Submit</button>
                                    <a
                                        href="{{ route('quiz-questions.index', ['id' => $id, 'category_id' => $category_id]) }}">
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

            $("#exampleFormControlFile1").on("change", function(event) {
                let input = event.target;
                if (input.files && input.files[0]) {
                    let reader = new FileReader();
                    reader.onload = function(e) {
                        $("#imagePreview")
                            .attr("src", e.target.result)
                            .fadeIn();
                    }
                    reader.readAsDataURL(input.files[0]);
                } else {
                    $("#imagePreview").hide();
                }
            });
        });
    </script>
@endpush
