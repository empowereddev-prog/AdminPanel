@extends('layout.headerFooter')
@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="title_left">
                <h2>School Management</h2>
            </div>
            <div class="card card-default faq">
                <div class="card-head mb-2">
                    @if (!empty($pre) && $pre->is_modify == 'yes')
                        <a href="{{ route('school.create') }}" class="btn btn-pill btn-primary">Add School</a>
                        <a href="{{ route('school.moods.overview') }}" class="btn btn-pill btn-primary ml-2">Show Moods</a>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table" id="users" summary="Data">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">School Name</th>
                                <th scope="col">School Code</th>
                                <th scope="col">Number of Parent</th>
                                <th scope="col">Number of Staff</th>
                                <th scope="col">Status</th>
                                @if (!empty($pre) && $pre->is_modify == 'yes')
                                    <th scope="col">Action(s)</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    </div>
    </div>

    <script type="text/javascript">
        $(function() {
            var check = "{{ $pre->is_modify }}";
            if (check == 'no') {
                $('#users').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('school.data') }}",
                        type: 'GET'
                    },
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            searchable: true,
                            orderable: false
                        },
                        {
                            data: 'name',
                            name: 'name',
                            searchable: true,
                            orderable: true
                        },
                        {
                            data: 'school_code',
                            name: 'school_code',
                            searchable: true,
                            orderable: false
                        },
                        {
                            data: 'child_count',
                            name: 'child_count',
                            searchable: true,
                            orderable: true
                        },
                        {
                            data: 'staff_count',    // ✅ Added properties index configuration element safely
                            name: 'staff_count',
                            searchable: false,
                            orderable: false
                        },
                        // { data: 'phone_no', name: 'phone_no', searchable: true, orderable: false },
                        // { data: 'description', name: 'description', searchable: true, orderable: false },
                        // { data: 'discount', name: 'discount', searchable: true, orderable: false },
                        {
                            data: 'status',
                            name: 'status',
                            searchable: true,
                            orderable: false
                        },
                        // {data: 'action', name: 'action', orderable:false },
                    ]
                });
            } else {
                $('#users').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('school.data') }}",
                        type: 'GET'
                    },
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            searchable: true,
                            orderable: false
                        },
                        {
                            data: 'name',
                            name: 'name',
                            searchable: true,
                            orderable: true
                        },
                        {
                            data: 'school_code',
                            name: 'school_code',
                            searchable: true,
                            orderable: false
                        },
                        {
                            data: 'child_count',
                            name: 'child_count',
                            searchable: true,
                            orderable: true
                        },
                        {
                            data: 'staff_count',    // ✅ Added properties index configuration element safely
                            name: 'staff_count',
                            searchable: false,
                            orderable: false
                        },
                        {
                            data: 'status',
                            name: 'status',
                            searchable: true,
                            orderable: false
                        },
                        {
                            data: 'action',
                            name: 'action',
                            orderable: false
                        },
                    ]
                });
            }
        });


        $("body").on('click', '.delete', function(e) {
            e.preventDefault();
            var id = $(this).data("id");
            // Get the CSRF token value from the meta tag in your HTML
            var csrfToken = $('meta[name="csrf-token"]').attr('content');

            Swal.fire({
                html: 'You want to delete data!',
                title: 'Are you sure?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "delete",
                        url: '{{ url('delete-school') }}' + "/" + id,
                        headers: {
                            'X-CSRF-TOKEN': csrfToken // Include the CSRF token in the request headers
                        },
                        success: function(data) {
                            var dataTable = $('#users').DataTable();
                            dataTable.ajax.reload();

                        },
                        error: function(data) {
                            var dataTable = $('#users').DataTable();
                            dataTable.ajax.reload();

                        }
                    });
                    Swal.fire(
                        '', 'School Deleted Successfully.', 'success'
                    )
                }
            });
        });
    </script>

    </div>
@endsection
