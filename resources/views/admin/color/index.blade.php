@extends('layout.headerFooter')
@section('content')
    <div class="content-wrapper">
        <div class="content">
           
            <div class="card card-default faq">
                <div class="title_left">
                    <h3>Color Managament</h3>
                </div>
                <div class="card-head mb-2">

                    @if ($pre->is_modify == 'yes')
                        <a href="{{ route('color.create') }}" class="btn btn-pill btn-primary">Add Color</a>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table" id="plan" summary="Data">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Color</th>
                                <th scope="col">Color Title</th>
                                @if ($pre->is_modify == 'yes')
                                    <th scope="col">Action(s)</th>
                                    @else
                                    <th></th>
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
    <script type="text/javascript">
        $(function() {
            var check = "{{ $pre->is_modify }}";
            if (check == 'no') {
                $('#plan').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('color.index') }}",
                        type: 'GET'
                    },
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            searchable: true,
                            orderable: false
                        },
                        {
                            data: 'color',
                            name: 'color',
                            searchable: true,
                            orderable: true
                        },
                        {
                            data: 'title_color',
                            name: 'title_color',
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
            } else {
                $('#plan').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('color.index') }}",
                        type: 'GET'
                    },
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            searchable: true,
                            orderable: false
                        },
                        {
                            data: 'color',
                            name: 'color',
                            searchable: true,
                            orderable: true
                        },
                        {
                            data: 'title_color',
                            name: 'title_color',
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
            var csrfToken = $('meta[name="csrf-token"]').attr('content');
            Swal.fire({
                html: 'You want to delete this color!',
                title: 'Are you sure?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "DELETE",
                        url: '{{ url('color/delete') }}' + "/" + id,
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                        },
                        success: function(data) {
                            $('#plan').DataTable().ajax.reload();
                            Swal.fire('Deleted!', 'Color deleted successfully.', 'success');
                        },
                        error: function(data) {
                            $('#plan').DataTable().ajax.reload();
                            Swal.fire('Error!', 'Something went wrong while deleting.',
                                'error');
                        }
                    });
                }
            });
        });
    </script>
@endsection
