@extends('layout.headerFooter')
@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="title_left">
                <h2>Add FAQ</h2>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <ul class="admin-breadcrumb">
                        <li>
                            <a href="{{ route('faq.index') }}">FAQs</a>
                        </li>
                        <li class="active">
                            <a href="#">Add FAQ</a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="row">
                <div class="col-xl-12">
                    <div class="card card-default">
                        <!-- <div class="card-header">
                                <h2>Add FAQ</h2>
                            </div> -->
                        <div class="card-body">
                            <form action="{{ route('faq.store') }}" enctype="multipart/form-data" method="post">
                                @csrf
                                @method('Post')
                                {{-- <div class="row">
                                    <div class="col-12">
                                        <div class="form-group">
                                        <label for="exampleFormControlFile1">Language</label>
                                       <select name="language" class="form-control" id="optionSelect">
                                        <option value="" disabled {{ old('language') ? '' : 'selected' }}>Select Language</option>
                                        <option value="english" {{ old('language') == 'english' ? 'selected' : '' }}>English</option>
                                        <option value="simplified_chinese" {{ old('language') == 'simplified_chinese' ? 'selected' : '' }}>Simplified Chinese</option>
                                        <option value="traditional_chinese" {{ old('language') == 'traditional_chinese' ? 'selected' : '' }}>Traditional Chinese</option>
                                        </select>
                                        @if ($errors->has('language'))
                                        <div class="text-danger small mt-1">
                                            {{ $errors->first('language') }}
                                        </div>
                                        @endif
                                        </div>
                                    </div>
                                </div> --}}

                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label for="type">Faq Type</label>
                                            <select name="type" class="form-control" id="">
                                                <option>Select Faq Type</option>
                                                <option value="parent"
                                                    {{ old('type', $faq->type ?? '') == 'parent' ? 'selected' : '' }}>Parent
                                                </option>
                                                <option value="child"
                                                    {{ old('type', $faq->type ?? '') == 'child' ? 'selected' : '' }}>Child
                                                </option>
                                                <option value="school"
                                                    {{ old('type', $faq->type ?? '') == 'school' ? 'selected' : '' }}>School
                                                </option>
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
                                                value="{{ old('question') }}">
                                            @if ($errors->has('question'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('question') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="answer">Answer</label>
                                            <textarea name="answer" id="answer" placeholder="Type your content Here"> {{ old('answer') }}</textarea>
                                            @if ($errors->has('answer'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('answer') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="form-footer mt-6">
                                    <button type="submit" class="btn btn-primary btn-pill mr-2">Submit</button>
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
