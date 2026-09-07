@extends('layout.headerFooter')
@section('content')
    <style type="text/css">
        /* .ui-datepicker-year
                                      {
                                        display: none;
                                      }
                                      .ui-datepicker-next {
                                        display: none;
                                      }
                                      .ui-datepicker-prev {
                                        display: none;
                                      } */
        .ui-widget-header {
            border: 1px solid #CC0000 !important;
            background: #CC0000 url(images/ui-bg_gloss-wave_35_f6a828_500x100.png) 50% 50% repeat-x !important;
        }

        .ui-state-highlight,
        .ui-widget-content .ui-state-highlight,
        .ui-widget-header .ui-state-highlight {
            border: 1px solid #CC0000 !important;
            background: #f5a0ac url(images/ui-bg_highlight-soft_75_ffe45c_1x100.png) 50% top repeat-x !important;
        }

        .ui-state-active,
        .ui-widget-content .ui-state-active,
        .ui-widget-header .ui-state-active {
            border: 1px solid #CC0000 !important;
            background: #ffffff url(images/ui-bg_glass_65_ffffff_1x400.png) 50% 50% repeat-x !important;
            color: #CC0000 !important;
        }

        .ui-state-hover,
        .ui-widget-content .ui-state-hover,
        .ui-widget-header .ui-state-hover,
        .ui-state-focus,
        .ui-widget-content .ui-state-focus,
        .ui-widget-header .ui-state-focus {
            border: 1px solid #ff531a !important;
            background: #ffd9cc url(images/ui-bg_glass_100_fdf5ce_1x400.png) 50% 50% repeat-x !important;
            color: #cc3300 !important;
        }
    </style>
    <div class="container mt-4">
        <div class="card">
            <div class="card-title">
                <h4 class="mb-0">{{ $key }} Report</h4>
            </div>
            <div class="card-body">
                <form method="post" action="{{ url('/reportcreate/' . $key) }}" autocomplete="off">
                    @csrf
                    @if ($key == 'User')
                        <div class="col-12">
                            <div class="form-group">
                                <label for="user_id">User Type</label>
                                @foreach ($user_type as $type)
                                    <input type="text" name="user_id" id="user_id" class="form-control"
                                        value="{{ $type->name }}" readonly>
                                    <input type="hidden" name="user_id" id="user_id"
                                        value="{{ old('user_id', $type->id) }}">
                                @endforeach
                                @if ($errors->has('user_id'))
                                    <div class="text-danger mt-1">
                                        {{ $errors->first('user_id') }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                    @if ($key == 'Video Content')
                        {{-- Category --}}
                        <div class="form-group">
                            <label for="category_id">Category</label>
                            <select name="category_id" id="category_id" class="form-control">
                                <option value="">Select Category</option>
                                @foreach ($category as $type)
                                    <option value="{{ $type->id }}"
                                        {{ old('category_id') == $type->id ? 'selected' : '' }}>
                                        {{ $type->category_name }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($errors->has('category_id'))
                                <div class="text-danger mt-1">
                                    {{ $errors->first('category_id') }}
                                </div>
                            @endif
                        </div>

                        {{-- Is Featured --}}
                        <div class="form-group mt-2">
                            <label for="is_featured">Is Featured</label>
                            <select name="is_featured" id="is_featured" class="form-control">
                                <option value="">Select Is Featured</option>
                                <option value="yes" {{ old('is_featured') == 'yes' ? 'selected' : '' }}>Yes</option>
                                <option value="no" {{ old('is_featured') == 'no' ? 'selected' : '' }}>No</option>
                            </select>
                            @if ($errors->has('is_featured'))
                                <div class="text-danger mt-1">
                                    {{ $errors->first('is_featured') }}
                                </div>
                            @endif
                        </div>

                        {{-- User Type --}}
                        <div class="form-group mt-2">
                            <label for="user_type">User Type</label>
                            <select name="user_type" id="user_type" class="form-control">
                                <option value="">Select User Type</option>
                                <option value="child" {{ old('user_type') == 'child' ? 'selected' : '' }}>Child</option>
                                <option value="parent" {{ old('user_type') == 'parent' ? 'selected' : '' }}>Parent</option>
                            </select>
                            @if ($errors->has('user_type'))
                                <div class="text-danger mt-1">
                                    {{ $errors->first('user_type') }}
                                </div>
                            @endif
                        </div>
                    @endif

                    @if ($key == 'School')
                        <div class="form-group">
                            <label for="text">Download School Excel File</label>
                        </div>
                    @endif
                    @if ($key == 'Knowledge Session')
                        <div class="row mt-2">
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="category_id">Category</label>
                                    <select name="category_id" id="category_id" class="form-control">
                                        <option value="">Select Category</option>
                                        @foreach ($category as $type)
                                            <option value="{{ $type->id }}"
                                                {{ old('category_id') == $type->id ? 'selected' : '' }}>
                                                {{ $type->category_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if ($errors->has('category_id'))
                                        <div class="text-danger mt-1">{{ $errors->first('category_id') }}</div>
                                    @endif
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="session_type">Session Type</label>
                                    <select name="session_type" id="session_type" class="form-control">
                                        <option value="">Select Session Type</option>
                                        <option value="online" {{ old('session_type') == 'online' ? 'selected' : '' }}>
                                            Online</option>
                                        <option value="offline" {{ old('session_type') == 'offline' ? 'selected' : '' }}>
                                            Offline</option>
                                        <option value="hybrid" {{ old('session_type') == 'hybrid' ? 'selected' : '' }}>
                                            Hybrid</option>
                                    </select>
                                    @if ($errors->has('session_type'))
                                        <div class="text-danger mt-1">{{ $errors->first('session_type') }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="is_featured">Is featured</label>
                                    <select name="is_featured" id="is_featured" class="form-control">
                                        <option value="">Select featured</option>
                                        <option value="yes" {{ old('is_featured') == 'yes' ? 'selected' : '' }}>Yes
                                        </option>
                                        <option value="no" {{ old('is_featured') == 'no' ? 'selected' : '' }}>No
                                        </option>
                                    </select>
                                    @if ($errors->has('is_featured'))
                                        <div class="text-danger mt-1">{{ $errors->first('is_featured') }}</div>
                                    @endif
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="user_type">User Type</label>
                                    <select name="user_type" id="user_type" class="form-control">
                                        <option value="">Select User Type</option>
                                        <option value="child" {{ old('user_type') == 'child' ? 'selected' : '' }}>Child
                                        </option>
                                        <option value="parent" {{ old('user_type') == 'parent' ? 'selected' : '' }}>Parent
                                        </option>
                                    </select>
                                    @if ($errors->has('user_type'))
                                        <div class="text-danger mt-1">{{ $errors->first('user_type') }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="form-footer mt-3">
                        <button type="submit" class="btn btn-primary mb-2 btn-pill changes">Export Report</button>
                        @if ($key == 'Knowledge Session' || $key == 'Video Content')
                            <button type="button" class="btn btn-primary mb-2 btn-pill changes"
                                onclick="location.reload();">Reset</button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        $(function() {
            var currentYear = new Date().getFullYear();
            $('input[name="from_order_date"]').datepicker({
                singleDatePicker: true,
                autoUpdateInput: false,
                dateFormat: 'dd/mm/yy',
            });
            $('input[name="from_order_date"]').on('apply.datepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('DD/MM/YYYY'));
            });
            $('input[name="to_order_date"]').datepicker({
                singleDatePicker: true,
                autoUpdateInput: false,
                dateFormat: 'dd/mm/yy',
            });
            $('input[name="to_order_date"]').on('apply.datepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('DD/MM/YYYY'));
            });



            $('input[name="from_dispatch_date"]').datepicker({
                autoUpdateInput: false,
                singleDatePicker: true,
                showDropdowns: true,
                startDate: new Date(),
                minYear: 1901,
                dateFormat: 'dd/mm/yy'
            });
            $('input[name="from_dispatch_date"]').on('apply.datepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('DD/MM/YYYY'));
            });
            $('input[name="to_dispatch_date"]').datepicker({
                autoUpdateInput: false,
                singleDatePicker: true,
                showDropdowns: true,
                startDate: new Date(),
                minYear: 1901,
                dateFormat: 'dd/mm/yy'
            });
            $('input[name="to_dispatch_date"]').on('apply.datepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('DD/MM/YYYY'));
            });




            $('input[name="from_delivery_date"]').datepicker({
                singleDatePicker: true,
                autoUpdateInput: false,
                dateFormat: 'dd/mm/yy',
            });
            $('input[name="from_delivery_date"]').on('apply.datepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('DD/MM/YYYY'));
            });
            $('input[name="to_delivery_date"]').datepicker({
                singleDatePicker: true,
                autoUpdateInput: false,
                dateFormat: 'dd/mm/yy',
            });
            $('input[name="to_delivery_date"]').on('apply.datepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('DD/MM/YYYY'));
            });
            if ($('#order_status').val() == 'dispatch') {
                $('#dispatch_date').show();
            } else {
                $('#dispatch_date').hide();
            }
        });
        $(document).ready(function() {
            $('#order_status').change(function() {
                if ($(this).val() == 'dispatch') {
                    $('#dispatch-search').show();
                } else {
                    $('#dispatch-search').hide();
                }
            })
        });
    </script>
@endsection
