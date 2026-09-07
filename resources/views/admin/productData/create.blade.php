@extends('layout.headerFooter')
@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="row">
                <div class="col-xl-12">
                    <div class="card card-default">
                        <div class="card-header">
                            <h2>Add Product Data</h2>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('product-data.store') }}" enctype="multipart/form-data" method="post">
                                @csrf
                                   <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="exampleFormControlName">Name</label>
                                        <input type="text" class="form-control" name="name"
                                            id="exampleFormControlName" placeholder="Enter Title"
                                            value="{{old('title')}}">
                                        @if ($errors->has('name'))
                                            <div class="text-danger small mt-1">
                                                {{ $errors->first('name') }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label for="exampleFormControlFile1">Category</label>
                                            <select name="category_id" class="form-control" id="category_id">
                                                <option value="" disabled {{ old('category_id') ? '' : 'selected' }}>Select Category</option>
                                                @foreach($categoryData as $category)
                                                    <option value="{{ $category->id }}" {{ old('category_id') == $category ? 'selected' : '' }}>{{ $category->name }}</option>
                                                @endforeach
                                            </select>
                                            @if ($errors->has('category_id'))
                                            <div class="text-danger small mt-1">
                                                {{ $errors->first('category_id') }}
                                            </div>
                                            @endif
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="form-group">
                                        <label for="exampleFormControlFile1">Type</label>
                                       <select name="type" class="form-control" id="optionSelect">
                                        <option value="" disabled {{ old('type') ? '' : 'selected' }}>Select Type</option>
                                        <option value="brand" {{ old('brand') == 'brand' ? 'selected' : '' }}>Brand</option>
                                        <!-- <option value="about_us_banner" {{ old('banner_type') == 'about_us_banner' ? 'selected' : '' }}>About us</option> -->
                                        <option value="company" {{ old('company') == 'company' ? 'selected' : '' }}>Company</option>
                                         <option value="model" {{ old('model') == 'model' ? 'selected' : '' }}>Model</option>
                                         <option value="sub_model" {{ old('sub_model') == 'sub_model' ? 'selected' : '' }}>Sub Model</option>
                                        </select>
                                        @if ($errors->has('type'))
                                        <div class="text-danger small mt-1">
                                            {{ $errors->first('type') }}
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                         
                                <div class="form-footer mt-6">
                                    <button type="submit" class="btn btn-primary btn-pill">Submit</button>
                                    <a href="{{ route('product-data.index') }}"><button type="button" class="btn btn-light btn-pill">Cancel</button></a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script src="https://cdn.ckeditor.com/ckeditor5/23.0.0/classic/ckeditor.js"></script>
<script>

    ClassicEditor
    .create( document.querySelector( '#description' ) )
        .catch( error => {
        console.error( error );
    });

    $(document).ready(function(){
        $('.first-level').addClass('in')
    })
</script>
@endsection
