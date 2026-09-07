@extends('layout.headerFooter')

@section('content')
    <style>
        .color-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .color-box {
            width: 42px;
            height: 42px;
            border-radius: 6px;
            cursor: pointer;
            border: 2px solid transparent;
            position: relative;
            transition: all 0.2s ease;
        }


        .color-box:hover {
            transform: scale(1.08);
        }

        .color-box.active {
            outline: 3px solid #fff;
            box-shadow: 0 0 0 2px #000;
        }

        .color-box .checkmark {
            display: none;
            color: #fff;
            font-size: 18px;
            font-weight: bold;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .color-box.active .checkmark {
            display: block;
        }
    </style>

    @php
        // ✅ normalize values to avoid mismatch
        $selectedColor = strtolower(trim(old('color', $product->color)));
        $selectedTitleColor = old('color_title', $product->title_color);
    @endphp
    <div class="content-wrapper">
        <div class="content">
            <div class="row">
                <div class="col-xl-12">
                    <div class="card card-default">
                        <div class="card-header">
                            {{-- Changed Title --}}
                            <h2>View Resource</h2>
                        </div>
                        <div class="card-body">
                            {{-- Updated form action to the update route and added @method('PUT') --}}
                            <form action="{{ route('product.update', $product->id) }}" method="post"
                                enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                {{-- COLOR SECTION --}}
                                <div class="row">
                                    <div class="col-md-6 mt-3">
                                        <label class="mb-2 d-block">Select Color</label>

                                        <div class="color-grid">
                                            @foreach ($colors as $color)
                                                @php
                                                    $boxColor = strtolower(trim($color->color));
                                                @endphp
                                                <div class="color-box {{ $selectedColor == $boxColor ? 'active' : '' }}"
                                                    style="background-color: {{ $color->color }};"
                                                    data-color="{{ strtolower(trim($color->color)) }}"
                                                    data-title-color="{{ $color->title_color }}"
                                                    title="{{ $color->color }}">
                                                    <span class="checkmark">✓</span>
                                                </div>
                                            @endforeach
                                        </div>

                                        {{-- Hidden + readonly fields --}}
                                        <input type="hidden" name="color" id="color" value="{{ $selectedColor }}">

                                        @error('color')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="col-md-6 mt-3">
                                        <div class="form-group">
                                            <label>Title Color</label>
                                            <input type="text" name="color_title" id="color_title" class="form-control"
                                                readonly value="{{ $selectedTitleColor }}">
                                            @error('color_title')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label for="title">Title</label>
                                            {{-- Pre-filled value with existing product data --}}
                                            <input type="text" class="form-control" name="title" id="title"
                                                placeholder="Enter Title" value="{{ old('title', $product->title) }}"
                                                readonly>
                                            @if ($errors->has('title'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('title') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-12 mt-2">
                                        <div class="form-group">
                                            <label for="url"> URL</label>
                                            <input type="url" class="form-control" name="url" id="url"
                                                placeholder="https://abc.com/username"
                                                value="{{ old('url', $product->url) }}" readonly>
                                            @if ($errors->has('url'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('url') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    @php
                                        $selectedColor = old('color', isset($product) ? $product->color : '');
                                    @endphp

                                    {{-- <div class="col-12 mt-2">
                                        <div class="form-group">
                                            <label for="color">Select Color</label>
                                            <select name="color" class="form-control" disabled>
                                                <option disabled {{ $selectedColor == '' ? 'selected' : '' }}>Select Color
                                                </option>

                                                <option value="#1D4ED8" style="background-color:#1D4ED8; color: #fff;"
                                                    {{ $selectedColor == '#1D4ED8' ? 'selected' : '' }}>
                                                    Blue
                                                </option>

                                                <option value="#EC4899" style="background-color:#EC4899; color: #fff;"
                                                    {{ $selectedColor == '#EC4899' ? 'selected' : '' }}>
                                                    Pink
                                                </option>

                                                <option value="#10B981" style="background-color:#10B981; color: #fff;"
                                                    {{ $selectedColor == '#10B981' ? 'selected' : '' }}>
                                                    Emerald Green
                                                </option>

                                                <option value="#FACC15" style="background-color:#FACC15; color: #000;"
                                                    {{ $selectedColor == '#FACC15' ? 'selected' : '' }}>
                                                    Yellow
                                                </option>

                                                <option value="#A5F3FC" style="background-color:#A5F3FC; color: #000;"
                                                    {{ $selectedColor == '#A5F3FC' ? 'selected' : '' }}>
                                                    Sky Blue
                                                </option>

                                                <option value="#FB7185" style="background-color:#FB7185; color: #fff;"
                                                    {{ $selectedColor == '#FB7185' ? 'selected' : '' }}>
                                                    Coral Red
                                                </option>

                                                <option value="#8B5CF6" style="background-color:#8B5CF6; color: #fff;"
                                                    {{ $selectedColor == '#8B5CF6' ? 'selected' : '' }}>
                                                    Violet
                                                </option>
                                            </select>

                                            @if ($errors->has('color'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('color') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div> --}}
                                    <div class="col-md-12 mt-2">
                                        <div class="form-group ">
                                            <label for="description">Description</label>
                                            {{-- Pre-filled value for textarea --}}
                                            <textarea name="description" id="description" class="form-control" rows="2"
                                                placeholder="Type your description Here" disabled>{{ old('description', $product->description) }}</textarea>
                                            @if ($errors->has('description'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('description') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-12 mt-2">
                                        <div class="form-group">
                                            <label for="image">Upload New Image (Optional)</label><br>
                                            {{-- <input type="file" name="image" id="image" class="form-control"
                                                accept="image/*">
                                            @if ($errors->has('image'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('image') }}
                                                </div>
                                            @endif --}}
                                        </div>

                                        {{-- Show current image initially --}}
                                        <div class="mt-2" id="image-preview-container">
                                            @if ($product->image)
                                                <img src="{{ getImagePathUrl($product->image, 'uploads/product') }}"
                                                    alt="Current Image" class="img-fluid rounded"
                                                    style="max-height: 150px; border: 1px solid #ddd; padding: 5px;">
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-md-12 mt-2">
                                        <div class="form-group">
                                            <label for="status">Status</label>
                                            <select name="status" class="form-control" disabled>
                                                <option value="active"
                                                    {{ old('status', $product->status) === 'active' ? 'selected' : '' }}>
                                                    Active</option>
                                                <option value="inactive"
                                                    {{ old('status', $product->status) === 'inactive' ? 'selected' : '' }}>
                                                    Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-footer mt-4">
                                    {{-- Changed button text --}}
                                    {{-- <button type="submit" class="btn btn-primary btn-pill">Update</button> --}}
                                    <a href="{{ route('product.index') }}" class="btn btn-light">Cancel</a>
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
    {{-- This script block requires no changes. It works for both create and edit. --}}
    <script>
        $(document).ready(function() {
            ClassicEditor
                .create(document.querySelector('#description'))
                .then(editor => {
                    editor.isReadOnly = true;
                })
                .catch(error => {
                    console.error('CKEditor Error:', error);
                });

            $('#image').on('change', function() {
                const previewContainer = $('#image-preview-container');
                previewContainer.empty(); // Clear current image preview
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('<img>', {
                                'src': e.target.result,
                                'class': 'img-fluid rounded',
                                'style': 'max-height: 150px; border: 1px solid #ddd; padding: 5px;'
                            })
                            .appendTo(previewContainer);
                    };
                    reader.readAsDataURL(file);
                }
            });


            // 🔥 FORCE ACTIVE ON LOAD (FINAL GUARANTEE)
            let savedColor = $('#color').val();

            $('.color-box').each(function() {
                if ($(this).data('color') === savedColor) {
                    $(this).addClass('active');
                    $('#color_title').val($(this).data('title-color'));
                }
            });

            // On click
            $('.color-box').on('click', function() {
                $('.color-box').removeClass('active');
                $(this).addClass('active');

                $('#color').val($(this).data('color'));
                $('#color_title').val($(this).data('title-color'));
            });

        });
    </script>
@endpush
