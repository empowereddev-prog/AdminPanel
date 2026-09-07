@extends('layout.headerFooter')
@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="title_left">
                <h2>Add Activity</h2>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <ul class="admin-breadcrumb">
                        <li>
                            <a href="{{ route('activity.index',['id' => $id]) }}">Activity</a>
                        </li>
                        <li class="active">
                            <a href="#">Add Activity</a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-12">
                    <div class="card card-default">
                        <div class="card-body">
                            <form id="bannerForm" action="{{ route('activity.store',['id' => $id]) }}" enctype="multipart/form-data" method="post">
                                @csrf

                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="group_name">Mood</label>
                                            <select class="form-control select2" name="category_disabled" id="category_disabled" disabled>
                                                @foreach($category as $value)
                                                    <option value="{{ $value['id'] }}"
                                                        class="{{ $id == $value['id'] ? 'bg-success text-white' : '' }}"
                                                        {{ $id == $value['id'] ? 'selected' : '' }}>
                                                        {{ $value['name'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <!-- Hidden input to ensure the value is submitted -->
                                            <input type="hidden" name="category" value="{{ $id }}">
                                            @error('category')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="group_name">Points</label>
                                            <input type="text" id="points" class="form-control" name="points" value="{{ old('points')}}" placeholder="Enter Points">
                                            @error('points')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="group_name">Activity  </label>
                                            <input type="text" id="activity" class="form-control" name="activity" value="{{ old('activity')}}" placeholder="Enter activity">
                                            @error('activity')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    {{-- <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="group_name">Activity (In Chinese)</label>
                                            <input type="text" id="activity_chinese" class="form-control" name="activity_chinese" value="{{ old('activity_chinese')}}" placeholder="Enter activity">
                                            @error('activity_chinese')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div> --}}
                                </div>
                                <div class="form-footer mt-6">
                                    <button type="submit" class="btn btn-primary btn-pill mr-2">Submit</button>
                                    <a href="{{ route('activity.index',['id' => $id]) }}">
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
    $('#category_disabled').select2();
});

    $('#category_disabled').prop('disabled', true).trigger('change'); // disable and trigger update

// Force re-render of select2 to apply disabled styles
$('#category_disabled').select2({
    minimumResultsForSearch: Infinity // optional: hides search box
});
    </script>
@endsection
