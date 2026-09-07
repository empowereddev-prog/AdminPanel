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
                <h2>Add Banner</h2>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <ul class="admin-breadcrumb">
                        <li>
                            <a href="{{ route('banners.index') }}">Hero Banner</a>
                        </li>
                        <li class="active">
                            <a href="#">Add Banner</a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="row">
                <div class="col-xl-12">
                    <div class="card card-default">
                        <!-- <div class="card-header">
                            <h2>Add Banner</h2>
                        </div> -->
                        <div class="card-body">
                            <form id="bannerForm" class="form-wrapper" action="{{ route('banners.store') }}" enctype="multipart/form-data" method="post">
                                @csrf
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="exampleFormControlFile1">Banner Type</label>
                                       <select name="banner_type" class="form-control" id="optionSelect">
                                       <option value="" disabled {{ old('banner_type') ? '' : 'selected' }}>Select Banner Type</option>
                                        @foreach (['home_banner', 'about_banner', 'purchase_banner'] as $type)
                                            @if (!in_array($type, $existingBannerTypes))
                                                <option value="{{ $type }}" {{ old('banner_type') == $type ? 'selected' : '' }}>
                                                    {{ ucfirst(str_replace('_', ' ', $type)) }}
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                        @if ($errors->has('banner_type'))
                                        <div class="text-danger small mt-1">
                                            {{ $errors->first('banner_type') }}
                                        </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="exampleFormControlName">Banner Title</label>
                                        <input type="text" class="form-control" name="banner_title" id="banner_title" 
                                            id="exampleFormControlName" placeholder="Enter Title"
                                            value="{{ old('banner_title') }}" maxlength="100">
                                         @if ($errors->has('banner_title'))
                                            <div class="text-danger small mt-1">
                                                 {{ $errors->first('banner_title') }}
                                            </div>
                                         @endif
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-group" id="imageInput">
                                        <label for="exampleFormControlFile1">Image</label>
                                        <input type="file" class="form-control-file" name="image"
                                            id="exampleFormControlFile1" value="{{old('image')}}" >
                                            <canvas id="canvas" style="display:none;"></canvas>
                                        @if ($errors->has('image'))
                                            <div class="text-danger small mt-1">
                                                {{ $errors->first('image') }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <input type="hidden" name="check" id="check">
  
                                <div class="col-12">
                                    <div class="form-group hidden" id="videoInput">
                                        <label for="exampleFormControlFile1">Video</label>
                                        <input type="file" class="form-control-file" name="video"
                                            id="exampleFormControlFile1" value="{{old('video')}}">
                                        @if ($errors->has('video'))
                                            <div class="text-danger small mt-1">
                                                {{ $errors->first('video') }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
 
                                <div class="form-footer mt-6">
                                    <button type="submit" class="btn btn-primary btn-pill">Submit</button>
                                    <a href="{{ route('banners.index') }}"><button type="button" class="btn btn-primary btn-pill">Cancel</button></a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
            // document.getElementById('optionSelect').value = selectedValue;
        }
    };
    document.getElementById('optionSelect').addEventListener('change', toggleInputs);
    window.onload = toggleInputs;

    // function validateForm(event) {

    //  }
    // document.getElementById('bannerForm').addEventListener('submit', validateForm);


// validate using js
 // function validateForm(event) {
    //     var bannerTitle = document.getElementById('banner_title').value;
    //     var titleError = document.getElementById('titleError');
    //     // alert('titleError');
    //     if (bannerTitle.trim() === '') {
    //         titleError.style.display = 'inline';
    //         event.preventDefault(); // Prevent form submission
    //     } else {
    //         titleError.style.display = 'none';
    //     }
    // }
    // document.getElementById('bannerForm').addEventListener('submit', validateForm);
//     document.getElementById('exampleFormControlFile1').addEventListener('change', function(event) {
//     const file = event.target.files[0];

//     if (file) {
//         const reader = new FileReader();
//         reader.onload = function(e) {
//             const img = new Image();
//             img.onload = function() {
//                 // Set the desired dimensions
//                 const desiredWidth = 550;
//                 const desiredHeight = 1440;

//                 // Create a canvas element
//                 const canvas = document.getElementById('canvas');
//                 const ctx = canvas.getContext('2d');
//               conole.log(ctx);
//                 // Set the canvas dimensions
//                 canvas.width = desiredWidth;
//                 canvas.height = desiredHeight;

//                 // Draw the image on the canvas
//                 ctx.drawImage(img, 0, 0, desiredWidth, desiredHeight);

//                 // Convert the canvas to a data URL
//                 const dataURL = canvas.toDataURL('image/jpeg');

//                 // You can now upload `dataURL` to the server
//                 console.log(dataURL);
//             };
//             img.src = e.target.result;
//         };
//         reader.readAsDataURL(file);
//     }
// });
// </script>




@endsection



