@extends('layout.headerFooter')

@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                <h2>View Article</h2>
                <div>
                    <a href="{{ route('knowledgeSession.index') }}" class="btn btn-outline-primary">Back to Articles</a>
                    @if ($pre->is_modify === 'yes')
                        <a href="{{ route('knowledgeSession.edit', $article->id) }}" class="btn btn-primary">Edit Article</a>
                    @endif
                </div>
            </div>
            <div class="card card-default">
                <div class="card-body">
                    <p class="text-muted">Preview of the saved article. Layout and fonts may vary in the mobile app.</p>
                    <iframe title="Article preview" sandbox="" referrerpolicy="no-referrer"
                        style="width:100%; height:75vh; min-height:500px; border:1px solid #e0e0e0; border-radius:8px; background:#fff;"
                        srcdoc="{{ view('admin.knowledgeSession.preview-content', compact('article'))->render() }}"></iframe>
                </div>
            </div>
        </div>
    </div>
@endsection
