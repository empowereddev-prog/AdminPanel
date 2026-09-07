
@extends('layout.headerFooter')
@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="row">
                <div class="col-xl-12">
                    <div class="card card-default">
                        <div class="card-header">
                            <h2>Edit address</h2>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('addresses.update',$data->id) }}" enctype="multipart/form-data" method="post">
                                @csrf
                                @method('PUT')
                                <div class="col-12">
                                        <div class="form-group">
                                            <label for="exampleFormControlName">Title</label>
                                            <input type="text" class="form-control" name="title"
                                                id="exampleFormControlName" placeholder="Enter Title"
                                                value="{{$data->title}}" maxlength="100">
                                            @if ($errors->has('title'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('title') }}
                                                </div>
                                            @endif
                                    </div>
                                </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="answer">Description</label>
                                            <textarea name="description" value="{{$data->description}}" id="description" placeholder="Type your description Here">{{$data->description}}</textarea>
                                            @if ($errors->has('description'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('description') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                <div class="col-12">
                                    <div class="form-group">
                                            <label for="exampleFormControlName">Contact No.</label>
                                            <input type="text" class="form-control" name="contact_no"
                                                id="exampleFormControlName" placeholder="Enter contact no."
                                                value="{{$data->contact_no}}">
                                            @if ($errors->has('contact_no'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('contact_no') }}
                                                </div>
                                            @endif
                                    </div>
                                </div>
                                <div class="form-footer mt-6">
                                    <button type="submit" class="btn btn-primary btn-pill">Submit</button>
                                    <a href="{{ route('addresses.index') }}"><button type="button" class="btn btn-light btn-pill">Cancel</button></a>
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
@endsection
