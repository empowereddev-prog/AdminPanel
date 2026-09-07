@extends('layout.headerFooter')
@section('content')
    <style>
        .hidden {
            display: none;
        }
    </style>
    <div class="content-wrapper">
        <div class="content">
            <div class="title_left">
                <h2>Add User</h2>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <ul class="admin-breadcrumb">
                        <li>
                            <a href="{{ route('user.index') }}">User Management</a>
                        </li>
                        <li class="active">
                            <a href="#">Add User</a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="row">
                <div class="col-xl-12">
                    <div class="card card-default">
                        <div class="card-header">
                            <h2>Add User</h2>
                        </div>
                        <div class="card-body">
                            <form id="bannerForm" action="{{ route('user.store') }}" enctype="multipart/form-data"
                                method="post">
                                @csrf
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="exampleFormControlFile1">Name</label>
                                            <input type="text" class="form-control" name="name"
                                                placeholder="Enter name" value="{{ old('name') }}" maxlength="100">
                                            @if ($errors->has('name'))
                                                <div class="text-danger small mt-1">

                                                    <strong>{{ $errors->first('name') }}</strong>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="exampleFormControlFile1">Email</label>
                                            <input type="text" class="form-control" name="email"
                                                placeholder="Enter email" value="{{ old('email') }}" maxlength="100">
                                            @if ($errors->has('email'))
                                                <div class="text-danger small mt-1">
                                                    <strong>{{ $errors->first('email') }}</strong>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                        </div>
                        <div class="row align-items-center" style="margin-left:20px; margin-bottom:10px;">
                            <div class="col-sm-2">
                                <label for="exampleFormControlFile1">Phone</label>
                                <select name="code" id="code" class="form-control select2 w-45">
                                    @foreach ($countrycode as $code)
                                        <option value="{{ $code->country_code }}"
                                            {{ old('code', request('code', '+65')) == $code->country_code ? 'selected' : '' }}>
                                            {{ $code->country }} ({{ $code->country_code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-4">
                                <input type="text" id="phone_no" class="form-control" name="phone_no"
                                    placeholder="Enter your number" value="{{ old('phone_no') }}"
                                    style="width: 79%; position:absolute; top:-6px">

                            </div>

                        </div>
                        <div class="row align-items-center">
                            <div class="col-sm-2"style="margin-left: 30px;"></div>
                            <div class="col-sm-4">
                                @if ($errors->has('phone_no'))
                                    <div class="text-danger small mt-1">
                                        <strong>{{ $errors->first('phone_no') }}</strong>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- </div> -->
                        <div class="form-footer mt-3" style="
                                    margin-left: 30px;">
                            <button type="submit" class="btn btn-primary btn-pill mr-2">Submit</button>
                            <a href="{{ route('user.index') }}"><button type="button"
                                    class="btn btn-primary btn-pill">Cancel</button></a>
                        </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    <script src="https://cdn.ckeditor.com/ckeditor5/23.0.0/classic/ckeditor.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>

    <script type="text/javascript">
        $(document).ready(function() {
            $('#code').select2();

            $('#code').on('select2:select', function(e) {
                let selectedValue = e.params.data.id; // Get selected country code
                console.log(selectedValue);

                let select2Container = $(this).next('.select2-container');
                setTimeout(function() {
                    select2Container.find('.select2-selection__rendered').text(selectedValue);
                }, 1);
            });

            setTimeout(function() {
                let select2Container = $('#code').next('.select2-container');
                select2Container.find('.select2-selection__rendered').text('+65');
            }, 1);
        });

        $(document).ready(function() {
            $('#code').select2({
                placeholder: 'Select an option'
            });
        });
        $(document).ready(function() {
            $('.first-level').addClass('in');

            $('.contents').each(function() {
                ClassicEditor.create(this)
                    .catch(error => {
                        console.error(error);
                    });
            });
        })
    </script>
@endsection
