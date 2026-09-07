@extends('layout.headerFooter')
@section('content')
<div class="content-wrapper">
    <div class="content">
        <div class="title_left">
            <h2>Notification Templates</h2>
        </div>
        <div class="card card-default">
            <div class="table-responsive">
                <table class="table" id="user" summary="Data">
                    <thead>
                        <tr>
                            <th>Variable Name</th>
                            <th>Subject</th>
                            @if(!empty($pre) && $pre->is_modify == 'yes')
                                <th>Action(s)</th>
                            @endif
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    $('#user').DataTable({
        ajax: "{{ route('notification-template.index') }}",
        paging: true,
        columns: [
            {data: 'variable_name', name: 'variable_name'},
            {data: 'subject', name: 'subject', orderable: false},
            @if(!empty($pre) && $pre->is_modify == 'yes')
            {data: 'action', name: 'action', orderable: false, searchable: false},
            @endif
        ]
    });
});
</script>
@endsection
