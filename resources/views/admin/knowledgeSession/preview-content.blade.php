<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Security-Policy" content="default-src 'none'; style-src 'unsafe-inline' https: http:; img-src https: http: data:; font-src https: http: data:; base-uri 'none'; form-action 'none'">
    <title>{{ $article->title }}</title>
    <link rel="stylesheet" href="https://cdn.ckeditor.com/ckeditor5/43.3.1/ckeditor5.css">
    <link rel="stylesheet" href="{{ asset('assets/css/article-content.css') }}">
    <style>
        body { margin:0; background:#fff; font-family:Arial,Helvetica,sans-serif; color:#212529; }
        article { max-width:800px; margin:0 auto; padding:28px 22px; }
        .article-banner { display:block; width:100%; height:auto; border-radius:10px; margin-bottom:24px; }
        h1 { font-size:30px; line-height:1.25; overflow-wrap:anywhere; }
        .article-meta { color:#666; font-size:14px; margin-bottom:24px; }
        .article-body { overflow-x:auto; }
        @media (max-width:600px) { article { padding:18px 14px; } h1 { font-size:24px; } }
    </style>
</head>
<body>
    <article>
        @if ($article->banner_image)
            <img class="article-banner" src="{{ getImagePathUrl($article->banner_image, 'assets/images') }}" alt="{{ $article->title }}">
        @endif
        <h1>{{ $article->title }}</h1>
        <div class="article-meta">
            @if ($article->written_by)<span>By {{ $article->written_by }}</span>@endif
            @if ($article->category)<span> &middot; {{ $article->category->category_name }}</span>@endif
        </div>
        <div class="ck-content article-body">{!! $article->description !!}</div>
    </article>
</body>
</html>
