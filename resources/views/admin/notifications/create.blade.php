@extends('layout.headerFooter')

@section('content')
<div class="content-wrapper">
    <div class="content">
        <div class="title_left">
            <h2>Create Notification</h2>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <ul class="admin-breadcrumb">
                    <li><a href="{{ route('notifications.index') }}">Notifications</a></li>
                    <li class="active"><a href="#">Create</a></li>
                </ul>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-12">
                <div class="card card-default">
                    <div class="card-body">
                        <form action="{{ route('notifications.store') }}" method="POST">
                            @csrf

                            {{-- User Type Selection --}}
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

                                    <!-- {{-- Select Users --}} -->
                                    <div class="row mb-3">
                                        <div class="col-md-12">
                                            <label>Select Users</label>
                                            <select name="user_ids[]" id="user_ids" class="form-control select2" multiple></select>
                                            @error('user_ids')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- {{-- Title --}} -->
                                    <div class="row mb-3">
                                        <div class="col-md-12">
                                            <label for="title">Title</label>
                                            <input name="title" id="title" class="form-control" value="{{old('title')}}">
                                            @error('title')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- {{-- Message --}} -->
                                    <div class="row mb-3">
                                        <div class="col-md-12">
                                            <label for="message">Message</label>
                                            <textarea name="message" id="message" class="form-control" rows="4">{{old('message')}}</textarea>
                                            @error('message')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                  
                                </div>
                            </div>

                            <!-- {{-- Submit --}} -->
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

        // Init Select2
        const $userSelect = $('#user_ids').select2({
            placeholder: "Select Users"
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

        function filterUsersByType(type) {
            $userSelect.empty();
            allUsers.forEach(user => {
                if (user.user_type === type) {
                    const newOption = new Option(user.name, user.id, false, false);
                    $userSelect.append(newOption);
                }
            });
            $userSelect.trigger('change');
        }

        // Initial filter
        filterUsersByType('child');

        // Handle radio change
        $('input[name="user_type"]').on('change', function () {
            const selectedType = $(this).val();
            filterUsersByType(selectedType);
        });
    });
</script>
@endsection
