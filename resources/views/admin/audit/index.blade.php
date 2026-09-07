@extends('layout.headerFooter')

@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="title_left">
                <h2>Audit Log</h2>
            </div>
            <div class="card card-default">
                 
                <div class="card-body" style=" overflow-y: auto;">
                    <table class="table" id="data" summary="Data" style="text-align: center;">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Model Type</th>
                                <th scope="col">Event Type</th>
                                <th scope="col">Old Values</th>
                                <th scope="col">New Values</th>
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
                    url: "{{ route('audit-log.index') }}",
                    type: 'GET'
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        searchable: false,
                        orderable: true
                    },
                    {
                        data: 'auditable_type',
                        name: 'auditable_type',
                        orderable: false,
                        searchable: true
                    },
                    {
                        data: 'event',
                        name: 'event',
                        orderable: false
                    },
                    {
                        data: 'old_values',
                        name: 'old_values',
                        orderable: false
                    },
                    {
                        data: 'new_values',
                        name: 'new_values',
                        orderable: false
                    },

                ],
            });
        });
    </script>
@endsection
