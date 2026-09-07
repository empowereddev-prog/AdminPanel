@extends('layout.headerFooter')
@section('content')
    <div class="content-wrapper">
        <div class="content">
            
            <div class="card card-default faq">
                <div class="title_left">
                    <h2>Child's Mood</h2>
                </div>
                <div class="card-head mb-2">
                    @if (!empty($pre) && $pre->is_modify == 'yes')
                        <a href="{{ route('add-mood') }}" class="btn btn-pill btn-primary">Add Mood</a>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table" id="user" summary="Data" style="table-layout:fixed">
                        <thead>
                            <tr>
                                <th scope="col" style="width:30px;">#</th>
                                <!-- <th scope="col">Session Type</th> -->
                                <th scope="col">Image</th>
                                <th scope="col">Name</th>

                                <th scope="col">Color</th>
                                <!-- <th scope="col">Link</th> -->
                                <!-- <th scope="col">Venue</th> -->
                                <!-- <th scope="col">Session Time</th> -->
                                {{-- <th scope="col">Points</th>   --}}
                                <th col="col">Type</th>
                                <th scope="col">Status</th>

                                @if (!empty($pre) && $pre->is_modify == 'yes')
                                    <th scope="col" style="width: 100px;">Action(s)</th>
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
                $('#user').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('mood-list') }}",
                        type: 'GET'
                    },
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            searchable: true,
                            orderable: false
                        },
                        {
                            data: 'image',
                            name: 'image',
                            searchable: true,
                            orderable: false
                        },
                        {
                            data: 'name',
                            name: 'name',
                            searchable: true,
                            orderable: true
                        },


                        {
                            data: 'color',
                            name: 'color',
                            searchable: true,
                            orderable: true
                        },
                        // { data: 'points', name: 'points', searchable: true, orderable: false },
                        {
                            data: 'type',
                            name: 'type',
                            searchable: true,
                            orderable: true
                        },
                        {
                            data: 'status',
                            name: 'status',
                            searchable: true,
                            orderable: false
                        },
                    ]
                });
            } else {
                $('#user').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('mood-list') }}",
                        type: 'GET'
                    },
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            searchable: true,
                            orderable: false
                        },
                        {
                            data: 'image',
                            name: 'image',
                            searchable: true,
                            orderable: false
                        },
                        {
                            data: 'name',
                            name: 'name',
                            searchable: true,
                            orderable: true
                        },

                        // { data: 'session_time', name: 'session_time', searchable: true, orderable: true },
                        {
                            data: 'color',
                            name: 'color',
                            searchable: true,
                            orderable: true
                        },
                        // { data: 'points', name: 'points', searchable: true, orderable: false },
                        {
                            data: 'type',
                            name: 'type',
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
                        type: "delete",
                        url: '{{ url('delete-mood') }}' + "/" + id,
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                        },
                        success: function(data) {
                            var dataTable = $('#user').DataTable();
                            dataTable.ajax.reload();

                        },
                        error: function(data) {
                            var dataTable = $('#user').DataTable();
                            dataTable.ajax.reload();

                        }
                    });
                    Swal.fire(
                        '', 'Mood Deleted Successfully.', 'success'
                    )
                }
            });
        });
    </script>

    </div>
@endsection
