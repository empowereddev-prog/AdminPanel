@extends('layout.headerFooter')
@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="title_left">
                <h2>Parent Video & Webinars</h2>
            </div>
            <div class="card card-default faq">
                {{-- <div class="card-head mb-2">
                    @if (!empty($pre) && $pre->is_modify == 'yes')
                        <a href="{{ route('knowledge-base.create') }}" class="btn btn-pill btn-primary">Add Video & Webinars</a>
                    @endif
                </div> --}}
                <div class="card-head mb-2 d-flex">
                    @if (!empty($pre) && $pre->is_modify == 'yes')
                        <a href="{{ route('knowledge-base.create') }}" class="btn btn-pill btn-primary me-2">Add Video
                            & Webinars</a>
                    @endif

                    <!-- 🟢 Category Filter -->
                    <select id="categoryFilter" class="form-control" style="width:200px;">
                        <option value="">All Categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->category_name }}</option>
                        @endforeach
                    </select>
                    <!-- 🟢 User Type Filter -->
                    {{-- <select id="userTypeFilter" class="form-control" style="width:200px; margin-left:10px;">
                        <option value="">All Users</option>
                        <option value="parent">Parent</option>
                        <option value="child">Child</option>
                    </select> --}}

                </div>
                <div class="table-responsive">
                    <table class="table" id="user" summary="Data" style="table-layout:fixed">
                        <thead>
                            <tr>
                                <th scope="col" style="width: 25px;">#</th>
                                <th scope="col">Category</th>
                                <th scope="col">Title</th>
                                <!-- <th scope="col">Description</th> -->
                                {{-- <th scope="col">Likes</th>
                                <th scope="col">Dislikes</th>
                                <th scope="col">Favourites</th> --}}

                                <th scope="col">Color</th>
                                <th scope="col">Title Color</th>
                                <th scope="col">User Type</th>
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
    <!-- User List Modal -->
    <div class="modal fade" id="userListModal" tabindex="-1" aria-labelledby="userListModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg video-modal"> <!-- Centered and large modal -->
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userListModalLabel">Users</h5>
                    <button type="button" class="custom-close-btn" data-bs-dismiss="modal" aria-label="Close">
                        <span class="text-32" aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div style="max-height: 300px; overflow-y: auto;"> <!-- Scrollable body -->
                        <table class="table table-bordered table-hover mb-0 video-table-pop" id="userListTable">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 50px; text-align:center;">#</th>
                                    <th class="text-center">User Name</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript">
        $(document).ready(function() {
            var dataTable = $('#user').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('knowledge-base.data') }}",
                    type: 'GET',
                    data: function(d) {
                        d.category_id = $('#categoryFilter').val();
                        d.user_type = $('#userTypeFilter').val();
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        searchable: false,
                        orderable: false
                    },
                    {
                        data: 'category',
                        name: 'category',
                        searchable: true,
                        orderable: true
                    },
                    {
                        data: 'title',
                        name: 'title',
                        searchable: true,
                        orderable: true
                    },
                    // { data: 'total_likes', name: 'total_likes', searchable: false, orderable: false },
                    // { data: 'total_dislikes', name: 'total_dislikes', searchable: false, orderable: false },
                    // { data: 'total_favourite', name: 'total_favourite', searchable: false, orderable: false },
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
                        data: 'like_count',
                        name: 'like_count',
                        searchable: false,
                        orderable: false
                    },
                    {
                        data: 'status',
                        name: 'status',
                        searchable: false,
                        orderable: false
                    }
                    @if (!empty($pre) && $pre->is_modify == 'yes')
                        , {
                            data: 'action',
                            name: 'action',
                            orderable: false
                        }
                    @endif
                ]
            });

            $('#categoryFilter, #userTypeFilter').change(function() {
                dataTable.ajax.reload(null, false);
            });

        });


        $('body').on('click', '.show-users', function() {
            var videoId = $(this).data('id');
            var type = $(this).data('type');

            $.ajax({
                url: '{{ url('video-users-popup') }}',
                type: 'GET',
                data: {
                    video_id: videoId,
                    type: type
                },
                success: function(res) {
                    $('#userListTable tbody').html('');

                    if (Array.isArray(res.users) && res.users.length > 0) {
                        $.each(res.users, function(i, user) {
                            $('#userListTable tbody').append(
                                '<tr title="' + user.name + '">' +
                                '<td class="text-center">' + (i + 1) + '</td>' +
                                '<td class="text-center">' + user.name + '</td>' +
                                '</tr>'
                            );
                        });
                    } else {
                        $('#userListTable tbody').append(
                            '<tr><td colspan="2" class="text-center">No users found.</td></tr>');
                    }

                    function ucfirst(str) {
                        return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
                    }
                    $('#userListModalLabel').html('<strong>Users Who ' + ucfirst(type) +
                        ' This Video</strong>');
                    $('#userListModal').modal('show');
                }
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
                        type: "delete",
                        url: '{{ url('delete-knowledge-base') }}' + "/" + id,
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
                        '', 'Video Content Deleted Successfully.', 'success'
                    )
                }
            });
        });
    </script>
    </div>
@endsection
