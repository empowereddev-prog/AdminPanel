@extends('layout.headerFooter')
@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="title_left">
                <h2>Add Age Group</h2>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <ul class="admin-breadcrumb">
                        <li>
                            <a href="{{ route('age-group.index') }}">Age Group</a>
                        </li>
                        <li class="active">
                            <a href="#">Add Age Group</a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="row">
                <div class="col-xl-12">
                    <div class="card card-default">
                        <div class="card-body">
                            <form id="bannerForm" action="{{ route('age-group.update', $data->id) }}" enctype="multipart/form-data" method="post">
                            @csrf
                            @method('PUT')

                                <div class="row">
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label for="group_name">Group Name</label>
                                            <input type="text" class="form-control" name="name"
                                                   id="group_name" placeholder="Enter Group Name"
                                                   value="{{$data->name ? $data->name : ''}}" maxlength="100">
                                            @error('name')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label for="start_age">Start Age</label>
                                            <input type="number" class="form-control" name="start_age"
                                                   id="start_age" placeholder="Enter Start Age"
                                                   value="{{$data->start_age ? $data->start_age : ''}}" maxlength="3">
                                            @error('start_age')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label for="end_age">End Age</label>
                                            <input type="number" class="form-control" name="end_age"
                                                   id="end_age" placeholder="Enter End Age"
                                                   value="{{$data->end_age ? $data->end_age : ''}}" maxlength="3">
                                            @error('end_age')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="exampleFormControlFile1">Status</label>
                                        <select name="status" class="form-control" id="optionSelect">
                                            <option value="">Select Status</option>
                                            <option value="1" {{ $data->status == '1' ? 'selected' : '' }}>Active</option>
                                            <option value="0" {{ $data->status == '0' ? 'selected' : '' }}>Inactive</option>
                                        </select>
                                        @if ($errors->has('status'))
                                        <div class="text-danger small mt-1">
                                            {{ $errors->first('status') }}
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                </div>

                                <div class="form-footer mt-6">
                                    <button type="submit" class="btn btn-primary btn-pill mr-2">Submit</button>
                                    <a href="{{ route('age-group.index') }}">
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
    document.getElementById('bannerForm').addEventListener('submit', function(event) {
        console.log('Form submitted!');
    });
</script>
@endsection
