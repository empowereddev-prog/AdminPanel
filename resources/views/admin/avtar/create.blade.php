@extends('layout.headerFooter')

@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="title_left">
                <h2>Add Avatar</h2>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <ul class="admin-breadcrumb">
                        <li>
                            <a href="{{ route('avtar.index', ['type' => $type ?? 'default']) }}">Avatar</a>
                        </li>
                        <li class="active">
                            <a href="{{ url('avtar/' . $type) }}">{{ $type. ' Category' }}</a>
                        </li>
                        <li class="active">
                            <a href="#">Add Avatar</a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-12">
                    <div class="card card-default">
                        <div class="card-body">
                            <form id="knowledgeForm" action="{{ route('avtar.store', ['type' => $type]) }}"
                                enctype="multipart/form-data" method="post">
                                @csrf

                                <div class="row">

                                    <!-- Type -->
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="type">Type</label>
                                            {{-- <select class="form-control select2" name="type" id="type">
                                                <option value="Expressions">Expressions</option>
                                                <option value="Glasses">Glasses</option>
                                                <option value="Backgrounds">Backgrounds</option>
                                                <option value="Shoes">Shoes</option>
                                                <option value="Caps">Caps</option>
                                                <option value="Scarves">Scarves</option>
                                                <option value="YogaMat">Yoga Mat</option>
                                                <option value="Bottles">Bottles</option>
                                                <option value="EyeColour">Eye Colour</option>
                                                <option value="ChestEmotions">Chest Emotions</option>
                                            </select> --}}
                                            <input type="text" name="type" class="form-control"
                                                value="{{ $type }}" readonly>
                                            @error('type')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="points">Battery (%)</label>
                                            <input type="text" class="form-control" name="points" id="points"
                                                placeholder="Enter Points" value="{{ old('points', $setting->option_value) }}" readonly>
                                            @error('points')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                </div>

                                {{-- <div class="row">
                                <!-- Age Range (Fetched from API) -->
                                <div class="col-md-6 " id="community">
                                    <!-- <label class="form-label"> Select Age Range </label> -->
                                    <label for="exampleFormControlFile1">Preview Image</label>

                                        <input type="file" class="form-control-file" name="preview_image"
                                            id="exampleFormControlFile1" value="{{old('preview_image')}}" >
                                            <canvas id="canvas" style="display:none;"></canvas>
                                        @if ($errors->has('preview_image'))
                                            <div class="text-danger small mt-1">
                                                {{ $errors->first('preview_image') }}
                                            </div>
                                        @endif
                                </div>

                                <!-- Category -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <!-- <label for="category">Apply Image</label> -->
                                        <label for="exampleFormControlFile1">Apply Image</label>
                                        <input type="file" class="form-control-file" name="apply_image"
                                            id="exampleFormControlFile1" value="{{old('apply_image')}}" >
                                            <canvas id="canvas" style="display:none;"></canvas>
                                        @if ($errors->has('apply_image'))
                                            <div class="text-danger small mt-1">
                                                {{ $errors->first('apply_image') }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div> --}}
                                <div class="row mt-3">
                                    <!-- Preview Image -->
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="preview_image">Preview Image</label>
                                            <input type="file" class="form-control-file" name="preview_image"
                                                id="preview_image">

                                            {{-- Live Preview --}}
                                            <div class="mt-2">
                                                <img id="preview_image_preview" src="" alt=""
                                                    style="display:none; width:80px; height:80px; object-fit:cover; border-radius:5px;">
                                            </div>

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

                                            {{-- Live Preview --}}
                                            <div class="mt-2">
                                                <img id="apply_image_preview" src="" alt=""
                                                    style="display:none; width:80px; height:80px; object-fit:cover; border-radius:5px;">
                                            </div>

                                            @error('apply_image')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <!-- Description -->

                                </div>

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
        // Preview Image
        document.getElementById("preview_image").addEventListener("change", function(e) {
            let reader = new FileReader();
            reader.onload = function(e) {
                let img = document.getElementById("preview_image_preview");
                img.src = e.target.result;
                img.style.display = "block";
            };
            reader.readAsDataURL(this.files[0]);
        });

        // Apply Image
        document.getElementById("apply_image").addEventListener("change", function(e) {
            let reader = new FileReader();
            reader.onload = function(e) {
                let img = document.getElementById("apply_image_preview");
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
            $('#type').select2({
                placeholder: "Select types",
                allowClear: true
            });
            $('#age_range').select2({
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
