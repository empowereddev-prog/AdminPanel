@extends('layout.headerFooter')
@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="title_left">
                <h2>FAQ Management</h2>
            </div>
            <div class="card card-default faq">
                <div class="card-head mb-2">
                    <a href="{{ url('faq/create') }}" class="btn btn-pill btn-primary">Add Faq</a>
                </div>
                <div class="table-responsive">
                    <table class="table" id="users" summary="Data">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Type</th>
                                <th scope="col">Question</th>
                                <th scope="col">Answer</th>
                                {{-- <th scope="col">Language</th> --}}
                                <th scope="col">Status</th>
                                @if (!empty($pre) && $pre->is_modify == 'yes')
                                    <th scope="col" width="100px">Action(s)</th>
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
                    ajax: {
                        url: "{{ route('faq.index') }}",
                    },
                    paging: true,
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            searchable: false,
                            orderable: true

                        },
                        {
                            data: 'type',
                            name: 'type',
                            searchable: true,
                            orderable: true
                        },
                        {
                            data: 'question',
                            name: 'question',
                            searchable: true,
                            orderable: true
                        }, {
                            data: 'answer',
                            name: 'answer',
                            searchable: true,
                            orderable: true
                        }
                        // , {
                        //     data: 'language'
                        //     , name: 'language'
                        //     , searchable: true
                        //     , orderable: false
                        // }

                        ,
                    ]

                });
            } else {
                $('#users').DataTable({
                    ajax: {
                        url: "{{ route('faq.index') }}",
                    },
                    paging: true,
                    columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        searchable: false,
                        orderable: true

                    }, {
                        data: 'type',
                        name: 'type',
                        searchable: true,
                        orderable: true
                    }, {
                        data: 'question',
                        name: 'question',
                        searchable: true,
                        orderable: true
                    }, {
                        data: 'answer',
                        name: 'answer',
                        searchable: true,
                        orderable: true
                    }, {
                        data: 'status',
                        name: 'status',
                        searchable: true,
                        orderable: false
                    }, {
                        data: 'action',
                        name: 'action',
                        orderable: false
                    }, ]

                });
            }
        });
    </script>
    <script>
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
                        url: '{{ route('faq.destroy', ':id') }}'.replace(':id', id),
                        type: 'DELETE',
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
                        '', 'Faq Data Deleted Successfully.', 'success'
                    )
                }
            });
        });
        $("body").on('click', '.switch-input', function(e) {
            e.preventDefault();
            var id = $(this).data("id");
            // Get the CSRF token value from the meta tag in your HTML
            var csrfToken = $('meta[name="csrf-token"]').attr('content');

            Swal.fire({
                html: 'You want to change status!',
                title: 'Are you sure?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "post",
                        url: '{{ url('admin/change-faq-status') }}' + "/" + id,
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
                        '', 'Status Updated Successfully.', 'success'
                    )
                }
            });
        });
    </script>
@endsection
