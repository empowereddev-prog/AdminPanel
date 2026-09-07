<!DOCTYPE html>
<html lang="en">
<head>
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
        <meta http-equiv="Pragma" content="no-cache">
        <meta http-equiv="Expires" content="0">
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
    <link href="{{ url('assets/images/new.png') }}" rel="shortcut icon" />
    <!-- <link rel="apple-touch-icon" sizes="180x180" href="{{url('assets/images/apple-touch-icon.png')}}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{url('assets/images/favicon-32x32.png')}}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{url('assets/images/favicon-16x16.png')}}"> -->

    <style>
        body.login-page {
            /* This forces a solid blue background.
            Adjust the hex code (#0047AB) to your preferred shade of blue */
            background: #1a5edb !important;
            background-image: none !important;
        }
    </style>

    </head>
</head>
<body class="login-page" id="body">
    <div class="container">
        <div class="d-flex flex-column justify-content-between">
            <div class="form-wrapper row justify-content-center align-items-center">
                <div class="col-sm-12 col-md-5 col-lg-5 branding">
                    <div class="d-flex align-items-end">
                        <a href="#">
                            <img src="{{url('assets/images/newlogo.png')}}" alt="AE Logo"/>
                        </a>
                    </div>
                </div>
                <div class="col-sm-12 col-md-7 col-lg-7">
                    <div class="card form-container">
                        <div class="card-header border-0 p-0 bg-white">
                            <h2 class="text-center">Log In to Admin Account</h2>
                            <p class="text-center">Please enter your email and password to continue</p>
                        </div>
                        <div class="card-body p-0">
                            {{-- <h4 class="text-dark mb-6 text-center">Tech Comp</h4> --}}
                            @if(session('fail'))
                            <div class="alert alert-danger" role="alert">{{ session('fail') }}</div>
                            @endif
                            <form class="admin-form login" action="{{url('/login')}}" method="post" enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label for="email">Email</label>
                                        <input type="email"
                                        class="form-control input-lg @if ($errors->has('email')) border-danger @endif"
                                        id="email" name="email" aria-describedby="emailHelp"
                                        placeholder="Enter Email" value="{{ old('email') }}">
                                        @if ($errors->has('email'))
                                        <div class="text-danger small mt-1">
                                            {{ $errors->first('email') }}
                                        </div>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-12 field password" style="
    display: grid;
">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <label for="email">Password</label>
                                            <!-- <a class="forget-password" href="{{route('forgot-password')}}">Forgot Password?</a> -->
                                        </div>
                                        <input type="password"
                                            class="form-control input-lg @if ($errors->has('password')) border-danger @endif"
                                            id="password" name="password" placeholder="Enter Password" value="{{ old('password') }}">
                                        <span class="icon" id="icon"></span>
                                        @if ($errors->has('password'))
                                            <div class="text-danger small mt-1">
                                                {{ $errors->first('password') }}
                                            </div>
                                        @endif
                                        <div class="col-md-12 field password" style="
    margin: 0;
    padding: 0;
">
                                    <div class="d-flex justify-content-between align-items-center">
                                            <label for="email"></label>
                                            <a class="forget-password" href="{{route('forgot-password')}}">Forgot Password?</a>
                                        </div>
                                     </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="d-flex justify-content-between mb-3">
                                            <!-- <div class="custom-control custom-checkbox mr-3 mb-3">
                                                <input type="checkbox" class="custom-control-input" id="customCheck2" name="remember_me">
                                                <label class="custom-control-label" for="customCheck2">Remember
                                                    me</label>
                                            </div> -->
                                        </div>
                                        <!-- <div class="col-md-12 mt-2"> -->
                                        <button type="submit" class="btn btn-primary submit btn-block btn-pill mb-4">Log In</button>
                                    <!-- </div> -->
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

<script>
    document.addEventListener("DOMContentLoaded", function() {
        let emailField = document.getElementById("email");
        let passwordField = document.getElementById("password");
        let eyeIcon = document.getElementById("icon");

        // Restore fields if available in sessionStorage
        if (sessionStorage.getItem("email")) {
            emailField.value = sessionStorage.getItem("email");
        }
        if (sessionStorage.getItem("password")) {
            passwordField.value = sessionStorage.getItem("password");
        }

        // Store values when form is submitted
        document.querySelector(".admin-form").addEventListener("submit", function() {
            sessionStorage.setItem("email", emailField.value);
            sessionStorage.setItem("password", passwordField.value);
        });

        // Clear stored values on successful login (no error message)
        if (!document.querySelector(".alert-danger")) {
            sessionStorage.removeItem("email");
            sessionStorage.removeItem("password");
        }


    });
    window.addEventListener("DOMContentLoaded", function(){
        let eyeIcon = document.getElementById("icon"),
        password = document.getElementById("password");
        eyeIcon.addEventListener("click", function(){
            document.querySelector(".password .icon").classList.toggle("visible");
            if(password.type === "password") {
                password.type = "text";
            } else {
                password.type = "password"
            }
        })
    })

</script>
