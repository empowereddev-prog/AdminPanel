<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <title>Password Reset Successful</title>

    <!-- GOOGLE FONTS -->
    <link href="https://fonts.googleapis.com/css?family=Karla:400,700|Roboto" rel="stylesheet">
    <link href="{{ url('assets/plugins/material/css/materialdesignicons.min.css') }}" rel="stylesheet" />
    <link href="{{ url('assets/plugins/simplebar/simplebar.css') }}" rel="stylesheet" />

    <!-- PLUGINS CSS STYLE -->
    <link href="{{ url('assets/plugins/nprogress/nprogress.css') }}" rel="stylesheet" />

    <!-- MONO CSS -->
    <link id="main-css-href" rel="stylesheet" href="{{ url('assets/css/style.css') }}" />

    <!-- FAVICON -->
    <link rel="icon" type="image/png" href="{{ url('assets/images/new.png') }}">
</head>

<body class="bg-light-gray login-page" id="body">
    <div class="container d-flex align-items-center justify-content-center" style="min-height: 100vh">
        <div class="d-flex flex-column justify-content-between">
            <div class="row justify-content-center">
                <div class="col-lg-12 col-md-10"> <!-- Increased card width -->
                    <div class="card card-default mb-0 login-box" style="padding: 40px 30px;"> <!-- Larger padding -->
                        <div class="card-header pb-0">
                            <div class="app-brand w-100 d-flex justify-content-center border-bottom-0">
                                <a class="w-auto pl-0">
                                    <img src="{{ url('assets/images/newlogo.png') }}" alt="AE-LOGO" style="max-width: 120px;">
                                </a>
                            </div>
                        </div>
                        <div class="card-body px-5 pb-5 pt-3">
                            <h3 class="text-dark mb-4 text-center"
                                style="font-family: 'Times New Roman', Times, serif; letter-spacing: 1px;">
                                Password Reset Successful
                            </h3>
                            <p class="text-center fs-5">You can now log in with your new password.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JS -->
    <script src="{{ url('assets/plugins/nprogress/nprogress.js') }}"></script>
</body>

</html>
