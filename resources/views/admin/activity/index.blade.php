@extends('layout.headerFooter')
@section('content')
<div class="content-wrapper">
    <div class="content">
        <div class="title_left">
            <h2>Activity</h2>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <ul class="admin-breadcrumb">
                    <li>
                        <a href="{{ route('mood-index') }}">Mood</a>
                    </li>
                    <li class="active">
                        <a href="#">Activity</a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="card card-default faq">
            <div class="card-head mb-2">
            @if(!empty($pre) && $pre->is_modify == 'yes')<a href="{{route('activity.create', ['id' => $id])}}" class="btn btn-pill btn-primary">Add Activity</a>
            @endif
            </div>
            <div class="table-responsive">
                            <table class="table" id="user" summary="Data">
                                <thead>
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col" style="max-width:20%;">Mood</th>
                                        <th scope="col" style="max-width:20%;">Activity</th>
                                        <th scope="col">Points</th>
                                        <th scope="col">Status</th>
                                        @if(!empty($pre) && $pre->is_modify == 'yes')<th scope="col">Action(s)</th>
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
            var check = "{{$pre->is_modify}}";
            if(check == 'no'){
            $('#user').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('activity.data',['id'=> $id]) }}",
                    type: 'GET'
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', searchable: true, orderable: false },
                    { data: 'category', name: 'category', searchable: true, orderable: true },
                    { data: 'activity', name: 'activity', searchable: true, orderable: true },
                    { data: 'points', name: 'points', searchable: true, orderable: true },
                    { data: 'status', name: 'status', searchable: true, orderable: false },
                    // {data: 'action', name: 'action', orderable:false },
                ]
            });
        }else{
            $('#user').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('activity.data',['id'=> $id]) }}",
                    type: 'GET'
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', searchable: true, orderable: false },
                    { data: 'category', name: 'category', searchable: true, orderable: true },
                    { data: 'activity', name: 'activity', searchable: true, orderable: true },
                    { data: 'points', name: 'points', searchable: true, orderable: true },
                    { data: 'status', name: 'status', searchable: true, orderable: false },
                    {data: 'action', name: 'action', orderable:false },
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
                    , url: '{{ url("delete-activity") }}' + "/" + id
                    , headers: {
                        'X-CSRF-TOKEN': csrfToken 
                    }
                    , success: function(data) {
                        var dataTable = $('#user').DataTable();
                        dataTable.ajax.reload();

                    }
                    , error: function(data) {
                        var dataTable = $('#user').DataTable();
                        dataTable.ajax.reload();

                    }
                });
                Swal.fire(
                    ''
                    , 'Activity Deleted Successfully.'
                    , 'success'
                )
            }
        });
    });
    </script>

</div>
@endsection
