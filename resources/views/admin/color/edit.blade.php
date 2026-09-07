@extends('layout.headerFooter')
@section('content')
<div class="content-wrapper">
    <div class="content">
        <div class="title_left">
            <h2>Edit Color</h2>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <ul class="admin-breadcrumb">
                    <li><a href="{{ route('color.index') }}">Color</a></li>
                    <li class="active"><a href="#">Edit Color</a></li>
                </ul>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-12">
                <div class="card card-default">
                    <div class="card-body">
                        <form action="{{ route('color.update', $color->id) }}" method="POST">
                            @csrf
                            <!-- Main Color -->
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <input type="hidden" name="old_color" value="{{ $color->title_color }}">
                                        <label for="color">Color</label>
                                        <div class="d-flex align-items-center gap-3">
                                            <input type="color" class="form-control w-auto p-1" id="color"
                                                value="{{ old('color', $color->color) }}">
                                            <div id="colorPreview" class="color-circle"></div>
                                            <input type="text" name="color" id="colorCode"
                                                class="form-control w-auto ml-2"
                                                value="{{ old('color', $color->color) }}">
                                        </div>
                                        @error('color')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Title Color -->
                                <div class="col-sm-6">
                                    <input type="hidden" name="old_title_color" value="{{ $color->title_color }}">
                                    <div class="form-group">
                                        <label for="color_title">Title Color</label>
                                        <div class="d-flex align-items-center gap-3">
                                            <input type="color" class="form-control w-auto p-1" id="color_title"
                                                value="{{ old('color_title', $color->title_color) }}">
                                            <div id="titleColorPreview" class="color-circle"></div>
                                            <input type="text" name="color_title" id="titleColorCode"
                                                class="form-control w-auto ml-2"
                                                value="{{ old('color_title', $color->title_color) }}">
                                        </div>
                                        @error('color_title')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-footer mt-6 d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary btn-pill mr-2">Update</button>
                                <a href="{{ route('color.index') }}">
                                    <button type="button" class="btn btn-primary btn-pill">Cancel</button>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .color-circle {
        width: 30px;
        height: 30px;
        /* border-radius: 50%; */
        border: 2px solid #ccc;
    }
</style>

<script>
    const colorInput = document.getElementById('color');
    const colorCodeInput = document.getElementById('colorCode');
    const colorPreview = document.getElementById('colorPreview');

    const titleColorInput = document.getElementById('color_title');
    const titleColorCodeInput = document.getElementById('titleColorCode');
    const titleColorPreview = document.getElementById('titleColorPreview');

    function syncColorInputs(picker, text, preview) {
        function updateFromText() {
            const value = text.value.trim();
            if (/^#[0-9A-Fa-f]{6}$/.test(value)) {
                picker.value = value;
                preview.style.backgroundColor = value;
            }
        }

        picker.addEventListener('input', () => {
            text.value = picker.value;
            preview.style.backgroundColor = picker.value;
        });

        text.addEventListener('input', updateFromText);
        text.addEventListener('blur', updateFromText);

        preview.addEventListener('click', () => {
            picker.click();
        });

        preview.style.backgroundColor = picker.value;
    }

    syncColorInputs(colorInput, colorCodeInput, colorPreview);
    syncColorInputs(titleColorInput, titleColorCodeInput, titleColorPreview);
</script>
@endsection
