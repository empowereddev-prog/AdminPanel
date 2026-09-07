
@extends('layout.headerFooter')
@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="row">
                <div class="col-xl-12">
                    <div class="card card-default">
                        <div class="card-header">
                            <h2>Edit Product Category</h2>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('product-data.update',$data->id) }}" enctype="multipart/form-data" method="post">
                                @csrf
                                @method('PUT')
                                <div class="row">
                                    <div class="col-12">
                                            <div class="form-group">
                                                <label for="exampleFormControlName">Name</label>
                                                <input type="text" class="form-control" name="name"
                                                    id="exampleFormControlName" placeholder="Enter Title"
                                                    value="{{$data->name}}">
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

                                                    @foreach($categories as $category)
                                                        <option value="{{ $category->id }}" {{ $category->id == old('category_id', $data->category_id) ? 'selected' : '' }} > {{ $category->name }} </option>
                                                    @endforeach
                                                </select>
                                                @if ($errors->has('category'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('category') }}
                                                </div>
                                                @endif
                                        </div>
                                    </div>


                                    <div class="col-6">
                                        <div class="form-group">
                                            <label for="exampleFormControlFile1">Type</label>
                                            <select name="type" class="form-control" id="optionSelect">
                                                <option value="" selected disabled>Select Banner Type</option>
                                                <!-- <option value="home_banner">Home banner</option>
                                                <option value="about_us_banner">About us</option> -->
    											<option value="brand" {{ $data->type == 'brand' ? 'selected' : '' }}> Brand</option>
    											<option value="company" {{ $data->type == 'company' ? 'selected' : '' }}>Company</option>
    											<option value="model" {{ $data->type == 'model' ? 'selected' : '' }}>Model</option>
    											<option value="sub_model" {{ $data->type == 'sub_model' ? 'selected' : '' }}>Sub Model</option>
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
} );


$(document).ready(function(){

    $('.first-level').addClass('in')
})
</script>
<script>
  
</script>
@endsection
