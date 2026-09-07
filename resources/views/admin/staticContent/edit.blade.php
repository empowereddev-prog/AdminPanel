@extends('layout.headerFooter')
@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="title_left">
                <h2>Edit Static Content</h2>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <ul class="admin-breadcrumb">
                        <li>
                            <a href="{{ route('static-content.index') }}">Static Content</a>
                        </li>
                        <li class="active">
                            <a href="#">Edit Static Content</a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <!-- <div class="title_left">
                            <h2>Edit Static Content</h2>
                        </div> -->
                </div>

                <div class="col-12">
                    <div class="card card-default">
                        <div class="card-header py-0">
                            <div class="title_left mb-2">
                                @if ($key === 'about-us')
                                    <h2>About Us</h2>
                                @elseif($key === 'terms-condition')
                                    <h2>Terms and Condition</h2>
                                @elseif($key === 'privacy-policy')
                                    <h2>Privacy Policy</h2>
                                @elseif($key === 'popup1')
                                    <h2>Privacy Copyright & Content Protectionolicy</h2>
                                @elseif($key === 'popup2')
                                    <h2>Educational Content Disclaimer</h2>
                                @endif
                            </div>
                        </div>

                        <div class="card-body">
                            <form action="{{ route('static-content.update', $key) }}" enctype="multipart/form-data"
                                method="post">
                                @csrf
                                @method('PUT')
                                <div class="card mb-4">
                                    <div class="card-body">
                                        {{-- <div class="row">
                                            <div class="col-12">
                                                <div class="form-group">
                                                    <label for="exampleFormControlName">Language</label>
                                                    <input type="text" class="form-control" name="key" value="English" readonly>
                                                </div>
                                            </div>
                                        </div> --}}

                                        <div class="row">
                                            <div class="col-12">
                                                <div class="form-group">
                                                    <label for="exampleFormControlName">Title</label>
                                                    <input type="text" class="form-control" name="title_english"
                                                        id="exampleFormControlName" placeholder="Enter Title"
                                                        value="{{ $data->get('english')->title }}" maxlength="50">
                                                    @if ($errors->has('title_english'))
                                                        <div class="text-danger small mt-1">
                                                            {{ $errors->first('title_english') }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="content_english">Content</label>
                                                    <textarea name="content_english" value="{{ $data->get('english')->content }}" class="content_data"
                                                        placeholder="Type your content Here">{{ @$data->get('english')->content }}</textarea>
                                                    @if ($errors->has('content_english'))
                                                        <div class="text-danger small mt-1">
                                                            {{ $errors->first('content_english') }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- <div class="card mb-4">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="form-group">
                                                    <label for="exampleFormControlName">Language</label>
                                                    <input type="text" class="form-control" name="key" value="Chinese" readonly>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-12">
                                                <div class="form-group">
                                                    <label for="exampleFormControlName">Title</label>
                                                    <input type="text" class="form-control" name="title_chinese"
                                                        id="exampleFormControlName" placeholder="Enter Title" value="{{ $data->get('chinese')->title ?? ''  }}"  maxlength="50">
                                                        @error('title_chinese')
                                    <div style="color: red">{{ $message }}</div>
                                    @enderror
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-12">
                                                <div class="form-group">
                                                    <label for="content_chinese">Content</label>
                                                    <textarea name="content_chinese" value="{{ $data->get('chinese')->content ?? '' }}" class="content_data" placeholder="Type your content Here">{{@$data->get('chinese')->content ?? ''}}</textarea>
                                                    <!-- @if ($errors->has('content'))
                                                    <div class="text-danger small mt-1">
                                                        {{ $errors->first('content') }}
                                                    </div>
                                                    @endif -->
                                                    @error('content_chinese')
                                                    <div style="color: red">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div> --}}


                                <div class="form-footer">
                                    <button type="submit" class="btn btn-primary btn-pill">Update</button>
                                    <a href="{{ route('static-content.index') }}"><button type="button"
                                            class="btn btn-primary btn-pill">Cancel</button></a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.ckeditor.com/ckeditor5/23.0.0/classic/ckeditor.js"></script>
    <script src="https://cdn.ckeditor.com/ckeditor5/23.0.0/classic/ckeditor.js"></script>

    <!-- <script src="https://cdn.ckeditor.com/ckfinder/ckfinder.js"></script> -->

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
    <script>
        $(document).ready(function() {
            $('.first-level').addClass('in');

            $('.content_data').each(function() {
                ClassicEditor
                    .create(this, {
                        ckfinder: {
                            uploadUrl: '{{ route('ckeditor.upload') . '?_token=' . csrf_token() }}',
                        },
                        toolbar: [
                            'heading', '|', 'bold', 'italic', 'link', 'imageUpload', 'blockQuote',
                            'numberedList', 'bulletedList', 'insertTable', 'mediaEmbed', 'undo',
                            'redo'
                        ],
                        image: {
                            toolbar: [
                                'imageTextAlternative', 'imageStyle:full', 'imageStyle:side'
                            ]
                        }
                    })
                    .catch(error => {
                        console.error(error);
                    });
            });
        });
    </script>
@endsection
