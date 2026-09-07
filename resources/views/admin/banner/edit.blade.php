@extends('layout.headerFooter')
@section('content')
<style>
        .hidden {
            display: none;
        }
       /* .error {
            color: red;
            display: none;
        }*/
    </style>
    <div class="content-wrapper">
       <div class="content">
            <div class="title_left">
                <h2>Edit Banner</h2>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <ul class="admin-breadcrumb">
                        <li>
                            <a href="{{ route('banners.index') }}">Hero Banner</a>
                        </li>
                        <li class="active">
                            <a href="#">Edit Banner</a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="row">
                <div class="col-xl-12">
                    <div class="card card-default">
                        <!-- <div class="card-header">
                            <h2>Edit Banner</h2>
                        </div> -->
                        <div class="card-body">
                            <form id="bannerForm" class="form-wrapper" action="{{ route('banners.update',$data->id) }}" enctype="multipart/form-data" method="post" >
                                @csrf
                                @method('PUT')
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="exampleFormControlFile1">Banner Type</label>
                                        <select name="banner_type" class="form-control" id="optionSelect" disabled>
                                        <option value="" disabled {{ old('banner_type') ? '' : 'selected' }}>Select Banner Type</option>
                                        <option value="home_banner" {{ $data->type == 'home_banner' ? 'selected' : '' }}>Home banner</option>
                                        <option value="about_banner" {{ $data->type == 'about_banner' ? 'selected' : '' }}>About Banner</option>
                                        <option value="purchase_banner" {{ $data->type == 'purchase_banner' ? 'selected' : '' }}>Purchase Banner</option>
                                        <!-- <option value="feature_banner" {{ $data->type == 'testimonial_banner' ? 'selected' : '' }}>Testimonial Banner</option> -->
                                          </select>
                                        @if ($errors->has('banner'))
                                        <div class="text-danger small mt-1">
                                            {{ $errors->first('banner') }}
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="exampleFormControlName">Banner Title</label>
                                        <input type="text" class="form-control" name="banner_title"
                                            id="exampleFormControlName" placeholder="Enter Title"
                                            value="{{ old('banner_title', $data->banner_title) }}" maxlength="100">
                                         @if ($errors->has('banner_title'))
                                            <div class="text-danger small mt-1">
                                                 {{ $errors->first('banner_title') }}
                                            </div>
                                         @endif
                                    </div>
                                </div>
                                   <div class="col-12" id="imageInput">
                                        <div class="form-group" >
                                            <label for="exampleFormControlFile1">Image</label>
                                            <input type="file" class="form-control-file" name="image"
                                                id="exampleFormControlFile1" value="{{ $data->banner_image }}">
                                            @if ($errors->has('image'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('image') }}
                                                </div>
                                            @endif
                                        
                                        </div><br>
                                            <img src="{{url('uploads/banner_img/'.$data->banner_image)}}" height="60" width="60" alt="image">
                                    </div>

                                    <div class="col-12">
                                        <div class="form-group hidden" id="videoInput">
                                            <label for="exampleFormControlFile1">Video</label>
                                            <input type="file" class="form-control-file" name="video"
                                                id="exampleFormControlFile1" value="{{ $data->banner_image }}">
                                            @if ($errors->has('video'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('video') }}
                                                </div>
                                            @endif
                                            @if($data->type == 'banner_video')

                                                <video playsinline="playsinline" autoplay="autoplay" muted="muted" loop="loop" width="160" height="120" class="">
                                                <source src="{{ url('uploads/banner_video/' . $data->banner_video) }}" type="video/mp4">Your browser does not support the video tag.
                                                </video>
                                            @endif
                                        </div>
                                    </div>
                                    <input type="hidden" name="check" id="check">
                                     <input type="hidden" name="getid" id="getid" value="{{ $data->id }}">
                                    
                                    <input type="hidden" name="types" value="edit">
                                <div class="form-footer mt-6">
                                    <button type="submit" class="btn btn-primary btn-pill">Update</button>
                                    <a href="{{ route('banners.index') }}"><button type="button" class="btn btn-primary btn-pill">Cancel</button></a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<!-- <script type="text/javascript">
    function toggleInputs() {
        var imageInput = document.getElementById('imageInput');
        var videoInput = document.getElementById('videoInput');
        var selectedValue = document.getElementById('optionSelect').value;
        
        if (selectedValue === 'banner_video') {
            videoInput.classList.remove('hidden');
            imageInput.classList.add('hidden');
            document.getElementById('check').value = 1;
        } else {
            videoInput.classList.add('hidden');
            imageInput.classList.remove('hidden');
            document.getElementById('check').value = 0;
        }
    }
    document.getElementById('optionSelect').addEventListener('change', toggleInputs);
    window.onload = toggleInputs;


</script> -->


<script type="text/javascript">
    function toggleInputs() {
        var imageInput = document.getElementById('imageInput');
        var videoInput = document.getElementById('videoInput');
        var selectedValue = document.getElementById('optionSelect').value;
        
        if (selectedValue === 'banner_video') {
            videoInput.classList.remove('hidden');
            imageInput.classList.add('hidden');
            document.getElementById('check').value = 1;
            // document.getElementById('optionSelect').value = selectedValue;
        } else {
            videoInput.classList.add('hidden');
            imageInput.classList.remove('hidden');
            document.getElementById('check').value = 0;
        }
    }
    document.getElementById('optionSelect').addEventListener('change', toggleInputs);
    window.onload = toggleInputs;



//     $(document).ready(function() {
//     $('#optionSelect').change(function() {
//         var banner_type = $(this).val();
//         var getid = document.getElementById('getid').value;
//         var  action = "{{ url('admin/update-type-banner') }}";
//         var csrfToken = $('meta[name="csrf-token"]').attr('content');

//         $.ajax({
//             url: action,
//             type: "POST",
//             dataType: "json",
//             data: {
//                 banner_type: banner_type,
//                 getid: getid,
//                 _token: csrfToken,
//             },
//         })
//     })
// });



</script>

@endsection
