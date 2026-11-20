@once
    @push('styles')
        <link rel="stylesheet" href="https://cdn.ckeditor.com/ckeditor5/43.3.1/ckeditor5.css">
        <link rel="stylesheet" href="{{ asset('assets/css/article-content.css') }}">
        <style>
            .ck-editor__editable_inline { min-height: 300px; }
            .ck.ck-editor { width: 100%; }
            .ck-content ul { list-style: disc; }
            .ck-content ol { list-style: decimal; }
        </style>
    @endpush
    @push('scripts')
        <script src="https://cdn.ckeditor.com/ckeditor5/43.3.1/ckeditor5.umd.js"></script>
        <script src="{{ asset('assets/js/article-editor.js') }}"></script>
    @endpush
@endonce
