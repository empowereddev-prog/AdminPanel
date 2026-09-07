@extends('layout.headerFooter')
@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="title_left">
                <h2>Edit category</h2>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <ul class="admin-breadcrumb">
                        <li>
                            <a href="{{ route('quizCategory.index') }}">Quiz Category</a>
                        </li>
                        <li class="active">
                            <a href="#">Add category</a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="row">
                <div class="col-xl-12">
                    <div class="card card-default">
                        <div class="card-body">
                            <form id="bannerForm" action="{{ route('quizCategory.update', $data->id) }}"
                                enctype="multipart/form-data" method="post">
                                @csrf
                                @method('PUT')

                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="group_name">Category Name</label>
                                            <input type="text" class="form-control" name="category_name"
                                                id="category_name" placeholder="Enter Category Name"
                                                value="{{ $data->category_name ? $data->category_name : '' }}">
                                            @error('category_name')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    {{-- <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="category_name_chinese">Category Name(In Chinese)</label>
                                        <input type="text" class="form-control" name="category_name_chinese" id="category_name_chinese"
                                            placeholder="Enter Category Name" value="{{$data->category_name_chinese ? $data->category_name_chinese : ''}}">
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
                                                <option disabled {{ $selectedColor == '' ? 'selected' : '' }}>Select Color
                                                </option>
                                                @foreach ($colors as $color)
                                                    <option value="{{ $color->color }}"
                                                        style="background-color: {{ $color->color }}; color: {{ $color->color == '#ffffff' ? '#000' : '#fff' }};"
                                                        {{ $selectedColor == $color->color ? 'selected' : '' }}>
                                                        {{ strtoupper($color->color) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('color')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                @php
                                    $selectedTitleColor = old('title_color', $data->title_color ?? '');
                                    $titleColors = $colors->pluck('title_color')->unique()->filter();
                                @endphp
                                <div class="row mt-2">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="title_color">Select Title Color</label>
                                            <select name="title_color" class="form-control">
                                                <option disabled {{ $selectedTitleColor == '' ? 'selected' : '' }}>Select
                                                    Title Color</option>
                                                @foreach ($titleColors as $titleColor)
                                                    <option value="{{ $titleColor }}"
                                                        style="background-color: {{ $titleColor }}; color: {{ $titleColor == '#ffffff' ? '#000' : '#fff' }};"
                                                        {{ $selectedTitleColor == $titleColor ? 'selected' : '' }}>
                                                        {{ strtoupper($titleColor) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('title_color')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <!--  -->
                                <div class="row mt-4">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="description">Description</label>
                                            <textarea class="form-control" name="description" id="description" placeholder="Enter Quiz Description">{{ old('description', $data->description ?? '') }}</textarea>
                                            @error('description')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <!-- Description -->
                                    {{-- <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="description_chinese">Description(In Chinese)</label>
                                        <textarea class="form-control" name="description_chinese" id="description_chinese" placeholder="Enter Quiz Description">{{ old('description_chinese', $data->description_chinese ?? '') }}</textarea>
                                        @error('description_chinese')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div> --}}
                                </div>


                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="exampleFormControlFile1">Status</label>
                                            <select name="status" class="form-control" id="optionSelect">
                                                <option value="">Select Status</option>
                                                <option value="active" {{ $data->status == 'active' ? 'selected' : '' }}>
                                                    Active</option>
                                                <option value="inactive"
                                                    {{ $data->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                            </select>
                                            @if ($errors->has('status'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('status') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-4">

                                </div>
                                <!--  -->
                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="exampleFormControlFile1">Image</label>
                                            <input type="file" class="form-control-file" name="banner_image"
                                                id="exampleFormControlFile1" value="{{ $data->banner_image }}">
                                            @if ($errors->has('banner_image'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('banner_image') }}
                                                </div>
                                            @endif

                                        </div>
                                        <br>
                                        <img src="{{ asset('assets/images/' . $data->banner_image) }}" height="60"
                                            width="60" alt="image">
                                    </div>
                                </div>



                                <div class="form-footer mt-6">
                                    <button type="submit" class="btn btn-primary btn-pill mr-2">Update</button>
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
            $('#video').select2({
                placeholder: "Select referred videos",
                //  width: '100%'
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
        });
        $(document).ready(function() {
            // Initialize CKEditor
            $('#description').each(function() {
                ClassicEditor.create(this).catch(error => {
                    console.error(error);
                });
            });
            $('#description_chinese').each(function() {
                ClassicEditor.create(this).catch(error => {
                    console.error(error);
                });
            });
        });
    </script>
@endpush
