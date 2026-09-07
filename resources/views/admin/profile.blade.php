@extends('layout.headerFooter')
@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="row">
                <div class="col-xl-12">
                    <div class="card card-default">
                        <div class="card-header">
                            <h2 class="mb-5">My Profile</h2>
                        </div>
                        <div class="card-body">
                            <div class="media media-sm">
                                <div class="media-sm-wrapper">
                                    <img src="{{ $user->profile_image ? url('uploads/profile_img/' . $user->profile_image) : url('assets/images/user.png') }}"
                                        alt="User Image">
                                </div>
                                <div class="media-body">
                                    <span class="title h3">{{ $user->name }}</span>
                                    <p>Click the choose file to change your photo.</p>
                                </div>
                            </div>
                            <form action="{{ route('update.profile') }}" method="post" enctype="multipart/form-data">
                                @csrf
                                <div class="form-group row mb-6">
                                    <label for="coverImage" class="col-sm-4 col-lg-2 col-form-label">Image</label>
                                    <div class="col-sm-8 col-lg-10">
                                        <div class="custom-file mb-1">
                                            <input type="file" class="custom-file-input" id="coverImage"
                                                name="profile_image" onchange="displayFileName()">
                                            <label class="custom-file-label" for="coverImage">Choose file...</label>
                                            <div class="invalid-feedback">Example invalid custom file feedback</div>
                                        </div>
                                        <span class="d-block ">Upload a new profile image</span>
                                        @if ($errors->has('profile_image'))
                                            <div class="text-danger small mt-1">
                                                {{ $errors->first('profile_image') }}
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="form-group row mb-6">
                                    <label for="occupation" class="col-sm-4 col-lg-2 col-form-label">Name</label>
                                    <div class="col-sm-8 col-lg-10">
                                        <input type="text" name="name" class="form-control" id="occupation"
                                            value="{{ old('name', $user->name) }}" placeholder="Enter your name">
                                        @if ($errors->has('name'))
                                            <div class="text-danger small mt-1">
                                                {{ $errors->first('name') }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="form-group row mb-6">
                                    <label for="com-name" class="col-sm-4 col-lg-2 col-form-label">Email</label>
                                    <div class="col-sm-8 col-lg-10">
                                        <input type="text" name="email" class="form-control" id="com-name"
                                            value="{{ $user->email }}" placeholder="Enter your email" readonly>
                                        @if ($errors->has('email'))
                                            <div class="text-danger small mt-1">
                                                {{ $errors->first('email') }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="form-group row mb-6">
                                    <label for="com-name" class="col-sm-4 col-lg-2 col-form-label">Gender</label>
                                    <div class="col-sm-8 col-lg-10">
                                        <select name="gender" class="form-control">
                                            <option value="" selected disabled>Select Gender</option>
                                            <option value="Female" {{ $user->gender == 'Female' ? 'selected' : '' }}>Female
                                            </option>
                                            <option value="Male" {{ $user->gender == 'Male' ? 'selected' : '' }}>Male
                                            </option>
                                            </option>
                                        </select>
                                        @if ($errors->has('gender'))
                                            <div class="text-danger small mt-1">
                                                {{ $errors->first('gender') }}
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="form-group row mb-6">
                                    <label for="phone-no" class="col-sm-4 col-lg-2 col-form-label">Contact No.</label>
                                    <div class="col-sm-10 col-lg-10 ">
                                       <div class="d-flex">
                                        <select name="country_code" id="country-code" class="form-control select2 w-25">
                                            @foreach ($user->countries as $country)
                                                <option value="{{ $country->country_code }}"
                                                    {{ old('country_code', $user->country_code) == $country->country_code ? 'selected' : '' }}>
                                                    {{ $country->country }} ({{ $country->country_code }})
                                                </option>
                                            @endforeach
                                        </select>

                                        <!-- Phone Number Input -->
                                        <input type="text" name="phone_no" class="form-control w-30" id="phone-no"
                                            value="{{ old('phone_no', $user->phone_no) }}"
                                            placeholder="Enter your contact number">
                                       </div>

                                        <!-- Error Messages -->
                                        @if ($errors->has('country_code'))
                                            <div class="text-danger small mt-1">{{ $errors->first('country_code') }}</div>
                                        @endif
                                        @if ($errors->has('phone_no'))
                                            <div class="text-danger small mt-1">{{ $errors->first('phone_no') }}</div>
                                        @endif
                                    </div>

                                </div>
                                <div class="d-flex justify-content-end">

                                    <button type="submit" class="btn btn-primary mb-2 btn-pill">Update Profile</button>
                                    <a href="{{ route('admin.dashboard') }}">
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
@endsection

<!-- jQuery (Ensure it's loaded) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Select2 JS (Ensure it's loaded) -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
<script>
    $(document).ready(function() {
        // Initialize Select2
        $('#country-code').select2();

        // Modify selected text display when a user selects an option
        $('#country-code').on('select2:select', function(e) {
            let selectedValue = e.params.data.id; // Get selected country code
            let select2Container = $(this).next('.select2-container');

            setTimeout(function() {
                select2Container.find('.select2-selection__rendered').text(selectedValue);
            }, 1);
        });

        // Ensure default text is '+65' on page load
        setTimeout(function() {
            // let initialValue = $('#country-code').val() || '+65';
            let initialValue = $('#country-code').val();
            let select2Container = $('#country-code').next('.select2-container');

            select2Container.find('.select2-selection__rendered').text(initialValue);
        }, 1);
    });

    function displayFileName() {
        var input = document.getElementById('coverImage');
        var fileName = input.files[0].name;
        var label = document.querySelector('.custom-file-label');
        label.innerHTML = fileName;
    }
</script>
