@extends('layout.headerFooter')
@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="title_left">
                <h2>Edit FAQ</h2>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <ul class="admin-breadcrumb">
                        <li>
                            <a href="{{ route('faq.index') }}">FAQs</a>
                        </li>
                        <li class="active">
                            <a href="#">Edit FAQ</a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="row">
                <div class="col-xl-12">
                    <div class="card card-default">
                        <!-- <div class="card-header">
                                <h2>Edit FAQ</h2>
                            </div> -->
                        <div class="card-body">
                            <form action="{{ route('faq.update', $data->id) }}" enctype="multipart/form-data" method="post">
                                @csrf
                                @method('PUT')
                                {{-- <div class="row">
                                    <div class="col-12">
                                    <div class="form-group">
                                    <label for="exampleFormControlName">Language</label>
                                    <input type="text" class="form-control" name="key" value="{{$data->language ? $data->language : ''}}" readonly>
                                     </div>
                                    </div>
                                  </div> --}}
                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label for="type">Faq Type</label>
                                            <select name="type" class="form-control" id="">
                                                <option>Select Faq Type</option>
                                                <option value="parent" {{ old('type', $data->type ?? '') == 'parent' ? 'selected' : '' }}>Parent</option>
                                                <option value="child" {{ old('type', $data->type ?? '') == 'child' ? 'selected' : '' }}>Child</option>
                                                <option value="school" {{ old('type', $data->type ?? '') == 'school' ? 'selected' : '' }}>School</option>
                                            </select>

                                            @if ($errors->has('type'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('type') }}
                                                </div>
                                            @endif
                                        </div>

                                    </div>
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label for="exampleFormControlName">Question</label>
                                            <input type="text" class="form-control" name="question"
                                                id="exampleFormControlName" placeholder="Question"
                                                value="{{ $data->question ? $data->question : '' }}">
                                            @if ($errors->has('question'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('question') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-sm-6">
                                        <label for="exampleFormControlFile1">Status</label>
                                        <select name="status" class="form-control" id="optionSelect">
                                            <option value="">Select Status</option>
                                            <option value="active" {{ $data->status == 'active' ? 'selected' : '' }}>
                                                Active</option>
                                            <option value="inactive" {{ $data->status == 'inactive' ? 'selected' : '' }}>
                                                Inactive</option>
                                        </select>
                                        @if ($errors->has('status'))
                                            <div class="text-danger small mt-1">
                                                {{ $errors->first('status') }}
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="answer">Answer</label>
                                            <textarea name="answer" id="answer" placeholder="Type your content Here">{{ old('answer', @$data->answer) }}</textarea>

                                            @if ($errors->has('answer'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('answer') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="form-footer mt-6">
                                    <button type="submit" class="btn btn-primary btn-pill mr-2">Update</button>
                                    <a href="{{ route('faq.index') }}"><button type="button"
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
        ClassicEditor
            .create(document.querySelector('#question'))
            .catch(error => {
                console.error(error);
            });

        ClassicEditor
            .create(document.querySelector('#answer'))
            .catch(error => {
                console.error(error);
            });


        $(document).ready(function() {

            $('.first-level').addClass('in')
        })
    </script>
@endsection
