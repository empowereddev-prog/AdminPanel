@extends('layout.headerFooter')

@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="title_left">
                <h2>Edit Mood</h2>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <ul class="admin-breadcrumb">
                        <li>
                            <a href="{{ route('mood-index') }}">Child's Mood</a>
                        </li>
                        <li class="active">
                            <a href="#">Edit Mood</a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="row">
                <div class="col-xl-12">
                    <div class="card card-default">
                        <div class="card-body">
                            <form id="knowledgeForm" action="{{ route('update-mood', $data->id) }}"
                                enctype="multipart/form-data" method="post">
                                @csrf
                                @method('PUT')
                                <!-- Basic Information Card -->
                                <div class="card card-default mb-4">
                                    <div class="card-header"><strong>Basic Information</strong></div>
                                    <div class="card-body">
                                        <div class="row mt-2">
                                            <div class="col-md-6">
                                                <label for="name">Name </label>
                                                <input type="text" class="form-control" name="name"
                                                    placeholder="Enter Name" value="{{ old('name', $data->name ?? '') }}">
                                                @error('name')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            {{-- <div class="col-md-6">
                                            <label for="name_chinese">Name (In Chinese)</label>
                                            <input type="text" class="form-control" name="name_chinese" placeholder="Enter Name" value="{{ old('name_chinese', $data->name_chinese ?? '') }}">
                                            @error('name_chinese') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        </div> --}}
                                            @php
                                                $selectedColor = old('color', $data->color ?? '');
                                            @endphp
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="color">Select Color</label>
                                                    <select name="color" class="form-control">
                                                        <option disabled {{ $selectedColor == '' ? 'selected' : '' }}>Select
                                                            Color
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

                                        <div class="row mt-3">
                                            <div class="col-md-6">
                                                <label for="points">Points (%)</label>
                                                <input type="text" class="form-control" name="points"
                                                    placeholder="Enter Points"
                                                    value="{{ old('points', $setting->option_value ?? '') }}">
                                                @error('points')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label for="image">Upload Image</label>
                                                <input type="file" class="form-control-file" name="image">
                                                @if (!empty($data->image))
                                                    <div class="mt-2">
                                                        <img src="{{ $data->image }}" alt="Current Image"
                                                            style="max-width: 100px; max-height: 100px;">
                                                    </div>
                                                @endif
                                                @error('image')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Type and Comments Card -->
                                <div class="card card-default mb-4">
                                    <div class="card-header"><strong>Type & Comments</strong></div>
                                    <div class="card-body">
                                        <div class="row mt-2">
                                            <div class="col-md-6">
                                                <label for="type">Select Type</label>
                                                <select class="form-control select2" name="type" id="type">
                                                    <option value="">Select Type</option>
                                                    <option value="positive"
                                                        {{ old('type', $data->type ?? '') == 'positive' ? 'selected' : '' }}>
                                                        Positive</option>
                                                    <option value="negative"
                                                        {{ old('type', $data->type ?? '') == 'negative' ? 'selected' : '' }}>
                                                        Negative</option>
                                                </select>
                                                @error('type')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label for="comment_english">Comment 1</label>
                                                <textarea class="form-control" name="comment_english" placeholder="Enter first comment" rows="1">{{ old('comment_english', $data->comment_english ?? '') }}</textarea>
                                                @error('comment_english')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-sm-4">
                                                <label for="comment1">Comment 2</label>
                                                <textarea name="comment1" class="form-control" placeholder="Enter second comment">{{ old('comment1', $data->comment1) }}</textarea>
                                                @error('comment1')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-sm-4">
                                                <label for="comment2">Comment 3</label>
                                                <textarea name="comment2" class="form-control" placeholder="Enter third comment">{{ old('comment2', $data->comment2) }}</textarea>
                                                @error('comment2')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-sm-4">
                                                <label for="comment3">Comment 4</label>
                                                <textarea name="comment3" class="form-control" placeholder="Enter fourth comment">{{ old('comment3', $data->comment3) }}</textarea>
                                                @error('comment3')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        {{-- <div class="row mt-3">
                                        <div class="col-md-6">
                                            <label for="comment_chinese">Comment (In Chinese)</label>
                                            <textarea class="form-control" name="comment_chinese" placeholder="Enter comment">{{ old('comment_chinese', $data->comment_chinese ?? '') }}</textarea>
                                            @error('comment_chinese') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        </div>
                                    </div> --}}
                                    </div>
                                </div>

                                <!-- Referred Videos Card -->
                                <div class="card card-default mb-4" id="referred_video_box">
                                    <div class="card-header"><strong>Referred Videos</strong></div>
                                    <div class="card-body">
                                        @php
                                            $moodSelectedVideoIds = array_map(
                                                'strval',
                                                (array) old('video', collect($data->referred_video)->pluck('id')->toArray()),
                                            );
                                        @endphp
                                        <div class="row">
                                            <div class="col-sm-12">
                                                @include('admin.moodTracker.partials.video-picker', [
                                                    'videos' => $referred_video,
                                                    'selectedIds' => $moodSelectedVideoIds,
                                                ])
                                            </div>
                                        </div>
                                        <div class="row mt-3">
                                            <div class="col-sm-6">
                                                <label for="optionSelect">Status</label>
                                                <select name="status" class="form-control" id="optionSelect">
                                                    <option value="">Select Status</option>
                                                    <option value="active"
                                                        {{ $data->status == 'active' ? 'selected' : '' }}>
                                                        Active</option>
                                                    <option value="inactive"
                                                        {{ $data->status == 'inactive' ? 'selected' : '' }}>
                                                        Inactive</option>
                                                </select>
                                                @if ($errors->has('status'))
                                                    <div class="text-danger small mt-1">
                                                        {{ $errors->first('status') }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                </div>

                                <div class="row mt-4">
                                    <!-- Age Range (Fetched from API) -->

                                </div>


                                <div class="form-footer mt-6">
                                    <button type="submit" class="btn btn-primary btn-pill mr-2">Update</button>
                                    <a href="{{ route('mood-index') }}">
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

@push('scripts')
    <script>
        $(document).ready(function() {
            $('#type').select2({
                placeholder: "Select type",
                allowClear: true
            });
        });
    </script>
@endpush
