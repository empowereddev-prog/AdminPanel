<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>OTP Verification</title>
    <link href="https://fonts.googleapis.com/css?family=Karla:400,700|Roboto" rel="stylesheet">
    <link href="{{ url('assets/css/style.css') }}" rel="stylesheet" />
    <link href="{{ url('assets/images/new.png') }}" rel="shortcut icon" />

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        body.login-page {
            /* This forces a solid blue background.
            Adjust the hex code (#0047AB) to your preferred shade of blue */
            background: #1a5edb !important;
            background-image: none !important;
        }
    </style>
</head>

<body class="bg-light-gray login-page" id="body">
    <div class="container d-flex align-items-center justify-content-center" style="min-height: 100vh">
        <div class="d-flex flex-column justify-content-between">
            <div class="row justify-content-center" style="
                    width: 200%;
                ">
                <div class="col-lg-5 col-md-10"
                    style="
                        width: 200%;
                        margin-right: 40%;
                    ">
                    <div class="card card-default mb-0 login-box">
                        <div class="card-header pb-0">
                            <div class="app-brand w-100 d-flex justify-content-center border-bottom-0">
                                <a class="w-auto pl-0">
                                    <img src="../assets/images/newlogo.png" alt="AE-LOGO">
                                </a>
                            </div>
                        </div>
                        <div class="card-body px-5 pb-5 pt-0">
                            <h4 class="text-dark mb-6 text-center"
                                style="font-family: 'Times New Roman', Times, serif; letter-spacing: 2px;">OTP
                                Verification</h4>
                            @if (session('fail'))
                                <div class="alert alert-danger alert-dismissible fade show" role="alert"
                                    id="failAlert">
                                    {{ session('fail') }}
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"
                                        onclick="hideAlerts()">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endif
                            @if (session('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert"
                                    id="successAlert">
                                    {{ session('success') }}
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"
                                        onclick="hideAlerts()">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endif

                            <form action="{{ route('admin.verifyOtp', ['email' => $email]) }}" method="post"
                                enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-md-12">
                                        <input type="hidden" name="email" value="{{ $email }}">

                                        <!-- Enter your email and we'll send you instructions to reset your password -->
                                    </div>
                                    <div class="form-group col-md-12 mt-2">
                                        <label for="email">OTP</label>
                                        <input type="text"
                                            class="form-control input-lg @if ($errors->has('otp')) border-danger @endif"
                                            id="otp" name="otp" aria-describedby="emailHelp"
                                            placeholder="Enter OTP">
                                        @if ($errors->has('otp'))
                                            <div class="text-danger small mt-1">
                                                {{ $errors->first('otp') }}
                                            </div>
                                        @endif
                                    </div>

                                    <!-- <div id="step2" > -->
                                    <!-- <p>Enter the OTP sent to your email.</p> -->

                                    <div class="col-md-12 mt-2">
                                        <button class="btn btn-success btn-block" id="verifyOtp"
                                            style="
    background: linear-gradient(#FF005E,#FFBDD5); border: unset;"
                                            type="submit">Verify OTP</button>
                                    </div>

                                    <div class="col-md-12 mt-2 text-center">
                                        <a href="{{ route('admin.resendOtp', ['email' => $email]) }}"
                                            id="resendOtp">Resend OTP</a>
                                    </div>
                                    <div class="col-md-12 mt-2 text-center">
                                        <a href="{{ url('/') }}">Back To Login</a>
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
