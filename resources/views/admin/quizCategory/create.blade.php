@extends('layout.headerFooter')
@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="title_left">
                <h2>Add Category</h2>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <ul class="admin-breadcrumb">
                        <li>
                            <a href="{{ route('quizCategory.index') }}">Quiz Category</a>
                        </li>
                        <li class="active">
                            <a href="#">Add Category</a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-12">
                    <div class="card card-default">
                        <div class="card-body">
                            <form id="bannerForm" action="{{ route('quizCategory.store') }}" enctype="multipart/form-data"
                                method="post">
                                @csrf

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="group_name">Category Name </label>
                                            <input type="text" class="form-control" name="category_name"
                                                id="category_name" placeholder="Enter Category Name"
                                                value="{{ old('category_name') }}">
                                            @error('category_name')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    {{-- <div class="col-md-5">
                                    <div class="form-group">
                                        <label for="category_name_chinese">Category Name (In Chinese)</label>
                                        <input type="text" class="form-control" name="category_name_chinese"
                                            id="category_name_chinese" placeholder="Enter Category Name"
                                            value="{{ old('category_name_chinese') }}">
                                        @error('category_name_chinese')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div> --}}

                                    @php
                                        $selectedColor = old('color', $data->color ?? '');
                                    @endphp

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="color">Select Color</label>
                                            <select name="color" class="form-control">
                                                <option disabled {{ $selectedColor == '' ? 'selected' : '' }}>
                                                    Select
                                                    Color
                                                </option>

                                                <option value="#1A5EDB" style="background-color:#1A5EDB; color: #fff;"
                                                    {{ $selectedColor == '#1A5EDB' ? 'selected' : '' }}>
                                                    Blue
                                                </option>

                                                <option value="#FB72CC" style="background-color:#FB72CC; color: #fff;"
                                                    {{ $selectedColor == '#FB72CC' ? 'selected' : '' }}>
                                                    Pink
                                                </option>

                                                <option value="#1D987C" style="background-color:#1D987C; color: #fff;"
                                                    {{ $selectedColor == '#1D987C' ? 'selected' : '' }}>
                                                    Emerald Green
                                                </option>

                                                <option value="#F3E75F" style="background-color:#F3E75F; color: #000;"
                                                    {{ $selectedColor == '#F3E75F' ? 'selected' : '' }}>
                                                    Yellow
                                                </option>

                                                <option value="#BAE5F5" style="background-color:#BAE5F5; color: #000;"
                                                    {{ $selectedColor == '#BAE5F5' ? 'selected' : '' }}>
                                                    Sky Blue
                                                </option>

                                                <option value="#FE564B" style="background-color:#FE564B color: #fff;"
                                                    {{ $selectedColor == '#FE564B' ? 'selected' : '' }}>
                                                    Coral Red
                                                </option>

                                                <option value="#7A36AD" style="background-color:#7A36AD; color: #fff;"
                                                    {{ $selectedColor == '#7A36AD' ? 'selected' : '' }}>
                                                    Violet
                                                </option>
                                            </select>

                                            @if ($errors->has('color'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('color') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-2">
                                    @php
                                        $selectedTitleColor = old('title_color', $data->title_color ?? '');
                                    @endphp

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="title_color">Select Title Color</label>
                                            <select name="title_color" class="form-control">
                                                <option disabled {{ $selectedTitleColor == '' ? 'selected' : '' }}>
                                                    Select Title Color
                                                </option>

                                                <option value="#000000" style="background-color:#0c0c0c; color: #fff;"
                                                    {{ $selectedTitleColor == '#000000' ? 'selected' : '' }}>
                                                    Black
                                                </option>

                                                <option value="#ffffff" style="background-color:#fff; color: back;"
                                                    {{ $selectedTitleColor == '#ffffff' ? 'selected' : '' }}>
                                                    White
                                            </select>

                                            @if ($errors->has('title_color'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('color') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    {{-- <div class="col-md-6">
                                        <label for="video">Referred Video</label>
                                        <select class="form-control select2" name="video[]" id="video" multiple
                                            data-tags="false">
                                            @foreach ($video_title as $title)
                                                <option value="{{ $title->id }}"
                                                    {{ in_array($title->id, old('video', [])) ? 'selected' : '' }}>
                                                    {{ $title->title }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('video')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div> --}}
                                </div>

                                <div class="row mt-4">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="description">Description</label>
                                            <textarea class="form-control" name="description" id="description" placeholder="Enter Quiz Description">{{ old('description') }}</textarea>
                                            @error('description')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                {{-- <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="description_chinese">Description (In Chinese)</label>
                                        <textarea class="form-control" name="description_chinese" id="description_chinese"
                                            placeholder="Enter Quiz Description">{{ old('description_chinese') }}</textarea>
                                        @error('description_chinese')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div> --}}



                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <label for="exampleFormControlFile1">Banner Image</label>
                                        <input type="file" class="form-control-file" name="banner_image"
                                            id="exampleFormControlFile1" value="{{ old('banner_image') }}">
                                        <canvas id="canvas" style="display:none;"></canvas>
                                        @if ($errors->has('banner_image'))
                                            <div class="text-danger small mt-1">
                                                {{ $errors->first('banner_image') }}
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="form-footer mt-5">
                                    <button type="submit" class="btn btn-primary btn-pill mr-2">Submit</button>
                                    <a href="{{ route('quizCategory.index') }}">
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

    <script>
        document.getElementById('bannerForm').addEventListener('submit', function(event) {
            console.log('Form submitted!');
        });
    </script>
@endsection

<script src="https://cdn.ckeditor.com/ckeditor5/23.0.0/classic/ckeditor.js"></script>

@push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('#video').select2({
                placeholder: "Select referred videos"
            });

            // Fix for overflowing selected tags in Select2
            setTimeout(function() {
                const select2Container = $('.select2-selection--multiple');

                // Style the container to limit height and allow scroll
                select2Container.css({
                    'max-height': '100px',
                    'overflow-y': 'auto',
                    'white-space': 'normal'
                });

                // Truncate long tag names
                $('.select2-selection__choice').css({
                    'max-width': '100%',
                    'overflow': 'hidden',
                    'text-overflow': 'ellipsis',
                    'white-space': 'nowrap'
                });

                // Make sure Select2 fits the form width
                $('.select2-container').css('width', '100%');
            }, 100); // Timeout ensures Select2 has rendered

            // Initialize CKEditor
            ClassicEditor.create(document.querySelector('#description')).catch(error => {
                console.error(error);
            });

            ClassicEditor.create(document.querySelector('#description_chinese')).catch(error => {
                console.error(error);
            });
        });
    </script>
@endpush
