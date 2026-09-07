<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>EmpowerEd Child Healthcare</title>

    <meta name="theme-name" content="mono" />

    <link href="https://fonts.googleapis.com/css?family=Karla:400,700|Roboto" rel="stylesheet">

    <link href="{{ asset('assets/plugins/material/css/materialdesignicons.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/plugins/simplebar/simplebar.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/plugins/nprogress/nprogress.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/plugins/DataTables/DataTables-1.10.18/css/jquery.dataTables.min.css') }}"
        rel="stylesheet" />
    <link href="{{ asset('assets/plugins/jvectormap/jquery-jvectormap-2.0.3.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/plugins/daterangepicker/daterangepicker.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/plugins/select2/css/select2.css') }}" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    {{-- <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet"> --}}
    <link rel="stylesheet" type="text/css"
        href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    <link id="main-css-href" rel="stylesheet" href="{{ asset('assets/css/style.css') }}" />
    <link id="main-css" rel="stylesheet" href="{{ asset('assets/css/custom.css') }}" />

    <link rel="shortcut icon" href="{{ asset('assets/images/new.png') }}" type="image/x-icon">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.0.1/js/toastr.js"></script>
    <script src="{{ asset('assets/plugins/nprogress/nprogress.js') }}"></script>
    @stack('styles')
    <style>
        .toast {
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            padding: 12px 16px 12px 48px;
            border-radius: 12px;
            position: relative;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            min-height: 60px;
            line-height: 1.3;
            border-left: 5px solid;
        }

        .toast-success {
            background-color: #d2f8e5 !important;
            color: #0f5132 !important;
            border-color: #198754;
        }

        .toast-error {
            background-color: #fde2e1 !important;
            color: #842029 !important;
            border-color: #dc3545;
        }

        .toast::before {
            content: '';
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            width: 24px;
            height: 24px;
            background-size: contain;
            background-repeat: no-repeat;
        }

        .toast-success::before {
            background-image: url('data:image/svg+xml,%3Csvg fill=\'%23198754\' viewBox=\'0 0 16 16\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cpath d=\'M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM6.854 10.854 11.207 6.5l-.707-.707L6.5 9.293 4.854 7.646l-.708.708L6.854 10.854z\'/%3E%3C/svg%3E');
        }

        .toast-error::before {
            background-image: url('data:image/svg+xml,%3Csvg fill=\'%23dc3545\' viewBox=\'0 0 16 16\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cpath d=\'M8.982 1.566a1.5 1.5 0 0 0-1.964 0L.165 7.864a1.5 1.5 0 0 0 0 2.272l6.853 6.298a1.5 1.5 0 0 0 1.964 0l6.853-6.298a1.5 1.5 0 0 0 0-2.272L8.982 1.566zM8 5a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-1 0v-3A.5.5 0 0 1 8 5zm0 6a.75.75 0 1 1 0-1.5A.75.75 0 0 1 8 11z\'/%3E%3C/svg%3E');
        }

        /* Optional: style the close button */
        .toast-close-button {
            color: inherit;
            right: 8px;
            top: 8px;
            font-size: 18px;
            opacity: 0.6;
        }

        .toast-close-button:hover {
            opacity: 1;
        }
    </style>

</head>

<body class="navbar-fixed sidebar-fixed" id="body">
    <script>
        NProgress.configure({
            showSpinner: false
        });
        NProgress.start();

        // Toastr and SweetAlert initializations
        $(document).ready(function() { // Ensure jQuery is ready
            toastr.options.timeOut = 3000;
            @if (Session::has('error'))
                toastr.error('{{ Session::get('error') }}');
            @elseif (Session::has('success'))
                toastr.success('{{ Session::get('success') }}');
            @endif

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
        });
    </script>

    @php
        $user_id = \Auth::user()->id;
    @endphp

    <div class="wrapper">
        <aside class="left-sidebar" id="left-sidebar">
            <div id="sidebar" class="sidebar sidebar-with-footer">
                <div class="app-brand">
                    <a href="{{ route('admin.dashboard') }}">
                        <img src="{{ asset('assets/images/newlogo.png') }}" alt="EmpowerEd Logo"
                            style="margin-right:32%;">
                    </a>
                </div>
                <div class="sidebar-left" data-simplebar style="height: 100%;">
                    <ul class="nav sidebar-inner" id="sidebar-menu">
                        <li class="{{ request()->routeIs(['admin.dashboard', 'profile']) ? 'active' : '' }}">
                            <a class="sidenav-item-link" href="{{ route('admin.dashboard') }}">
                                <img src="{{ asset('assets/images/dashboard.svg') }}" class="invert" title="Dashboard"
                                    alt="Dashboard icon">
                                <span class="nav-text">Dashboard</span>
                            </a>
                        </li>
                        @if (hasPermission(2))
                            <li
                                class="{{ request()->routeIs(['user.index', 'user.create', 'user.edit', 'user.subscription']) ? 'active' : '' }}">
                                <a class="sidenav-item-link" href="{{ route('user.index') }}">
                                    <img src="{{ asset('assets/images/User.svg') }}" title="User Management"
                                        alt="User icon">
                                    <span class="nav-text">User-Management</span>
                                </a>
                            </li>
                        @endif
                        @if (hasPermission(3))
                            <li
                                class="{{ request()->routeIs(['school.index', 'school.create', 'school.edit', 'school.show', 'school-user-child-detail', 'school.children.progress']) ? 'active' : '' }}">
                                <a class="sidenav-item-link" href="{{ route('school.index') }}">
                                    <img src="{{ asset('assets/images/School.svg') }}" title="School Management"
                                        alt="School icon">
                                    <span class="nav-text">School-Management</span>
                                </a>
                            </li>
                        @endif

                        @if (hasPermission(4))
                            <li
                                class="{{ request()->routeIs(['assign-permission.index', 'assign-permission.create', 'assign-permission.edit']) ? 'active' : '' }}">
                                <a class="sidenav-item-link" href="{{ route('assign-permission.index') }}">
                                    <img src="{{ asset('assets/images/Role.svg') }}" title="Role and Permission"
                                        alt="Role icon">
                                    <span class="nav-text">Role and Permission</span>
                                </a>
                            </li>
                        @endif
                        @if (hasPermission(23))
                            <li
                                class="{{ request()->routeIs(['payment.history_index', 'payment.history_view']) ? 'active' : '' }}">
                                <a class="sidenav-item-link" href="{{ route('payment.history_index') }}">
                                    <img src="{{ asset('assets/images/payment-history.svg') }}" title="Payment History"
                                        alt="Payment History icon">
                                    <span class="nav-text">Payment History</span>
                                </a>
                            </li>
                        @endif
                        @if (hasPermission(13))
                            <li
                                class="{{ request()->routeIs(['avtar.type_index', 'avtar.index', 'avtar.create', 'avtar.edit']) ? 'active' : '' }}">
                                <a class="sidenav-item-link" href="{{ route('avtar.type_index') }}">
                                    <img src="{{ asset('assets/images/avatar.png') }}" title="Avatar"
                                        alt="Avatar icon">
                                    <span class="nav-text">Avatar</span>
                                </a>
                            </li>
                        @endif
                        @if (hasPermission(35))
                            <li
                                class="{{ request()->routeIs(['color.index', 'color.index', 'color.create', 'color.edit']) ? 'active' : '' }}">
                                <a class="sidenav-item-link" href="{{ route('color.index') }}">
                                    <img src="{{ asset('assets/images/color.svg') }}" title="Avatar"
                                        alt="Avatar icon">
                                    <span class="nav-text">Color Managament</span>
                                </a>
                            </li>
                        @endif
                        @if (hasPermission(16) || hasPermission(17) || hasPermission(18) || hasPermission(38))
                            <li
                                class="{{ request()->routeIs(['category.index', 'category.edit', 'category.create', 'category.show', 'knowledge-base.index', 'knowledge-base.create', 'knowledge-base.edit', 'knowledge-base-child.index', 'knowledge-base-child.create', 'knowledge-base-child.edit', 'knowledgeSession.index', 'knowledgeSession.create', 'knowledgeSession.edit','video-other.index', 'video-other.create', 'video-other.edit'])  ? 'active has-sub' : 'has-sub' }}">
                                <a class="sidenav-item-link" href="javascript:void(0)" **data-toggle="collapse"**
                                    data-target="#Management"
                                    aria-expanded="{{ request()->routeIs(['category.index', 'category.edit', 'category.create', 'category.show', 'knowledge-base.index', 'knowledge-base.create', 'knowledge-base.edit', 'knowledge-base-child.index', 'knowledge-base-child.create', 'knowledge-base-child.edit', 'knowledgeSession.index', 'knowledgeSession.create', 'knowledgeSession.edit','video-other.index', 'video-other.create', 'video-other.edit']) ? 'true' : 'false' }}"
                                    aria-controls="Management">
                                    <img src="{{ asset('assets/images/Knowledge.png') }}" title="Video Management"
                                        alt="Knowledge icon">
                                    <span class="nav-text">Video Management</span>
                                    <img class="arrow-icon" src="{{ asset('assets/images/down-arrow.png') }}"
                                        alt="Arrow icon">
                                </a>
                                <ul class="collapse {{ request()->routeIs(['category.index', 'category.edit', 'category.create', 'category.show', 'knowledge-base.index', 'knowledge-base.create', 'knowledge-base.edit', 'knowledge-base-child.index', 'knowledge-base-child.create', 'knowledge-base-child.edit', 'knowledgeSession.index', 'knowledgeSession.create', 'knowledgeSession.edit','video-other.index', 'video-other.create', 'video-other.edit']) ? 'active show' : '' }}"
                                    id="Management">
                                    <div class="sub-menu">
                                        @if (hasPermission(16))
                                            <li
                                                class="{{ request()->routeIs(['category.index', 'category.create', 'category.edit', 'category.show']) ? 'active' : '' }}">
                                                <a href="{{ route('category.index') }}">
                                                    <span class="nav-text">Category</span>
                                                </a>
                                            </li>
                                        @endif
                                        @if (hasPermission(17))
                                            <li
                                                class="{{ request()->routeIs(['knowledge-base.index', 'knowledge-base.create', 'knowledge-base.edit']) ? 'active' : '' }}">
                                                <a class="sidenav-item-link"
                                                    href="{{ route('knowledge-base.index') }}">
                                                    <span class="nav-text">Parent Video Webinars</span>
                                                </a>
                                            </li>
                                        @endif
                                        @if (hasPermission(38))
                                            <li
                                                class="{{ request()->routeIs(['knowledge-base-child.index', 'knowledge-base-child.create', 'knowledge-base-child.edit']) ? 'active' : '' }}">
                                                <a class="sidenav-item-link"
                                                    href="{{ route('knowledge-base-child.index') }}">
                                                    <p class="nav-text">child Video Webinars</p>
                                                </a>
                                            </li>
                                        @endif
                                        @if (hasPermission(18))
                                            <li
                                                class="{{ request()->routeIs(['knowledgeSession.index', 'knowledgeSession.create', 'knowledgeSession.edit']) ? 'active' : '' }}">
                                                <a class="sidenav-item-link"
                                                    href="{{ route('knowledgeSession.index') }}">
                                                    <span class="nav-text">Articles</span>
                                                </a>
                                            </li>
                                        @endif
                                           @if (hasPermission(39))
                                          <li
                                             class="{{ request()->routeIs(['video-other.index', 'video-other.create', 'video-other.edit']) ? 'active' : '' }}">
                                             <a class="sidenav-item-link"
                                                   href="{{ route('video-other.index') }}">
                                                   <span class="nav-text">Wellness Category </span>
                                           </a>
                                          </li>
                                        @endif
                                    </div>
                                </ul>
                            </li>
                        @endif

                        @if (hasPermission(22) || hasPermission(20))
                            <li
                                class="{{ request()->routeIs(['quizCategory.index', 'quiz.index', 'quiz.create', 'quiz.edit', 'quizCategory.edit', 'quizCategory.index', 'quizCategory.create', 'quiz-questions.index', 'quiz-questions.create', 'quiz-questions.edit', 'question-options', 'question-options.create', 'question-options.edit', 'users-attempt-quizzes', 'users-attempt-quizzes.detail']) ? 'active has-sub' : 'has-sub' }}">
                                <a class="sidenav-item-link" href="javascript:void(0)" **data-toggle="collapse"**
                                    data-target="#quizManagement"
                                    aria-expanded="{{ request()->routeIs(['quizCategory.index', 'quiz.index', 'quiz.create', 'quiz.edit', 'quizCategory.edit', 'quizCategory.index', 'quizCategory.create', 'quiz-questions.index', 'quiz-questions.create', 'quiz-questions.edit', 'question-options', 'question-options.create', 'question-options.edit', 'users-attempt-quizzes', 'users-attempt-quizzes.detail']) ? 'true' : 'false' }}"
                                    aria-controls="quizManagement">
                                    <img src="{{ asset('assets/images/administration.png') }}"
                                        title="Quiz Management" alt="Administration icon">
                                    <span class="nav-text">Quiz Management</span>
                                    <img class="arrow-icon" src="{{ asset('assets/images/down-arrow.png') }}"
                                        alt="Arrow icon">
                                </a>
                                <ul class="collapse {{ request()->routeIs(['quizCategory.index', 'quiz.index', 'quiz.create', 'quiz.edit', 'quizCategory.edit', 'quizCategory.index', 'quizCategory.create', 'quiz-questions.index', 'quiz-questions.create', 'quiz-questions.edit', 'question-options', 'question-options.create', 'question-options.edit', 'users-attempt-quizzes', 'users-attempt-quizzes.detail']) ? 'active show' : '' }}"
                                    id="quizManagement">
                                    <div class="sub-menu">
                                        @if (hasPermission(20))
                                            <li
                                                class="{{ request()->routeIs(['quizCategory.index', 'quiz.index', 'quiz.create', 'quiz.edit', 'quizCategory.create', 'quizCategory.edit', 'quiz-questions.index', 'quiz-questions.create', 'quiz-questions.edit', 'question-options', 'question-options.create', 'question-options.edit']) ? 'active' : '' }}">
                                                <a href="{{ route('quizCategory.index') }}">
                                                    <span class="nav-text">Quiz Category</span>
                                                </a>
                                            </li>
                                        @endif
                                        @if (hasPermission(22))
                                            <li
                                                class="{{ request()->routeIs(['users-attempt-quizzes', 'users-attempt-quizzes.detail']) ? 'active' : '' }}">
                                                <a href="{{ route('users-attempt-quizzes') }}">
                                                    <span class="nav-text">User Attempt Quizzes</span>
                                                </a>
                                            </li>
                                        @endif
                                    </div>
                                </ul>
                            </li>
                        @endif
                        @if (hasPermission(24))
                            <li
                                class="{{ request()->routeIs(['mood-list', 'mood-index', 'add-mood', 'edit-mood', 'activity.index', 'activity.add', 'activity.edit']) ? 'active' : '' }}">
                                <a class="sidenav-item-link" href="{{ route('mood-index') }}">
                                    <img src="{{ asset('assets/images/child_mood.svg') }}" title="Child's Mood"
                                        alt="Child mood icon">
                                    <span class="nav-text">Child's Mood</span>
                                </a>
                            </li>
                        @endif
                        @if (hasPermission(25))
                            <li
                                class="{{ request()->routeIs(['child-mood-tracker', 'child-mood-tracker.detail']) ? 'active' : '' }}">
                                <a href="{{ route('child-mood-tracker') }}">
                                    <img src="{{ asset('assets/images/child_mood_tracker.svg') }}"
                                        title="Child Mood Tracker" alt="Child Mood Tracker icon">
                                    <span class="nav-text">Child Mood Tracker</span>
                                </a>
                            </li>
                        @endif
                        @if (hasPermission(26))
                            <li
                                class="{{ request()->routeIs(['product.index', 'product.create', 'product.edit', 'product.show']) ? 'active' : '' }}">
                                <a class="sidenav-item-link" href="{{ route('product.index') }}">
                                    <img src="{{ asset('assets/images/product.png') }}" title="Product"
                                        alt="Product icon" height="25px" width="25px">
                                    <span class="nav-text">Resources </span>
                                </a>
                            </li>
                        @endif
                        @if (hasPermission(27))
                            <li
                                class="{{ request()->routeIs(['meet-team.index', 'meet-team.create', 'meet-team.edit', 'meet-team.show']) ? 'active' : '' }}">
                                <a class="sidenav-item-link" href="{{ route('meet-team.index') }}">
                                    <img src="{{ asset('assets/images/team_icon.svg') }}" title="Product"
                                        alt="Product icon">
                                    <span class="nav-text">Meet Team </span>
                                </a>
                            </li>
                        @endif

                        @if (hasPermission(6) || hasPermission(7) || hasPermission(8) || hasPermission(36) || hasPermission(37))
                            <li
                                class="{{ request()->routeIs(['popup-content.index', 'popup-content.create', 'popup-content.edit', 'static-content.index', 'static-content.edit', 'faq.index', 'faq.create', 'faq.edit', 'email-template.index', 'email-template.edit', 'testimonial.index', 'testimonial.create', 'testimonial.edit', 'notification-template.index', 'notification-template.edit']) ? 'active has-sub' : 'has-sub' }}">
                                <a class="sidenav-item-link" href="javascript:void(0)" **data-toggle="collapse"**
                                    data-target="#icons"
                                    aria-expanded="{{ request()->routeIs(['popup-content.index', 'popup-content.create', 'popup-content.edit', 'static-content.index', 'static-content.edit', 'faq.index', 'faq.create', 'faq.edit', 'testimonial.index', 'testimonial.create', 'testimonial.edit', 'notification-template.index', 'notification-template.edit']) ? 'true' : 'false' }}"
                                    aria-controls="icons">
                                    <img src="{{ asset('assets/images/Content.svg') }}" title="Content Management"
                                        alt="Content icon">
                                    <span class="nav-text">Content Management</span>
                                    <img class="arrow-icon" src="{{ asset('assets/images/down-arrow.png') }}"
                                        alt="Arrow icon">
                                </a>
                                <ul class="collapse {{ request()->routeIs(['popup-content.index', 'popup-content.create', 'popup-content.edit', 'static-content.index', 'static-content.edit', 'faq.index', 'notification-template.index', 'notification-template.edit', 'faq.create', 'faq.edit', 'email-template.index', 'email-template.edit', 'testimonial.index', 'testimonial.create', 'testimonial.edit']) ? 'active show' : '' }}"
                                    id="icons">
                                    <div class="sub-menu">
                                        @if (hasPermission(7))
                                            <li
                                                class="{{ request()->routeIs(['static-content.index', 'static-content.edit']) ? 'active' : '' }}">
                                                <a href="{{ route('static-content.index') }}">
                                                    <span class="nav-text">Static Content</span>
                                                </a>
                                            </li>
                                        @endif
                                        @if (hasPermission(8))
                                            <li
                                                class="{{ request()->routeIs(['faq.index', 'faq.create', 'faq.edit']) ? 'active' : '' }}">
                                                <a href="{{ route('faq.index') }}">
                                                    <span class="nav-text">FAQ Management</span>
                                                </a>
                                            </li>
                                        @endif
                                        @if (hasPermission(6))
                                            <li
                                                class="{{ request()->routeIs(['email-template.index', 'email-template.edit']) ? 'active' : '' }}">
                                                <a href="{{ route('email-template.index') }}">
                                                    <span class="nav-text">Email Template</span>
                                                </a>
                                            </li>
                                        @endif
                                        @if (hasPermission(36))
                                            <li
                                                class="{{ request()->routeIs(['notification-template.index', 'notification-template.edit']) ? 'active' : '' }}">
                                                <a href="{{ route('notification-template.index') }}">
                                                    <span class="nav-text">Notification Template</span>
                                                </a>
                                            </li>
                                        @endif
                                        @if (hasPermission(37))
                                            <li
                                                class="{{ request()->routeIs(['popup-content.index', 'popup-content.create', 'popup-content.edit']) ? 'active' : '' }}">
                                                <a href="{{ route('popup-content.index') }}">
                                                    <span class="nav-text">Popup Content</span>
                                                </a>
                                            </li>
                                        @endif
                                    </div>
                                </ul>
                            </li>
                        @endif


                        @if (hasPermission(31) || hasPermission(32) || hasPermission(33) || hasPermission(34))
                            <li class="{{ request()->is(['exports/*']) ? 'active has-sub' : 'has-sub' }}">
                                <a class="sidenav-item-link" href="javascript:void(0)" data-toggle="collapse"
                                    data-target="#exportManagement"
                                    aria-expanded="{{ request()->is(['exports/*']) ? 'true' : 'false' }}"
                                    aria-controls="exportManagement">
                                    <img src="{{ asset('assets/images/Content.svg') }}" title="Export Reports"
                                        alt="Content icon">
                                    <span class="nav-text">Export Reports</span>
                                    <img class="arrow-icon" src="{{ asset('assets/images/down-arrow.png') }}"
                                        alt="Arrow icon">
                                </a>
                                <ul class="collapse {{ request()->is(['exports/*']) ? 'active show' : '' }}"
                                    id="exportManagement">
                                    <div class="sub-menu">
                                        @if (hasPermission(31))
                                            <li class="{{ request()->is('exports/User') ? 'active' : '' }}">
                                                <a href="{{ url('/exports/User') }}">
                                                    <span class="nav-text">Export User</span>
                                                </a>
                                            </li>
                                        @endif

                                        @if (hasPermission(32))
                                            <li class="{{ request()->is('exports/School') ? 'active' : '' }}">
                                                <a href="{{ url('/exports/School') }}">
                                                    <span class="nav-text">Export School</span>
                                                </a>
                                            </li>
                                        @endif

                                        {{-- @if (hasPermission(33))
                                            <li class="{{ request()->is('exports/VideoContent') ? 'active' : '' }}">
                                                <a href="{{ url('/exports/VideoContent') }}">
                                                    <span class="nav-text">Export Video Content</span>
                                                </a>
                                            </li>
                                        @endif

                                        @if (hasPermission(34))
                                            <li
                                                class="{{ request()->is('exports/KnowledgeSession') ? 'active' : '' }}">
                                                <a href="{{ url('/exports/KnowledgeSession') }}">
                                                    <span class="nav-text">Export Knw. Session</span>
                                                </a>
                                            </li>
                                        @endif --}}
                                    </div>
                                </ul>
                            </li>
                        @endif
                        @if (hasPermission(39))
                            <li class="{{ request()->routeIs(['video-requests.index', 'video-requests.show']) ? 'active' : '' }}">
                                <a class="sidenav-item-link" href="{{ route('video-requests.index') }}">
                                    <img src="{{ asset('assets/images/Knowledge.png') }}" title="Video Management"
                                        alt="Knowledge icon">
                                    <span class="nav-text">Video Request</span>
                                </a>
                            </li>
                        @endif

                        @if (hasPermission(11))
                            <li class="{{ request()->routeIs(['contact-us.index']) ? 'active' : '' }}">
                                <a class="sidenav-item-link" href="{{ route('contact-us.index') }}">
                                    <img src="{{ asset('assets/images/Contact.svg') }}" title="Contact Us"
                                        alt="Contact Us icon">
                                    <span class="nav-text">Contact Us</span>
                                </a>
                            </li>
                        @endif

                        {{-- @if (hasPermission(12))
                            <li class="{{ request()->routeIs(['settings.edit']) ? 'active' : '' }}">
                                <a class="sidenav-item-link" href="{{ route('settings.edit') }}">
                                    <img src="{{ asset('assets/images/settings.svg') }}" title="Settings"
                                        alt="Settings icon">
                                    <span class="nav-text">Settings</span>
                                </a>
                            </li>
                        @endif --}}
                        @if (hasPermission(28))
                            <li class="{{ request()->routeIs(['audit-log.index']) ? 'active' : '' }}">
                                <a class="sidenav-item-link" href="{{ route('audit-log.index') }}">
                                    <svg style="color:rgb(123, 126, 126)" width="20px" height="20px"
                                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                        <path
                                            d="M12.186 0H11.79c-1.656 0-3.27.01-4.924.013C2.96.027.264 2.661.013 6.066c-.012.163-.013.328-.013.492.002 3.148-.021 6.298.008 9.444.016 1.492.461 2.84 1.486 3.985C3.07 22.621 5.22 23.973 8.02 24c1.743.002 3.49.008 5.232-.002 1.472-.01 2.87-.391 4.074-1.301 1.704-1.268 2.679-2.985 2.652-5.161-.012-.985.007-1.973-.004-2.958-.002-.158.043-.202.2-.197.591.018 1.183-.002 1.774.01.112.002.148-.03.148-.145-.004-1.656.022-3.314-.011-4.968-.05-2.6-2.108-4.988-4.622-5.537-.444-.101-.894-.141-1.347-.14-1.179.002-2.36-.008-3.538.006m-3.48 6.27h5.784c.285 0 .576.012.857.057 1.058.17 1.938 1.002 2.164 2.04.038.175.06.355.066.533.01.288.004.576.004.88h-2.618c-.076-.008-.153-.014-.232-.014-.175-.004-.24-.065-.238-.24.002-.25.01-.5-.002-.748-.014-.335-.155-.572-.482-.63a2.747 2.747 0 0 0-.43-.025H8.708c-.45 0-.702-.248-.702-.68-.002-.42.26-.672.7-.672m5.782 7.747c.425.016.747.37.735.805a.773.773 0 0 1-.79.767H8.94c-.433 0-.728-.27-.75-.682a.772.772 0 0 1 .758-.89h5.542z" />
                                    </svg>
                                    <span class="nav-text" style="margin-left:14px;"> Audit Log</span>
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </aside>

        <div class="page-wrapper">
            <header class="main-header" id="header">
                <nav class="navbar navbar-expand-lg navbar-light" id="navbar">
                    <button id="sidebar-toggler" class="sidebar-toggle">
                        <span class="sr-only">Toggle navigation</span>
                    </button>
                    @if (Auth::guard('admin')->user()->user_role_id == '1' || Auth::guard('admin')->user()->user_role_id == '2')
                        <div class="navbar-right ">
                            <ul class="nav navbar-nav">
                                <li class="dropdown user-menu">
                                    <button class="dropdown-toggle nav-link" data-bs-toggle="dropdown"
                                        aria-expanded="false">
                                        <img src="{{ Auth::guard('admin')->user()->profile_image ? asset('uploads/profile_img/' . Auth::guard('admin')->user()->profile_image) : asset('assets/images/user.png') }}"
                                            class="user-image rounded-circle" alt="User Image" />
                                        <div class="admin-name">
                                            <span>{{ Auth::guard('admin')->user()->name }}</span>
                                            <span class="d-none d-lg-block">Admin</span>
                                        </div>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-right">
                                        <li>
                                            <a class="dropdown-link-item" href="{{ route('profile') }}">
                                                <img src="{{ asset('assets/images/manage-account.svg') }}"
                                                    alt="Manage Account icon" />
                                                <span class="nav-text">Manage Account</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-link-item" href="{{ route('change-password') }}">
                                                <img src="{{ asset('assets/images/change-password.svg') }}"
                                                    alt="Change Password icon" />
                                                <span class="nav-text">Change Password</span>
                                            </a>
                                        </li>
                                        @if (hasPermission(12))
                                            <li>
                                                <a class="dropdown-link-item" href="{{ route('settings.edit') }}">
                                                    <img src="{{ asset('assets/images/settings.svg') }}"
                                                        alt="Setting icon" />
                                                    <span class="nav-text">Setting</span>
                                                </a>
                                            </li>
                                        @endif

                                        <li>
                                            <a class="dropdown-link-item"
                                                href="{{ route('battery-setting.index') }}">
                                                {{-- <img src="{{ asset('assets/images/settings.svg') }}"
                                                    alt="Setting icon" /> --}}
                                                <i class="mdi mdi-battery" style="margin-right: 0px"></i>
                                                <span class="nav-text">Battery Setting</span>
                                            </a>
                                        </li>

                                        <li class="logout">
                                            <a class="dropdown-link-item" href="{{ url('logout') }}">
                                                <img src="{{ asset('assets/images/logout.svg') }}" title="Logout"
                                                    alt="Logout icon">
                                                <span class="nav-text">Logout</span>
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                        </div>
                    @endif
                </nav>
            </header>
            @yield('content')
        </div>
    </div>

    <script src="{{ asset('assets/plugins/simplebar/simplebar.min.js') }}"></script>
    <script src="https://unpkg.com/hotkeys-js/dist/hotkeys.min.js"></script>
    <script src="{{ asset('assets/plugins/apexcharts/apexcharts.js') }}"></script>
    <script src="{{ asset('assets/plugins/DataTables/DataTables-1.10.18/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/jvectormap/jquery-jvectormap-2.0.3.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/jvectormap/jquery-jvectormap-world-mill.js') }}"></script>
    <script src="{{ asset('assets/plugins/jvectormap/jquery-jvectormap-us-aea.js') }}"></script>
    <script src="{{ asset('assets/plugins/daterangepicker/moment.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/daterangepicker/daterangepicker.js') }}"></script>
    {{-- <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script> --}}
    <script src="{{ asset('assets/js/mono.js') }}"></script>
    <script src="{{ asset('assets/js/custom.js') }}"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.1/js/select2.min.js"></script>

    <script>
        jQuery(document).ready(function() {
            // Daterangepicker initialization
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
            // Wrap tables in responsive div
            jQuery("table").wrap("<div class='table-responsive'></div>");

            // Logout confirmation
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
                            type: "GET",
                            url: '{{ route('logout') }}',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken
                            },
                            success: function(data) {
                                Swal.fire({
                                    title: "Logged Out",
                                    text: "You have been logged out successfully!",
                                    icon: "success",
                                    // confirmButtonText: "OK"
                                }).then(() => {
                                    window.location.href =
                                        "{{ route('login') }}";
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

            // --- START Sidebar Dropdown Fix ---
            // This script handles the sidebar dropdown functionality
            // by toggling 'active' and 'show' classes manually,
            // which often works better with custom themes.
            $('aside.left-sidebar li.has-sub > a').on('click', function(e) {
                e.preventDefault(); // Prevent default link behavior

                var $this = $(this);
                var $parentLi = $this.parent('li.has-sub');
                var $targetUl = $($this.attr('data-target'));

                // Close other open sub-menus at the same level
                $('aside.left-sidebar li.has-sub').not($parentLi).removeClass('active');
                $('aside.left-sidebar li.has-sub > .collapse').not($targetUl).removeClass('show').attr(
                    'aria-expanded', 'false');

                // Toggle 'active' class on the parent <li>
                $parentLi.toggleClass('active');

                // Toggle 'show' class on the target <ul> and update aria-expanded
                if ($targetUl.hasClass('show')) {
                    $targetUl.removeClass('show').attr('aria-expanded', 'false');
                } else {
                    $targetUl.addClass('show').attr('aria-expanded', 'true');
                }
            });
            // --- END Sidebar Dropdown Fix ---
        });
    </script>
    {{-- <script src="https://cdn.ckeditor.com/ckeditor5/23.0.0/classic/ckeditor.js"></script> --}}

    <link href="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-lite.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-lite.min.js"></script>

    @stack('scripts')
</body>

</html>
