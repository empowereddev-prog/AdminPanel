@extends('layout.headerFooter')
@section('content')
    <div class="content-wrapper">&nbsp;&nbsp;
        <div class="content">
            <div class="card card-default">
                <div class="card-header">
                    <h6 class="font-weight-bold">Users</h6>
                </div>
                <div class="card-body">
                    <table class="table" id="users" summary="Data">
                        <thead>
                            <tr>
                                <th scope="col">S.NO</th>
                                <th scope="col">Name</th>
                                <th scope="col">Email</th>
                                <th scope="col">Phone Number</th>
                                <th scope="col">User Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be populated by DataTables AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript">
        $(function() {
            $('#users').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('users.data') }}",
                    type: 'GET'
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', searchable: true, orderable: true },
                    { data: 'name', name: 'name', searchable: true, orderable: false },
                    { data: 'email', name: 'email', searchable: true, orderable: false },
                    { data: 'contact', name: 'contact', searchable: true, orderable: false },
                    { data: 'user_type', name: 'user_type', searchable: true, orderable: false }
                ]
            });
        });
    </script>
@endsection
