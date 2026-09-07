@extends('layout.headerFooter')
@section('content')
    <style>
        .hidden {
            display: none;
        }

        /* .error {
                                                                                                                                                                                                                                                                            color: red;
                                                                                                                                                                                                                                                                            display: none;
                                                                                                                                                                                                                                                                        }*/
    </style>

    <div class="content-wrapper">
        <div class="content">
            <div class="title_left">
                <h2>Edit User details</h2>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <ul class="admin-breadcrumb">
                        <li>
                            <a href="{{ route('user.index') }}">User Management</a>
                        </li>
                        <li class="active">
                            <a href="#">Edit User</a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="row mx-0">
                <div class="col-sm-12">
                    <div class="row card card-default">
                        <div class="col-sm-12">

                            <div class="form-wrapper">
                                <form id="bannerForm" action="{{ route('user.update', $data->id) }}"
                                    enctype="multipart/form-data" method="post">
                                    @csrf
                                    @method('PUT')
                                    <div class="row mb-5">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="exampleFormControlFile1">Name</label>
                                                <input type="text" class="form-control" name="name"
                                                    id="exampleFormControlName" placeholder="Enter name"
                                                    value="{{ old('name', $data->name) }}" maxlength="100">
                                                @if ($errors->has('name'))
                                                    <div class="text-danger small mt-1">
                                                        <strong>{{ $errors->first('name') }}</strong>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-6  ">
                                            <div class="form-group">
                                                <label for="exampleFormControlFile1">Email</label>
                                                <input type="text" class="form-control" name="email"
                                                    id="exampleFormControlName" placeholder="Enter email"
                                                    value="{{ old('email', $data->email) }}" maxlength="100" readonly>
                                                @if ($errors->has('email'))
                                                    <div class="text-danger small mt-1">
                                                        <strong>{{ $errors->first('email') }}</strong>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 ">
                                            <div class="form-group">
                                                <label for="exampleFormControlFile1">Phone no.</label>
                                                <input type="text" class="form-control" name="phone_no"
                                                    id="exampleFormControlName" placeholder="Enter phone number"
                                                    value="{{ old('phone_no', ($data->country_code ?? '') . ($data->phone_no ? ' ' . $data->phone_no : '')) }}"
                                                    readonly>
                                                @if ($errors->has('phone_no'))
                                                    <div class="text-danger small mt-1">
                                                        <strong>{{ $errors->first('phone_no') }}</strong>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-6  ">
                                            <div class="form-group">
                                                <label for="exampleFormControlFile1">User Status</label>
                                                <select name="status" class="form-control" id="optionSelect">
                                                    <option value="">Select User Status</option>
                                                    <option value="active"
                                                        {{ $data->status == 'active' ? 'selected' : '' }}>Active
                                                    </option>
                                                    <option value="inactive"
                                                        {{ $data->status == 'inactive' ? 'selected' : '' }}>Inactive
                                                    </option>
                                                </select>
                                                @if ($errors->has('status'))
                                                    <div class="text-danger small mt-1">
                                                        <strong>{{ $errors->first('status') }}</strong>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    {{-- ================= Parent Progress ================= --}}
                                    @if (!empty($data->categoryProgress))
                                        <div class="card p-3 mt-3 mb-4 bg-light"
                                            style="width: 100%; max-width: 931px; border: 2px solid #afb2b5;">
                                            <h5 class="mb-3 text-primary">Parent Video Progress</h5>

                                            <div class="row">
                                                @foreach ($data->categoryProgress as $progress)
                                                    <div class="col-md-6 mb-3 ">
                                                        <strong>{{ $progress['category_name'] }}</strong>
                                                        <div class="d-flex align-items-center justify-content-between ">
                                                            <span>{{ (int) $progress['percentage'] }}%</span>
                                                            <div class="progress flex-grow-1 ml-2" style="height: 8px;">
                                                                <div class="progress-bar bg-primary"
                                                                    style="width: {{ $progress['percentage'] }}%">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    <div class="card p-3 mt-3 mb-4 bg-light"
                                    style="width: 100%; max-width: 931px; border: 2px solid #afb2b5;">

                                    <h5 class="mb-3 text-primary">Policy Verification Details</h5>

                                    {{-- Popup 1 --}}
                                    <div class="mb-3">
                                        <strong>Parental consent policy Verified at:</strong>

                                        @if($data->popup_1_updated_at)
                                            <span class="text-success">
                                                {{ $data->popup_1_updated_at->format('d M Y, h:i A') }}
                                            </span>
                                        @else
                                            <span class="text-danger">Not Verified</span>
                                        @endif
                                    </div>

                                    {{-- Popup 2 --}}
                                    <div class="mb-2">
                                        <strong>Your child’s privacy policy verified at:</strong>

                                        @if($data->popup_2_updated_at)
                                            <span class="text-success">
                                                {{ $data->popup_2_updated_at->format('d M Y, h:i A') }}
                                            </span>
                                        @else
                                            <span class="text-danger">Not Verified</span>
                                        @endif
                                    </div>

                                </div>

                                    <!-- child details  -->
                                    @if ($children->isNotEmpty())
                                        @foreach ($children as $index => $child)
                                            @php
                                                $progressItems = $child->categoryProgress ?? [];
                                                $chunks =
                                                    count($progressItems) > 0
                                                        ? array_chunk($progressItems, ceil(count($progressItems) / 2))
                                                        : [];
                                            @endphp

                                            <div class="card p-3 mt-3 mb-4"
                                                style="width: 100%; max-width: 931px; border: 1px solid gray;">
                                                {{-- <h5 class="mb-3">Child {{ $index + 1 }}</h5> --}}
                                                <h5 class="mb-3">
                                                    Child {{ $index + 1 }}

                                                    <button type="button" class="float-right delete-child"
                                                        data-id="{{ $child->id }}" title="Delete Child">
                                                        <span class="mdi mdi-trash-can text-danger "></span>
                                                    </button>
                                                </h5>
                                                @php
                                                    $childAge = $child->dob
                                                        ? (int) \Carbon\Carbon::parse($child->dob)->diffInYears(\Carbon\Carbon::now())
                                                        : 0;
                                                @endphp
                                                @if($childAge >= 19)
                                                    <div class="alert alert-danger d-flex align-items-center mb-3" role="alert"
                                                        style="border-left: 5px solid #dc3545; font-weight: 500;">
                                                        <i class="mdi mdi-alert-circle mr-2" style="font-size: 20px;"></i>
                                                        <span>
                                                            <strong>Age Policy Violation:</strong>
                                                            This child has exceeded the maximum age of 18 years. As per our policy,
                                                            the account has been deactivated automatically.
                                                        </span>
                                                    </div>
                                                @endif
                                                <input type="hidden" name="children[{{ $index }}][id]"
                                                    value="{{ $child->id }}">

                                                <div class="row align-items-center">
                                                    <div class="col-md-4 col-sm-12 text-center">
                                                        <label>Image</label>
                                                        <div>
                                                            @php
                                                                $childImage =
                                                                    $child->is_avatar_primary == 'yes'
                                                                        ? $child->avtar_image
                                                                        : $child->image;
                                                                $imageUrl = $childImage
                                                                    ? getImagePathUrl($childImage, 'assets/avtar')
                                                                    : asset('assets/avtar/defalt.jpg');
                                                            @endphp

                                                            <img src="{{ $imageUrl }}" alt="Child Image"
                                                                class="img-fluid rounded"
                                                                style="max-width: 150px; border: 2px solid #000;">
                                                        </div>

                                                        {{-- <div>
                                                            @if ($child->avtar_image || $child->image)
                                                                <img src="{{ $child->avtar_image ?? $child->image }}"
                                                                    alt="Child Image" class="img-fluid rounded"
                                                                    style="max-width: 150px; border: 2px solid #000;">
                                                            @else
                                                                <img src="{{ asset('assets/avtar/defalt.jpg') }}"
                                                                    alt="Child Image" class="img-fluid rounded"
                                                                    style="max-width: 150px; border: 2px solid #000;">
                                                            @endif
                                                        </div> --}}

                                                        {{-- <input type="file" class="form-control mt-2" name="children[{{ $index }}][image]"> --}}
                                                    </div>

                                                    <div class="col-md-8 col-sm-12">

                                                        <div class="row">

                                                            <div class="col-md-6 col-sm-12">
                                                                <div class="form-group">
                                                                    <label>Name</label>
                                                                    <input type="text" class="form-control"
                                                                        name="children[{{ $index }}][name]"
                                                                        value="{{ $child->name }}">
                                                                    @if ($errors->has("children.$index.name"))
                                                                        <div class="text-danger small mt-1">
                                                                            {{ $errors->first("children.$index.name") }}
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6 col-sm-12">
                                                                <div class="form-group">
                                                                    <label>User Name</label>
                                                                    <input type="text" class="form-control"
                                                                        name="children[{{ $index }}][name]"
                                                                        value="{{ $child->username }}" readonly>
                                                                    @if ($errors->has("children.$index.name"))
                                                                        <div class="text-danger small mt-1">
                                                                            {{ $errors->first("children.$index.name") }}
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row mt-2">
                                                            <div class="col-md-6 col-sm-12">
                                                                <div class="form-group">
                                                                    <label>Date of Birth</label>
                                                                    <input type="month" class="form-control"
                                                                        name="children[{{ $index }}][dob]"
                                                                        value="{{ \Carbon\Carbon::parse($child->dob)->format('Y-m') }}" readonly>
                                                                    @if ($errors->has("children.$index.dob"))
                                                                        <div class="text-danger small mt-1">
                                                                            {{ $errors->first("children.$index.dob") }}
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6 col-sm-12">
                                                                <div class="form-group">
                                                                    <label>Age</label>
                                                                    <input type="text" class="form-control"
                                                                        name="children[{{ $index }}][age]"
                                                                        value="{{ $child->age }} year" readonly>
                                                                </div>
                                                            </div>
                                                            {{-- <div class="col-md-6 col-sm-12">
                                                                <div class="form-group">
                                                                    <label>Loyalty points</label>
                                                                    <input type="text" class="form-control"
                                                                        name="children[{{ $index }}][loyalty_points]"
                                                                        value="{{ $child->loyalty_points }}">
                                                                    @if ($errors->has("children.$index.loyalty_points"))
                                                                        <div class="text-danger small mt-1">
                                                                            {{ $errors->first("children.$index.loyalty_points") }}
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            </div> --}}
                                                        </div>

                                                        <div class="row mt-3">
                                                            <div class="col-md-12">
                                                                <div class="p-4 border rounded bg-light shadow-sm w-100">
                                                                    <h6 class="mb-3 text-primary"
                                                                        style="font-weight: 600;">Category Progress</h6>

                                                                    @if (!empty($chunks))
                                                                        <div class="row">
                                                                            @foreach ($chunks as $chunk)
                                                                                <div class="col-md-6">
                                                                                    @foreach ($chunk as $progress)
                                                                                        <div class="mb-4">
                                                                                            <strong>{{ $progress['category_name'] }}</strong>
                                                                                            <div
                                                                                                class="d-flex align-items-center justify-content-between">
                                                                                                <span>{{ (int) $progress['percentage'] }}%</span>
                                                                                                <div class="progress flex-grow-1 ml-2"
                                                                                                    style="height: 8px;">
                                                                                                    <div class="progress-bar bg-primary"
                                                                                                        role="progressbar"
                                                                                                        style="width: {{ $progress['percentage'] }}%;"
                                                                                                        aria-valuenow="{{ $progress['percentage'] }}"
                                                                                                        aria-valuemin="0"
                                                                                                        aria-valuemax="100">
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>
                                                                                    @endforeach
                                                                                </div>
                                                                            @endforeach
                                                                        </div>
                                                                    @else
                                                                        <p class="text-muted mb-0">No progress data
                                                                            available.</p>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row mt-3">
                                                            <div class="col-md-12">
                                                                <div class="p-4 border rounded bg-light shadow-sm w-100">
                                                                    <h6 class="mb-3 text-primary"
                                                                        style="font-weight: 600;">Quiz Progress</h6>

                                                                    @if (!empty($child->quizProgress))
                                                                        <div class="row">
                                                                            @foreach ($child->quizProgress as $quiz)
                                                                                <div class="col-md-6 mb-4">
                                                                                    <strong>{{ $quiz['category_name'] }}</strong>
                                                                                    <div
                                                                                        class="d-flex align-items-center justify-content-between">
                                                                                        <span>{{ round($quiz['percentage']) }}%</span>
                                                                                        <div class="progress flex-grow-1 ml-2"
                                                                                            style="height: 8px;">
                                                                                            <div class="progress-bar bg-success"
                                                                                                role="progressbar"
                                                                                                style="width: {{ $quiz['percentage'] }}%;"
                                                                                                aria-valuenow="{{ $quiz['percentage'] }}"
                                                                                                aria-valuemin="0"
                                                                                                aria-valuemax="100">
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            @endforeach
                                                                        </div>
                                                                    @else
                                                                        <p class="text-muted mb-0">No quiz progress data
                                                                            available.</p>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row mt-3">
                                                            <div class="col-md-12">
                                                                <div class="p-4 border rounded bg-light shadow-sm w-100">

                                                                    <h6 class="mb-3 text-primary" style="font-weight: 600;">
                                                                        Policy Verification Details
                                                                    </h6>

                                                                    {{-- Popup 1 --}}
                                                                    <div class="mb-3">
                                                                        <strong>Parental consent policy Verified at:</strong>

                                                                        @if($child->popup_1_updated_at)
                                                                            <span class="text-success">
                                                                                {{ $child->popup_1_updated_at->format('d M Y, h:i A') }}
                                                                            </span>
                                                                        @else
                                                                            <span class="text-danger">Not Verified</span>
                                                                        @endif
                                                                    </div>

                                                                    {{-- Popup 2 --}}
                                                                    <div>
                                                                        <strong>Your child’s privacy policy verified at:</strong>

                                                                        @if($child->popup_2_updated_at)
                                                                            <span class="text-success">
                                                                                {{ $child->popup_2_updated_at->format('d M Y, h:i A') }}
                                                                            </span>
                                                                        @else
                                                                            <span class="text-danger">Not Verified</span>
                                                                        @endif
                                                                    </div>

                                                                </div>
                                                            </div>
                                                        </div>


                                                    </div> <!-- col-md-8 -->
                                                </div> <!-- row -->
                                            </div> <!-- card -->
                                        @endforeach
                                    @else
                                        <p class="mt-4 text-muted text-center">No children found.</p>
                                    @endif


                                    <input type="hidden" name="check" id="check">
                                    <input type="hidden" name="getid" id="getid" value="{{ $data->id }}">
                                    <input type="hidden" name="types" value="edit">
                                    <!-- <div class="form-footer mt-6">
                                                                                                                                                                                                                                                                                                        <button type="submit" class="btn btn-primary mr-2">Update</button>
                                                                                                                                                                                                                                                                                                        <a href="{{ route('user.index') }}"><button type="button" class="btn btn-primary btn-pill">Cancel</button></a>
                                                                                                                                                                                                                                                                                                    </div> -->


                                    <div class="form-footer mt-3"
                                        style="
                                    margin-left: 30px;">
                                        <button type="submit" class="btn btn-primary btn-pill  ">Update</button>
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
    </div>

    <!-- <script type="text/javascript">
        function toggleInputs() {
            var imageInput = document.getElementById('imageInput');
            var videoInput = document.getElementById('videoInput');
            var selectedValue = document.getElementById('optionSelect').value;

            if (selectedValue === 'banner_video') {
                videoInput.classList.remove('hidden');
                imageInput.classList.add('hidden');
                document.getElementById('check').value = 1;
            } else {
                videoInput.classList.add('hidden');
                imageInput.classList.remove('hidden');
                document.getElementById('check').value = 0;
            }
        }
        document.getElementById('optionSelect').addEventListener('change', toggleInputs);
        window.onload = toggleInputs;
    </script> -->

    <script src="https://cdn.ckeditor.com/ckeditor5/23.0.0/classic/ckeditor.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
    <script type="text/javascript">
        function toggleInputs() {
            var imageInput = document.getElementById('imageInput');
            var videoInput = document.getElementById('videoInput');
            var selectedValue = document.getElementById('optionSelect').value;

            if (selectedValue === 'banner_video') {
                videoInput.classList.remove('hidden');
                imageInput.classList.add('hidden');
                document.getElementById('check').value = 1;
                // document.getElementById('optionSelect').value = selectedValue;
            } else {
                videoInput.classList.add('hidden');
                imageInput.classList.remove('hidden');
                document.getElementById('check').value = 0;
            }
        }
        document.getElementById('optionSelect').addEventListener('change', toggleInputs);
        window.onload = toggleInputs;



        //     $(document).ready(function() {
        //     $('#optionSelect').change(function() {
        //         var banner_type = $(this).val();
        //         var getid = document.getElementById('getid').value;
        //         var  action = "{{ url('admin/update-type-banner') }}";
        //         var csrfToken = $('meta[name="csrf-token"]').attr('content');

        //         $.ajax({
        //             url: action,
        //             type: "POST",
        //             dataType: "json",
        //             data: {
        //                 banner_type: banner_type,
        //                 getid: getid,
        //                 _token: csrfToken,
        //             },
        //         })
        //     })
        // });


        $(document).ready(function() {
            $('.first-level').addClass('in');

            $('.contents').each(function() {
                ClassicEditor.create(this)
                    .catch(error => {
                        console.error(error);
                    });
            });
        })
        $(document).on('click', '.delete-child', function() {

            let childId = $(this).data('id');

            Swal.fire({
                title: 'Are you sure?',
                text: "You want to delete this child!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {

                if (result.isConfirmed) {

                    $.ajax({
                        url: "/delete-child/" + childId,
                        type: "DELETE",
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(response) {

                            Swal.fire(
                                'Deleted!',
                                'Child deleted successfully.',
                                'success'
                            ).then(() => {
                                location.reload();
                            });

                        }
                    });

                }

            });

        });
    </script>
@endsection
