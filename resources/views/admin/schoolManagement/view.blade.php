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
                 landed silently (imports, deletes). --}}
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
                        @if ($school->price !== null)
                            — SGD {{ number_format((float) $school->price, 2) }}
                        @endif
                    </span>
                </div>
            </div>

            <div class="card shadow p-4">
                <h3 class="fw-bold" style="margin:0 0 6px;">Parents</h3>
                <p class="text-muted" style="margin:0 0 16px;max-width:70ch;">
                    Parent accounts and the school's parent email list in one place. Teachers have their own list in the Teacher Roster below.
                    Disable an account to block sign-in without deleting it; you can enable it again later.
                </p>

                {{-- Parents are added by spreadsheet only, so the upload lives
                     next to the list it populates. Same file and same rules as
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
                                <th>Roster</th>
                                <th>Account</th>
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
                        { data: 'roster_badge', orderable: false, searchable: false },
                        { data: 'account_badge', orderable: false, searchable: false },
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

                $('#rosterTable').on('click', '.roster-restore', function() {
                    var id = $(this).data('id');

                    swal({
                        title: 'Enable roster access?',
                        text: 'This parent will be able to use the school code again.',
                        icon: 'info',
                        buttons: ['Cancel', 'Enable']
                    }).then(function(confirmed) {
                        if (!confirmed) {
                            return;
                        }

                        $.ajax({
                            type: 'POST',
                            url: '/school/roster/' + id + '/restore',
                            headers: { 'X-CSRF-TOKEN': csrfToken },
                            data: { _token: csrfToken },
                            success: function(res) {
                                rosterTable.ajax.reload(null, false);
                                swal('', res.message, 'success');
                            },
                            error: function(xhr) {
                                swal('', (xhr.responseJSON && xhr.responseJSON.message) || 'Could not restore that entry.', 'error');
                            }
                        });
                    });
                });

                $('#rosterTable').on('click', '.parent-status', function() {
                    var id = $(this).data('id');
                    var next = $(this).data('next');
                    var enabling = next === 'active';

                    swal({
                        title: enabling ? 'Enable this parent?' : 'Disable this parent?',
                        text: enabling
                            ? 'They and their children will be able to sign in again.'
                            : 'They and their children will not be able to sign in until you enable them again. The account is not deleted.',
                        icon: enabling ? 'info' : 'warning',
                        buttons: ['Cancel', enabling ? 'Enable' : 'Disable'],
                        dangerMode: !enabling
                    }).then(function(confirmed) {
                        if (!confirmed) {
                            return;
                        }

                        $.ajax({
                            type: 'POST',
                            url: '/school/{{ $school->id }}/parents/' + id + '/status',
                            headers: { 'X-CSRF-TOKEN': csrfToken },
                            data: { _token: csrfToken, status: next },
                            success: function(res) {
                                rosterTable.ajax.reload(null, false);
                                swal('', res.message, 'success');
                            },
                            error: function(xhr) {
                                swal('', (xhr.responseJSON && xhr.responseJSON.message) || 'Could not update that account.', 'error');
                            }
                        });
                    });
                });

                $('#rosterTable').on('click', '.parent-delete', function() {
                    var id = $(this).data('id');

                    swal({
                        title: 'Remove this parent?',
                        text: 'Their account and children will be removed from this school. This cannot be undone from here.',
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
                                rosterTable.ajax.reload(null, false);
                                swal('', 'Parent removed.', 'success');
                            },
                            error: function() {
                                swal('', 'Could not remove that parent.', 'error');
                            }
                        });
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
            });
        </script>
    @endsection
