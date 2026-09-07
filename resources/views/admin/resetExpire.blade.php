<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>EmpowerEd Child Healthcare</title>

    <!-- theme meta -->
    <meta name="theme-name" content="mono" />

    <!-- GOOGLE FONTS -->
    <link href="https://fonts.googleapis.com/css?family=Karla:400,700|Roboto" rel="stylesheet">
    <link href="{{ url('assets/plugins/material/css/materialdesignicons.min.css') }}" rel="stylesheet" />
    <link href="{{ url('assets/plugins/simplebar/simplebar.css') }}" rel="stylesheet" />

    <!-- PLUGINS CSS STYLE -->
    <link href="{{ url('assets/plugins/nprogress/nprogress.css') }}" rel="stylesheet" />

    <link href="{{ url('assets/plugins/DataTables/DataTables-1.10.18/css/jquery.dataTables.min.css') }}" rel="stylesheet" />

    <link href="{{ url('assets/plugins/jvectormap/jquery-jvectormap-2.0.3.css') }}" rel="stylesheet" />

    <link href="{{ url('assets/plugins/daterangepicker/daterangepicker.css') }}" rel="stylesheet" />

    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

    <link href="{{ url('assets/plugins/toaster/toastr.min.css" rel="stylesheet') }}" />

    <!-- MONO CSS -->
    <link id="main-css-href" rel="stylesheet" href="{{ url('assets/css/style.css') }}" />

    <link id="main-css" rel="stylesheet" href="{{ url('assets/css/custom.css') }}" />

    <!-- Jquery CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>

    <!-- FAVICON -->
    <link href="{{ url('assets/images/new.png') }}" rel="shortcut icon" />
    <script src="{{ url('assets/plugins/nprogress/nprogress.js') }}"></script>

</head>
<body>
    <div class="content-wrapper">
        <div class="content">
            <div class="row">
                <div class="col-xl-9">
                    <div class="">
                        <div class="reset-password-link-expired">
                        <h3>Reset password link has expired. Please request a new one.</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
