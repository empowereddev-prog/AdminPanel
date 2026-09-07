@extends('layout.headerFooter')

@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>View School Details</h2>
                <a href="{{ route('school.index') }}" class="btn btn-secondary">
                    <i class="mdi mdi-arrow-left"></i> Back
                </a>
            </div>

            <div class="row mb-3">
                <div class="col-sm-12">
                    <ul class="admin-breadcrumb">
                        <li><a href="{{ route('school.index') }}">School Management</a></li>
                        <li class="active"><a href="#">View School</a></li>
                    </ul>
                </div>
            </div>

            <div class="card shadow-lg p-4 mb-4 border-0 rounded-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="fw-bold text-primary mb-0">
                        <i class="mdi mdi-school"></i> {{ $school->name }}
                    </h3>
                    <a href="{{ route('school.children.progress', $school->id) }}" class="btn btn-outline-primary px-4">
                        <i class="mdi mdi-chart-bar"></i> Students Report
                    </a>
                </div>

                <hr class="my-3">

                <div class="mb-2">
                    <span class="fw-semibold text-muted">🏫 School Code:</span>
                    <span class="ms-2">{{ $school->school_code }}</span>
                </div>

                <div class="mb-2">
                    <span class="fw-semibold text-muted">👥 Max Limit:</span>
                    <span class="ms-2">{{ $school->max_limit }}</span>
                </div>

                <div class="mb-2">
                    <span class="fw-semibold text-muted">📦 Subscription:</span>
                    <span class="ms-2">
                        {{ \Illuminate\Support\Str::of($school->subscription_type)->replace('_', '')->camel()->ucfirst() }}
                    </span>
                </div>
            </div>

            <div class="card shadow p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="fw-bold mb-0">School User List</h3>

                    <div style="min-width: 200px;">
                        <select id="userRoleFilter" class="form-select">
                            <option value="all"> All Users (Parents & Staff)</option>
                            <option value="parent"> Parents Only</option>
                            <option value="teacher"> Staff Only</option>
                        </select>
                    </div>
                </div>

                <div class="card shadow p-3 mb-4">
                    <form method="GET" action="{{ route('school.export.users', $school->id) }}" class="row g-3">
                        <input type="hidden" name="role_type" id="exportRoleType" value="all">

                        <div class="col-md-4">
                            <label>Start Date</label>
                            <input type="date" name="start_date" id="start_date" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label>End Date</label>
                            <input type="date" name="end_date" id="end_date" class="form-control">
                        </div>

                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="mdi mdi-file-excel"></i> Export Users
                            </button>
                        </div>
                    </form>
                </div>

                @if ($school->students->isEmpty())
                    <p class="text-center">No school users found.</p>
                @else
                    <table class="table table-striped" id="schoolUsersTable">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role Type</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $displayIndex = 1; @endphp
                            @foreach ($school->students as $student)
                                {{-- Determine role based on user_role_id or fallback configuration --}}
                                @php
                                    $roleType = ($student->user_role_id == 5 || $student->user_type == 'teacher') ? 'teacher' : 'parent';
                                @endphp
                                <tr id="row-{{ $student->id }}" class="user-row" data-role="{{ $roleType }}">
                                    <td class="row-index">{{ $displayIndex++ }}</td>
                                    <td>{{ $student->name }}</td>
                                    <td>{{ $student->email }}</td>
                                    <td>
                                        @if($roleType === 'teacher')
                                            <span class="text-muted fw-semibold">Staff / Teacher</span>
                                        @else
                                            <span class="text-muted fw-semibold">Parent</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center" style="gap: 12px;">
                                            <a
                                                href="{{ route('school-user-child-detail', $student->id) }}"
                                                title="View Details"
                                                aria-label="View Details"
                                                class="text-decoration-none d-flex align-items-center justify-content-center"
                                            >
                                                <i class="mdi mdi-eye" style="font-size: 20px; line-height: 1;"></i>
                                            </a>

                                            <button
                                                type="button"
                                                class="delete-student btn btn-link p-0 text-danger d-flex align-items-center justify-content-center"
                                                data-id="{{ $student->id }}"
                                            >
                                                <i class="mdi mdi-trash-can" style="font-size: 20px; line-height: 1;"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            <tr id="noUsersFoundRow" style="display: none;">
                                <td colspan="5" class="text-center text-muted">No records match the selected filter.</td>
                            </tr>
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

        <script type="text/javascript">
            $(document).ready(function() {
                $('#userRoleFilter').on('change', function() {
                    var selectedRole = $(this).val();
                    var visibleCount = 0;

                    $('#exportRoleType').val(selectedRole);

                    $('.user-row').each(function() {
                        var rowRole = $(this).data('role');

                        if (selectedRole === 'all' || rowRole === selectedRole) {
                            $(this).show();
                            visibleCount++;
                            $(this).find('.row-index').text(visibleCount);
                        } else {
                            $(this).hide();
                        }
                    });

                    if (visibleCount === 0) {
                        $('#noUsersFoundRow').show();
                    } else {
                        $('#noUsersFoundRow').hide();
                    }
                });

                // Prevent selecting an End Date before the Start Date
                $('#start_date').on('change', function () {
                    let startDate = $(this).val();

                    $('#end_date').attr('min', startDate);

                    if ($('#end_date').val() && $('#end_date').val() < startDate) {
                        $('#end_date').val('');
                    }
                });

                // Validate before export
                $('form[action="{{ route('school.export.users', $school->id) }}"]').on('submit', function (e) {
                    let startDate = $('#start_date').val();
                    let endDate = $('#end_date').val();

                    if (startDate && endDate && endDate < startDate) {
                        e.preventDefault();

                        Swal.fire({
                            icon: 'warning',
                            title: 'Invalid Date Range',
                            text: 'End Date must be greater than or equal to Start Date.'
                        });
                    }
                });

                $(document).on("click", ".delete-student", function() {
                    var studentId = $(this).data("id");

                    Swal.fire({
                        title: "Delete School User",
                        text: "Are you sure you want to delete this user?",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonText: "Yes",
                        cancelButtonText: "Cancel",
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: '{{ url('delete-school-user') }}/' + studentId,
                                type: "POST",
                                data: {
                                    _token: "{{ csrf_token() }}",
                                    _method: "DELETE"
                                },
                                success: function(response) {
                                    Swal.fire({
                                        icon: "success",
                                        text: "School user deleted successfully.",
                                        showConfirmButton: true
                                    }).then(() => {
                                        $("#row-" + studentId).remove();
                                        $('#userRoleFilter').trigger('change');
                                    });
                                },
                                error: function() {
                                    Swal.fire("Error", "Something went wrong!", "error");
                                }
                            });
                        }
                    });
                });
            });
        </script>
    @endsection
