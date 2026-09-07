@extends('layout.headerFooter')
@section('content')
<div class="content-wrapper">
    <div class="content">
        <div class="title_left">
            <h2>User Subscription</h2>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <ul class="admin-breadcrumb">
                    <li>
                        <a href="{{ route('user.index') }}">User Management</a>
                    </li>
                    <li class="active">
                        <a href="#">User Subscription</a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="card mb-4">
                <!-- <div class="card-header"> -->
                    <!-- <h5>Basic Information</h5> -->
                <!-- </div> -->
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name">User Name</label>
                            <input type="text" class="form-control" name="name" id="name" value="{{ $user_details->name }}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label for="email">User Email</label>
                            <input type="text" class="form-control" name="email" id="email" value="{{ $user_details->email }}" disabled>
                        </div>  
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="exampleFormControlFile1">Phone no.</label>
                                <input type="text" class="form-control" name="phone_no"
                                    id="exampleFormControlName" placeholder="Enter phone number"
                                    value="{{ old('phone_no', ($user_details->country_code ?? '') . ($user_details->phone_no ? ' ' . $user_details->phone_no : '')) }}"
                                    readonly>
                                @if ($errors->has('phone_no'))
                                    <div class="text-danger small mt-1">
                                        {{ $errors->first('phone_no') }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                
        
            <div class="card mb-4 mt-4">
                <div class="card-header text-center">
                    <h5>Subscription Details</h5>
                </div>
                <div class="card-body">
                @if(!empty($data))
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="subscription_type_id">Subscription Type id </label>
                            <input type="text" class="form-control" name="subscription_type_id" id="subscription_type_id" placeholder="Enter Session Title" value="{{ $data->subscription_type_id }}" disabled>
                        </div>

                        <div class="col-md-3">
                            <label for="subscription_type">Subscription Type </label>
                            <input type="text" class="form-control" name="subscription_type" id="subscription_type" value="{{ $data->subscription_type }}" disabled>
                        </div>

                        <div class="col-md-3">
                            <label for="price">Price </label>
                            <input type="text" class="form-control" name="price" id="price" value="{{ $data->price }}" disabled>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="start_date">Start Date </label>
                            <input type="text" class="form-control" name="start_date" id="start_date" placeholder="Enter Session Title" value="{{ $data->start_date }}" disabled>
                        </div>

                        <div class="col-md-6">
                            <label for="end_date">End Date </label>
                            <input type="text" class="form-control" name="end_date" id="end_date" value="{{ $data->end_date }}" disabled>
                        </div>
                    </div>
                    @else
                   
                        <div class="" style="text-align: center;">
                            {{-- <label for="price" class="text-center"> </label> --}}
                            <p >No subscription details found</p>
                        </div>
                     
                @endif
                </div>
            </div>

            <div class="form-footer mt-6">
                <a href="{{ route('user.index') }}">
                    <button type="button" class="btn btn-primary btn-pill mr-2">Back</button>
                </a>
            </div>
        </div>
</div>
@endsection
