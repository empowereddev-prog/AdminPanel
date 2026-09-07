@extends('layout.headerFooter')
@section('content')
<div class="content-wrapper">
    <div class="content">
        <h2>Manage Battery Setting</h2>
        <div class="card card-default mt-4">
            <div class="card-body">
                <form action="{{ route('battery-setting.update') }}" method="POST">
                    @csrf

                    <div class="row">
                        @foreach($batterySettings as $setting)
                            <div class="col-md-6 mt-2">
                                <div class="form-group">
                                    <label>{{ ucwords(str_replace('_', ' ', $setting->option_key)) }}</label>
                                    <input type="hidden" name="option_key[]" value="{{ $setting->option_key }}">
                                    <input type="text" name="option_value[]" class="form-control"
                                        value="{{ $setting->option_value }}">
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="form-footer mt-4">
                        <button type="submit" class="btn btn-primary btn-pill">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
