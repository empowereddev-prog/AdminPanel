@extends('layout.headerFooter')
@section('content')
    <div class="content-wrapper">
        <div class="content">
           
            <div class="card card-default faq">
                <div class="title_left">
                    <h2>Category</h2>
                </div>
                {{-- <div class="card-head mb-2">
            @if (!empty($pre) && $pre->is_modify == 'yes')<a href="{{route('category.create')}}" class="btn btn-pill btn-primary">Add Category</a>
            @endif
            </div> --}}
                <div class="table-responsive">
                    <table class="table" id="plan" summary="Data" style="table-layout:fixed">
                        <thead>
                            <tr>
                                <th scope="col" style="width: 25px;">#</th>
                                <th scope="col">Category Name</th>
                                <th scope="col">Color</th>
                                <th scope="col">Title Color</th>
                                <th scope="col">Priority</th>
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
                $('#plan').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('category.data') }}",
                        type: 'GET'
                    },
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            searchable: true,
                            orderable: false
                        },
                        {
                            data: 'category_name',
                            name: 'category_name',
                            searchable: true,
                            orderable: true
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
                            orderable: true
                        },
                        {
                            data: 'priority',
                            name: 'priority',
                            searchable: true,
                            orderable: false
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
                $('#plan').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('category.data') }}",
                        type: 'GET'
                    },
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            searchable: true,
                            orderable: false
                        },
                        {
                            data: 'category_name',
                            name: 'category_name',
                            searchable: true,
                            orderable: true
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
                            orderable: true
                        },
                        {
                            data: 'priority',
                            name: 'priority',
                            searchable: true,
                            orderable: false
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
                        url: '{{ url('delete-category') }}' + "/" + id,
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                        },
                        success: function(data) {
                            var dataTable = $('#plan').DataTable();
                            dataTable.ajax.reload();

                        },
                        error: function(data) {
                            var dataTable = $('#plan').DataTable();
                            dataTable.ajax.reload();

                        }
                    });
                    Swal.fire(
                        '', 'Category Deleted Successfully.', 'success'
                    )
                }
            });
        });
        $(document).on('change', '.priority-input', function() {
            let quizId = $(this).data('id');
            let newPriority = $(this).val();
            let $inputField = $(this);

            $.ajax({
                url: "{{ route('categories.updatePriority') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    id: quizId,
                    priority: newPriority
                },
                success: function(response) {
                    toastr.success(response.message || "Priority updated successfully.");
                    $inputField.css("border", "2px solid green");
                    $('#plan').DataTable().ajax.reload(null, false);
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

    </div>
@endsection
