@extends('layout.headerFooter')

@section('content')
<style>
    /* Status Filter Tabs */
    .filter-pills {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
    }

    .filter-btn {
        border: 1px solid #e2e8f0;
        background-color: #ffffff;
        color: #4a5568;
        padding: 8px 18px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease-in-out;
    }

    .filter-btn.active {
        background-color: #fff0f5;
        border-color: #e83e8c;
        color: #e83e8c;
        font-weight: 600;
    }

    /* Status Badges */
    .status-badge {
        padding: 4px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
    }

    .status-new {
        background-color: #eef2ff;
        color: #4f46e5;
    }

    .status-in_review {
        background-color: #fef3c7;
        color: #d97706;
    }

    .status-completed {
        background-color: #d1fae5;
        color: #059669;
    }

    .status-rejected {
        background-color: #fee2e2;
        color: #dc2626;
    }

    /* Request Info Box in Modal */
    .request-info-box {
        background-color: #fafafa;
        border: 1px solid #f0f0f0;
        border-radius: 12px;
        padding: 18px;
        margin-bottom: 20px;
        max-height: 250px;
        overflow-y: auto;
        word-break: break-word;
    }

    /* Modal Scrolling & Max Height Fix */
    .modal-body {
        max-height: calc(80vh - 120px);
        overflow-y: auto;
    }

    .btn-gradient {
        background: linear-gradient(90deg, #ff416c, #ff4b2b);
        color: #ffffff !important;
        border: none;
        padding: 10px 24px;
        border-radius: 20px;
        font-weight: 600;
        box-shadow: 0 4px 12px rgba(255, 65, 108, 0.3);
    }

    .btn-gradient:hover {
        opacity: 0.9;
    }
</style>

<div class="content-wrapper">
    <div class="content">
        <div class="title_left">
            <h2>Video Requests</h2>
        </div>

        <!-- Filter Tabs -->
        <div class="filter-pills">
            <button type="button" class="filter-btn active" data-status="all">All Requests</button>
            <button type="button" class="filter-btn" data-status="new">New</button>
            <button type="button" class="filter-btn" data-status="in_review">In Review</button>
            <button type="button" class="filter-btn" data-status="completed">Completed</button>
            <button type="button" class="filter-btn" data-status="rejected">Rejected</button>
        </div>

        <div class="card card-default">
            <div class="table-responsive p-3">
                <table class="table" id="videoRequestsTable" summary="Data">
                    <thead>
                        <tr>
                            <th scope="col" width="60px">S.No</th>
                            <th scope="col">Message</th>
                            <th scope="col" width="120px">Status</th>
                            <th scope="col" width="180px">Requested On</th>
                            @if (!empty($pre) && $pre->is_modify == 'yes')
                            <th scope="col" width="100px">Action</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Video Request Details Modal -->
<div class="modal fade" id="videoRequestModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0 px-4 pt-4">
                <h5 class="modal-title font-weight-bold" style="color: #2c3e50;">Video Request Details</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="updateVideoRequestForm">
                @csrf
                @method('PUT')
                <input type="hidden" id="modal_request_id" name="id">

                <div class="modal-body px-4">
                    <div class="request-info-box">
                        <h6 style="color: #e83e8c;" class="font-weight-bold mb-3">Request Information</h6>
                        <p class="mb-2"><strong>Requested On :</strong> <span id="modal_date" class="text-muted"></span></p>
                        <p class="mb-2"><strong>Requested By :</strong> <span id="modal_user" class="text-muted"></span></p>
                        <p class="mb-2"><strong>Status :</strong> <span id="modal_status_badge"></span></p>
                        <p class="mb-2"><strong>Message :</strong> <span id="modal_message" class="text-muted"></span></p>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold" for="modal_status_select">Update Status</label>
                        <select name="status" id="modal_status_select" class="form-control" style="border-radius: 8px;">
                            <option value="new">New</option>
                            <option value="in_review">In Review</option>
                            <option value="completed">Completed</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="font-weight-bold" for="modal_admin_notes">Admin Notes (Optional)</label>
                            <small class="text-muted"><span id="admin_notes_count">0</span> / 1500 characters</small>
                        </div>
                        <textarea name="admin_notes" id="modal_admin_notes" class="form-control" rows="3" style="border-radius: 8px;" placeholder="Add notes about this request (5 to 1500 characters)..." maxlength="1500"></textarea>
                        <span class="text-danger small" id="admin_notes_error" style="display: none;"></span>
                    </div>
                </div>

                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="submit" class="btn btn-gradient">SAVE CHANGES</button>
                    <button type="button" class="btn btn-light px-4" style="border-radius: 20px;" data-bs-dismiss="modal">CANCEL</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function() {
        var check = "{{ $pre->is_modify }}";
        var currentStatusFilter = 'all';
        var baseUrl = "{{ route('video-requests.index') }}";

        var columns = [{
                data: 'DT_RowIndex',
                name: 'DT_RowIndex',
                searchable: false,
                orderable: true
            },
            {
                data: 'user_message',
                name: 'user_message',
                searchable: true,
                orderable: false,
                render: function(data, type, row) {
                    if (!data) return '';
                    if (type === 'display') {
                        var maxLength = 60;
                        if (data.length > maxLength) {
                            return '<span title="' + $('<div>').text(data).html() + '">' + $('<div>').text(data.substr(0, maxLength)).html() + '...</span>';
                        }
                    }
                    return data;
                }
            },
            {
                data: 'status',
                name: 'status',
                orderable: false,
                searchable: false
            },
            {
                data: 'created_at',
                name: 'created_at',
                searchable: false,
                orderable: true
            }
        ];

        if (check == 'yes') {
            columns.push({
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false
            });
        }

        var table = $('#videoRequestsTable').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: baseUrl,
                data: function(d) {
                    d.status_filter = currentStatusFilter;
                }
            },
            paging: true,
            columns: columns,
            initComplete: function(settings, json) {
                var currentUrl = window.location.href;
                var savedPage = localStorage.getItem('dataTablePage_' + encodeURIComponent(currentUrl));
                var pageNumber = parseInt(savedPage);
                var totalPages = table.page.info().pages;

                if (!isNaN(pageNumber) && pageNumber >= 0 && pageNumber < totalPages) {
                    setTimeout(function() {
                        table.page(pageNumber).draw(false);
                    }, 100);
                }
            }
        });

        table.on('page.dt', function() {
            var info = table.page.info();
            var currentUrl = window.location.href;
            localStorage.setItem('dataTablePage_' + encodeURIComponent(currentUrl), info.page);
        });

        // Status Filter Pills Click Handler
        $('.filter-btn').on('click', function() {
            $('.filter-btn').removeClass('active');
            $(this).addClass('active');
            currentStatusFilter = $(this).data('status');
            table.ajax.reload();
        });

        // Real-time character counter & validation highlight
        $('#modal_admin_notes').on('input', function() {
            var length = $(this).val().length;
            $('#admin_notes_count').text(length);

            if (length > 0 && length < 5) {
                $('#admin_notes_error').text('Admin notes must be at least 5 characters long.').show();
                $(this).addClass('is-invalid');
            } else if (length > 1500) {
                $('#admin_notes_error').text('Admin notes cannot exceed 1500 characters.').show();
                $(this).addClass('is-invalid');
            } else {
                $('#admin_notes_error').hide();
                $(this).removeClass('is-invalid');
            }
        });

        // Open View/Edit Details Modal
        $('body').on('click', '.view-request', function() {
            var id = $(this).data('id');

            $.get(baseUrl + "/" + id, function(response) {
                if (response.status) {
                    var data = response.data;
                    $('#modal_request_id').val(data.id);
                    $('#modal_message').text(data.user_message);
                    $('#modal_date').text(data.requested_on);

                    var userText = 'Anonymous';
                    if (data.username && data.email) {
                        userText = data.username + ' (' + data.email + ')';
                    } else if (data.username) {
                        userText = data.username;
                    } else if (data.email) {
                        userText = data.email;
                    }
                    $('#modal_user').text(userText);

                    var statusLabels = {
                        'new': 'New',
                        'in_review': 'In Review',
                        'completed': 'Completed',
                        'rejected': 'Rejected'
                    };
                    var statusClasses = {
                        'new': 'status-new',
                        'in_review': 'status-in_review',
                        'completed': 'status-completed',
                        'rejected': 'status-rejected'
                    };

                    $('#modal_status_badge').html('<span class="status-badge ' + statusClasses[data.status] + '">' + statusLabels[data.status] + '</span>');

                    $('#modal_status_select').val(data.status);

                    // Reset validation error and set values
                    var notes = data.admin_notes || '';
                    $('#modal_admin_notes').val(notes).removeClass('is-invalid');
                    $('#admin_notes_count').text(notes.length);
                    $('#admin_notes_error').hide();

                    new bootstrap.Modal(document.getElementById('videoRequestModal')).show();
                }
            }).fail(function() {
                Swal.fire('Error', 'Unable to fetch request details.', 'error');
            });
        });

        // Submit Updated Status & Admin Notes via AJAX
        $('#updateVideoRequestForm').on('submit', function(e) {
            e.preventDefault();

            var adminNotes = $('#modal_admin_notes').val().trim();

            // Perform client-side min/max validation before submitting
            if (adminNotes.length > 0 && adminNotes.length < 5) {
                $('#admin_notes_error').text('Admin notes must be at least 5 characters long.').show();
                $('#modal_admin_notes').addClass('is-invalid').focus();
                return false;
            }

            if (adminNotes.length > 1500) {
                $('#admin_notes_error').text('Admin notes cannot exceed 1500 characters.').show();
                $('#modal_admin_notes').addClass('is-invalid').focus();
                return false;
            }

            var id = $('#modal_request_id').val();
            var csrfToken = $('meta[name="csrf-token"]').attr('content');

            $.ajax({
                type: "POST",
                url: baseUrl + "/" + id,
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                data: $(this).serialize(),
                success: function(response) {
                    if (response.status) {
                        bootstrap.Modal.getInstance(document.getElementById('videoRequestModal')).hide();
                        table.ajax.reload(null, false);
                        Swal.fire('', response.message, 'success');
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422 && xhr.responseJSON.errors) {
                        var errors = xhr.responseJSON.errors;
                        if (errors.admin_notes) {
                            $('#admin_notes_error').text(errors.admin_notes[0]).show();
                            $('#modal_admin_notes').addClass('is-invalid');
                        }
                    } else {
                        Swal.fire('Error', 'Something went wrong while updating.', 'error');
                    }
                }
            });
        });

        // Delete Action Handler
        $("body").on('click', '.delete', function(e) {
            e.preventDefault();
            var id = $(this).data("id");
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
                        url: baseUrl + "/" + id,
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                        },
                        data: {
                            _method: 'DELETE'
                        },
                        success: function(data) {
                            table.ajax.reload();
                            Swal.fire('', 'Video Request Deleted Successfully.', 'success');
                        },
                        error: function(data) {
                            table.ajax.reload();
                        }
                    });
                }
            });
        });
    });
</script>
@endsection
