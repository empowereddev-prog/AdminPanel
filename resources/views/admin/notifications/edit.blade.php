@extends('layout.headerFooter')
@section('content')
<!-- <div class="container">
    <h2 class="my-4">Create Notification</h2>

    <form action="{{ route('notifications.store') }}" method="POST">
        @csrf
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="title">Title:</label>
                <input name="title" id="title" class="form-control" placeholder="Enter notification title" required>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-12">
                <label for="message">Message:</label>
                <textarea name="message" id="message" class="form-control" rows="5" placeholder="Enter notification message" required></textarea>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-12">
                <label for="user_id">Select Users:</label>
                <select name="user_id" id="user_id" class="form-control" required>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="text-center">
            <button class="btn btn-success">Send Notification</button>
        </div>
    </form>
</div> -->

<div class="content-wrapper">
    <div class="content">
        <div class="title_left">
            <h2>Edit Notification</h2>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <ul class="admin-breadcrumb">
                    <li><a href="{{ route('notifications.index') }}">Notifications</a></li>
                    <li class="active"><a href="#">Edit</a></li>
                </ul>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-12">
                <div class="card card-default">
                    <div class="card-body">
                        <form action="{{ route('notifications.update', $notification->id) }}" method="POST">
                            @csrf
                            @method('PUT') 
                            <div class="card mb-4">
                                <div class="card-header">
                                    <!-- <h5>Basic Information</h5> -->
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label>User Type</label><br>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="user_type" id="user_type_child" value="child" checked>
                                                <label class="form-check-label" for="user_type_child">Child</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="user_type" id="user_type_parent" value="parent">
                                                <label class="form-check-label" for="user_type_parent">Parent</label>
                                            </div>
                                        </div>
                                        
                                    </div>
                                    <!-- Select User -->
                                    <div class="row mb-3">
                                        <div class="col-md-12">
                                            <label>Select Users</label>
                                            <select name="user_ids[]" id="user_ids" class="form-control" multiple>
                                                @foreach($users as $user)
                                                    <option value="{{ $user->id }}" {{ in_array($user->id, $selectedUserIds) ? 'selected' : '' }}>
                                                        {{ $user->name }}
                                                    </option>
                                                @endforeach
                                            </select>  
                                            @error('user_ids')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <!-- Title -->
                                    <div class="row mb-3">
                                        <div class="col-md-12">
                                            <label for="title">Title</label>
                                            <input name="title" id="title" class="form-control" value="{{$notification->title ? $notification->title : old('title')}}">
                                            @error('title')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- Message -->
                                    <div class="row mb-3">
                                        <div class="col-md-12">
                                            <label for="message">Message</label>
                                            <textarea name="message" id="message" class="form-control" rows="4">{{$notification->message ? $notification->message : old('message')}}</textarea>
                                            @error('message')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                  
                                </div>
                            </div>

                            <!-- Submit -->
                            <div class="form-footer text-end">
                                <button type="submit" class="btn btn-primary btn-pill">Send</button>
                                <a href="{{ route('notifications.index') }}">
                                <button type="button" class="btn btn-primary btn-pill mr-2">Cancel</button>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function () {
    const allUsers = @json($users);
    const initialSelectedUserIds = @json($selectedUserIds); // only used on first load
    let selectedUserIds = [...initialSelectedUserIds]; // clone array for later updates
    const selectedUserType = "{{ $selectedUserType[0] }}";
    const $userSelect = $('#user_ids');

    // Initialize Select2
    $userSelect.select2({
        placeholder: "Select Users",
        allowClear: true
    });
    setTimeout(function () {
            const select2Container = $('.select2-selection--multiple');

            // Style the container to limit height and allow scroll
            select2Container.css({
                'max-height': '100px',
                'overflow-y': 'auto',
                'white-space': 'normal'
            });

            // Truncate long tag names
            $('.select2-selection__choice').css({
                'max-width': '100%',
                'overflow': 'hidden',
                'text-overflow': 'ellipsis',
                'white-space': 'nowrap'
            });

            // Make sure Select2 fits the form width
            $('.select2-container').css('width', '100%');
        }, 100); // Timeout ensures Select2 has rendered

    function populateUsers(type) {
        $userSelect.empty();

        allUsers.forEach(user => {
            if (user.user_type === type) {
                const isSelected = selectedUserIds.includes(user.id);
                const newOption = new Option(user.name, user.id, isSelected, isSelected);
                $userSelect.append(newOption);
            }
        });

        $userSelect.trigger('change');
    }

    // Pre-check the appropriate radio button
    $(`input[name="user_type"][value="${selectedUserType}"]`).prop('checked', true);

    // Populate on page load
    populateUsers(selectedUserType);

    // Re-populate users when user type changes
    $('input[name="user_type"]').on('change', function () {
        const type = $(this).val();
        // Reset selectedUserIds to nothing
        selectedUserIds = [];
        populateUsers(type);
    });
});
</script>
@endsection
