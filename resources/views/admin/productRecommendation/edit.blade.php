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
                            <h2>Edit Resource</h2>
                        </div>

                        <div class="card-body">
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

                                {{-- OTHER FIELDS --}}
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label>Title</label>
                                            <input type="text" class="form-control" name="title"
                                                value="{{ old('title', $product->title) }}">
                                            @error('title')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-12 mt-2">
                                        <div class="form-group">
                                            <label>URL</label>
                                            <input type="url" class="form-control" name="url"
                                                value="{{ old('url', $product->url) }}">
                                            @error('url')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-12 mt-2">
                                        <div class="form-group">
                                            <label>Description</label>
                                            <textarea name="description" id="description" class="form-control" rows="3">{{ old('description', $product->description) }}</textarea>
                                            @error('description')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-12 mt-2">
                                        <div class="form-group">
                                            <label>Upload New Image (Optional)</label>
                                            <input type="file" name="image" id="image" class="form-control">
                                            @error('image')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>

                                        <div class="mt-2" id="image-preview-container">
                                            @if ($product->image)
                                                <img src="{{ getImagePathUrl($product->image, 'uploads/product') }}"
                                                    class="img-fluid rounded"
                                                    style="max-height:150px;border:1px solid #ddd;padding:5px;">
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-md-12 mt-2">
                                        <div class="form-group">
                                            <label>Status</label>
                                            <select name="status" class="form-control">
                                                <option value="active"
                                                    {{ old('status', $product->status) === 'active' ? 'selected' : '' }}>
                                                    Active
                                                </option>
                                                <option value="inactive"
                                                    {{ old('status', $product->status) === 'inactive' ? 'selected' : '' }}>
                                                    Inactive
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-footer mt-4">
                                    <button type="submit" class="btn btn-primary btn-pill">Update</button>
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
    <script>
        $(document).ready(function() {
            $('#description').summernote({
                height: 300,
                dialogsInBody: true,
                disableResizeEditor: true
            });
            // CKEditor
            // ClassicEditor.create(document.querySelector('#description'));

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

            // Image preview
            $('#image').on('change', function() {
                let container = $('#image-preview-container');
                container.empty();
                let file = this.files[0];
                if (file) {
                    let reader = new FileReader();
                    reader.onload = e => {
                        $('<img>', {
                            src: e.target.result,
                            class: 'img-fluid rounded',
                            style: 'max-height:150px;border:1px solid #ddd;padding:5px;'
                        }).appendTo(container);
                    };
                    reader.readAsDataURL(file);
                }
            });

        });
    </script>
@endpush
