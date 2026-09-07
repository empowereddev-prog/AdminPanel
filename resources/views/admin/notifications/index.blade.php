@extends('layout.headerFooter')
@section('content')
<div class="content-wrapper">
    <div class="content">
        <div class="title_left">
            <h2>Notifications</h2>
        </div>

        <div class="card card-default faq">
            <div class="card-head mb-2">
                <a href="{{ route('notifications.create') }}" class="btn btn-pill btn-primary">Create Notification</a>
            </div>

            <div class="table-responsive">
                <table class="table" id="notificationTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <!-- <th>User</th> -->
                            <th>Title</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Sent At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Include DataTables and SweetAlert if not already loaded -->
<script type="text/javascript">
$(function() {
    $('#notificationTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("notifications.index") }}',
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            // { data: 'user_name', name: 'user.name' },
            { data: 'title', name: 'title' },
            { data: 'message', name: 'message', render: function(data) {
                return data.length > 50 ? data.substring(0, 50) + '...' : data;
            }},
            { data: 'is_sent', name: 'is_sent', render: function(data) {
                return data ? 'Sent' : 'Pending';
            }},
            { data: 'date_time', name: 'date_time' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });

    $('body').on('click', '.deleteNotification', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        const csrfToken = $('meta[name="csrf-token"]').attr('content');

        Swal.fire({
            title: 'Are you sure?',
            html: 'You want to delete this notification!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: 'DELETE',
                    url: '{{ url("admin/notifications") }}/' + id,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    success: function(response) {
                        $('#notificationTable').DataTable().ajax.reload();
                        Swal.fire('Deleted!', 'Notification deleted.', 'success');
                    },
                    error: function() {
                        $('#notificationTable').DataTable().ajax.reload();
                        Swal.fire('Failed!', 'Notification could not be deleted.', 'error');
                    }
                });
            }
        });
    });
});
</script>
@endsection
