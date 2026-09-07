@extends('layout.headerFooter')

@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="title_left pl-4">
                <h2>Payment History Detail</h2>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <ul class="admin-breadcrumb pl-4">
                        <li>
                            <a href="{{ route('payment.history_index') }}">Payment History</a>
                        </li>
                        <li class="active">
                            <a href="#" active>Invoice {{ $data->order_id }}</a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <div class="card card-default">
                        <div class="card-body">
                            <form>
                                <fieldset class="form-group">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">Name</label>
                                                <p class="form-control" id="name">{{ $data->user->name ?? 'N/A' }}</p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="email">Email</label>
                                                <p class="form-control" id="email">{{ ucfirst($data->user->email ?? 'N/A') }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="subscription_type">Subscription Type</label>
                                                <p class="form-control" id="subscription_type">{{ ucfirst($data->subscription_type) }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="price">Amount </label>
                                                <p class="form-control" id="price">
                                                    {{ old('price', $data->price) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="purchased_on">Purchased On</label>
                                                <p class="form-control" id="purchased_on">
                                                    {{ $data->start_date }}</p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="status">Payment Status</label>
                                                <p class="form-control" id="status">
                                                    {{ ucfirst($data->status) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>
                            </form>
                        </div>
                        <div class="form-footer mt-6">
                        <!-- <button type="submit" class="btn btn-primary btn-pill mr-2">Submit</button> -->
                        <a href="{{ route('payment.history_index') }}">
                            <button type="button" class="btn btn-primary btn-pill mr-2">Back</button>
                        </a>
                    </div>
                    </div>
                   
                </div>
            </div>
        </div>
    </div>
@endsection
