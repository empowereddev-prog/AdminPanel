@extends('layout.headerFooter')
@section('content')
    <div class="content-wrapper">
        <div class="content">
        <div class="title_left">
                <h2>Edit School</h2>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <ul class="admin-breadcrumb">
                        <li>
                            <a href="{{ route('school.index') }}">School Management</a>
                        </li>
                        <li class="active">
                            <a href="#">Edit School</a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="row">
                <div class="col-xl-12">
                    <div class="card card-default">
                        <div id="page-loader"
                            style="display:none; position:fixed; top:0; left:0; width:100%; height:100%;
                        background:rgba(255,255,255,0.7); z-index:9999; text-align:center; padding-top:20%;">
                            <div class="spinner-border text-primary" role="status" style="width:3rem; height:3rem;">
                                <span class="sr-only">Loading...</span>
                            </div>
                            <p class="mt-2 text-dark">Please wait...</p>
                        </div>

                        <div class="card-body">
                            <form id="schoolEditForm" action="{{ route('school.update',$data->id) }}" enctype="multipart/form-data" method="post">
                                @csrf
                                @method('PUT')
                               <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="exampleFormControlName">School Name</label>
                                            <input type="text" class="form-control" name="school_name"
                                                id="exampleFormControlName" placeholder="School name" value="{{$data->name ? $data->name : ''}}" >
                                            @if ($errors->has('school_name'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('school_name') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="school_code">School Code</label>
                                            <input type="text" class="form-control" name="school_code"
                                                id="exampleFormControlName" placeholder="School code" value="{{$data->school_code ? $data->school_code : ''}}" readonly >
                                            @if ($errors->has('school_code'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('school_code') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-5">
                                <div class="col-md-6 col-sm-12">
                                            <div class="form-group">
                                                <label for="exampleFormControlFile1">Email</label>
                                                <input type="text" class="form-control" name="email"
                                                    id="exampleFormControlName" placeholder="Enter email"
                                                    value="{{ old('email', $data->email) }}" readonly>
                                                @if ($errors->has('email'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('email') }}
                                                </div>
                                                @endif
                                            </div>
                                        </div>

                               <div class="col-md-6 col-sm-12">
                                        <div class="form-group">
                                            <label for="limit">Max Limit</label>
                                            <input type="text" class="form-control" name="max_limit"
                                                id="exampleFormControlName" placeholder="Enter max limit" value="{{$data->max_limit ? $data->max_limit : ''}}" >
                                            @if ($errors->has('max_limit'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('max_limit') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                            <div class="row mb-4">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        @php $filePath = 'uploads/' . $data->id . '_sample_students.xlsx'; @endphp
                                        <label for="student_excel">Upload School User Excel(Optional)</label>
                                        <div class="input-group">
                                            <!-- <input type="text" class="form-control" id="uploaded_file_name" value="{{ Storage::exists($filePath) ? $data->id . '_sample_students.xlsx' : '' }}" readonly> -->
                                             <input type="text"
                                                class="form-control"
                                                id="uploaded_file_name"
                                                value=""
                                                readonly
                                                placeholder="No file chosen">
                                            <input type="file" class="form-control d-none" name="student_excel" id="student_excel" accept=".xlsx,.xls">
                                            <div class="input-group-append">
                                                <button class="btn btn-primary" type="button" onclick="document.getElementById('student_excel').click();">Choose File</button>
                                            </div>
                                            <a href="{{ route('download.sample.excel') }}" class="btn"><strong>Download Sample</strong></a>
                                        </div>
                                        @error('student_excel') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        @php $staffPath = 'uploads/' . $data->id . '_sample_staff.xlsx'; @endphp
                                        <label for="staff_excel">Upload Staff Details Excel(Optional)</label>
                                        <div class="input-group">
                                            <!-- <input type="text" class="form-control" id="uploaded_staff_file_name" value="{{ Storage::exists($staffPath) ? $data->id . '_sample_staff.xlsx' : '' }}" readonly> -->
                                            <input type="text"
                                                class="form-control"
                                                id="uploaded_staff_file_name"
                                                value=""
                                                readonly
                                                placeholder="No file chosen">
                                            <input type="file" class="form-control d-none" name="staff_excel" id="staff_excel" accept=".xlsx,.xls">
                                            <div class="input-group-append">
                                                <button class="btn btn-primary" type="button" onclick="document.getElementById('staff_excel').click();">Choose File</button>
                                            </div>
                                            <a href="{{ route('download.sample.staff.excel') }}" class="btn"><strong>Download Sample</strong></a>
                                        </div>
                                        @error('staff_excel') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </div>


                            <div class="row ">
                                <div class="col-md-6">
                                    <div class="form-group">
                                    <label for="subscription_type">Subscription Type</label>
                                        <select class="form-control select2" id="session_type" name="subscription_type">
                                            <option value="">Select Subscription Type</option>
                                        <option value="monthly" {{ $data->subscription_type == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                            <option value="quarterly" {{ $data->subscription_type == 'quarterly' ? 'selected' : '' }}>Quaterly</option>
                                            <option value="yearly" {{ $data->subscription_type == 'yearly' ? 'selected' : '' }}>Yearly</option>
                                        </select>
                                        @error('subscription_type')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                    <div class="col-md-6 ">
                                        <div class="form-group">
                                            <label for="exampleFormControlFile1">Status</label>
                                            <select name="status" class="form-control" id="optionSelect">
                                                <option value="">Select Status</option>
                                                <option value="active" {{ $data->status == 'active' ? 'selected' : '' }}>Active</option>
                                                <option value="inactive" {{ $data->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
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
                                    <button type="submit" class="btn btn-primary btn-pill mr-2">Update</button>
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
    var fileName = this.files.length > 0 ? this.files[0].name : "";
    document.getElementById("uploaded_file_name").value = fileName;
});
document.getElementById("staff_excel").addEventListener("change", function() {
    var fileName = this.files.length > 0 ? this.files[0].name : "";
    document.getElementById("uploaded_staff_file_name").value = fileName;
});
</script>
<script>
    ClassicEditor
        .create( document.querySelector( '#question' ) )
        .catch( error => {
            console.error( error );
        } );

        ClassicEditor
        .create( document.querySelector( '#answer' ) )
        .catch( error => {
            console.error( error );
        } );


        $(document).ready(function(){
            $('.first-level').addClass('in');

            $('#schoolEditForm').on('submit', function() {
                $('#page-loader').fadeIn(200);
            });
        });
</script>
@endsection
