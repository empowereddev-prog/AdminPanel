@extends('layout.headerFooter')
@section('content')
<div class="content-wrapper">
    <div class="content">
        <div class="title_left">
            <h2>Child Mood Tracker</h2>
        </div>
        <div class="card card-default faq card-clild-mood">
            <div class="card-head mb-2">
            </div>
            <div class="table-responsive">
                            <table class="table" id="user" summary="Data">
                                <thead>
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Child Name</th>
                                        <th scope="col">Username</th>
                                        <!-- <th scope="col">Category</th> -->
                                         <th scope="col">Date</th>  
                                        @if(!empty($pre) && $pre->is_modify == 'yes')
                                        <th scope="col" width="150px" >Action(s)</th>
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
            console.log(check,'check');
            if(check == 'no'){
            $('#user').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('child-mood-tracker') }}",
                    type: 'GET'
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', searchable: true, orderable: true },
                    { data: 'user', name: 'user', searchable: true, orderable: true },
                    { data: 'user_name', name: 'user_name', searchable: true, orderable: true },
                    // { data: 'category', name: 'category', searchable: true, orderable: true },
                    { data: 'date', name: 'date', searchable: true, orderable: true },
                ]
            });
        }else{
            $('#user').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('child-mood-tracker') }}",
                    type: 'GET'
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', searchable: true, orderable: true },
                    { data: 'user', name: 'user', searchable: true, orderable: true },
                    { data: 'user_name', name: 'user_name', searchable: true, orderable: true },
                    // { data: 'category', name: 'category', searchable: true, orderable: true },
                    { data: 'date', name: 'date', searchable: true, orderable: true },
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
                    , url: '{{ url("delete-question") }}' + "/" + id
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
                    , 'Category Deleted Successfully.'
                    , 'success'
                )
            }
        });
    });
    </script>

</div>
@endsection
