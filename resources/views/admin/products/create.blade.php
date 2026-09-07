@extends('layout.headerFooter')
@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="row">
                <div class="col-xl-12">
                    <div class="card card-default">
                        <div class="card-header">
                            <h2>Add Product</h2>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('products.store') }}" enctype="multipart/form-data" method="post">
                                @csrf
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="exampleFormControlName">Name</label>
                                        <input type="text" class="form-control" name="name"
                                            id="exampleFormControlName" placeholder="Enter Title"
                                            value="{{old('name')}}" maxlength="50">
                                        @if ($errors->has('name'))
                                            <div class="text-danger small mt-1">
                                                {{ $errors->first('name') }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                    <div class="col-md-12">
                                        <div class="form-group"> 
                                            <label for="answer">Description</label>
                                            <textarea name="description" value="" id="description"  class="form-control" rows="2" placeholder="Type your description Here" >{{old('description')}}</textarea>
                                            @if ($errors->has('description'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('description') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="specification">Specification</label>
                                        <textarea name="specification" id="specification" class="form-control" rows="2" placeholder="Type your specification here">{{ old('specification') }}</textarea>
                                        @if ($errors->has('specification'))
                                        <div class="text-danger small mt-1">
                                            {{ $errors->first('specification') }}
                                        </div>
                                        @endif
                                    </div>
                                </div>


                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="overview">Overview</label>
                                        <textarea name="overview" id="overview" class="form-control" rows="2" placeholder="Type your overview here">{{ old('overview') }}</textarea>
                                        @if ($errors->has('overview'))
                                        <div class="text-danger small mt-1">
                                            {{ $errors->first('overview') }}
                                        </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label for="exampleFormControlFile1">Category</label>
                                               <select name="category_id" class="form-control" id="category">
                                                    <option value="" disabled {{ old('category_id') ? '' : 'selected' }}>Select Category</option>
                                                    @foreach($p_category as $category)
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
                                            <label for="exampleFormControlFile1">Company</label>
                                               <select name="company_id" class="form-control" id="company_id">
                                                    <option value="" disabled {{ old('company_id') ? '' : 'selected' }}>Select Company</option>
                                                    @foreach($p_company as $company)
                                                        <option value="{{ $company->id }}" {{ old('company_id') == $company ? 'selected' : '' }}>{{ $company->name }}</option>
                                                    @endforeach
                                                </select>
                                                @if ($errors->has('company_id'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('company_id') }}
                                                </div>
                                                @endif
                                        </div>
                                    </div>

                                    
                                </div>

                                <div class="row">

                                    <div class="col-6">
                                        <div class="form-group">
                                            <label for="exampleFormControlFile1">Brand</label>
                                               <select name="brand_id" class="form-control" id="category">
                                                    <option value="" disabled {{ old('brand_id') ? '' : 'selected' }}>Select Brand</option>
                                                    @foreach($p_brand as $brand)
                                                        <option value="{{ $brand->id }}" {{ old('brand_id') == $brand ? 'selected' : '' }}>{{ $brand->name }}</option>
                                                    @endforeach
                                                </select>
                                                @if ($errors->has('brand_id'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('brand_id') }}
                                                </div>
                                                @endif
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label for="exampleFormControlFile1">Model</label>
                                               <select name="model_id" class="form-control" id="model_id">
                                                    <option value="" disabled {{ old('model_id') ? '' : 'selected' }}>Select Model</option>
                                                    @foreach($p_model as $model)
                                                        <option value="{{ $model->id }}" {{ old('model_id') == $model ? 'selected' : '' }}>{{ $model->name }}</option>
                                                    @endforeach
                                                </select>
                                                @if ($errors->has('model_id'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('model_id') }}
                                                </div>
                                                @endif
                                        </div>
                                    </div>

                                    
                                </div>
                            
                                <div class="form-group">
                                        <label for="file">Upload Document(Optional)</label><br>
                                        <input type="file" name="product_document" id="file" accept=".pdf,.doc,.docx,.xls,.xlsx">
                                        @if ($errors->has('product_document'))
                                            <div class="text-danger small mt-1">
                                                {{ $errors->first('product_document') }}
                                            </div>
                                        @endif
                                    </div>
                               
                                <div class="form-footer mt-6">
                                    <button type="submit" class="btn btn-primary btn-pill">Submit</button>
                                    <a href="{{ route('products.index') }}"><button type="button" class="btn btn-light btn-pill">Cancel</button></a>
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
    .create( document.querySelector( '#specification' ) )
        .catch( error => {
        console.error( error );
    });

    $(document).ready(function(){
        $('.first-level').addClass('in')
    })

    
      ClassicEditor
    .create( document.querySelector('#description' ))
        .catch( error => {
        console.error( error );
    });

    $(document).ready(function(){
        $('.first-level').addClass('in')
    })
</script>

 <script>
//     $(document).ready(function() {
//     $('#category').change(function() {
//         var category_id = $(this).val();
//         var  action = "{{ url('admin/get-product') }}";
//         var csrfToken = $('meta[name="csrf-token"]').attr('content');

//         $.ajax({
//             url: action,
//             type: "POST",
//             dataType: "json",
//             data: {
//                 category_id: category_id,
//                 _token: csrfToken,
//             },
//             success: function(response){
//                 console.log(response);
//                 var len = 0;
//                 if(response['data'] != null){
//                     len = response['data'].length;
//                     console.log(len);
//                 }

//                 if(len > 0){
//                     // Read data and create <option >
//                     $("#product_id").empty();
//                     for(var i=0; i<len; i++){
//                         var id = response['data'][i].id;
//                         var code = response['data'][i].name;
//                         var option = "<option value='"+ id +"'>"+ code +"</option>"; 
//                         $("#product_id").append(option); 
//                     }
//                 }
//             }
//         })
//     })
// });
 </script>

@endsection
