@extends('layout.headerFooter')
@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="title_left">
                <h2>Edit Activity</h2>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <ul class="admin-breadcrumb">
                        <li>
                            <a href="{{ route('activity.index', ['id' => $question->mood_id]) }}">Activity</a>
                        </li>
                        <li class="active">
                            <a href="#">Edit activity</a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="row">
                <div class="col-xl-12">
                    <div class="card card-default">
                        <div class="card-body">
                            <form id="bannerForm" action="{{ route('activity.update', $question->id) }}"
                                enctype="multipart/form-data" method="post">
                                @csrf
                                @method('PUT')

                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="category">Mood</label>
                                            <select class="form-control select2" name="category" id="category" disabled>
                                                @foreach ($category as $value)
                                                    <option value="{{ $value['id'] }}"
                                                        class="{{ $question->mood_id == $value['id'] ? 'bg-success text-white' : '' }}"
                                                        {{ $question->mood_id == $value['id'] ? 'selected' : '' }}>
                                                        {{ $value['name'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <input type="hidden" name="category" value="{{ $question->mood_id }}">
                                            @error('category')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="group_name">Points</label>
                                            <input type="text" class="form-control" name="points" id="points"
                                                placeholder="Enter Points"
                                                value="{{ $question->points ? $question->points : '' }}" maxlength="100">
                                            @error('points')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="exampleFormControlFile1">Status</label>
                                            <select name="status" class="form-control" id="optionSelect">
                                                <option value="">Select Status</option>
                                                <option value="active"
                                                    {{ $question->status == 'active' ? 'selected' : '' }}>Active</option>
                                                <option value="inactive"
                                                    {{ $question->status == 'inactive' ? 'selected' : '' }}>Inactive
                                                </option>
                                            </select>
                                            @if ($errors->has('status'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('status') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                </div>
                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="group_name">Activity Name </label>
                                            <input type="text" class="form-control" name="name" id="name"
                                                placeholder="Enter Activity Name"
                                                value="{{ $question->name ? $question->name : '' }}" maxlength="100">
                                            @error('name')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- <div class="row"> -->
                                    {{-- <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="group_name">Activity Name ( In Chinese)</label>
                                            <input type="text" class="form-control" name="name_chinese" id="name_chinese"
                                                placeholder="Enter Activity Name"
                                                value="{{ $question->name_chinese ? $question->name_chinese : '' }}"
                                                maxlength="100">
                                            @error('name_chinese')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div> --}}

                                </div>
                                <!-- <div class="row">
                                   
                                </div> -->




                                <div class="form-footer mt-6">
                                    <button type="submit" class="btn btn-primary btn-pill mr-2">Update</button>
                                    <a href="{{ route('activity.index', ['id' => $question->mood_id]) }}">
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

    <script>
        $(document).ready(function() {
            $('#category').select2();
        });

        $('#category').prop('disabled', true).trigger('change'); // disable and trigger update

        // Force re-render of select2 to apply disabled styles
        $('#category').select2({
            minimumResultsForSearch: Infinity // optional: hides search box
        });
    </script>
@endsection
