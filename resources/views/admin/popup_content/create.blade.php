@extends('layout.headerFooter')
@section('content')
<div class="content-wrapper">
    <div class="content">
        <div class="title_left">
            <h2>Add Popup Content</h2>
        </div>

        <div class="row">
            <div class="col-xl-12">
                <div class="card card-default">
                    <div class="card-body">
                        <form action="{{ route('popup-content.store') }}" method="post">
                            @csrf
                            <div class="form-group mb-3">
                                <label for="type">Type</label>
                                <select name="type" class="form-control">
                                    <option value="">Select Type</option>
                                    <option value="popup1" {{ old('type') == 'popup1' ? 'selected' : '' }}>Popup1</option>
                                    <option value="popup2" {{ old('type') == 'popup2' ? 'selected' : '' }}>Popup2</option>
                                </select>
                                @error('type')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="title">Title</label>
                                <input type="text" name="title" class="form-control" placeholder="Enter title" value="{{ old('title') }}">
                                @error('title')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="description">Description</label>
                                <textarea name="description" id="description" class="form-control" placeholder="Enter description">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-footer mt-3">
                                <button type="submit" class="btn btn-primary btn-pill">Submit</button>
                                <a href="{{ route('popup-content.index') }}" class="btn btn-primary btn-pill">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CKEditor -->
<script src="https://cdn.ckeditor.com/ckeditor5/34.1.0/classic/ckeditor.js"></script>
<script>
ClassicEditor
    .create(document.querySelector('#description'))
    .catch(error => { console.error(error); });
</script>
@endsection
