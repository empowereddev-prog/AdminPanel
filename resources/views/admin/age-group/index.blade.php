@extends('layout.headerFooter')
@section('content')
<div class="content-wrapper">
    <div class="content">
        <div class="title_left">
            <h2>Age Groups</h2>
        </div>
        <div class="card card-default faq">
            <div class="card-head mb-2">
                <a href="{{route('age-group.create')}}" class="btn btn-pill btn-primary">Add Age Group</a>
            </div>
            <div class="table-responsive">
                            <table class="table" id="plan" summary="Data">
                                <thead>
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Group Name</th>
                                        <th scope="col">Start Age</th>
                                        <th scope="col">End Age</th>
                                        <!-- <th scope="col">Status</th> -->
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
        </div>
    </div>

    <script type="text/javascript">
        $(function() {
            $('#plan').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('age-group.data') }}",
                    type: 'GET'
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', searchable: true, orderable: true },
                    { data: 'name', name: 'name', searchable: true, orderable: true },
                    { data: 'start_age', name: 'start_age', searchable: true, orderable: false },
                    { data: 'end_age', name: 'end_age', searchable: true, orderable: false },
                    // { data: 'status', name: 'status', searchable: true, orderable: false },
                    {data: 'action', name: 'action', orderable:false },
                ]
            });
        });


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
                    , url: '{{ url("admin/delete-age-group") }}' + "/" + id
                    , headers: {
                        'X-CSRF-TOKEN': csrfToken // Include the CSRF token in the request headers
                    }
                    , success: function(data) {
                        var dataTable = $('#plan').DataTable();
                        dataTable.ajax.reload();

                    }
                    , error: function(data) {
                        var dataTable = $('#plan').DataTable();
                        dataTable.ajax.reload();

                    }
                });
                Swal.fire(
                    ''
                    , 'School Deleted Successfully.'
                    , 'success'
                )
            }
        });
    });
    </script>

</div>
@endsection
