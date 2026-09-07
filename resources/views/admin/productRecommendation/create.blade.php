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
        $oldColor = old('color');
        $oldTitleColor = old('color_title');
    @endphp

    <div class="content-wrapper">
        <div class="content">
            <div class="row">
                <div class="col-xl-12">
                    <div class="card card-default">
                        <div class="card-header">
                            <h2>Add Resource</h2>
                        </div>

                        <div id="page-loader"
                            style="display:none; position:fixed; inset:0;
                        background:rgba(255,255,255,0.7); z-index:9999;
                        text-align:center; padding-top:20%;">
                            <div class="spinner-border text-primary" style="width:3rem;height:3rem;"></div>
                            <p class="mt-2 text-dark">Please wait...</p>
                        </div>

                        <div class="card-body">
                            <form action="{{ route('product.store') }}" method="post" enctype="multipart/form-data">
                                @csrf

                                {{-- COLOR SECTION --}}
                                <div class="row">
                                    <div class="col-md-6 mt-3">
                                        <label class="mb-2 d-block">Select Color</label>

                                        <div class="color-grid">
                                            @foreach ($colors as $color)
                                                <div class="color-box {{ $oldColor === $color->color ? 'active' : '' }}"
                                                    style="background-color: {{ $color->color }};"
                                                    data-color="{{ $color->color }}"
                                                    data-title-color="{{ $color->title_color }}"
                                                    title="{{ $color->color }}">
                                                    <span class="checkmark">✓</span>
                                                </div>
                                            @endforeach
                                        </div>

                                        <input type="hidden" name="color" id="color" value="{{ $oldColor }}">

                                        @error('color')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="col-md-6 mt-3">
                                        <div class="form-group">
                                            <label>Title Color</label>
                                            <input type="text" name="color_title" id="color_title" class="form-control"
                                                readonly value="{{ $oldTitleColor }}" placeholder="Auto-filled">
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
                                                value="{{ old('title') }}" placeholder="Enter Title">
                                            @error('title')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-12 mt-2">
                                        <div class="form-group">
                                            <label>URL</label>
                                            <input type="url" class="form-control" name="url"
                                                value="{{ old('url') }}" placeholder="https://example.com">
                                            @error('url')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-12 mt-2">
                                        <div class="form-group">
                                            <label>Description</label>
                                            <textarea name="description" id="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                                            @error('description')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-12 mt-2">
                                        <div class="form-group">
                                            <label>Upload Image</label>
                                            <input type="file" name="image" id="image" class="form-control">
                                            @error('image')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="mt-2" id="image-preview-container"></div>
                                    </div>
                                </div>

                                <div class="form-footer mt-4">
                                    <button type="submit" class="btn btn-primary btn-pill">Submit</button>
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

            // CKEditor
            // ClassicEditor.create(document.querySelector('#description'));
            $('#description').summernote({
                height: 300,
                dialogsInBody: true,
                disableResizeEditor: true
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

            // Color select
            $('.color-box').on('click', function() {
                $('.color-box').removeClass('active');
                $(this).addClass('active');

                $('#color').val($(this).data('color'));
                $('#color_title').val($(this).data('title-color'));
            });

            // Loader
            $('form').on('submit', function() {
                $('#page-loader').fadeIn();
            });

        });
    </script>
@endpush
