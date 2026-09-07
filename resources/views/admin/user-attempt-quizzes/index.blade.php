@extends('layout.headerFooter')

@section('content')
    <div class="content-wrapper">
        <div class="content">

            <div class="title_left mb-3">
                <h2>User Attempt Quiz</h2>
            </div>

            {{-- 🔽 FILTER SECTION --}}


            <div class="card card-default faq card-clild-mood">
                <div class="card-head mb-2">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label><strong>Filter Type</strong></label>
                            <select id="parent_type" class="form-control">
                                <option value="">All</option>
                                <option value="normal">Individual Account</option>
                                <option value="school">School Account</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-bordered" id="user" summary="Data">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>User Name</th>
                                @if (!empty($pre) && $pre->is_modify == 'yes')
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

    {{-- ================== DATATABLE SCRIPT ================== --}}
    <script type="text/javascript">
        $(function() {

            let canModify = "{{ $pre->is_modify }}";

            let columns = [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    searchable: false,
                    orderable: true
                },
                {
                    data: 'user_name',
                    name: 'user.name', // 🔥 server-side search
                    searchable: true,
                    orderable: true
                }
            ];

            if (canModify === 'yes') {
                columns.push({
                    data: 'action',
                    name: 'action',
                    searchable: false,
                    orderable: false
                });
            }

            let table = $('#user').DataTable({
                processing: true,
                serverSide: true,
                searching: true, // ✅ Search box ON
                lengthChange: true, // ✅ Show entries dropdown
                ajax: {
                    url: "{{ route('users-attempt-quizzes') }}",
                    type: 'GET',
                    data: function(d) {
                        d.parent_type = $('#parent_type').val(); // 🔥 filter param
                    }
                },
                columns: columns
            });

            // 🔁 Reload table when filter changes
            $('#parent_type').on('change', function() {
                table.ajax.reload();
            });

        });
    </script>
@endsection
