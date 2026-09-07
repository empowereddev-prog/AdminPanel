@extends('layout.headerFooter')
@section('content')
  <style>
        .hidden {
            display: none;
        }
    </style>
    <div class="content-wrapper">
        <div class="content">
        <div class="title_left">
                <h2>Edit Testimonial</h2>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <ul class="admin-breadcrumb">
                        <li>
                            <a href="{{ route('testimonial.index') }}">Testimonials</a>
                        </li>
                        <li class="active">
                            <a href="#">Edit Testimonial</a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="row">
                <div class="col-xl-12">
                    <div class="card card-default">
                        <!-- <div class="card-header">
                            <h2>Add Reviews</h2>
                        </div> -->
                        <div class="card-body">
                            <form id="bannerForm" action="{{ route('testimonial.update',$data->id) }}" enctype="multipart/form-data" method="post">
                                @csrf
                                @method('PUT')
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="exampleFormControlName">Name</label>
                                        <input type="text" class="form-control" name="name" id="name" 
                                            id="exampleFormControlName" placeholder="Enter Your Name"
                                            value="{{old('name', $data->name)}}" maxlength="100">
                                         @if ($errors->has('name'))
                                            <div class="text-danger small mt-1">
                                                 {{ $errors->first('') }}
                                            </div>
                                         @endif
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="exampleFormControlName">About</label>
                                        <input type="text" class="form-control" name="about" id="about" 
                                            id="exampleFormControlName" placeholder="Enter About Yourself"
                                            value="{{old('about', $data->about) }}" maxlength="100">
                                         @if ($errors->has('name'))
                                            <div class="text-danger small mt-1">
                                                 {{ $errors->first('name') }}
                                            </div>
                                         @endif
                                    </div>
                                </div>

                                <div class="col-12"  id="imageInput">
                                    <div class="form-group">
                                    <label for="exampleFormControlFile1">Image</label>
                                            <input type="file" class="form-control-file" name="image"
                                                id="exampleFormControlFile1" value="{{ $data->image }}">
                                            @if ($errors->has('image'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('image') }}
                                                </div>
                                            @endif
                                    </div>
                                    <br/>
                                            <img src="{{url('uploads/testmonial/'.$data->image)}}" height="60" width="60" alt="image">
                                </div>
                                <!-- <input type="hidden" name="check" id="check"> -->
                                 <br/>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="exampleFormControlName">Reviews (In English )</label>
                                        <textarea name="review" value="{{old('review',$data->review_comments)}}" class="review" placeholder="Type your content Here">{{@$data->review_comments}}</textarea>

                                         @if ($errors->has('review'))
                                            <div class="text-danger small mt-1">
                                                 {{ $errors->first('review') }}
                                            </div>
                                         @endif
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="exampleFormControlName">Reviews (In Simplified Chinese)</label>
                                        <textarea name="zh_CN_review" value="{{$data->zh_CN_review}}" class="review" placeholder="Type your content Here">{{@$data->zh_CN_review}}</textarea>

                                         @if ($errors->has('zh_CN_review'))
                                            <div class="text-danger small mt-1">
                                                 {{ $errors->first('zh_CN_review') }}
                                            </div>
                                         @endif
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="exampleFormControlName">Reviews  (In Traditional Chinese)</label>
                                        <textarea name="zh_TW_review" value="{{$data->zh_TW_review}}" class="review" placeholder="Type your content Here">{{@$data->zh_TW_review}}</textarea>

                                         @if ($errors->has('zh_TW_review'))
                                            <div class="text-danger small mt-1">
                                                 {{ $errors->first('zh_TW_review') }}
                                            </div>
                                         @endif
                                    </div>
                                </div>
 
                                <div class="form-footer mt-6">
                                    <button type="submit" class="btn btn-primary btn-pill">Update</button>
                                    <a href="{{ route('testimonial.index') }}"><button type="button" class="btn btn-primary btn-pill">Cancel</button></a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
 <script src="https://cdn.ckeditor.com/ckeditor5/23.0.0/classic/ckeditor.js"></script>
 <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
    <script type="text/javascript">
    //   function toggleInputs() {
    //     var imageInput = document.getElementById('imageInput');
    //     var videoInput = document.getElementById('videoInput');     
    //     var selectedValue = document.getElementById('optionSelect').value;
        
    //     if (selectedValue === 'banner_video') {
    //         videoInput.classList.remove('hidden');
    //         imageInput.classList.add('hidden');
    //         document.getElementById('check').value = 1;
    //         // document.getElementById('optionSelect').value = selectedValue;
    //     } else {
    //         videoInput.classList.add('hidden');
    //         imageInput.classList.remove('hidden');
    //          document.getElementById('check').value = 0;
    //         // document.getElementById('optionSelect').value = selectedValue;
    //     }
    // };
    // document.getElementById('optionSelect').addEventListener('change', toggleInputs);
    // window.onload = toggleInputs;
    
    
    $(document).ready(function () {
        $('.first-level').addClass('in');

        $('.review').each(function () {
            ClassicEditor.create(this)
                .catch(error => {
                    console.error(error);
                });
        });
    });
</script>
</script>




@endsection



