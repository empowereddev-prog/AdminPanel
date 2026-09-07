@extends('layout.headerFooter')
@section('content')
    <!-- <style>
        .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .search-bar input {
        padding: 10px;
        font-size: 16px;
    }

    .user-info {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
    }

    .main {
        display: flex;
        flex-direction: column;
    }

    .dashboard-summary {
        display: flex;
        justify-content: space-between;
        margin-bottom: 20px;
    }

    .summary-card {
        background: #f5f5f5;
        padding: 20px;
        text-align: center;
        border-radius: 10px;
        flex: 1;
        margin-right: 10px;
    }

    .summary-card:last-child {
        margin-right: 0;
    }

    .active-plans {
        background: #ffffff;
        padding: 20px;
        border-radius: 10px;
    }



    .plan-filter button,
    .plan-filter input,
    .plan-filter span {
        margin-right: 10px;
    }

    .plan-filter button:last-child,
    .plan-filter input:last-child,
    .plan-filter span:last-child {
        margin-right: 0;
    }

    .plans-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    .plans-table th,
    .plans-table td {
        padding: 10px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }

    .plans-table th {
        background: #f5f5f5;
    }

    .pagination {
        display: flex;
        justify-content: flex-end;
    }
    .plan-filter {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
            }
            .plan-filter > div {
                margin-right: 10px;
            }
            .plan-filter button {
                padding: 5px 10px;
                cursor: pointer;
            }
    </style> -->
    <div class="content-wrapper">
        <div class="content">
          
            <div class="card card-default faq">
                <div class="title_left">
                    <h2>User Management</h2>
                </div>
                <div class="card-head mb-2">
                    @if (!empty($pre) && $pre->is_modify == 'yes')
                        <a href="{{ route('user.create') }}" class="btn btn-pill btn-primary">Add User</a>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table" id="users" summary="Data">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Name</th>
                                <th scope="col">Email</th>
                                <th scope="col">Phone no.</th>
                                <!-- <th scope="col"></th> -->
                                <!-- <th scope="col">Tax</th> -->
                                <th scope="col">Status</th>
                                @if (!empty($pre))
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
                        url: "{{ route('user.data') }}",
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
                            data: 'email',
                            name: 'email',
                            searchable: true,
                            orderable: false
                        },
                        {
                            data: 'phone_no',
                            name: 'phone_no',
                            searchable: true,
                            orderable: false
                        },
                        // { data: 'description', name: 'description', searchable: true, orderable: false },
                        // { data: 'discount', name: 'discount', searchable: true, orderable: false },
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
            } else {
                var table = $('#users').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: "{{ route('user.data') }}",
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
                            data: 'email',
                            name: 'email',
                            searchable: true,
                            orderable: false
                        },
                        {
                            data: 'phone_no',
                            name: 'phone_no',
                            searchable: true,
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
                        url: '{{ url('delete-user') }}' + "/" + id,
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
                        '', 'User Deleted Successfully.', 'success'
                    )
                }
            });
        });
    </script>

    </div>
@endsection
