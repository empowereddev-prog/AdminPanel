@extends('layout.headerFooter')

@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="row">
                <div class="col-xl-12">
                    <div class="card card-default">
                        <div class="card-header">
                            <h2>Add Meet Team</h2>
                        </div>
                        <div class="card-body">
                            {{-- Corrected the form action to point to a standard Laravel store route --}}
                            <form action="{{ route('meet-team.store') }}" method="post" enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label for="title">Title</label>
                                            <input type="text" class="form-control" name="title" id="name"
                                                placeholder="Enter Title" value="{{ old('title') }}">
                                            @if ($errors->has('title'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('title') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-12 mt-2">
                                        <div class="form-group">
                                            <label for="profession">Profession</label>
                                            <input type="text" class="form-control" name="profession" id="profession"
                                                placeholder="Enter Profession" value="{{ old('profession') }}">
                                            @if ($errors->has('profession'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('profession') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-12 mt-2">
                                        <div class="form-group">
                                            <label for="designation">Designation</label>
                                            <input type="text" class="form-control" name="designation" id="designation"
                                                placeholder="Enter Designation" value="{{ old('designation') }}">
                                            @if ($errors->has('designation'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('designation') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-12 mt-2">
                                        <div class="form-group">
                                            <label for="url"> URL</label>
                                            <input type="url" class="form-control" name="url" id="url"
                                                placeholder="https://abc.com/username" value="{{ old('url') }}">
                                            @if ($errors->has('url'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('url') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    {{-- @php
                                        $selectedColor = old('color', $meetTeam->color ?? '');
                                    @endphp --}}

                                    {{-- <div class="col-12 mt-2">
                                        <div class="form-group">
                                            <label for="color">Select Color</label>
                                            <select name="color" class="form-control">
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
                                        <div class="form-group">
                                            <label for="description">Description</label>
                                            <textarea name="description" id="description" class="form-control" rows="2"
                                                placeholder="Type your description Here">{{ old('description') }}</textarea>
                                            @if ($errors->has('description'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('description') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>


                                    <div class="col-12 mt-2">
                                        <div class="form-group">
                                            <label for="image">Upload Image</label><br>
                                            <input type="file" name="image" id="image" class="form-control"
                                                accept="image/*">
                                            @if ($errors->has('image'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('image') }}
                                                </div>
                                            @endif
                                        </div>
                                        {{-- Container for the image preview --}}
                                        <div class="mt-2" id="image-preview-container"></div>
                                    </div>

                                    {{-- <div class="col-md-12 mt-2">
                                        <div class="form-group">
                                            <label for="status">Status</label>
                                            <select name="status" class="form-control">
                                                <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>
                                                    Active</option>
                                                <option value="inactive"
                                                    {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                            </select>
                                        </div>
                                    </div> --}}

                                </div>
                                <div class="form-footer mt-4">
                                    <button type="submit" class="btn btn-primary btn-pill">Submit</button>
                                    {{-- Corrected the route name to a more common convention --}}
                                    <a href="{{ route('meet-team.index') }}" class="btn btn-light">Cancel</a>
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
            // ClassicEditor
            //     .create(document.querySelector('#description'))
            //     .catch(error => {
            //         console.error('CKEditor Error:', error);
            //     });
            $('#description').summernote({
                height: 300,
                dialogsInBody: true,
                disableResizeEditor: true
            });
            $('#image').on('change', function() {
                const previewContainer = $('#image-preview-container');
                previewContainer.empty();
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
        });
    </script>
@endpush
