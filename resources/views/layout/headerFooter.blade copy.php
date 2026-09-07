{{-- <!DOCTYPE html>
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <!-- Select2 CSS -->
    <!-- <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" /> -->
    <link href="{{ asset('assets/plugins/select2/css/select2.css') }}" rel="stylesheet" />

    <link rel="shortcut icon" href="{{ url('assets/images/favicon.png') }}" type="image/x-icon">

    <!-- PLUGINS CSS STYLE -->
    <link href="{{ url('assets/plugins/nprogress/nprogress.css') }}" rel="stylesheet" />

    <link href="{{ url('assets/plugins/DataTables/DataTables-1.10.18/css/jquery.dataTables.min.css') }}"
        rel="stylesheet" />

    <link href="{{ url('assets/plugins/jvectormap/jquery-jvectormap-2.0.3.css') }}" rel="stylesheet" />
    <link href="{{ url('assets/plugins/daterangepicker/daterangepicker.css') }}" rel="stylesheet" />
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <!-- <link href="{{ url('assets/plugins/toaster/toastr.min.css')}}" rel="stylesheet" /> -->

    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    <!-- MONO CSS -->
    <link id="main-css-href" rel="stylesheet" href="{{ url('assets/css/style.css') }}" />
    <link id="main-css" rel="stylesheet" href="{{ url('assets/css/custom.css') }}" />

    <!-- Jquery CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- FAVICON -->
    <!-- <link href="{{ url('assets/images/favicon.png') }}" rel="shortcut icon" /> -->
    <!-- <link rel="apple-touch-icon" sizes="180x180" href="{{url('assets/images/apple-touch-icon.png')}}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{url('assets/images/favicon-32x32.png')}}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{url('assets/images/favicon-16x16.png')}}"> -->

    <script src="{{ url('assets/plugins/nprogress/nprogress.js') }}"></script>
</head>

<body class="navbar-fixed sidebar-fixed" id="body">
    <script>
        NProgress.configure({
            showSpinner: false
        });
        NProgress.start();
    </script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.0.1/js/toastr.js"></script>

    <script>
        // $(document).ready(function() {

            (function() {
            toastr.options.timeOut = 3000;
            @if (Session::has('error'))
                toastr.error('{{ Session::get('error') }}');
            @elseif (Session::has('success'))
                toastr.success('{{ Session::get('success') }}');
            @endif
        })();

        // });
    @if (session('added'))
      Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: '{{ session('added') }}',
        confirmButtonText: 'OK'
    });
    @endif
    @if (session('updated'))
      Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: '{{ session('updated') }}',
        confirmButtonText: 'OK'
    });
    @endif
    </script>
