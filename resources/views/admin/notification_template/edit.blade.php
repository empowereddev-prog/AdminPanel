@extends('layout.headerFooter')
@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="title_left">
                <h2>Edit Notification Template</h2>
            </div>

            <div class="card card-default">
                <div class="card-body">
                    <form action="{{ route('notification-template.update', $template->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="form-group">
                            <label>Variable Name</label>
                            <input type="text" class="form-control"
                                value="{{ convertInCamelCase($template->variable_name) }}" readonly>
                        </div>
                        <div class="form-group">
                            <label>Subject</label>
                            <input type="text" name="subject" class="form-control"
                                value="{{ old('subject', $template->subject) }}" maxlength="150">
                            @error('subject')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="6">{{ old('description', $template->description) }}</textarea>
                            @error('description')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary btn-pill mr-2">Update</button>
                            <a href="{{ route('notification-template.index') }}"><button type="button"
                                    class="btn btn-primary btn-pill">Cancel</button></a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- CKEditor --}}
    <script src="https://cdn.ckeditor.com/ckeditor5/23.0.0/classic/ckeditor.js"></script>
    <script>
        document.querySelectorAll('.content_data').forEach(el => {
            ClassicEditor.create(el).catch(error => console.error(error));
        });
    </script>
@endsection
