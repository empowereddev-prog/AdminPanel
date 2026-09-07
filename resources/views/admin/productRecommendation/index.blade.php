@extends('layout.headerFooter')

@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="card card-default">
                <div class="card-header">
                    <h3 class="font-weight-bold">Resources</h3>
                    @if ($pre->is_modify == 'yes')
                        <a href="{{ route('product.create') }}"><button type="button"
                                class="mb-1 btn btn-pill btn-primary">Add</button></a>
                    @endif
                </div>
                {{-- {{ dd($pre->is_modify) }} --}}
                <div class="card-body" style=" overflow-y: auto;">
                    <table class="table" id="data" summary="Data">
                        <thead>
                            <tr>
                                <th scope="col">S.No</th>
                                <th scope="col">Image</th>
                                <th scope="col">Title</th>
                                <th scope="col">Description</th>
                                <th scope="col">Priority</th>

                                <th scope="col">Status</th>
                                @if ($pre->is_modify == 'yes')
                                    <th scope="col" style="width: 100px;">Action(s)</th>
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
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $('#data').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('product.index') }}",
                    type: 'GET'
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        searchable: false,
                        orderable: true
                    },
                    {
                        data: 'image',
                        name: 'image',
                        orderable: false
                    },
                    {
                        data: 'title',
                        name: 'title',
                        orderable: false,
                        searchable: true
                    },
                    {
                        data: 'description',
                        name: 'description',
                        orderable: false
                    },
                    {
                        data: 'priority',
                        name: 'priority',
                        searchable: true,
                        orderable: true
                    },

                    {
                        data: 'status',
                        name: 'status',
                        orderable: false
                    },
                    {
                        data: 'action',
                        name: 'action',
                        searchable: false,
                        orderable: false
                    },
                ],
            });
        });


        $("body").on('click', '.delete', function(e) {
            e.preventDefault();
            var id = $(this).data("id");
            var csrfToken = $('meta[name="csrf-token"]').attr('content');

            // CORRECT WAY: Create a URL template with a placeholder
            var urlTemplate = '{{ route('product.destroy', ['id' => 'PLACEHOLDER']) }}';

            // Now, replace the placeholder with the real ID from the button
            var deleteUrl = urlTemplate.replace('PLACEHOLDER', id);

            Swal.fire({
                title: 'Are you sure?',
                html: 'You want to delete this resource!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "DELETE",
                        url: deleteUrl, // Use the corrected URL
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                        },
                        success: function(data) {
                            Swal.fire(
                                'Deleted!',
                                'Resource has been deleted successfully.',
                                'success'
                            );
                            $('#data').DataTable().ajax.reload();
                        },
                        error: function(data) {
                            Swal.fire(
                                'Error!',
                                'Something went wrong. Could not delete product.',
                                'error'
                            );
                        }
                    });
                }
            });
        });
        $(document).on('change', '.priority-input', function() {
            let quizId = $(this).data('id');
            let newPriority = $(this).val();
            let $inputField = $(this);

            $.ajax({
                url: "{{ route('product.updatePriority') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    id: quizId,
                    priority: newPriority
                },
                success: function(response) {
                    toastr.success(response.message || "Priority updated successfully.");
                    $inputField.css("border", "2px solid green");
                    $('#data').DataTable().ajax.reload(null, false);
                },
                error: function(xhr) {
                    let errorMessage = "Something went wrong.";
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    toastr.error(errorMessage);
                    $inputField.css("border", "2px solid red");
                }
            });
        });
    </script>
@endsection
