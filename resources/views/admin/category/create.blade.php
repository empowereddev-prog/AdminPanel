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
                        <li><a href="{{ route('category.index') }}">Category</a></li>
                        <li class="active"><a href="#">Add Category</a></li>
                    </ul>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-12">
                    <div class="card card-default">
                        <div class="card-body">
                            <form id="bannerForm" action="{{ route('category.store') }}" enctype="multipart/form-data"
                                method="post">
                                @csrf

                                <div class="row">
                                    {{-- Category Name --}}
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="category_name">Category Name</label>
                                            <input type="text" class="form-control" name="category_name"
                                                id="category_name" placeholder="Enter Category Name"
                                                value="{{ old('category_name') }}" maxlength="100">
                                            @error('category_name')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    {{-- Select Color --}}
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

                                {{-- Title Color --}}
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

                                {{-- Buttons --}}
                                <div class="form-footer mt-6 d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary btn-pill mr-2">Submit</button>
                                    <a href="{{ route('category.index') }}">
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
    <script>
        document.getElementById('bannerForm').addEventListener('submit', function(event) {
            console.log('Form submitted!');
        });
    </script>
@endsection