@php
$user_id = \Auth::user()->id;
@endphp
    <div class="wrapper">
        <aside class="left-sidebar" id="left-sidebar">
            <div id="sidebar" class="sidebar sidebar-with-footer">
                <!-- Aplication Brand -->
                <div class="app-brand">
                    <a href="{{ route('admin.dashboard') }}">
                        <img src="{{ url('assets/images/Logo.png') }}" alt="Mono">
                         <!-- <p >AE Note</p> -->
                    </a>
                </div>
                <!-- begin sidebar scrollbar -->
                <div class="sidebar-left" data-simplebar style="height: 100%;">
                    <!-- sidebar menu -->
                    <ul class="nav sidebar-inner" id="sidebar-menu">
                        <li
                            class="{{ request()->routeIs(['admin.dashboard','profile']) ? 'active' : '' }}">
                            <a class="sidenav-item-link" href="{{route('admin.dashboard')}}">
                                <img src="{{url('assets/images/dashboard.svg')}}" class="invert" title="Dashboard">
                                <span class="nav-text">Dashboard</span>
                            </a>
                        </li>
                        <!-- <li
                            class="{{ request()->routeIs(['admin.sub-admin']) ? 'active' : '' }}"> -->
                            @if(App\Models\PermissionUser::checkpermission($user_id,2))
                            <li class="{{ request()->routeIs(['user.index','user.edit','user.subscription']) ? 'active' : '' }}">
                                <a class="sidenav-item-link" href="{{ route('user.index') }}">
                                <img src="{{url('assets/images/User.svg')}}" title="Plan Management">
                                    <span class="nav-text">User-Management</span>
                                </a>
                            </li>
                            @endif
                            <!-- Schhol Manangement -->
                            @if(App\Models\PermissionUser::checkpermission($user_id,3))
                            <li class="{{ request()->routeIs(['school.index','school.create','school.edit','school.show']) ? 'active' : '' }}">
                                <a class="sidenav-item-link" href="{{ route('school.index') }}">
                                <img src="{{url('assets/images/School.svg')}}" title="Plan Management">
                                    <span class="nav-text">School-Management</span>
                                </a>
                            </li>
                            @endif
                           
                           @if(Auth::guard('admin')->user()->user_role_id == '1')
                            <li class="{{ request()->routeIs(['role.rolePermission','rolePermission.create','rolePermission.edit']) ? 'active' : '' }}">
                                <a class="sidenav-item-link" href="{{ route('role.rolePermission') }}">
                                <img src="{{url('assets/images/Role.svg')}}" title="Payment History">
                                    <span class="nav-text">Role and Permission</span>
                                </a>
                            </li>
                            @endif
                            @if(App\Models\PermissionUser::checkpermission($user_id,23))
                            <li class="{{ request()->routeIs(['payment.history_index','payment.history_view']) ? 'active' : '' }}">
                                <a class="sidenav-item-link" href="{{ route('payment.history_index') }}">
                                <img src="{{url('assets/images/payment-history.svg')}}" title="Payment History">
                                    <span class="nav-text">Payment History</span>
                                </a>
                            </li>
                            @endif
                            @if(App\Models\PermissionUser::checkpermission($user_id,13))
                            <li class="{{ request()->routeIs(['avtar.type_index','avtar.index','avtar.create','avtar.edit','school.show']) ? 'active' : '' }}">
                                <a class="sidenav-item-link" href="{{ route('avtar.type_index') }}">
                                <img src="{{url('assets/images/avatar.png')}}" title="Plan Management">
                                    <span class="nav-text">Avatar</span>
                                </a>
                            </li>
                            @endif
                            
                           
                       
                        

                        <!-- <li class="{{ request()->routeIs(['knowledge-base.index','knowledge-base.create','knowledge-base.edit']) ? 'active' : '' }}">
                            <a class="sidenav-item-link" href="{{ route('knowledge-base.index') }}">
                                <img src="{{url('assets/images/Knowledge.png')}}" title="Plan Management">
                                <span class="nav-text">Quiz Management</span>
                            </a>
                        </li>
                         -->
                         @if(App\Models\PermissionUser::checkpermission($user_id,15))
                         <li class="{{ request()->routeIs(['category.index', 'category.edit', 'category.create','category.show','knowledge-base.index','knowledge-base.create','knowledge-base.edit','knowledgeSession.index','knowledgeSession.create','knowledgeSession.edit']) ? 'active has-sub' : 'has-sub' }}">
                        <a class="sidenav-item-link" href="javascript:void(0)" data-toggle="collapse" data-target="#Management"
                           aria-expanded="{{ request()->routeIs(['category.index', 'category.edit', 'category.create','category.show','knowledge-base.index','knowledge-base.create','knowledge-base.edit','knowledgeSession.index','knowledgeSession.create','knowledgeSession.edit']) ? 'true' : 'false' }}" aria-controls="Management">
                           <img src="{{url('assets/images/Knowledge.png')}}" title="Content Management">
                            <span class="nav-text">Video Management</span>
                            <img class="arrow-icon"  src="{{url('assets/images/down-arrow.png')}}" title="Content Management">
                        </a>
                        <ul class="collapse {{ request()->routeIs(['category.index', 'category.edit', 'category.create','category.show','knowledge-base.index','knowledge-base.create','knowledge-base.edit','knowledgeSession.index','knowledgeSession.create','knowledgeSession.edit']) ? 'active show' : '' }}" id="Management">
                            <div class="sub-menu">

                                <li class="{{ request()->routeIs(['category.index','category.create','category.edit','category.show']) ? 'active' : '' }}">
                                    <a href="{{ route('category.index') }}">
                                    <!-- <img src="{{url('assets/images/School.svg')}}" title="Plan Management"> -->
                                        <span class="nav-text">Category</span>
                                    </a>
                                </li>
                                 <!-- video content Manangement -->
                                <li class="{{ request()->routeIs(['knowledge-base.index','knowledge-base.create','knowledge-base.edit']) ? 'active' : '' }}">
                                    <a class="sidenav-item-link" href="{{ route('knowledge-base.index') }}">
                                        <!-- <img src="{{url('assets/images/Knowledge.png')}}" title="Plan Management"> -->
                                        <span class="nav-text">Video Content</span>
                                    </a>
                                </li>
                                <!-- Knowledge Session -->
                                <li class="{{ request()->routeIs(['knowledgeSession.index','knowledgeSession.create','knowledgeSession.edit']) ? 'active' : '' }}">
                                    <a class="sidenav-item-link" href="{{ route('knowledgeSession.index') }}">
                                        <!-- <img src="{{url('assets/images/Knowledge.png')}}" title="Plan Management"> -->
                                        <span class="nav-text">Knowledge Session</span>
                                    </a>
                                </li>
                            </div>
                        </ul>
                    </li>
                    @endif
                    @if(App\Models\PermissionUser::checkpermission($user_id,19))
                         <li class="{{ request()->routeIs(['quizCategory.index', 'quizCategory.edit', 'quizCategory.index','quizCategory.create', 'quiz-questions.index','quiz-questions.create', 'quiz-questions.edit','question-options','question-options.create','question-options.edit','users-attempt-quizzes','users-attempt-quizzes.detail']) ? 'active has-sub' : 'has-sub' }}">
                        <a class="sidenav-item-link" href="javascript:void(0)" data-toggle="collapse" data-target="#quizManagement"
                           aria-expanded="{{ request()->routeIs(['quizCategory.index', 'quizCategory.edit', 'quizCategory.index','quizCategory.create','quiz-questions.index','quiz-questions.create', 'quiz-questions.edit','question-options','question-options.create','question-options.edit','users-attempt-quizzes','users-attempt-quizzes.detail']) ? 'true' : 'false' }}" aria-controls="quizManagement">
                           <img src="{{url('assets/images/administration.png')}}" title="Content Management">
                            <span class="nav-text">Quiz Management</span>
                            <img class="arrow-icon"  src="{{url('assets/images/down-arrow.png')}}" title="Content Management">

                        </a>
                        <ul class="collapse {{ request()->routeIs(['quizCategory.index', 'quizCategory.edit', 'quizCategory.index','quizCategory.create','quiz-questions.index','quiz-questions.create', 'quiz-questions.edit','question-options','question-options.create','question-options.edit','users-attempt-quizzes','users-attempt-quizzes.detail']) ? 'active show' : '' }}" id="quizManagement">
                            <div class="sub-menu">

                                <li class="{{ request()->routeIs(['quizCategory.index','quizCategory.create', 'quizCategory.edit','quiz-questions.index','quiz-questions.create', 'quiz-questions.edit','question-options','question-options.create','question-options.edit']) ? 'active' : '' }}">
                                    <a href="{{ route('quizCategory.index') }}">
                                        <!-- <i class="mdi mdi-content-save"></i> -->
                                        <span class="nav-text">Quiz Category</span>
                                    </a>
                                </li>
                                <li class="{{ request()->routeIs(['users-attempt-quizzes', 'users-attempt-quizzes.detail']) ? 'active' : '' }}">
                                    <a href="{{ route('users-attempt-quizzes') }}">
                                        <span class="nav-text">User Attempt Quizzes</span>
                                    </a>
                                </li>
                            </div>
                        </ul>
                    </li>
                    @endif
                    @if(App\Models\PermissionUser::checkpermission($user_id,24))
                        <li class="{{ request()->routeIs(['mood-list','mood-index','add-mood','edit-mood','activity.index','activity.add','activity.edit']) ? 'active' : '' }}">
                            <a class="sidenav-item-link" href="{{ route('mood-index') }}">
                            <img src="{{url('assets/images/child_mood.svg')}}" title="Child's Mood">
                                <span class="nav-text">Child's Mood</span>
                            </a>
                        </li>
                    @endif
                    @if(App\Models\PermissionUser::checkpermission($user_id,25))
                    <li class="{{ request()->routeIs(['child-mood-tracker', 'child-mood-tracker.detail']) ? 'active' : '' }}">
                                    <a href="{{ route('child-mood-tracker') }}">
                                    <img src="{{url('assets/images/child_mood_tracker.svg')}}" title="Plan Management">
                                        <span class="nav-text">Child Mood Tracker</span>
                                    </a>
                                </li>
                    @endif

                    <!-- @if(App\Models\PermissionUser::checkpermission($user_id,25))
                    <li class="{{ request()->routeIs('notifications.*') ? 'active' : '' }}">
                        <a href="{{ route('notifications.index') }}">
                            <img src="{{ url('assets/images/avatar.png') }}" title="Notifications">
                            <span class="nav-text">Notification</span>
                        </a>
                    </li>
                    @endif -->

                    <!-- @if(App\Models\PermissionUser::checkpermission($user_id,25))
                    <li class="{{ request()->routeIs('export-report.*') ? 'active' : '' }}">
                        <a href="{{ route('notifications.index') }}">
                            <img src="{{ url('assets/images/avatar.png') }}" title="exportReport">
                            <span class="nav-text">Export Reports</span>
                        </a>
                    </li>
                    @endif -->
                    @if(App\Models\PermissionUser::checkpermission($user_id,7))
                       <li class="{{ request()->routeIs(['static-content.index', 'static-content.edit', 'faq.index', 'faq.create', 'faq.edit','email-template.index', 'email-template.edit','testimonial.index','testimonial.create','testimonial.edit']) ? 'active has-sub' : 'has-sub' }}">
                        <a class="sidenav-item-link" href="javascript:void(0)" data-toggle="collapse" data-target="#icons"
                           aria-expanded="{{ request()->routeIs(['static-content.index', 'static-content.edit', 'faq.index', 'faq.create', 'faq.edit','testimonial.index','testimonial.create','testimonial.edit']) ? 'true' : 'false' }}" aria-controls="icons">
                           <img src="{{url('assets/images/Content.svg')}}" title="Content Management">
                            <span class="nav-text">Content Management</span>
                            <img class="arrow-icon"  src="{{url('assets/images/down-arrow.png')}}" title="Content Management">

                        </a>
                        <ul class="collapse {{ request()->routeIs(['static-content.index', 'static-content.edit', 'faq.index', 'faq.create', 'faq.edit','email-template.index', 'email-template.edit','testimonial.index','testimonial.create','testimonial.edit']) ? 'active show' : '' }}" id="icons">
                            <div class="sub-menu">

                                <li class="{{ request()->routeIs(['static-content.index', 'static-content.edit']) ? 'active' : '' }}">
                                    <a href="{{ route('static-content.index') }}">
                                        <!-- <i class="mdi mdi-content-save"></i> -->
                                        <span class="nav-text">Static Content</span>
                                    </a>
                                </li>

                                <li class="{{ request()->routeIs(['faq.index', 'faq.create', 'faq.edit']) ? 'active' : '' }}">
                                    <a href="{{ route('faq.index') }}">
                                        <!-- <i class="mdi mdi-account-question"></i> -->
                                        <span class="nav-text">FAQ Management</span>
                                    </a>
                                </li>
                                <li class="{{ request()->routeIs(['email-template.index', 'email-template.edit']) ? 'active' : '' }}">
                                    <a href="{{ route('email-template.index') }}">
                                        <!-- <i class="mdi mdi-account-question"></i> -->
                                        <span class="nav-text">Email Template</span>
                                    </a>
                                </li>
                            </div>
                        </ul>
                    </li>
                    @endif

                    @if(App\Models\PermissionUser::checkpermission($user_id,7))
                       <li class="{{ request()->is(['exports/User','exports/School','exports/VideoContent','exports/KnowledgeSession']) ? 'active has-sub' : 'has-sub' }}">
                        <a class="sidenav-item-link" href="javascript:void(0)" data-toggle="collapse" data-target="#exportManagement"
                           aria-expanded="{{ request()->is(['exports/User','exports/School','exports/VideoContent','exports/KnowledgeSession']) ? 'true' : 'false' }}" aria-controls="exportManagement">
                           <img src="{{url('assets/images/Content.svg')}}" title="Content Management">
                            <span class="nav-text">Export Reports</span>
                            <img class="arrow-icon"  src="{{url('assets/images/down-arrow.png')}}" title="Content Management">

                        </a>
                        <ul class="collapse {{ request()->is(['exports/User','exports/School','exports/VideoContent','exports/KnowledgeSession']) ? 'active show' : '' }}" id="exportManagement">
                            <div class="sub-menu">

                                <li class="{{ request()->is(['exports/User']) ? 'active' : '' }}">
                                    <a href="{{ url('/exports/User') }}">
                                        <span class="nav-text">Export User</span>
                                    </a>
                                </li>
                                <li class="{{ request()->is(['exports/School']) ? 'active' : '' }}">
                                    <a href="{{ url('/exports/School') }}">
                                        <span class="nav-text">Export School</span>
                                    </a>
                                </li>
                                <li class="{{ request()->is(['exports/VideoContent']) ? 'active' : '' }}">
                                    <a href="{{ url('/exports/VideoContent') }}">
                                        <span class="nav-text">Export Video Content</span>
                                    </a>
                                </li>
                                <li class="{{ request()->is(['exports/KnowledgeSession']) ? 'active' : '' }}">
                                    <a href="{{ url('/exports/KnowledgeSession') }}">
                                        <span class="nav-text">Export Knw. Session</span>
                                    </a>
                                </li>
                                <!-- <li class="{{ request()->routeIs(['faq.index', 'faq.create', 'faq.edit']) ? 'active' : '' }}">
                                    <a href="{{ route('faq.index') }}">
                                        <span class="nav-text">Export School</span>
                                    </a>
                                </li>
                                <li class="{{ request()->routeIs(['email-template.index', 'email-template.edit']) ? 'active' : '' }}">
                                    <a href="{{ route('email-template.index') }}">
                                        <span class="nav-text">Export Payment History</span>
                                    </a>
                                </li>
                                <li class="{{ request()->routeIs(['email-template.index', 'email-template.edit']) ? 'active' : '' }}">
                                    <a href="{{ route('email-template.index') }}">
                                        <span class="nav-text">Export Video Content</span>
                                    </a>
                                </li>
                                <li class="{{ request()->routeIs(['email-template.index', 'email-template.edit']) ? 'active' : '' }}">
                                    <a href="{{ route('email-template.index') }}">
                                        <span class="nav-text">Export Knowledge Session</span>
                                    </a>
                                </li>
                                <li class="{{ request()->routeIs(['email-template.index', 'email-template.edit']) ? 'active' : '' }}">
                                    <a href="{{ route('email-template.index') }}">
                                        <span class="nav-text">Export Attempt Quizzes</span>
                                    </a>
                                </li> -->
                               
                            </div>
                        </ul>
                    </li>
                    @endif
                    @if(App\Models\PermissionUser::checkpermission($user_id,11))
                    <li class="{{ request()->routeIs(['contact-us.index']) ? 'active' : '' }}">
                                <a class="sidenav-item-link" href="{{ route('contact-us.index') }}">
                                    <img src="{{url('assets/images/Contact.svg')}}" title="Contact Us">
                                    <span class="nav-text">Contact Us</span>
                                </a>
                            </li>
                            @endif
                   <!-- Administration Section  -->

                   <!-- <li class="{{ request()->routeIs(['system-log.index']) ? 'active has-sub' : 'has-sub' }}">
                        <a class="sidenav-item-link" href="javascript:void(0)" data-toggle="collapse" data-target="#icons2"
                           aria-expanded="{{ request()->routeIs(['system-log.index']) ? 'true' : 'false' }}" aria-controls="icons">
                           <img src="{{url('assets/images/administration.png')}}" title="Administration ">
                            <span class="nav-text">Administration</span>
                        </a>
                        <ul class="collapse {{ request()->routeIs(['system-log.index']) ? 'active show' : '' }}" id="icons2">
                            <div class="sub-menu">
                                <li class="{{ request()->routeIs(['system-log.index']) ? 'active' : '' }}">
                                    <a href="{{route('system-log.index')}}">
                                        <span class="nav-text">System Logs</span>
                                    </a>
                                </li>
                               
                            </div>
                        </ul>
                    </li> -->

                    <!-- <li class="{{ request()->routeIs(['system-log.index']) ? 'active' : '' }}">
                                <a class="sidenav-item-link" href="{{ route('system-log.index') }}">
                                    <i class="mdi mdi-cash-multiple"></i>
                                    <span class="nav-text">System Logs</span>
                                </a>
                            </li> -->
                    @if(Auth::guard('admin')->user()->user_role_id == '1')
                        <li
                            class="{{ request()->routeIs(['settings.edit']) ? 'active' : '' }}">
                            <a class="sidenav-item-link" href="{{route('settings.edit')}}">
                                <img src="{{url('assets/images/settings.svg')}}" title="Settings">
                                <span class="nav-text">Settings</span>
                            </a>
                        </li>
                    @endif
                       
                </div>
            </div>
        </aside>
        <div class="page-wrapper">
            <!-- Header -->
            <header class="main-header" id="header">
                <nav class="navbar navbar-expand-lg navbar-light" id="navbar">
                    <!-- Sidebar toggle button -->
                    <button id="sidebar-toggler" class="sidebar-toggle">
                        <span class="sr-only">Toggle navigation</span>
                    </button>
                    @if(Auth::guard('admin')->user()->user_role_id == '1' || Auth::guard('admin')->user()->user_role_id == '2')

                    <div class="navbar-right ">
                        <ul class="nav navbar-nav">
                            <!-- User Account -->
                            <li class="dropdown user-menu">
                                <button class="dropdown-toggle nav-link" data-toggle="dropdown">
                                    <img src="{{ Auth::guard('admin')->user()->profile_image ? url('uploads/profile_img/' . Auth::guard('admin')->user()->profile_image) : url('assets/images/user-xs-01.jpg') }}"
                                        class="user-image rounded-circle" alt="User Image" />
                                    <div class="admin-name">
                                        <span>{{ Auth::guard('admin')->user()->name}}</span>
                                        <span class="d-none d-lg-block">Admin</span>
                                    </div>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-right">
                                    <li>
                                        <a class="dropdown-link-item" href="{{route('profile')}}">
                                            <img src="{{url('assets/images/manage-account.svg')}}" />
                                            <span class="nav-text">Manage Account</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-link-item" href="{{route('change-password')}}">
                                            <img src="{{url('assets/images/change-password.svg')}}" />
                                            <span class="nav-text">Change Password</span>
                                        </a>
                                    </li>
                                    <li class="logout">
                                        <a class="dropdown-link-item" href="{{url('logout')}}">
                                            <img src="{{url('assets/images/logout.svg')}}" title="Settings">
                                            <span class="nav-text">Logout</span>
                                        </a>
                                    </li>
                                    <!-- <li>
                                        <a class="dropdown-link-item" href="#">
                                            <i class="mdi mdi-settings"></i>
                                            <span class="nav-text">Account Setting</span>
                                        </a>
                                    </li> -->

                                    <!-- <li class="dropdown-footer">
                                        <a class="dropdown-link-item" href="{{route('logout')}}"> <i
                                            class="mdi mdi-logout"></i> Log Out </a>
                                    </li> -->
                                </ul>
                            </li>
                        </ul>
                    </div>
                    @endif
                </nav>
            </header>
            @yield('content')

            <!-- Footer -->
            <!-- <footer class="footer mt-auto">
                <div class="copyright bg-white">
                    <p style="text-align: center">
                        &copy; <span id="copy-year"></span> Copyright Tech Comp
                    </p>
                </div>
                <script>
                    var d = new Date();
                    var year = d.getFullYear();
                    document.getElementById("copy-year").innerHTML = year;
                </script>
            </footer> -->

        </div>
    </div>
    <script src="{{ url('assets/plugins/jquery/jquery.min.js') }}"></script>
    <script src="{{ url('assets/plugins/simplebar/simplebar.min.js') }}"></script>
    <script src="{{ url('assets/https://unpkg.com/hotkeys-js/dist/hotkeys.min.js') }}"></script>
    <script src="{{ url('assets/plugins/apexcharts/apexcharts.js') }}"></script>
    <script src="{{ url('assets/plugins/DataTables/DataTables-1.10.18/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ url('assets/plugins/jvectormap/jquery-jvectormap-2.0.3.min.js') }}"></script>
    <script src="{{ url('assets/plugins/jvectormap/jquery-jvectormap-world-mill.js') }}"></script>
    <script src="{{ url('assets/plugins/jvectormap/jquery-jvectormap-us-aea.js') }}"></script>
    <script src="{{ url('assets/plugins/daterangepicker/moment.min.js') }}"></script>
    <script src="{{ url('assets/plugins/daterangepicker/daterangepicker.js') }}"></script>
    <script>
        jQuery(document).ready(function() {
            jQuery('input[name="dateRange"]').daterangepicker({
                autoUpdateInput: false,
                singleDatePicker: true,
                locale: {
                    cancelLabel: 'Clear'
                }
            });
            jQuery('input[name="dateRange"]').on('apply.daterangepicker', function(ev, picker) {
                jQuery(this).val(picker.startDate.format('DD/MM/YYYY'));
            });
            jQuery('input[name="dateRange"]').on('cancel.daterangepicker', function(ev, picker) {
                jQuery(this).val('');
            });
            jQuery("table").wrap("<div class='table-responsive'></div>");

        });
    </script>
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <script src="{{ url('assets/js/mono.js') }}"></script>
    <!-- <script src="{{ url('assets/js/chart.js') }}"></script> -->
    <!-- <script src="{{ url('assets/js/map.js') }}"></script> -->
    <script src="{{ url('assets/js/custom.js') }}"></script>
    <script>
        $("body").on('click', '.logout', function(e) {
        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        e.preventDefault();
        
        Swal.fire({
            title: 'Are you sure?',
            text: 'You want to logout?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes',
            cancelButtonText: 'Cancel',
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "GET",  // Use POST for logout to properly handle CSRF
                    url: '{{ route("logout") }}',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken 
                    },
                    success: function(data) {
                        Swal.fire({
                            title: "Logged Out",
                            text: "You have been logged out successfully!",
                            icon: "success",
                            confirmButtonText: "OK"
                        }).then(() => {
                            window.location.href = "{{ route('login') }}";
                        });
                    },
                    error: function() {
                        Swal.fire({
                            title: "Error",
                            text: "Something went wrong. Please try again.",
                            icon: "error",
                        });
                    }
                });
            }
        });
    });

