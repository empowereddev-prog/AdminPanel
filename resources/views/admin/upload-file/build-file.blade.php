@extends('layout.headerFooter')
 
@section('content')
<!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="card-header">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>{{ $title }}</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <!-- <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">{{ $title }}</li> -->
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="card">
      <div class="row">
        <div class="col-md-8">
         @if(session('message'))
              <div class="alert alert-danger">{{session('message')}}</div>
          @endif
           <form  method="POST" enctype="multipart/form-data"  action="{{ url('admin/upload-file/update')}}" autocomplete="off">
        @csrf
        @method('PUT')
            <div class="card-header">
             <div class="form-group row">
                <label for="image" class="col-sm-2 col-form-label">Image:</label>
                <div class="col-sm-9">
                <input type="file" class="form-control"  name="image" value="{{ old('image')}}" id="image">
                @error('image')
                <p style="color: red">{{ $message }}</p>
                @enderror
                <br/> 
                </div>
              </div>
              <div class="row">
              <div class="col-12"> 
                <input type="submit" value="Update" class="btn btn-success">
                <a class="btn btn-default" href="{{ url('build-upload')}}" >Cancel<a>
              </div>
            </div>
            
            <!-- /.card-body -->
         
          <!-- /.card -->
        </div>
        </form>
      </div>
   </div>
   </div>
    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->
@endsection

@push('inlinescript')
<script src="https://stackpath.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js"></script>
  <script>
      $(document).ready(function() {
             $('#summernote').summernote({
              fontSizes: ['8', '9', '10', '11', '12', '14', '18'],
              fontNames: [ 'Serif', 'Sans', 'Arial', 'Arial Black', 'Courier', 'Courier New', 'Comic Sans MS', 'Helvetica', 'Impact', 'Lucida Grande', 'Sacramento','Open Sans'],
               height: 200,
             callbacks: {
               onImageUpload: function(files, editor, welEditable) {
                 sendFile(files[0], editor, welEditable);
               }
             },
             popover: {
               image: [],
               link: [],
               air: []
             },
             toolbar: [

             ['font', ['bold', 'italic', 'underline', 'clear']],

             ['color', ['color']],
             ['fontsize', ['fontsize']],
             ['fontname', ['fontname']],
             ['para', ['ul', 'ol', 'paragraph']],
             ['insert', ['link', 'picture', 'hr']],
             ['view', ['codeview']],
              ['table', ['table']],

             ]

                   });
              function sendFile(file, editor, welEditable) {
         data = new FormData();
         data.append("file", file);

         $.ajax({
         headers: {
                       'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                   },
         data: data,
         type: "POST",
         url: "{{url('uploads-summernote')}}",
         cache: false,
         contentType: false,
         processData: false,
         success: function(url) {
           var image = $('<img>').attr('src',url.url);
           $('#summernote').summernote('insertNode', image[0]);
         }
         });
         }
         });
  </script>
 
@endpush
