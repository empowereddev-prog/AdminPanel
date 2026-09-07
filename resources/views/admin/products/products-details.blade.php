@extends('layout.headerFooter')

@section('styles')
@endsection

@section('content')
<div class="content-wrapper">
    <div class="content">
        <div class="row">
            <div class="col-xl-12">
                <div class="card card-default">
                    <div class="card-header">
                        <h2 class="card-title">Product Details</h2>
                        <button class="btn btn-primary btn-pill" onclick="window.history.back()">Back</button>
                    </div>
                    <div class="card-body">
                        <div class="row">  
                            <div class="col-md-6">
                                <p><strong>Product Name :</strong> {{ $productDetaiLdata->name }}</p>
                                <p><strong>Product Company :</strong> {{ $productDetaiLdata->company }}</p>
                                <p><strong>Product Brand :</strong> {{ $productDetaiLdata->brand }}</p>
                                <p><strong>Product Category :</strong> {{ $productDetaiLdata->category }}</p>
                                <p><strong>Product Model :</strong> {{ $productDetaiLdata->model }}</p>
                            </div>
                            <div class="col-md-6">
                               <p><strong>Description :</strong> {{ $productDetaiLdata->description }}</p>

                                <p><strong>Overview :</strong> {{ $productDetaiLdata->overview }}</p>
                                <p><strong>Specification :</strong> {!! $productDetaiLdata->specification !!}</p>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            @foreach($allImagesOfProduct as $image)
                            <div class="col-md-4 mb-3">
                                <div class="card">
                                    <img src="{{ asset('uploads/upload_product_images/' . $image->image_name) }}" alt="{{ $image->original_image_name }}" class="card-img-top" style="max-height: 200px; object-fit: cover;">
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <div class="image-upload-delete-btn mt-3" style="display: none;">
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete the selected images?')">Delete Selected</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