//     $(document).ready(function() {
//     $("#quizManagement > a").click(function() {
//         $("#icons .collapse").removeClass("show"); // Close Content Management
//     });

//     $("#icons > a").click(function() {
//         $("#icons .collapse").removeClass("show"); // Close Quiz Management
//     });
// });
document.addEventListener("DOMContentLoaded", function () {
  // Select all <li> elements with class 'has-sub'
  const dropdownItems = document.querySelectorAll("li.has-sub");

  dropdownItems.forEach((item) => {
    // Find the link with data-toggle="collapse"
    const link = item.querySelector('a[data-toggle="collapse"]');

    if (link) {
      link.addEventListener("click", function (e) {
        e.preventDefault(); // Prevent default link behavior

        // Get the target <ul> from data-target
        const targetId = link.getAttribute("data-target");
        const targetUl = document.querySelector(targetId);

        // Close other open dropdowns
        dropdownItems.forEach((otherItem) => {
          if (otherItem !== item) {
            // Remove 'active' from other <li>
            otherItem.classList.remove("active");

            // Get other <ul> and remove 'show'
            const otherLink = otherItem.querySelector('a[data-toggle="collapse"]');
            if (otherLink) {
              const otherTargetId = otherLink.getAttribute("data-target");
              const otherTargetUl = document.querySelector(otherTargetId);
              if (otherTargetUl) {
                otherTargetUl.classList.remove("show");
                otherLink.setAttribute("aria-expanded", "false");
              }
            }
          }
        });

        // Toggle 'active' class on the clicked <li>
        item.classList.toggle("active");

        // Toggle 'show' class on the target <ul>
        if (targetUl) {
          targetUl.classList.toggle("show");

          // Update aria-expanded for accessibility
          const isExpanded = targetUl.classList.contains("show");
          link.setAttribute("aria-expanded", isExpanded);
        }
      });
    }
  });
});
    </script>

    <!-- Select2 JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.1/js/select2.min.js"></script>
    @stack('scripts')
</body>
</html> --}}