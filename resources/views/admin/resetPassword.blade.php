<!DOCTYPE html>
<html lang="en">
<head>
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />

        <title>EmpowerEd Child Healthcare</title>

        <!-- GOOGLE FONTS -->
        <link href="https://fonts.googleapis.com/css?family=Karla:400,700|Roboto" rel="stylesheet">
        <link href="{{ url('assets/plugins/material/css/materialdesignicons.min.css') }}" rel="stylesheet" />
        <link href="{{ url('assets/plugins/simplebar/simplebar.css') }}" rel="stylesheet" />

        <!-- PLUGINS CSS STYLE -->
        <link href="{{ url('assets/plugins/nprogress/nprogress.css') }}" rel="stylesheet" />

        <!-- MONO CSS -->
        <link id="main-css-href" rel="stylesheet" href="{{ url('assets/css/style.css') }}" />
        <!-- FAVICON -->
        <!-- <link href="{{ url('assets/images/new.png') }}" rel="shortcut icon" /> -->
        <!-- <link rel="apple-touch-icon" sizes="180x180" href="{{url('assets/images/apple-touch-icon.png')}}"> -->
        {{-- <link rel="icon" type="image/png" href="{{url('assets/images/favicon.png')}}"> --}}
        <link href="{{ url('assets/images/new.png') }}" rel="shortcut icon" />
        <!-- <link rel="icon" type="image/png" sizes="16x16" href="{{url('assets/images/favicon-16x16.png')}}"> -->

    </head>
</head>
<body class="bg-light-gray login-page" id="body">
    <div class="container d-flex align-items-center justify-content-center" style="min-height: 100vh">
        <div class="d-flex flex-column justify-content-between">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-md-10">
                    <div class="card card-default mb-0 login-box">
                        <div class="card-header pb-0">
                            <div class="app-brand w-100 d-flex justify-content-center border-bottom-0">
                                <a class="w-auto pl-0">
                                    <img src="{{url('assets/images/newlogo.png')}}" alt="AE-LOGO">
                                </a>
                            </div>
                        </div>
                        <div class="card-body px-5 pb-5 pt-0">
                            <h4 class="text-dark mb-6 text-center" style="font-family: 'Times New Roman', Times, serif; letter-spacing: 2px;">Reset Password</h4>
                            @if(session('fail'))
                            <div class="alert alert-danger" role="alert">{{ session('fail') }}</div>
                            @endif
                            <form class="second-form" action="{{ url('reset-password/'.$token)}}" method="post">
                                @csrf
                                <div class="row">
                                    <input type="hidden" name="old_password" value="{{$decryptedPassword}}">
                                    <div class="form-group col-md-12 mb-4 field password">
                                    <label for="email">New Password</label>
                                    <input type="password"
                                            class="form-control input-lg @if ($errors->has('password')) border-danger @endif"
                                            id="password" name="password" placeholder="New password">
                                        <span class="icon" id="icon" style="
                                        top: 51px;
                                    "></span>
                                        @if ($errors->has('password'))
                                            <div class="text-danger small mt-1">
                                                {{ $errors->first('password') }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-12 field password">
                                    <label for="email">Confirm Password</label>
                                        <input type="password"
                                            class="form-control input-lg @if ($errors->has('confirm_password')) border-danger @endif"
                                            id="confirm_password" name="confirm_password" placeholder="Confirm password">
                                          <span class="icon" id="icon" style="
    top: 51px;
"></span>
                                        @if ($errors->has('confirm_password'))
                                            <div class="text-danger small mt-1">
                                                {{ $errors->first('confirm_password') }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="col-md-12 mt-2">
                                        <button type="submit" class="btn btn-primary submit btn-block btn-pill mb-4 resetchanges">Reset Password</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="{{ url('assets/plugins/nprogress/nprogress.js') }}"></script>
</body>
</html>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    $(document).ready(function() {
        // Fetch Laravel session data into JavaScript variables
        var sessionSuccess = @json(session('pass'));
        var sessionPass = @json(session('pass'));
        var resetToken = "{{ $token }}"; // Pass token from Blade
        // Debugging: Log session values to the console
        console.log("Session Success:", sessionSuccess);
        console.log("Session Pass:", sessionPass);

        if (sessionPass) {  // Check if session 'pass' exists
            Swal.fire({
                title: "Are you sure?",
                text: "You want to reset your password",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, change it!",
                cancelButtonText: "Cancel",
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                reverseButtons: false
            }).then((result) => {
                if (result.isConfirmed) {
                Swal.fire({
                    text: "Password Changed Successfully!",
                    icon: "success",
                    showConfirmButton: true,
                }).then(() => {
                    // Redirect after clicking "OK"
                    // window.location.href = "{{ route('success', ['token' => $token]) }}";
                    window.location.href = "{{ route('success') }}";
                });
            }
            });
        }
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
</script>
<script>

document.addEventListener("DOMContentLoaded", function() {
        let emailField = document.getElementById("password");
        let passwordField = document.getElementById("confirm_password");
        let eyeIcon = document.getElementById("icon");

        // Restore fields if available in sessionStorage
        if (sessionStorage.getItem("password")) {
            emailField.value = sessionStorage.getItem("password");
        }
        if (sessionStorage.getItem("confirm_password")) {
            passwordField.value = sessionStorage.getItem("confirm_password");
        }

        // Store values when form is submitted
        document.querySelector(".second-form").addEventListener("submit", function() {
            sessionStorage.setItem("password", emailField.value);
            sessionStorage.setItem("confirm_password", passwordField.value);
        });

        // Clear stored values on successful login (no error message)
        if (!document.querySelector(".alert-danger")) {
            sessionStorage.removeItem("password");
            sessionStorage.removeItem("confirm_password");
        }

       
    });
    window.addEventListener("DOMContentLoaded", function(){
        const fields = document.querySelectorAll(".field");
        fields.forEach(field=>{
            let icon = field.querySelector(".icon");
            icon.addEventListener("click", function(){
                field.querySelector(".icon").classList.toggle("visible");
                if(field.querySelector("input").type === "password") {
                    field.querySelector("input").type = "text";
                } else {
                    field.querySelector("input").type = "password";
                }
            });
        });
    });

</script>
