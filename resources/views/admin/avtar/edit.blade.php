@extends('layout.headerFooter')

@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="title_left">
                <h2>Edit Avatar</h2>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <ul class="admin-breadcrumb">
                        <li>
                            <a href="{{ route('avtar.type_index') }}">Avatar</a>
                        </li>
                        <li>
                            <a href="{{ route('avtar.index', ['type' => $type ?? 'default']) }}">{{ $type. ' Category' }}</a>
                        </li>
                        <li class="active">
                            <a href="#">Edit Avatar</a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-12">
                    <div class="card card-default">
                        <div class="card-body">
                            <form id="knowledgeForm"
                                action="{{ route('avtar.update', ['type' => $type, 'id' => $data->id]) }}"
                                enctype="multipart/form-data" method="post">
                                @csrf
                                @method('PUT')
                                <!-- <div class="row"> -->
                                <!-- Title -->
                                <!-- <div class="col-md-6"> -->
                                {{-- <div class="form-group row" style="margin-bottom: 2rem;">
                                    <label for="name" class="col-sm-1 col-form-label">Type:</label>
                                    <!-- <div class="col-sm-6"> -->
                                    <label for="name" class="col-sm-3 col-form-label">{{ $data->body_part }}</label>
                                    <!-- </div> -->
                                </div> --}}
                                {{-- <div class="row">
                                    
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="exampleFormControlFile1">Preview Image</label>
                                            <input type="file" class="form-control-file" name="preview_image"
                                                id="exampleFormControlFile1" value="{{ old('preview_image') }}">
                                            </br>
                                            <a href="{{ asset('assets/avtar/' . $data->preview_image) }}" target="_blank">
                                                <img src="{{ asset('assets/avtar/' . $data->preview_image) }}"
                                                    alt="No Image" width="80" height="80">
                                            </a>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="exampleFormControlFile1">Apply Image</label>
                                            <input type="file" class="form-control-file" name="apply_image"
                                                id="exampleFormControlFile1" value="{{ old('apply_image') }}">
                                            </br>
                                            <a href="{{ asset('assets/avtar/' . $data->apply_image) }}" target="_blank">
                                                <img src="{{ asset('assets/avtar/' . $data->apply_image) }}" alt="No Image"
                                                    width="80" height="80">
                                            </a>
                                        </div>
                                    </div>
                                </div> --}}
                                <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="type">Type</label>
                                        
                                        <input type="text" name="type" class="form-control" value="{{ $type }}" readonly>
                                        @error('type')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                                <div class="row mt-4">
                                    <!-- Preview Image -->
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="preview_image">Preview Image</label>
                                            <input type="file" class="form-control-file" name="preview_image"
                                                id="preview_image">

                                            {{-- Existing Image --}}
                                            @if ($data->preview_image)
                                                <div class="mt-2">
                                                    <a href="{{ asset('assets/avtar/' . $data->preview_image) }}"
                                                        target="_blank">
                                                        <img id="preview_image_tag"
                                                            src="{{ getImagePathUrl($data->preview_image, 'assets/avtar') }}"
                                                            alt="Preview Image" width="80" height="80"
                                                            style="object-fit: cover; border-radius: 5px;">
                                                    </a>
                                                </div>
                                            @else
                                                <div class="mt-2">
                                                    <img id="preview_image_tag" src="" alt="No Image"
                                                        style="display:none; width:80px; height:80px; object-fit:cover; border-radius:5px;">
                                                </div>
                                            @endif

                                            @error('preview_image')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- Apply Image -->
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="apply_image">Apply Image</label>
                                            <input type="file" class="form-control-file" name="apply_image"
                                                id="apply_image">

                                            {{-- Existing Image --}}
                                            @if ($data->apply_image)
                                                <div class="mt-2">
                                                    <a href="{{ asset('assets/avtar/' . $data->apply_image) }}"
                                                        target="_blank">
                                                        <img id="apply_image_tag"
                                                            src="{{ getImagePathUrl($data->apply_image, 'assets/avtar') }}"
                                                            alt="Apply Image" width="80" height="80"
                                                            style="object-fit: cover; border-radius: 5px;">
                                                    </a>
                                                </div>
                                            @else
                                                <div class="mt-2">
                                                    <img id="apply_image_tag" src="" alt="No Image"
                                                        style="display:none; width:80px; height:80px; object-fit:cover; border-radius:5px;">
                                                </div>
                                            @endif

                                            @error('apply_image')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>




                                <!-- School -->
                                <!-- <div class="form-group row">
                                                    <label for="email" class="col-sm-2 col-form-label">Apply Image:</label>
                                                    <div class="col-sm-6">
                                                        <a href="{{ asset('assets/avtar/' . $data->apply_image) }}" target="_blank">
                                                        <img src="{{ asset('assets/avtar/' . $data->apply_image) }}"  alt="No Image" width="80" height="80">
                                                        </a>
                                                </div>

                                            </div>
                                             -->

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="exampleFormControlFile1">Battery (%)</label>
                                            <input type="text" class="form-control" name="points" id="points"
                                                placeholder="Enter Points" value="{{ old('points', $setting->option_value) }}" readonly>
                                            @error('points')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

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
                                <!-- <div class="row">
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <label for="description">Description</label>
                                                        <textarea class="form-control" name="description" id="description" placeholder="Enter Video Description">{{ old('description', $data->description ?? '') }}</textarea>
                                                        @error('description')
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
                                                    </div>
                                                </div>
                                            </div> -->
                                <div class="form-footer mt-6">
                                    <button type="submit" class="btn btn-primary btn-pill mr-2">Submit</button>
                                    <a href="{{ route('avtar.index', ['type' => $type ?? 'default']) }}">
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

<script src="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.js"></script>

@push('scripts')
    <script>
        // Replace Preview Image
        document.getElementById("preview_image").addEventListener("change", function() {
            let reader = new FileReader();
            reader.onload = function(e) {
                let img = document.getElementById("preview_image_tag");
                img.src = e.target.result;
                img.style.display = "block";
            };
            reader.readAsDataURL(this.files[0]);
        });

        // Replace Apply Image
        document.getElementById("apply_image").addEventListener("change", function() {
            let reader = new FileReader();
            reader.onload = function(e) {
                let img = document.getElementById("apply_image_tag");
                img.src = e.target.result;
                img.style.display = "block";
            };
            reader.readAsDataURL(this.files[0]);
        });
    </script>
    <script>
        CKEDITOR.replace('description', {
            height: 100,
            removePlugins: 'image',
        });
        $(document).ready(function() {
            $('#school').select2({
                placeholder: "Select schools",
                allowClear: true
            });
            $('#age').select2({
                placeholder: "Select age range",
                allowClear: true
            });
            $('#category').select2({
                placeholder: "Select category",
                allowClear: true
            });
        });
    </script>
@endpush
