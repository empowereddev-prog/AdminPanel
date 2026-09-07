
@extends('layout.headerFooter')
@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="title_left">
                <h2>Hero Banner</h2>
            </div>
            <div class="card card-default">
                <div class="card-head mb-2 text-right">
                    <a href="{{route('banners.create')}}" class="btn btn-pill btn-primary">
                        Add
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table" id="users" summary="Data">
                        <thead>
                            <tr>
                            <th scope="col">S.No</th>
                            <th scope="col">Type</th>
                            <th scope="col">Title</th>
                            <th scope="col">Image</th>
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
                    url: "{{ route('banners.index') }}",
                },
                paging: true,
                columns: [{
                        data: 'DT_RowIndex'
                        , name: 'DT_RowIndex'
                        , searchable: false
                        , orderable: true

                    },
                    {
                        data: 'type',
                        name: 'type',
                        searchable: true,
                        orderable: false
                    },
                   {
                        data: 'banner_title',
                        name: 'banner_title',
                        searchable: true,
                        orderable: false
                    },
                    {
                        data: 'media',
                        name: 'media',
                        searchable: false,
                        orderable: false
                    },
                    {
                        data: 'status',
                        name: 'status',
                        searchable:false,
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
                    , url: '{{ url("admin/banners") }}' + "/" + id
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
                    , 'Banner Image  Deleted Successfully.'
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
                    , url: '{{ url("admin/banner/toggle-status") }}' + "/" + id
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
