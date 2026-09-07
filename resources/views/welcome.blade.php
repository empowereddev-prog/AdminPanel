<!DOCTYPE html>
<html lang="en">
<head>
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />

        <title>Tech Comp</title>

        <!-- GOOGLE FONTS -->
        <link href="https://fonts.googleapis.com/css?family=Karla:400,700|Roboto" rel="stylesheet">
        <link href="{{ url('assets/plugins/material/css/materialdesignicons.min.css') }}" rel="stylesheet" />
        <link href="{{ url('assets/plugins/simplebar/simplebar.css') }}" rel="stylesheet" />

        <!-- PLUGINS CSS STYLE -->
        <link href="{{ url('assets/plugins/nprogress/nprogress.css') }}" rel="stylesheet" />

        <!-- MONO CSS -->
        <link id="main-css-href" rel="stylesheet" href="{{ url('assets/css/style.css') }}" />
        <!-- FAVICON -->
        <link href="" rel="shortcut icon" />

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
                                    <img src="{{ url('assets/images/logo.png') }}" alt="Mono">
                                </a>
                            </div>
                        </div>
                        <div class="card-body px-5 pb-5 pt-0">
                            {{-- <h4 class="text-dark mb-6 text-center" style="font-family: 'Times New Roman', Times, serif; letter-spacing: 2px;">Tech Comp</h4> --}}
                            @if(session('fail'))
                            <div class="alert alert-danger" role="alert">{{ session('fail') }}</div>
                            @endif
                            <form action="#" method="post" enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="form-group col-md-12 mb-4">
                                    <label for="email">Email</label>
                                        <input type="email"
                                        class="form-control input-lg @if ($errors->has('email')) border-danger @endif"
                                        id="email" name="email" aria-describedby="emailHelp"
                                        placeholder="Email">
                                        @if ($errors->has('email'))
                                        <div class="text-danger small mt-1">
                                            {{ $errors->first('email') }}
                                        </div>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-12 field password">
                                    <label for="email">Password</label>
                                        <input type="password"
                                            class="form-control input-lg @if ($errors->has('password')) border-danger @endif"
                                            id="password" name="password" placeholder="Password">
                                        <span class="icon" id="icon"></span>
                                        @if ($errors->has('password'))
                                            <div class="text-danger small mt-1">
                                                {{ $errors->first('password') }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="col-md-12">
                                        <div class="d-flex justify-content-between mb-3">
                                            <div class="custom-control custom-checkbox mr-3 mb-3">
                                                <input type="checkbox" class="custom-control-input" id="customCheck2" name="remember_me">
                                                <label class="custom-control-label" for="customCheck2">Remember
                                                    me</label>
                                            </div>
                                            <a class="text-color" href="#"> Forgot password? </a>
                                        </div>
                                        <button type="submit" class="btn btn-primary submit btn-block btn-pill mb-4">Login</button>
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
