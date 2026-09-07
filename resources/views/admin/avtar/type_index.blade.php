@extends('layout.headerFooter')
@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="title_left">
                <h2>Avatar</h2>
            </div>
            <div class="card card-default">
                <!-- <div class="card-head mb-2">
                    <a href="" class="btn btn-pill btn-primary">Add Avtar</a>
                </div> -->

                <div class="table-responsive">
                    <table class="table" id="plan" summary="Data">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Type</th>
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
                $('#plan').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('avtar.type_data') }}",
                        type: 'GET'
                    },
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            searchable: true,
                            orderable: true
                        },
                        {
                            data: 'body_part',
                            name: 'body_part',
                            searchable: true,
                            orderable: true
                        },
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
                $('#plan').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('avtar.type_data') }}",
                        type: 'GET'
                    },
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            searchable: true,
                            orderable: true
                        },
                        {
                            data: 'body_part',
                            name: 'body_part',
                            searchable: true,
                            orderable: true
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

        $("body").on('click', '.switch-input', function(e) {
            e.preventDefault();
            var id = $(this).data("id");
            var currentStatus = $(this).data("status"); // Get current status
            var newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            // Get the CSRF token value from the meta tag in your HTML
            var csrfToken = $('meta[name="csrf-token"]').attr('content');

            Swal.fire({
                html: `Are you sure you want to set status to <b>${newStatus.toUpperCase()}</b>?`,
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
                        url: '{{ url('avtar/change-status') }}' + "/" + id,
                        headers: {
                            'X-CSRF-TOKEN': csrfToken // Include the CSRF token in the request headers
                        },
                        success: function(data) {
                            var dataTable = $('#plan').DataTable();
                            dataTable.ajax.reload();
                        },
                        error: function(data) {
                            var dataTable = $('#plan').DataTable();
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

    </div>
@endsection
