
@extends('layout.headerFooter')
@section('content')
    <div class="content-wrapper">&nbsp;&nbsp;
        <div class="content">
            <div class="card card-default">
                <div class="card-header">
                <h6 class="font-weight-bold">Address Management</h6>
                <a href="{{route('addresses.create')}}"><button type="button" class="mb-1 btn btn-pill btn-primary">Add</button></a>
                </div>
                <div class="card-body">
                    <table class="table" id="users" summary="Data">
                        <thead>
                        <tr>
                        <th scope="col">S.No.</th>
                        <th scope="col">Title</th>
                        <th scope="col">Description</th>
                        <th scope="col">Contact No.</th>
                        <th scope="col" width="100px">Status</th>
                        <th scope="col" width="100px">Action(s)</th>
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
            $('#users').DataTable({
                ajax: {
                    url: "{{ route('addresses.index') }}",
                },
                paging: true,
                columns: [{
                        data: 'DT_RowIndex'
                        , name: 'DT_RowIndex'
                        , searchable: false
                        , orderable: true

                    },
                    {
                        data: 'title',
                        name: 'title',
                        searchable:true,
                        orderable:false
                    },
                    {
                        data: 'description',
                        name: 'description',
                         render: function(data, type, row) {
                            // Create a temporary div element to strip out HTML tags
                            var div = document.createElement('div');
                            div.innerHTML = data;
                            return div.innerText;
                        },
                        searchable:true,
                        orderable:false
                    },
                    {
                        data: 'contact_no',
                        name: 'contact_no',
                        searchable:true,
                        orderable:false
                    },
                    {
                        data: 'status',
                        name: 'status',
                        searchable:true,
                        orderable:false
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable:false
                    },
                ]

            });
        });
    </script>

    <script>
    $("body").on('click', '.delete', function(e) {
        e.preventDefault();
        var id = $(this).data("id");
        // Get the CSRF token value from the meta tag in your HTML
        var csrfToken = $('meta[name="csrf-token"]').attr('content');

        Swal.fire({
            html: 'You want to delete data!'
            , title: 'Are you sure?'
            , icon: 'warning'
            , showCancelButton: true
            , confirmButtonColor: '#3085d6'
            , cancelButtonColor: '#d33'
            , confirmButtonText: 'Yes'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "delete"
                    , url: '{{ url("admin/addresses") }}' + "/" + id
                    , headers: {
                        'X-CSRF-TOKEN': csrfToken // Include the CSRF token in the request headers
                    }
                    , success: function(data) {
                        var dataTable = $('#users').DataTable();
                        dataTable.ajax.reload();

                    }
                    , error: function(data) {
                        var dataTable = $('#users').DataTable();
                        dataTable.ajax.reload();

                    }
                });
                Swal.fire(
                    ''
                    , 'Address  Deleted Successfully.'
                    , 'success'
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
            html: 'You want to change status!'
            , title: 'Are you sure?'
            , icon: 'warning'
            , showCancelButton: true
            , confirmButtonColor: '#3085d6'
            , cancelButtonColor: '#d33'
            , confirmButtonText: 'Yes'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "post"
                    , url: '{{ url("admin/change-address-status") }}' + "/" + id
                    , headers: {
                        'X-CSRF-TOKEN': csrfToken // Include the CSRF token in the request headers
                    }
                    , success: function(data) {
                        var dataTable = $('#users').DataTable();
                        dataTable.ajax.reload();
                    }
                    , error: function(data) {
                        var dataTable = $('#users').DataTable();
                        dataTable.ajax.reload();

                    }
                });
                Swal.fire(
                    ''
                    , 'Status Updated Successfully.'
                    , 'success'
                )
            }
        });
    });

</script>
@endsection
