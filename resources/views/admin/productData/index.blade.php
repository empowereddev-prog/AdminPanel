@extends('layout.headerFooter')

@section('content')
<div class="content-wrapper">
    <div class="content">
        <div class="card card-default">
            <div class="card-header">
                <h6 class="font-weight-bold">Product Data</h6>
                <a href="{{ route('product-data.create') }}"><button type="button" class="mb-1 btn btn-pill btn-primary">Add</button></a>
            </div>
            <div class="card-body">
                <table class="table" id="data" summary="Data">
                    <thead>
                        <tr>
                            <th scope="col">S.No</th>
                            <th scope="col">Name</th>
                            <th scope="col">Type</th>
                            <th scope="col">Action(s)</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $('#data').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('product-data.index') }}",
                type: 'GET'
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', searchable: false, orderable: true },
                { data: 'name', name: 'name',orderable: false, 
               render: function(data, type, full, meta) {
                     return formatColumnName(data);// Capitalize the first letter
                  },
            },
                { data: 'type', name: 'type',orderable: false,
                    render: function(data, type, full, meta) {
                     return formatColumnName(data);// Capitalize the first letter
                  },

                 },
                { data: 'action', name: 'action', searchable: false, orderable: false },
            ],
        });
    });
function formatColumnName(name) {
        return name.replace(/_/g, ' ').replace(/\b\w/g, function(l){ return l.toUpperCase() });
    }

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
                    , url: '{{ url("admin/product-data") }}' + "/" + id
                    , headers: {
                        'X-CSRF-TOKEN': csrfToken // Include the CSRF token in the request headers
                    }
                    , success: function(data) {
                        var dataTable = $('#data').DataTable();
                        dataTable.ajax.reload();
                    }
                    , error: function(data) {
                        var dataTable = $('#data').DataTable();
                        dataTable.ajax.reload();
                    }
                });
                Swal.fire(
                    ''
                    , 'Product data  Deleted Successfully.'
                    , 'success'
                )
            }
        });
    });
    // $("body").on('click', '.switch-input', function(e) {
    //     e.preventDefault();
    //     var id = $(this).data("id");
    //     // Get the CSRF token value from the meta tag in your HTML
    //     var csrfToken = $('meta[name="csrf-token"]').attr('content');

    //     Swal.fire({
    //         html: 'You want to change status!'
    //         , title: 'Are you sure?'
    //         , icon: 'warning'
    //         , showCancelButton: true
    //         , confirmButtonColor: '#3085d6'
    //         , cancelButtonColor: '#d33'
    //         , confirmButtonText: 'Yes'
    //     }).then((result) => {
    //         if (result.isConfirmed) {
    //             $.ajax({
    //                 type: "post"
    //                 , url: '{{ url("admin/change-service-status") }}' + "/" + id
    //                 , headers: {
    //                     'X-CSRF-TOKEN': csrfToken // Include the CSRF token in the request headers
    //                 }
    //                 , success: function(data) {
    //                     var dataTable = $('#users').DataTable();
    //                     dataTable.ajax.reload();
    //                 }
    //                 , error: function(data) {
    //                     var dataTable = $('#users').DataTable();
    //                     dataTable.ajax.reload();

    //                 }
    //             });
    //             Swal.fire(
    //                 ''
    //                 , 'Status Updated Successfully.'
    //                 , 'success'
    //             )
    //         }
    //     });
    // });

</script>
@endsection
