@extends('layout.headerFooter')
@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="title_left">
                <h2>Edit Quiz</h2>
            </div>

            <ul class="admin-breadcrumb">
                <li><a href="{{ route('quizCategory.index') }}">Quiz Category</a></li>
                <li><a href="{{ route('quiz.index', ['id' => $quiz->quiz_category_id]) }}">
                        - {{ $categoryName }}</a></li>
                <li><a href="#">Quiz Edit</a></li>
            </ul>

            <div class="card card-default">
                <div class="card-body">
                    <form action="{{ route('quiz.update', $quiz->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('POST')

                        <input type="hidden" name="category" value="{{ $quiz->quiz_category_id }}">
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Quiz Title</label>
                                    <input type="text" class="form-control" name="title"
                                        value="{{ old('title', $quiz->title) }}">
                                    @error('title')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Status</label>
                                    <select name="status" class="form-control">
                                        <option value="active" {{ $quiz->status == 'active' ? 'selected' : '' }}>Active
                                        </option>
                                        <option value="inactive" {{ $quiz->status == 'inactive' ? 'selected' : '' }}>
                                            Inactive
                                        </option>
                                    </select>
                                    @error('status')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>


                        <div class="row mt-3">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Age Range</label>
                                    <select name="age" class="form-control">
                                        <option value="">Select Age Range</option>
                                        <option value="11-14" {{ old('age', $quiz->age) == '11-14' ? 'selected' : '' }}>
                                            11-14
                                        </option>
                                        <option value="15-18" {{ old('age', $quiz->age) == '15-18' ? 'selected' : '' }}>
                                            15-18
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Banner Image</label>
                                    <input type="file" name="image" class="form-control-file">
                                    @if ($quiz->image)
                                        <img id="imagePreview" src="{{ getImagePathUrl($quiz->image, 'assets/images') }}"
                                            style="max-width: 200px; margin-top: 10px; border: 1px solid #ddd; border-radius: 5px;" />
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="form-footer mt-4">
                            <button type="submit" class="btn btn-primary btn-pill">Update</button>

                            <a href="{{ route('quiz.index', ['id' => $quiz->quiz_category_id]) }}">
                                <button type="button" class="btn btn-primary btn-pill mr-2">Cancel</button>
                            </a>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            $("input[name='image']").on("change", function(e) {
                const input = e.target;
                if (input.files && input.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $("#imagePreview").attr("src", e.target.result).fadeIn();
                    }
                    reader.readAsDataURL(input.files[0]);
                }
            });
        </script>
    @endpush
@endsection
