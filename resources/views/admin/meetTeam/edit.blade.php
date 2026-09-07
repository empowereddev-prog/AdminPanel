@extends('layout.headerFooter')

@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="row">
                <div class="col-xl-12">
                    <div class="card card-default">
                        <div class="card-header">
                            {{-- Changed Title --}}
                            <h2>Edit Meet Team</h2>
                        </div>
                        <div class="card-body">
                            {{-- Updated form action to the update route and added @method('PUT') --}}
                            <form action="{{ route('meet-team.update', $meetTeam->id) }}" method="post"
                                enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label for="title">Title</label>
                                            {{-- Pre-filled value with existing meet-team data --}}
                                            <input type="text" class="form-control" name="title" id="title"
                                                placeholder="Enter Title" value="{{ old('title', $meetTeam->title) }}">
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
                                                placeholder="Enter Profession"
                                                value="{{ old('profession', $meetTeam->profession) }}">
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
                                                placeholder="Enter Designation"
                                                value="{{ old('designation', $meetTeam->designation) }}">
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
                                                placeholder="https://abc.com/username"
                                                value="{{ old('url', $meetTeam->url) }}">
                                            @if ($errors->has('url'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('url') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    {{-- @php
                                        $selectedColor = old('color', isset($meetTeam) ? $meetTeam->color : '');
                                    @endphp

                                    <div class="col-12 mt-2">
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
                                            {{-- Pre-filled value for textarea --}}
                                            <textarea name="description" id="description" class="form-control" rows="2"
                                                placeholder="Type your description Here">{{ old('description', $meetTeam->description) }}</textarea>
                                            @if ($errors->has('description'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('description') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-12 mt-2">
                                        <div class="form-group">
                                            <label for="image">Upload New Image</label><br>
                                            <input type="file" name="image" id="image" class="form-control"
                                                accept="image/*">
                                            @if ($errors->has('image'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('image') }}
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Show current image initially --}}
                                        <div class="mt-2" id="image-preview-container">
                                            @if ($meetTeam->image)
                                                <img src="{{ getImageUrl($meetTeam->image) }}" alt="Current Image"
                                                    class="img-fluid rounded"
                                                    style="max-height: 150px; border: 1px solid #ddd; padding: 5px;">
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-md-12 mt-2">
                                        <div class="form-group">
                                            <label for="status">Status</label>
                                            <select name="status" class="form-control">
                                                <option value="active"
                                                    {{ old('status', $meetTeam->status) === 'active' ? 'selected' : '' }}>
                                                    Active</option>
                                                <option value="inactive"
                                                    {{ old('status', $meetTeam->status) === 'inactive' ? 'selected' : '' }}>
                                                    Inactive</option>
                                            </select>
                                        </div>
                                    </div>

                                </div>

                                <div class="form-footer mt-4">
                                    {{-- Changed button text --}}
                                    <button type="submit" class="btn btn-primary btn-pill">Update</button>
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
    {{-- This script block requires no changes. It works for both create and edit. --}}
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
        });
    </script>
@endpush
