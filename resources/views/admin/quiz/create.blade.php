@extends('layout.headerFooter')

@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="title_left">
                <h2>Add Quiz</h2>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <ul class="admin-breadcrumb">
                        <li>
                            <a href="{{ route('quizCategory.index') }}">Quiz Category</a>
                        </li>
                        <li>
                            <a href="{{ route('quiz.index', ['id' => $id]) }}"> {{ $categoryName }}</a>
                        </li>
                        <li class="active">
                            <a href="#">Add Quiz</a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-12">
                    <div class="card card-default">
                        <div class="card-body">
                            <form action="{{ route('quiz.store', ['id' => $id]) }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="category" value="{{ $id }}">

                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="title">Quiz Title</label>
                                            <input type="text" id="title" class="form-control" name="title"
                                                value="{{ old('title') }}" placeholder="Enter Quiz Title">
                                            @error('title')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="age">Select Age Range</label>
                                        <select class="form-control" id="age" name="age">
                                            <option value="">Select Age Range</option>
                                            <option value="11-14" {{ old('age') == '11-14' ? 'selected' : '' }}>Age (11-14)
                                            </option>
                                            <option value="15-18" {{ old('age') == '15-18' ? 'selected' : '' }}>Age (15-18)
                                            </option>
                                        </select>
                                        @error('age')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-6">
                                        <label for="image">Banner Image</label>
                                        <input type="file" class="form-control-file" name="image" id="image">
                                        <img id="imagePreview" src="#" alt="Image Preview"
                                            style="display:none; max-width: 200px; margin-top: 10px;
                                         border: 1px solid #ddd; border-radius: 5px;" />
                                        @error('image')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="form-footer mt-4">
                                    <button type="submit" class="btn btn-primary btn-pill">Submit</button>

                                    <a href="{{ route('quiz.index', ['id' => $id]) }}">
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
        document.getElementById('image').addEventListener('change', function(event) {
            let input = event.target;
            if (input.files && input.files[0]) {
                let reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('imagePreview').src = e.target.result;
                    document.getElementById('imagePreview').style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            } else {
                document.getElementById('imagePreview').style.display = 'none';
            }
        });
    </script>
@endpush
