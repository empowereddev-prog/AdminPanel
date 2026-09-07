
@extends('layout.headerFooter')
@section('content')
    <div class="content-wrapper">
    <div class="content">
            <div class="title_left">
                <h2>Edit Email Template</h2>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <ul class="admin-breadcrumb">
                        <li>
                            <a href="{{ route('email-template.index') }}">Email Template</a>
                        </li>
                        <li class="active">
                            <a href="#">Edit Email Template</a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="row">
                <div class="col-xl-12">
                    <div class="card card-default">
                    <div class="card-header py-0">
                            <div class="title_left mb-2">
                              <h2>{{convertInCamelCase($var_name ? $var_name : '')}}</h2>
                            </div>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('email-template.update',$var_name) }}" enctype="multipart/form-data" method="post">
                                @csrf
                                @method('PUT')
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <!-- <div class="row">
                                            <div class="col-12">
                                                <div class="form-group">
                                                    <label for="exampleFormControlName">Variable Name</label>
                                                    <input type="text" class="form-control" name="variable_name"
                                                id="exampleFormControlName" placeholder="Enter Title" value="{{convertInCamelCase($var_name ? $var_name : '')}}" readonly>
                                                </div>
                                            </div>
                                        </div> -->

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
                                                    <label for="exampleFormControlName">Subject</label>
                                                    <input type="text" class="form-control" name="subject_english"
                                                        id="exampleFormControlName" placeholder="Enter Title" value="{{$data->get('english')->subject ? $data->get('english')->subject : ''}}">
                                                    @if ($errors->has('subject_english'))
                                                        <div class="text-danger small mt-1">
                                                            {{ $errors->first('subject_english') }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="answer">description</label>
                                                    <textarea name="description_english" value="{{$data->get('english')->description}}" class="content_data" placeholder="Type your content Here">{{@$data->get('english')->description}}</textarea>
                                                    @if ($errors->has('description_english'))
                                                        <div class="text-danger small mt-1">
                                                            {{ $errors->first('description_english') }}
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
                                                    <label for="exampleFormControlName">Subject</label>
                                                    <input type="text" class="form-control" name="subject_chinese"
                                                        id="exampleFormControlName" placeholder="Enter Title" value="{{$data->get('chinese') ? $data->get('chinese')->subject : ''}}"  maxlength="50">
                                                    @if ($errors->has('subject_chinese'))
                                                        <div class="text-danger small mt-1">
                                                            {{ $errors->first('subject_chinese') }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-12">
                                                <div class="form-group">
                                                    <label for="answer">description</label>
                                                    <textarea name="description_chinese" value="{{$data->get('chinese') ? $data->get('chinese')->description: ''}}" class="content_data" placeholder="Type your content Here">{{@$data->get('chinese') ? @$data->get('chinese')->description : ''}}</textarea>
                                                    @if ($errors->has('description_chinese'))
                                                        <div class="text-danger small mt-1">
                                                            {{ $errors->first('description_chinese') }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div> --}}


                                <div class="form-footer">
                                    <button type="submit" class="btn btn-primary btn-pill mr-2">Update</button>
                                    <a href="{{ route('email-template.index') }}"><button type="button"
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
 <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
<script>

        $(document).ready(function(){
            $('.first-level').addClass('in');

            $('.content_data').each(function () {
                ClassicEditor.create(this)
                    .catch(error => {
                        console.error(error);
                    });
            });
                })
</script>
@endsection
