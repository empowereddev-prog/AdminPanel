@extends('layout.headerFooter')

@push('styles')
    <style>
        .access-permission-container {
            background-color: #f8f9fa;
            padding: 1.5rem;
            border-radius: .5rem;
        }

        .form-check-label {
            margin-left: 5px;
        }

        .inner-empoered-card {
            width: 91%;
            margin: 0 auto;
        }

        .card-width {
            width: 85%;
        }
    </style>
@endpush

@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="title_left">
                <h2>Edit Role</h2>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <ul class="admin-breadcrumb">
                        <li><a href="{{ route('assign-permission.index') }}">Role Management</a></li>
                        <li class="active"><a href="#">Edit Details</a></li>
                    </ul>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-12">
                    <form action="{{ route('assign-permission.update', $user->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        {{-- User Info --}}
                        <div class="card card-default mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Role Details</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    {{-- Name --}}
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Name</label>
                                            <input type="text" name="name" class="form-control"
                                                value="{{ old('name', $user->name) }}">
                                            @error('name')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    {{-- Email --}}
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Email</label>
                                            <input type="text" name="email" class="form-control"
                                                value="{{ old('email', $user->email) }}" readonly>
                                            @error('email')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <!-- Phone Input -->
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label>Phone</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend" style="width: 40%;">
                                                    <select name="code" class="form-control select2">
                                                        @foreach ($countrycode as $code)
                                                            <option value="{{ $code->country_code }}"
                                                                {{ old('code', $user->country_code) == $code->country_code ? 'selected' : '' }}>
                                                                {{ $code->country }} ({{ $code->country_code }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <input type="text" name="phone_no" class="form-control"
                                                    value="{{ old('phone_no', $user->phone_no) }}">
                                            </div>
                                            @error('phone_no')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- Status Dropdown -->
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="status">Status</label>
                                            <select name="status" class="form-control" id="status">
                                                <option value="active"
                                                    {{ old('status', $user->status) == 'active' ? 'selected' : '' }}>
                                                    Active</option>
                                                <option value="inactive"
                                                    {{ old('status', $user->status) == 'inactive' ? 'selected' : '' }}>
                                                    Inactive</option>
                                            </select>
                                            @error('status')
                                                <div class="text-danger mt-1 small">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>



                            </div>
                        </div>

                        {{-- Permissions --}}
                        <div class="card p-3 mb-4">
                            <div class="inner-empoered-card">
                                <h5 class="mb-3">Access Permission</h5>
                                @error('permissions')
                                    <div class="text-danger mb-2">{{ $message }}</div>
                                @enderror
                                @foreach ($adminMenu as $menu)
                                    <div class="form-group row align-items-center mb-3 justify-content-between card-width">
                                        <label class=" col-form-label font-weight-bold">
                                            {{ $menu->menu_name ?? 'Unnamed Menu' }}
                                        </label>
                                        <div class=" d-flex gap-3">
                                            <div class="form-check mr-3">
                                                <input type="checkbox" class="form-check-input"
                                                    name="permissions[{{ $menu->id }}][is_view]" value="yes"
                                                    {{ isset($userPermissions[$menu->id]) && $userPermissions[$menu->id] === 'yes' ? 'checked' : '' }}>
                                                <label class="form-check-label">View</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input"
                                                    name="permissions[{{ $menu->id }}][is_modify]" value="yes"
                                                    {{ isset($userModifyPermissions[$menu->id]) && $userModifyPermissions[$menu->id] === 'yes' ? 'checked' : '' }}>
                                                <label class="form-check-label">Modify</label>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Buttons --}}
                            <div class="form-footer mt-4 text-right">
                                <button type="submit" class="btn btn-primary btn-pill mr-2">Update</button>
                                <a href="{{ route('assign-permission.index') }}" class="btn btn-light">Cancel</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $('.select2').select2();

            // Modify checkbox logic
            $(document).on('change', 'input[name*="[is_modify]"]', function() {
                let formGroup = $(this).closest('.form-group');
                let viewCheckbox = formGroup.find('input[name*="[is_view]"]');

                if ($(this).is(':checked')) {
                    viewCheckbox.prop('checked', true).prop('readonly', true); // view tick + readonly
                } else {
                    viewCheckbox.prop('checked', false).prop('readonly',
                    false); // view untick + remove readonly
                }
            });

            // View checkbox logic
            $(document).on('change', 'input[name*="[is_view]"]', function() {
                let formGroup = $(this).closest('.form-group');
                let modifyCheckbox = formGroup.find('input[name*="[is_modify]"]');

                if ($(this).is(':checked') && !modifyCheckbox.is(':checked')) {
                    // केवल view ही checked रहेगा
                    $(this).prop('checked', true).prop('readonly', false);
                } else if (!$(this).is(':checked') && modifyCheckbox.is(':checked')) {
                    // अगर modify checked है तो view को uncheck करने से रोको
                    $(this).prop('checked', true);
                }
            });
        });
    </script>
@endpush
