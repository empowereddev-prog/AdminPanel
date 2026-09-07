@extends('layout.headerFooter')
@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="title_left">
                <h2>Popup Content</h2>
            </div>
            <div class="card card-default faq">
                <div class="card-head mb-2">
                    @if ($pre->is_modify == 'yes')
                        <a href="{{ route('popup-content.create') }}" class="btn btn-pill btn-primary">Add Popup Content</a>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table" id="popupContentTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Type</th>
                                <th>Title</th>
                                <th>Description</th>
                                @if ($pre->is_modify == 'yes')
                                    <th>Action(s)</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(function() {
            var table = $('#popupContentTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('popup-content.index') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'type',
                        name: 'type'
                    },
                    {
                        data: 'title',
                        name: 'title'
                    },
                    {
                        data: 'description',
                        name: 'description'
                    },
                    @if ($pre->is_modify == 'yes')
                        {
                            data: 'action',
                            name: 'action',
                            orderable: false,
                            searchable: false
                        }
                    @endif
                ]
            });

            $('body').on('click', '.delete', function(e) {
                e.preventDefault();
                var id = $(this).data('id');
                var csrfToken = $('meta[name="csrf-token"]').attr('content');

                Swal.fire({
                    title: 'Are you sure?',
                    text: "You want to delete this popup content!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('popup-content.destroy', '') }}/" + id,
                            type: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken
                            },
                            success: function() {
                                table.ajax.reload();
                                Swal.fire('Deleted!', 'Popup content has been deleted.',
                                    'success');
                            },
                            error: function() {
                                Swal.fire('Error!', 'Something went wrong.', 'error');
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
