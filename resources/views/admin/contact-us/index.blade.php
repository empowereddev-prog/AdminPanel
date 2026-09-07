@extends('layout.headerFooter')

@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="title_left">
                <h2>Contact Us</h2>
            </div>
            <div class="card card-default">
                <div class="card-head mb-2 text-right">
                    <!-- <a href="{{ route('banners.create') }}" class="btn btn-pill btn-primary">
                                Add
                            </a> -->
                </div>
                <div class="table-responsive">
                    <table class="table" id="users" summary="Data">
                        <thead>
                            <tr>
                                <th scope="col">S.No</th>
                                <th scope="col">Name</th>
                                <th scope="col">Email</th>
                                @if (!empty($pre) && $pre->is_modify == 'yes')
                                    <th scope="col" width="100px">Action(s)</th>
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
    <!-- /.content-wrapper -->

    <script type="text/javascript">
        $(function() {
            var check = "{{ $pre->is_modify }}";
            if (check == 'no') {
                $('#users').DataTable({
                    // console.log('hhhhhhhh');
                    ajax: {
                        url: "{{ route('contact-us.index') }}",
                    },
                    paging: true,
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            searchable: false,
                            orderable: true

                        },
                        {
                            data: 'name',
                            name: 'name',
                            searchable: true,
                            orderable: false
                        },
                        {
                            data: 'email',
                            name: 'email',
                            searchable: false,
                            orderable: false
                        }
                    ],
                    initComplete: function(settings, json) {
                        var pageNumber = parseInt(savedPage);
                        var totalPages = table.page.info().pages;

                        if (!isNaN(pageNumber) && pageNumber >= 0 && pageNumber < totalPages) {
                            setTimeout(function() {
                                table.page(pageNumber).draw(false);
                            }, 100);
                        } else {
                            table.page(0).draw(false);
                        }
                    }
                });
            } else {
                $('#users').DataTable({
                    // console.log('hhhhhhhh');
                    ajax: {
                        url: "{{ route('contact-us.index') }}",
                    },
                    paging: true,
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            searchable: false,
                            orderable: true

                        },
                        {
                            data: 'name',
                            name: 'name',
                            searchable: true,
                            orderable: false
                        },
                        {
                            data: 'email',
                            name: 'email',
                            searchable: false,
                            orderable: false
                        },
                        {
                            data: 'action',
                            name: 'action',
                            orderable: false
                        },
                    ],
                    initComplete: function(settings, json) {
                        var pageNumber = parseInt(savedPage);
                        var totalPages = table.page.info().pages;

                        if (!isNaN(pageNumber) && pageNumber >= 0 && pageNumber < totalPages) {
                            setTimeout(function() {
                                table.page(pageNumber).draw(false);
                            }, 100);
                        } else {
                            table.page(0).draw(false);
                        }
                    }
                });
            }
            table.on('page.dt', function() {
                var info = table.page.info();
                localStorage.setItem('dataTablePage_' + encodeURIComponent(currentUrl), info.page);
            });



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
                        type: "POST",
                        url: "/contact-us/" + id,
                        headers: {
                            'X-CSRF-TOKEN': csrfToken // Include the CSRF token in the request headers
                        },
                        success: function(data) {
                            var dataTable = $('#users').DataTable();
                            dataTable.ajax.reload();

                        },
                        error: function(data) {
                            var dataTable = $('#users').DataTable();
                            dataTable.ajax.reload();

                        }
                    });
                    Swal.fire(
                        '', 'Contact Us Deleted Successfully.', 'success'
                    )
                }
            });

        });
        @if (session('updated'))
            swal("Replied successfully!", {
                icon: "success",
                button: "OK"
            });
        @endif
    </script>
@endsection
