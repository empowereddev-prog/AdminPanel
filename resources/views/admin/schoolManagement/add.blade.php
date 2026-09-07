@extends('layout.headerFooter')
@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="title_left">
                <h2>Add School</h2>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <ul class="admin-breadcrumb">
                        <li>
                            <a href="{{ route('school.index') }}">School Management</a>
                        </li>
                        <li class="active">
                            <a href="#">Add School</a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="row">
                <div class="col-xl-12">
                    <div class="card card-default">
                        <div class="card-body">
                            <form id="bannerForm" action="{{ route('school.store') }}" enctype="multipart/form-data"
                                method="post"> @csrf
                                @method('Post')
                                <div class="row mt-4">
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label for="exampleFormControlName">School Name</label>
                                            <input type="text" class="form-control" name="school_name"
                                                id="exampleFormControlName" placeholder="Enter school"
                                                value="{{ old('school_name') }}">
                                            @if ($errors->has('school_name'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('school_name') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label for="exampleFormControlName">School code</label>
                                            <input type="text" class="form-control" name="school_code"
                                                id="exampleFormControlName" placeholder="Enter unique code"
                                                value="{{ $schoolCode }}" readOnly>
                                            @if ($errors->has('school_code'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('school_code') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>


                                <div class="row mt-4">
                                    <!-- <div class="col-md-6">
                                    <div class="form-group">
                                            <label for="exampleFormControlFile1">Status</label>
                                            <select name="status" class="form-control" id="optionSelect">
                                                <option value="">Select Status</option>
                                                <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                                                <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                            </select>
                                            @if ($errors->has('status'))
    <div class="text-danger small mt-1">
                                                    {{ $errors->first('status') }}
                                                </div>
    @endif
                                        </div>
                                    </div> -->
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label for="exampleFormControlFile1">Email</label>
                                            <input type="text" class="form-control" name="email"
                                                placeholder="Enter email" value="{{ old('email') }}" maxlength="100">
                                            @if ($errors->has('email'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('email') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label for="student_excel">Import Student Data (Excel) (Optional)</label>

                                            <div class="input-group">
                                                <!-- Text field to show the uploaded file name -->
                                                <input type="text" class="form-control" id="uploaded_file_name"
                                                    placeholder="No file chosen" readonly>

                                                <!-- Hidden file input field -->
                                                <input type="file" class="form-control d-none" name="student_excel"
                                                    id="student_excel" accept=".xlsx, .xls">

                                                <!-- Button to trigger file selection -->
                                                <div class="input-group-append">
                                                    <button class="btn btn-primary" type="button"
                                                        onclick="document.getElementById('student_excel').click();">
                                                        Choose File
                                                    </button>
                                                </div>
                                            </div>

                                            @if ($errors->has('student_excel'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('student_excel') }}
                                                </div>
                                            @endif

                                            <div class="mt-2">
                                                <a href="{{ route('download.sample.excel') }}"
                                                    class="btn"><strong>Download Sample</strong></a>

                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row ">

                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label for="exampleFormControlName">Max Limit</label>
                                            <input type="text" class="form-control" name="max_limit"
                                                id="exampleFormControlName" placeholder="Enter max limit"
                                                value="{{ old('max_limit') }}" maxlength="100">
                                            @if ($errors->has('max_limit'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('max_limit') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label for="subscription_type">Subscription Type</label>
                                            <select class="form-control select2" id="session_type" name="subscription_type">
                                                <option value="">Select Subscription Type</option>
                                                <option value="monthly"
                                                    {{ old('subscription_type') == 'monthly' ? 'selected' : '' }}>Monthly
                                                </option>
                                                <option value="quarterly"
                                                    {{ old('subscription_type') == 'quarterly' ? 'selected' : '' }}>
                                                    Quaterly</option>
                                                <option value="yearly"
                                                    {{ old('subscription_type') == 'yearly' ? 'selected' : '' }}>Yearly
                                                </option>
                                            </select>
                                            @error('subscription_type')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="form-footer mt-6">
                                    <button type="submit" class="btn btn-primary btn-pill mr-2">Submit</button>
                                    <a href="{{ route('school.index') }}"><button type="button"
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

    <script>
        document.getElementById("student_excel").addEventListener("change", function() {
            var fileName = this.files.length > 0 ? this.files[0].name : "No file chosen";
            document.getElementById("uploaded_file_name").value = fileName;
        });
    </script>
    <script>
        ClassicEditor
            .create(document.querySelector('#question'))
            .catch(error => {
                console.error(error);
            });

        ClassicEditor
            .create(document.querySelector('#answer'))
            .catch(error => {
                console.error(error);
            });


        $(document).ready(function() {

            $('.first-level').addClass('in')
        })
    </script>
@endsection
