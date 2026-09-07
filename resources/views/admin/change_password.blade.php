@extends('layout.headerFooter')
@section('content')
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- Eye Icon Styling -->
    <style>
        .eye-icon {
            position: absolute;
            top: 23px;
            right: 15px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
            margin-right: 13px;
        }

        
    </style>

    <div class="content-wrapper">
        <div class="content">
            <div class="row">
                <div class="col-xl-12">
                    <div class="card card-default">
                        <div class="card-header">
                            <h2 class="mb-5">Change Password</h2>
                        </div>
                        <div class="card-body">

                            @if (session('success'))
                                <div class="alert alert-success">{{ session('success') }}</div>
                            @endif
                            @if (session('error'))
                                <div class="alert alert-danger">{{ session('error') }}</div>
                            @endif

                            <form action="{{ route('update-password') }}" method="POST">
                                @csrf

                                <div class="form-group row mb-6">
                                    <label for="current_password" class="col-sm-5 col-lg-3 col-form-label">Current
                                        Password</label>
                                    <div class="col-sm-7 col-lg-9 position-relative">
                                        <input type="password" class="form-control" id="current_password"
                                            name="current_password" placeholder="Current Password">
                                        <i class="fa-solid fa-eye-slash eye-icon toggle-password"
                                            data-target="current_password"></i>
                                        @error('current_password')
                                            <div class="text-danger small mt-1 danger-class">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="form-group row mb-6">
                                    <label for="password" class="col-sm-5 col-lg-3 col-form-label">New Password</label>
                                    <div class="col-sm-7 col-lg-9 position-relative">
                                        <input type="password" class="form-control " id="password" name="password"
                                            placeholder="New Password">
                                        <i class="fa-solid fa-eye-slash eye-icon toggle-password"
                                            data-target="password"></i>
                                        @error('password')
                                            <div class="text-danger small mt-1 danger-class">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="form-group row mb-6">
                                    <label for="confirm_password" class="col-sm-5 col-lg-3 col-form-label">Confirm
                                        Password</label>
                                    <div class="col-sm-7 col-lg-9 position-relative">
                                        <input type="password" class="form-control" id="confirm_password"
                                            name="confirm_password" placeholder="Confirm Password">
                                        <i class="fa-solid fa-eye-slash eye-icon toggle-password"
                                            data-target="confirm_password"></i>
                                        @error('confirm_password')
                                            <div class="text-danger small mt-1 danger-class">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary mb-2 btn-pill">Update Password</button>
                                    <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary mb-2  "
                                        style="border-radius: 50px;">Cancel</a>
                            </form>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Toggle Password Visibility Script -->
<script>
    $(document).ready(function() {
        $('.toggle-password').on('click', function() {
            let input = $('#' + $(this).data('target'));
            let isPassword = input.attr('type') === 'password';

            input.attr('type', isPassword ? 'text' : 'password');

            // Toggle icon
            $(this)
                .toggleClass('fa-eye-slash', !isPassword)
                .toggleClass('fa-eye', isPassword);
        });
    });
</script>

{{-- <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    $(document).ready(function() {
        $(".third-form").submit(function(event) {
        event.preventDefault(); // Prevent default form submission

        Swal.fire({
            title: "Are you sure?",
            text: "After changing your password, you will be logged out automatically.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, change it!",
            cancelButtonText: "Cancel",
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            reverseButtons: false
        }).then((result) => {
            if (result.isConfirmed) {
                let formData = new FormData(this);

                $.ajax({
                    url: "{{ route('update-password') }}", 
                    method: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    xhrFields: { withCredentials: true }, // Ensures cookies/sessions are sent
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        Swal.fire({
                            title: "Success",
                            text: "Password updated successfully! Redirecting...",
                            icon: "success",
                            timer: 2000,
                            showConfirmButton: true
                        }).then(() => {
                            window.location.href = "{{ route('logout') }}"; // Redirect after success
                        });
                    },
                    error: function(xhr) {
                        if (xhr.status === 401) { 
                            Swal.fire({
                                title: "Unauthorized",
                                text: "Your session has expired. Please log in again.",
                                icon: "error"
                            }).then(() => {
                                window.location.href = "{{ route('login') }}"; // Redirect to login page
                            });
                        } else if (xhr.status === 422) { 
                            let errors = xhr.responseJSON.errors;
                            let errorMessage = Object.values(errors).flat().join("\n");

                            Swal.fire({
                                title: "Validation Error",
                                text: errorMessage,
                                icon: "error"
                            });
                        } else {
                            Swal.fire({
                                title: "Error",
                                text: "Something went wrong. Please try again.",
                                icon: "error"
                            });
                        }
                    }
                });
            }
        });
    });
    // });

        // Toggle password visibility
        $(".icon.eye").click(function() {
            let input = $(this).siblings("input");
            if (input.attr("type") === "password") {
                input.attr("type", "text");
            } else {
                input.attr("type", "password");
            }
            $(this).toggleClass("visible");
        });
    });
</script> --}}
