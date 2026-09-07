@extends('layout.headerFooter')
@section('content')
<div class="content-wrapper">
    <div class="content">
        <div class="row">
            <div class="col-xl-12">
                <div class="card card-default">
                    <div class="card-header">
                        <h2>Upload Video</h2>
                    </div>
                    <div class="card-body">
                        <form action="{{ url('/admin/product-video-store') }}" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="product_id" value="{{ $id }}">
                            @csrf
                            <div class="row gx-0">
                                <div class="col-sm-6">
                                    <div>
                                        <label for="video_name">Video Embed Code</label>
                                        <textarea name="video_name" id="video_name" placeholder="Paste YouTube embed code here" class="form-control">{{ old('video_name') }}</textarea>
                                        @if ($errors->has('video_name'))
                                        <div class="text-danger small mt-1">
                                            {{ $errors->first('video_name') }}
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="upload-button-section">
                                        <button type="submit" class="btn btn-primary btn-pill">Upload</button>
                                        <a href="{{ route('products.index') }}"><button type="button" class="btn btn-light btn-pill">Back</button></a>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <hr>
                        <h3>Uploaded Videos</h3>
                        @if($videos->isEmpty())
                            <p>No videos uploaded yet.</p>
                        @else
                            <form id="delete-form" action="{{ url('admin/delete-product-videos') }}" method="post">
                                @csrf
                                <div class="row">
                                    @foreach($videos as $video)
                                        <div class="col-sm-6">
                                        <input type="checkbox" name="video_ids[]" value="{{ $video->id }}" class="video-checkbox">
                                            <div class="video-wrapper fixed-size">
                                                {!! $video->video_name !!}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <button type="submit" id="delete-button" class="btn btn-danger btn-pill mt-3" style="display: none;">Delete Selected Videos</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteButton = document.getElementById('delete-button');
    const checkboxes = document.querySelectorAll('.video-checkbox');

    function toggleDeleteButton() {
        const anyChecked = Array.from(checkboxes).some(checkbox => checkbox.checked);
        deleteButton.style.display = anyChecked ? 'inline-block' : 'none';
    }

    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', toggleDeleteButton);
    });

    document.getElementById('delete-button').addEventListener('click', function(event) {
        event.preventDefault();
        if (confirm('Are you sure you want to delete the selected videos?')) {
            document.getElementById('delete-form').submit();
        }
    });
});
</script>
@endsection
