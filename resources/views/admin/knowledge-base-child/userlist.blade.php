@extends('layout.headerFooter')
@section('content')
    <div class="content-wrapper">
        <div class="content">

            <div class="card card-default faq">
                <div class="title_left">
                    <h3>Video Like, Dislike & Favourite User List</h3>
                </div>

                {{-- Filter Buttons with Icons --}}
                <div class="card-head mb-2">
                    <button class="btn btn-outline-secondary btn-sm type-btn" data-type="all" title="Show All Users">
                        <i class="mdi mdi-account-multiple-outline"></i>
                    </button>
                    <button class="btn btn-outline-success btn-sm type-btn" data-type="like" title="Show Likes">
                        <i class="mdi mdi-thumb-up-outline"></i>
                    </button>
                    <button class="btn btn-outline-danger btn-sm type-btn" data-type="dislike" title="Show Dislikes">
                        <i class="mdi mdi-thumb-down-outline"></i>
                    </button>
                    <button class="btn btn-outline-primary btn-sm type-btn" data-type="favourite" title="Show Favourites">
                        <i class="mdi mdi-star-outline"></i>
                    </button>

                    <a href="{{ route('knowledge-base-child.index') }}" class="btn btn-outline-secondary btn-sm"
                        title="Go Back">
                        <i class="mdi mdi-arrow-left-bold"></i>
                    </a>
                </div>

                {{-- Users Table --}}
                <div class="table-responsive">
                    <table class="table" id="plan" summary="Data">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Type</th>
                                <th>Name</th>

                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript">
        $(function() {
            var videoId = "{{ $video_id }}";
            var table;

            function loadTable(type = 'all') {
                var url = "{{ route('user-like.index', ':id') }}".replace(':id', videoId);

                if (table) {
                    table.destroy();
                }

                table = $('#plan').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: url,
                        type: 'GET',
                        data: {
                            type: type
                        }
                    },
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
                            data: 'name',
                            name: 'name'
                        },

                    ]
                });
            }

            // Load table initially with all users
            loadTable();

            // Handle button clicks
            $('.type-btn').click(function() {
                var type = $(this).data('type');
                loadTable(type);

                // Highlight active button
                $('.type-btn').removeClass('active');
                $(this).addClass('active');
            });
        });
    </script>
@endsection
