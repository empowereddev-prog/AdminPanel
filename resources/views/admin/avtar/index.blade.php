@extends('layout.headerFooter')
@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="title_left">
                <h2>Avatar</h2>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <ul class="admin-breadcrumb">
                        <li>
                            <a href="{{ route('avtar.type_index') }}"> Avatar</a>
                        </li>
                        <li class="active">
                            <a href="#">{{ $type. ' Category' }}</a>
                        </li>

                    </ul>
                </div>
            </div>
            <div class="card card-default">
                <div class="card-head mb-2 d-flex justify-content-end">
                    <a href="{{ route('avtar.create', ['type' => $type ?? 'default']) }}"
                        class="btn btn-pill btn-primary">Add Avatar</a>
                    <a href="{{ route('avtar.type_index') }}" class="btn btn-light">Back</a>
                </div>
                <div class="table-responsive">
                    <table class="table" id="user" summary="Data">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Type</th>
                                <th scope="col">Preview Image</th>
                                <!-- <th scope="col">Apply Image</th> -->
                                <th scope="col">Battery (%)</th>
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
                var type = "{{ $type ?? '' }}"; // Ensure `$type` is passed from Blade
                $('#user').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ url('avtar') }}/" + encodeURIComponent(type) + "/data",
                        type: 'post',
                        data: {
                            _token: "{{ csrf_token() }}" // Ensure CSRF token is sent
                        }
                    },
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            searchable: true,
                            orderable: false
                        },
                        {
                            data: 'body_part',
                            name: 'body_part',
                            searchable: true,
                            orderable: true
                        },
                        {
                            data: 'preview_image',
                            name: 'preview_image',
                            searchable: true,
                            orderable: true
                        },
                        // { data: 'apply_image', name: 'apply_image', searchable: true, orderable: true },
                        {
                            data: 'points',
                            name: 'points',
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
                var type = "{{ $type ?? '' }}"; // Ensure `$type` is passed from Blade
                $('#user').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ url('avtar') }}/" + encodeURIComponent(type) + "/data",
                        type: 'post',
                        data: {
                            _token: "{{ csrf_token() }}" // Ensure CSRF token is sent
                        }
                    },
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            searchable: true,
                            orderable: false
                        },
                        {
                            data: 'body_part',
                            name: 'body_part',
                            searchable: true,
                            orderable: true
                        },
                        {
                            data: 'preview_image',
                            name: 'preview_image',
                            searchable: true,
                            orderable: true
                        },
                        // { data: 'apply_image', name: 'apply_image', searchable: true, orderable: true },
                        {
                            data: 'points',
                            name: 'points',
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


        //     $("body").on('click', '.delete', function(e) {
        //     e.preventDefault();
        //     var id = $(this).data("id");
        //     var type = $(this).data("type"); // Get type from data attribute
        //     // Get the CSRF token value from the meta tag in your HTML
        //     var csrfToken = $('meta[name="csrf-token"]').attr('content');

        //     Swal.fire({
        //         html: 'You want to delete data!'
        //         , title: 'Are you sure?'
        //         , icon: 'warning'
        //         , showCancelButton: true
        //         , confirmButtonColor: '#3085d6'
        //         , cancelButtonColor: '#d33'
        //         , confirmButtonText: 'Yes'
        //     }).then((result) => {
        //         if (result.isConfirmed) {
        //             $.ajax({
        //                 type: "delete"
        //                 , url: `/avtar/${type}/${id}/delete`
        //                 , headers: {
        //                     'X-CSRF-TOKEN': csrfToken 
        //                 }
        //                 , success: function(data) {
        //                     var dataTable = $('#user').DataTable();
        //                     dataTable.ajax.reload();

        //                 }
        //                 , error: function(data) {
        //                     var dataTable = $('#user').DataTable();
        //                     dataTable.ajax.reload();

        //                 }
        //             });
        //             Swal.fire(
        //                 ''
        //                 , 'Avtar Image Deleted Successfully.'
        //                 , 'success'
        //             )
        //         }
        //     });
        // });

        $("body").on('click', '.delete', function(e) {
            e.preventDefault();
            var id = $(this).data("id");
            var type = $(this).data("type");
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
                        url: `/avtar/${type}/${id}/delete`,
                        type: "POST",  
                        data: {
                            _method: "DELETE",
                            _token: csrfToken
                        },
                        success: function(response) {
                            $('#user').DataTable().ajax.reload();
                            Swal.fire('', response.message, 'success');
                        },
                        error: function(xhr) {
                            let message = 'Something went wrong!';

                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                message = xhr.responseJSON.message;
                            }

                            Swal.fire('Error', message, 'error');
                        }
                    });

                }
            });
        });
    </script>

    </div>
@endsection
