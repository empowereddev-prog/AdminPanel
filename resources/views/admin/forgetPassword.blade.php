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
    <link href="{{ url('assets/images/new.png') }}" rel="shortcut icon" />

    </head>
</head>
<body class="bg-light-gray login-page" id="body">
    <div class="container d-flex align-items-center justify-content-center" style="min-height: 100vh">
        <div class="d-flex flex-column justify-content-between">
            <div class="row justify-content-center">
                <div class="col-lg-5 col-md-10">
                    <div class="card card-default mb-0 login-box">
                        <div class="card-header pb-0">
                            <div class="app-brand w-100 d-flex justify-content-center border-bottom-0">
                                <a class="w-auto pl-0">
                                    <img src="{{url('assets/images/newlogo.png')}}" alt="AE-LOGO">
                                </a>
                            </div>
                        </div>
                        <div class="card-body px-5 pb-5 pt-0">
                            <h4 class="text-dark mb-6 text-center" style="font-family: 'Times New Roman', Times, serif; letter-spacing: 2px;">Forgot Password</h4>
                            @if(session('fail'))
                                <div class="alert alert-danger alert-dismissible fade show" role="alert" id="failAlert">
                                    {{ session('fail') }}
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close" onclick="hideAlerts()">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endif
                            @if(session('pass'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert" id="successAlert">
                                    {{ session('pass') }}
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close" onclick="hideAlerts()">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endif

                            <form class="first-form" action="{{route('reset-password')}}" method="post" enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-md-12">
                                    Enter your email and we'll send you instructions to reset your password
                                    </div>
                                    <div class="form-group col-md-12 mt-2">
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
                                    <div class="col-md-12 mt-2">
                                        <button type="submit" class="btn btn-primary submit btn-block btn-pill mb-4">Send Reset Link</button>
                                    </div>
                                    <div class="col-md-12 mt-2 text-center">
                                        <a href="{{url('/')}}">Back To Login</a>
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
   <script>
    //  document.addEventListener("DOMContentLoaded", function() {
    //     let emailField = document.getElementById("email");

    //     // Restore fields if available in sessionStorage
    //     if (sessionStorage.getItem("email")) {
    //         emailField.value = sessionStorage.getItem("email");
    //     }
      

    //     // Store values when form is submitted
    //     document.querySelector(".first-form").addEventListener("submit", function() {
    //         sessionStorage.setItem("email", emailField.value);
    //     });

    //     // Clear stored values on successful login (no error message)
    //     if (!document.querySelector(".alert-danger")) {
    //         sessionStorage.removeItem("email");
    //     }

       
    // });
        function hideAlerts() {
            var successAlert = document.getElementById('successAlert');
            var failAlert = document.getElementById('failAlert');
            if (successAlert) {
                successAlert.style.display = 'none';
            }
            if (failAlert) {
                failAlert.style.display = 'none';
            }
        }
    </script>
</body>
</html>
