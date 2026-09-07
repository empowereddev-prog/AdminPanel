@extends('layout.headerFooter')

@push('styles')
    <style>
        .access-permission-container {
            background-color: #f8f9fa;
            padding: 1.5rem;
            border-radius: .5rem;
        }

        .permission-card {
            border-radius: .5rem;
            background-color: #fff;
            border: 1px solid #dee2e6;
        }

        .permission-card .card-header {
            background-color: #f1f3f5;
        }

        .permission-card .card-body {
            padding: 1.25rem;
        }

        .permission-item,
        .child-item {
            display: block;
        }

        .permission-item:last-child,
        .child-item:last-child {
            margin-bottom: 0;
        }

        .card-header .permission-item label {
            font-weight: 600;
            color: #212529;
            font-size: 14px;
        }

        .permission-item.parent-option,
        .child-item.child-name {
            padding-left: 1rem;
        }

        .child-item.child-option {
            padding-left: 2rem;
        }

        .custom-checkbox {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .custom-checkbox label {
            margin-bottom: 0;
            font-weight: 500;
            color: #555;
            font-size: 12px;
        }

        .card-width {
            width: 85%;
        }

        .inner-card-empowered {
            width: 91%;
            margin: 0 auto;
        }
    </style>
@endpush

@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="title_left">
                <h2>Add Role</h2>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <ul class="admin-breadcrumb">
                        <li><a href="{{ route('assign-permission.index') }}">Role Management</a></li>
                        <li class="active"><a href="#">Add Details</a></li>
                    </ul>
                </div>
            </div>
            <div class="row">
                <div class="col-xl-12">
                    <form action="{{ route('assign-permission.store') }}" method="post" enctype="multipart/form-data">
                        @csrf
                        <div class="card card-default mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Role Details</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="name">Name</label>
                                            <input type="text" id="name" name="name" class="form-control"
                                                placeholder="Enter Your Name" value="{{ old('name') }}">
                                            @error('name')
                                                <div class="text-danger mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="email">Email</label>
                                            <input type="text" id="email" name="email" class="form-control"
                                                placeholder="Enter your email" value="{{ old('email') }}">
                                            @error('email')
                                                <div class="text-danger mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="form-group col-sm-6">
                                        <label for="phone_no">Phone</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend" style="width: 40%;">
                                                <select name="code" id="code" class="form-control select2 w-100">
                                                    @foreach ($countrycode as $code)
                                                        <option value="{{ $code->country_code }}"
                                                            {{ old('code', request('code', '+65')) == $code->country_code ? 'selected' : '' }}>
                                                            {{ $code->country }} ({{ $code->country_code }})</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <input type="text" id="phone_no" name="phone_no" class="form-control"
                                                placeholder="Enter Your Number" value="{{ old('phone_no') }}">
                                        </div>
                                        @error('phone_no')
                                            <div class="text-danger mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="card p-3 mb-4">
                            <div class="inner-card-empowered">
                                <h4 class="mb-3 me-2">Access Permission</h4>
                                @error('permissions')
                                    <div class="text-danger mb-2">{{ $message }}</div>
                                @enderror
                                @foreach ($adminMenu as $menu)
                                    <div class="form-group row align-items-center mb-3 justify-content-between card-width">
                                        <label
                                            class="col-form-label font-weight-bold">{{ $menu->menu_name ?? 'Unnamed Menu' }}</label>
                                        <div class=" d-flex gap-3">
                                            <div class="form-check mr-3">
                                                <input type="checkbox" class="form-check-input"
                                                    name="permissions[{{ $menu->id }}][is_view]" value="yes">
                                                <label class="form-check-label">View</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input"
                                                    name="permissions[{{ $menu->id }}][is_modify]" value="yes">
                                                <label class="form-check-label">Modify</label>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="form-footer mt-4 text-right">
                                <button type="submit" class="btn btn-primary btn-pill mr-2">Submit</button>
                                <a href="{{ route('assign-permission.index') }}" class="btn btn-light"
                                    style="border-radius: 50px;">Cancel</a>
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
            $('#code').select2();
             // Modify checkbox logic
        $(document).on('change', 'input[name*="[is_modify]"]', function() {
            let formGroup = $(this).closest('.form-group');
            let viewCheckbox = formGroup.find('input[name*="[is_view]"]');

            if ($(this).is(':checked')) {
                viewCheckbox.prop('checked', true).prop('readonly', true); // view tick + readonly
            } else {
                viewCheckbox.prop('checked', false).prop('readonly', false); // view untick + remove readonly
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
