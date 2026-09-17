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

            {{-- This page had no flash region at all, so every back()->with(...)
                 landed silently: the export redirecting with "No users found"
                 looked like a button that did nothing, and the same was true of
                 the import results. --}}
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

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

                {{-- Relabelled from "Max Limit": this number has always capped
                     PARENT accounts, and the ambiguity is half the reason
                     nobody noticed children were never counted. --}}
                <div class="mb-2">
                    <span class="fw-semibold text-muted">👥 Parent Limit:</span>
                    <span class="ms-2">
                        {{ $seats['parents_used'] }} / {{ $school->max_limit ?? 'Unlimited' }}
                    </span>
                </div>

                <div class="mb-2">
                    <span class="fw-semibold text-muted">🎟️ Child Places:</span>
                    <span class="ms-2">
                        {{ $seats['child_seats_used'] }} /
                        {{ $seats['child_seat_limit'] ?? 'Unlimited' }}
                        @if (!is_null($seats['child_seats_remaining']))
                            <small class="text-muted">({{ $seats['child_seats_remaining'] }} remaining)</small>
                        @endif
                    </span>
                </div>

                <div class="mb-2">
                    <span class="fw-semibold text-muted">👤 Children Per Parent:</span>
                    <span class="ms-2">{{ $seats['per_parent_child_limit'] ?? 'Unlimited' }}</span>
                </div>

                <div class="mb-2">
                    <span class="fw-semibold text-muted">📦 Subscription:</span>
                    <span class="ms-2">
                        {{ \Illuminate\Support\Str::of($school->subscription_type)->replace('_', '')->camel()->ucfirst() }}
                    </span>
                </div>
            </div>

            <div class="card shadow p-4">
                <h3 class="fw-bold" style="margin:0 0 6px;">Parent Accounts</h3>
                <p class="text-muted" style="margin:0 0 16px;max-width:70ch;">
                    Parents of this school. Teachers have their own list in the Teacher Roster below.
                </p>

                <div class="card shadow p-3 mb-4">
                    <form method="GET" action="{{ route('school.export.users', $school->id) }}" class="row g-3">
                        {{-- This list is parents only, so the export is too. The
                             endpoint still accepts all/parent/teacher. --}}
                        <input type="hidden" name="role_type" value="parent">

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
                                <i class="mdi mdi-file-excel"></i> Export Parents
                            </button>
                        </div>
                    </form>
                </div>

                @if ($school->parents->isEmpty())
                    <p class="text-center">No parent accounts yet.</p>
                @else
                    <table class="table table-striped" id="schoolUsersTable">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $displayIndex = 1; @endphp
                            @foreach ($school->parents as $student)
                                <tr id="row-{{ $student->id }}" class="user-row">
                                    <td class="row-index">{{ $displayIndex++ }}</td>
                                    <td>{{ $student->name }}</td>
                                    <td>{{ $student->email }}</td>
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
                        </tbody>
                    </table>
                @endif
            </div>

            {{-- A third card rather than tabs: no admin view in this project uses
                 nav-tabs, so tabs here would need new JS, new active-state handling
                 and would look unlike every other screen. Stacked cards are the
                 established idiom on this page. --}}
            <div class="card shadow p-4 mt-4">
                <h4 class="fw-bold text-primary" style="margin:0 0 6px;">
                    <i class="mdi mdi-account-check"></i> Parent Roster
                </h4>
                <p class="text-muted" style="margin:0 0 20px;max-width:70ch;">
                    The school's list of parent email addresses. Parents are added by uploading the school's parent
                    list — there is no manual entry here.
                </p>

                {{-- Parents are added by spreadsheet only, so the upload lives
                     next to the roster it populates. Same file and same rules as
                     the Edit screen - one service behind both. --}}
                <div style="border:1px solid #e6edf5;border-radius:8px;padding:18px;background:#fafbfd;margin:0 0 24px;">
                    <form action="{{ route('school.import.parents', $school->id, false) }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        <div class="fw-semibold" style="margin-bottom:4px;">Import parents</div>
                        <div class="text-muted" style="font-size:13px;margin-bottom:12px;">
                            Creates a parent account per row and emails each one their sign-in details.
                        </div>

                        <input type="file" name="parent_excel" id="parent_excel" class="d-none" accept=".xlsx,.xls" required>

                        <div style="display:flex;flex-wrap:wrap;align-items:center;">
                            <button type="button" class="btn btn-secondary" style="margin:0 8px 8px 0;"
                                onclick="document.getElementById('parent_excel').click();">Choose File</button>
                            <span id="parent_excel_name" class="text-muted" style="font-size:13px;margin:0 8px 8px 0;">No file chosen</span>
                            <button type="submit" class="btn btn-primary" style="margin:0 8px 8px 0;">Upload</button>
                            <a href="{{ route('download.sample.excel', [], false) }}" class="text-muted"
                                style="font-size:13px;margin-bottom:8px;">Download sample</a>
                        </div>
                    </form>
                </div>

                {{-- Settings sit in their own strip rather than on the heading row.
                     On the heading row they had to share horizontal space with the
                     title and collapsed into each other on anything but a wide
                     screen; here each one owns a full row and reads the same at
                     every width. --}}
                <div style="border:1px solid #e6edf5;border-radius:8px;padding:4px 18px;margin:0 0 20px;background:#fafbfd;">
                    <label style="display:flex;align-items:flex-start;padding:14px 0;margin:0;cursor:pointer;border-bottom:1px solid #eef2f7;">
                        <input type="checkbox" class="school-flag" data-flag="enforce_parent_roster"
                            style="margin:3px 12px 0 0;flex:0 0 auto;"
                            {{ ($school->enforce_parent_roster ?? 'no') === 'yes' ? 'checked' : '' }}>
                        <span>
                            <span class="fw-semibold" style="display:block;">Only roster emails may join</span>
                            <span class="text-muted" style="font-size:13px;">
                                A parent can register with this school code only if their address is on the list above,
                                and each address can be claimed once.
                            </span>
                        </span>
                    </label>
                    <label style="display:flex;align-items:flex-start;padding:14px 0;margin:0;cursor:pointer;">
                        <input type="checkbox" class="school-flag" data-flag="self_signup_enabled"
                            style="margin:3px 12px 0 0;flex:0 0 auto;"
                            {{ ($school->self_signup_enabled ?? 'yes') === 'yes' ? 'checked' : '' }}>
                        <span>
                            <span class="fw-semibold" style="display:block;">Allow sign-up with school code</span>
                            <span class="text-muted" style="font-size:13px;">
                                Turn this off for schools that onboard only by import, so the code cannot be used to
                                register at all.
                            </span>
                        </span>
                    </label>
                </div>

                <div class="table-responsive">
                    <table id="rosterTable" class="table table-striped w-100">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Children</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <div class="card shadow p-4 mt-4">
                <h4 class="fw-bold text-primary" style="margin:0 0 6px;">
                    <i class="mdi mdi-account-child"></i> Child Accounts
                </h4>
                <p class="text-muted" style="margin:0 0 20px;max-width:70ch;">
                    Children created by this school's parents. Each one uses a child place:
                    <strong>{{ $seats['child_seats_used'] }} of {{ $seats['child_seat_limit'] ?? 'unlimited' }}</strong>
                    @if (!is_null($seats['child_seats_remaining']))
                        used, {{ $seats['child_seats_remaining'] }} remaining.
                    @else
                        used.
                    @endif
                </p>

                <div class="table-responsive">
                    <table id="childTable" class="table table-striped w-100">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Child</th>
                                <th>Username</th>
                                <th>Age</th>
                                <th>Parent</th>
                                <th>Status</th>
                                <th>Added</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <div class="card shadow p-4 mt-4">
                <h4 class="fw-bold text-primary" style="margin:0 0 6px;">
                    <i class="mdi mdi-human-male-board"></i> Teacher Roster
                </h4>
                <p class="text-muted" style="margin:0 0 20px;max-width:70ch;">
                    Staff accounts for this school. Teachers are added by spreadsheet and use no parent or child
                    places — they are counted separately from the school's paid seats.
                </p>

                <div style="border:1px solid #e6edf5;border-radius:8px;padding:18px;background:#fafbfd;margin:0 0 24px;">
                    <form action="{{ route('school.import.staff', $school->id, false) }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        <div class="fw-semibold" style="margin-bottom:4px;">Import teachers</div>
                        <div class="text-muted" style="font-size:13px;margin-bottom:12px;">
                            Each row needs a username as well as a name, email, country code and phone number.
                        </div>

                        <input type="file" name="staff_excel" id="staff_excel" class="d-none" accept=".xlsx,.xls" required>

                        <div style="display:flex;flex-wrap:wrap;align-items:center;">
                            <button type="button" class="btn btn-secondary" style="margin:0 8px 8px 0;"
                                onclick="document.getElementById('staff_excel').click();">Choose File</button>
                            <span id="staff_excel_name" class="text-muted" style="font-size:13px;margin:0 8px 8px 0;">No file chosen</span>
                            <button type="submit" class="btn btn-primary" style="margin:0 8px 8px 0;">Upload</button>
                            <a href="{{ route('download.sample.staff.excel', [], false) }}" class="text-muted"
                                style="font-size:13px;margin-bottom:8px;">Download sample</a>
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table id="teacherTable" class="table table-striped w-100">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Username</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Added</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

        <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

        <script type="text/javascript">
            $(document).ready(function() {
                var csrfToken = $('meta[name="csrf-token"]').attr('content');

                ['parent_excel', 'staff_excel'].forEach(function(id) {
                    var input = document.getElementById(id);
                    if (!input) { return; }
                    input.addEventListener('change', function() {
                        document.getElementById(id + '_name').textContent =
                            this.files.length ? this.files[0].name : 'No file chosen';
                    });
                });

                var rosterTable = $('#rosterTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: '{{ route('school.roster.data', $school->id, false) }}',
                    columns: [
                        { data: 'DT_RowIndex', orderable: false, searchable: false },
                        { data: 'name', name: 'name' },
                        { data: 'email', name: 'email' },
                        { data: 'phone_no', name: 'phone_no', orderable: false },
                        { data: 'status_badge', name: 'status' },
                        { data: 'children', orderable: false, searchable: false },
                        { data: 'action', orderable: false, searchable: false }
                    ]
                });

                $('#childTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: '{{ route('school.children.data', $school->id, false) }}',
                    order: [],
                    columns: [
                        { data: 'DT_RowIndex', orderable: false, searchable: false },
                        { data: 'name', name: 'c.name' },
                        { data: 'username', name: 'c.username' },
                        { data: 'age', orderable: false, searchable: false },
                        { data: 'parent', name: 'p.name' },
                        { data: 'status_badge', name: 'c.status' },
                        { data: 'added', orderable: false, searchable: false }
                    ]
                });

                var teacherTable = $('#teacherTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: '{{ route('school.teachers.data', $school->id, false) }}',
                    columns: [
                        { data: 'DT_RowIndex', orderable: false, searchable: false },
                        { data: 'name', name: 'name' },
                        { data: 'email', name: 'email' },
                        { data: 'username', name: 'username' },
                        { data: 'phone', orderable: false, searchable: false },
                        { data: 'status_badge', name: 'status' },
                        { data: 'added', orderable: false, searchable: false },
                        { data: 'action', orderable: false, searchable: false }
                    ]
                });

                $('#teacherTable').on('click', '.teacher-delete', function() {
                    var id = $(this).data('id');

                    swal({
                        title: 'Remove this teacher?',
                        text: 'Their staff account will be removed from this school.',
                        icon: 'warning',
                        buttons: ['Cancel', 'Remove'],
                        dangerMode: true
                    }).then(function(confirmed) {
                        if (!confirmed) {
                            return;
                        }

                        $.ajax({
                            type: 'POST',
                            url: '/delete-school-user/' + id,
                            headers: { 'X-CSRF-TOKEN': csrfToken },
                            data: { _token: csrfToken, _method: 'DELETE' },
                            success: function() {
                                teacherTable.ajax.reload(null, false);
                                swal('', 'Teacher removed.', 'success');
                            },
                            error: function() {
                                swal('', 'Could not remove that teacher.', 'error');
                            }
                        });
                    });
                });

                $('#rosterTable').on('click', '.roster-revoke', function() {
                    var id = $(this).data('id');

                    swal({
                        title: 'Revoke this parent?',
                        text: 'They will no longer be able to join this school with the school code. Their existing account is not deleted.',
                        icon: 'warning',
                        buttons: ['Cancel', 'Revoke'],
                        dangerMode: true
                    }).then(function(confirmed) {
                        if (!confirmed) {
                            return;
                        }

                        $.ajax({
                            type: 'POST',
                            url: '/school/roster/' + id + '/revoke',
                            headers: { 'X-CSRF-TOKEN': csrfToken },
                            data: { _token: csrfToken },
                            success: function(res) {
                                rosterTable.ajax.reload(null, false);
                                swal('', res.message, 'success');
                            },
                            error: function() {
                                swal('', 'Could not revoke that entry.', 'error');
                            }
                        });
                    });
                });

                $('#rosterTable').on('click', '.roster-resend', function() {
                    var id = $(this).data('id');

                    $.ajax({
                        type: 'POST',
                        url: '/school/roster/' + id + '/resend',
                        headers: { 'X-CSRF-TOKEN': csrfToken },
                        data: { _token: csrfToken },
                        success: function(res) {
                            swal('', res.message, 'success');
                        },
                        error: function() {
                            swal('', 'Could not send that invitation.', 'error');
                        }
                    });
                });

                $('.school-flag').on('change', function() {
                    var $input = $(this);
                    var flag = $input.data('flag');

                    $.ajax({
                        type: 'POST',
                        url: '/school/{{ $school->id }}/toggle-flag/' + flag,
                        headers: { 'X-CSRF-TOKEN': csrfToken },
                        data: { _token: csrfToken },
                        success: function(res) {
                            $input.prop('checked', res.value === 'yes');
                            swal('', res.message, 'success');
                        },
                        error: function(xhr) {
                            // Most often: the backfill has not been run, so
                            // turning enforcement on would lock parents out.
                            $input.prop('checked', !$input.prop('checked'));
                            swal('', (xhr.responseJSON && xhr.responseJSON.message) || 'Could not change that setting.', 'error');
                        }
                    });
                });

                // The list is parents only now, so there is nothing to filter -
                // this just keeps the row numbers contiguous after a removal.
                function renumberParentRows() {
                    $('.user-row:visible').each(function(i) {
                        $(this).find('.row-index').text(i + 1);
                    });
                }

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
                                url: '/delete-school-user/' + studentId,
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
                                        renumberParentRows();
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
