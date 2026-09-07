@extends('layout.headerFooter')

@section('content')
<div class="content-wrapper">
    <div class="content">
        <div class="title_left">
            <h2>Edit Video & Mood Content</h2>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <ul class="admin-breadcrumb">
                    <li><a href="{{ route('video-other.index') }}">Video Content</a></li>
                    <li class="active"><a href="#">Edit Video Content</a></li>
                </ul>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-12">
                <div class="card card-default">
                    <div class="card-body">
                        <form id="knowledgeForm" action="{{ route('video-other.update', $data->id) }}" enctype="multipart/form-data" method="post">
                            @csrf
                            @method('PUT')

                            {{-- Basic Information --}}
                            <div class="card mb-4">
                                <div class="card-header"><h5>Basic Information</h5></div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="category">Category</label>
                                            <select class="form-control select2" name="category" id="category">
                                                <option value="">Select Category</option>
                                                @foreach ($categories as $value)
                                                    <option value="{{ $value->id }}" data-color="{{ $value->color }}" data-title_color="{{ $value->title_color }}" {{ $data->category_id == $value->id ? 'selected' : '' }}>
                                                        {{ $value->category_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('category') <div class="text-danger small">{{ $message }}</div> @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label for="is_featured">Select as Featured</label>
                                            <select class="form-control select2" name="is_featured" id="is_featured">
                                                <option value="no" {{ $data->is_featured == 'no' ? 'selected' : '' }}>No</option>
                                                <option value="yes" {{ $data->is_featured == 'yes' ? 'selected' : '' }}>Yes</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <label>Status</label>
                                            <select name="status" id="status" class="form-control">
                                                <option value="active" {{ $data->status == 'active' ? 'selected' : '' }}>Active</option>
                                                <option value="inactive" {{ $data->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Ratio Type</label>
                                            <select name="ratio_type" id="ratio_type" class="form-control">
                                                <option value="landscape" {{ $data->ratio_type == 'landscape' ? 'selected' : '' }}>Landscape</option>
                                                <option value="portrait" {{ $data->ratio_type == 'portrait' ? 'selected' : '' }}>Portrait</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Content Details --}}
                            <div class="card mb-4">
                                <div class="card-header"><h5>Content Details</h5></div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label>Title</label>
                                            <input type="text" class="form-control" name="title" id="title" value="{{ old('title', $data->title) }}">
                                            @error('title') <div class="text-danger small">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label>Written By</label>
                                            <input type="text" name="written_by" id="written_by" class="form-control" value="{{ old('written_by', $data->written_by) }}">
                                            @error('written_by') <div class="text-danger small">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-3">
                                            <label>Title Color</label>
                                            <input type="text" name="title_color" id="title-color-display" class="form-control" value="{{ $data->title_color }}" readonly>
                                        </div>
                                        <div class="col-md-3">
                                            <label>Background Color</label>
                                            <input type="text" name="color" id="color-display" class="form-control" value="{{ $data->color }}" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Featured Key</label>
                                            <input type="text" name="featured_key" class="form-control" value="{{ $data->featured_key }}">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <label>Description</label>
                                            <textarea id="description" name="description" class="form-control">{{ $data->description }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Media Section with Amazon S3 Helper URLs --}}
                            <div class="card mb-4">
                                <div class="card-header"><h5>Media</h5></div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label>Video</label>
                                            <input type="file" name="media" class="form-control" accept="video/*">
                                            @if($data->video_link)
                                                <video width="200" controls class="mt-2 border rounded">
                                                    <source src="{{ getImagePathUrl($data->video_link, 'assets/video') }}" type="video/mp4">
                                                    Your browser does not support the video tag.
                                                </video>
                                            @endif
                                        </div>
                                        <div class="col-md-6">
                                            <label>Thumbnail</label>
                                            <input type="file" name="thumbnail" class="form-control" accept="image/*">
                                            @if($data->thumbnail)
                                                <img src="{{ getImagePathUrl($data->thumbnail, 'assets/images') }}" width="100" class="mt-2 border rounded" alt="Thumbnail">
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @include('admin.partials.deeplink-share', [
                                'deeplinkType' => 'podcast',
                                'shareId' => $data->id,
                                'canonicalUrl' => $data->canonical_url,
                                'shareStatus' => $data->status,
                            ])
                            {{-- Action Buttons with Real-time Upload Progress Bar Wrapper --}}
                            <div class="form-footer text-end">
                                <div id="upload-progress" class="mb-3 text-start" style="display:none; max-width:400px; margin-left:auto;">
                                    <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                                        <small class="text-muted">Uploading to cloud...</small>
                                        <small class="text-muted"><span id="upload-percent">0</span>%</small>
                                    </div>
                                    <div class="progress" style="height:10px;">
                                        <div id="upload-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                                            role="progressbar" style="width:0%"
                                            aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                                <button type="submit" id="submit-btn" class="btn btn-primary btn-pill">Update</button>
                                <a href="{{ route('video-other.index') }}" class="btn btn-primary btn-pill">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

{{-- Script segment configured safely for dynamic layouts --}}
<script src="https://cdn.ckeditor.com/ckeditor5/23.0.0/classic/ckeditor.js"></script>

@push('scripts')
<script>
$(document).ready(function () {

    // Check if ClassicEditor is initialized to prevent script execution crashes
    if (typeof ClassicEditor !== 'undefined') {
        ClassicEditor.create(document.querySelector('#description')).catch(err => console.error(err));
    }

    // Dynamic color picker mapper
    $('#category').on('change', function () {
        let opt = $(this).find('option:selected');
        $('#color-display').val(opt.data('color'));
        $('#title-color-display').val(opt.data('title_color'));
    });

    // Elegant Toast alerts notification handler
    function showToast(message, type) {
        $('#ajax-toast').remove();
        let bg   = type === 'success' ? '#16a34a' : type === 'error' ? '#dc2626' : '#2563eb';
        let icon = type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️';
        $('body').append(`
            <div id="ajax-toast" style="
                position:fixed; top:20px; right:20px; z-index:99999;
                background:${bg}; color:#fff; padding:14px 22px;
                border-radius:8px; font-size:14px; font-weight:500;
                box-shadow:0 4px 12px rgba(0,0,0,0.25);
                min-width:280px; max-width:420px;
                display:flex; align-items:center; gap:10px;">
                <span style="font-size:18px;">${icon}</span>
                <span>${message}</span>
            </div>`);
        setTimeout(() => { $('#ajax-toast').fadeOut(400, function(){ $(this).remove(); }); }, 6000);
    }

    function showProgress(pct) {
        $('#upload-progress').show();
        $('#upload-bar').css('width', pct + '%').attr('aria-valuenow', pct);
        $('#upload-percent').text(pct);
    }

    function hideProgress() {
        setTimeout(() => { $('#upload-progress').hide(); showProgress(0); }, 1000);
    }

    // Protected AJAX upload logic mapped explicitly to REST rules
    $('#knowledgeForm').on('submit', function (e) {
        e.preventDefault();
        e.stopPropagation();

        // Frontend validation logic to filter out empty fields BEFORE sending file buffers
        let category   = $('#category').val();
        let isFeatured = $('#is_featured').val();
        let status     = $('#status').val();
        let ratioType  = $('#ratio_type').val();
        let title      = $.trim($('#title').val());
        let writtenBy  = $.trim($('#written_by').val());

        if (!category) { showToast('The category field is required.', 'error'); return false; }
        if (!isFeatured) { showToast('The featured field is required.', 'error'); return false; }
        if (!status) { showToast('The status field is required.', 'error'); return false; }
        if (!ratioType) { showToast('The ratio type field is required.', 'error'); return false; }
        if (!title) { showToast('The title field is required.', 'error'); return false; }
        if (!writtenBy) { showToast('The written by field is required.', 'error'); return false; }

        let $form    = $(this);
        let formData = new FormData(this);

        let $btn = $('#submit-btn');
        $btn.prop('disabled', true).text('Uploading...');
        showProgress(0);
        showToast('Upload started, please wait...', 'info');

        $.ajax({
            url:         $form.attr('action'),
            type:        'POST',
            data:        formData,
            processData: false,
            contentType: false,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept':           'application/json'
            },
            xhr: function () {
                let xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function (e) {
                    if (e.lengthComputable) {
                        let pct = Math.round((e.loaded / e.total) * 100);
                        showProgress(pct);
                        if (pct >= 100) showToast('File reached cloud, finishing processing...', 'info');
                    }
                }, false);
                return xhr;
            },
            success: function (res) {
                hideProgress();
                $btn.prop('disabled', false).text('Update');
                if (typeof res === 'object' && res.success) {
                    showToast(res.message || 'Updated successfully!', 'success');
                    setTimeout(() => { window.location.href = res.redirect || "{{ route('video-other.index') }}"; }, 1500);
                } else {
                    showToast('Updated successfully!', 'success');
                    setTimeout(() => { window.location.href = "{{ route('video-other.index') }}"; }, 1500);
                }
            },
            error: function (xhr) {
                hideProgress();
                $btn.prop('disabled', false).text('Update');
                console.error('Status:', xhr.status);

                let msg = 'An unknown error occurred.';
                if      (xhr.status === 0)   msg = 'Connection lost or upload timed out.';
                else if (xhr.status === 413)  msg = 'File too large. Server rejected the upload.';
                else if (xhr.status === 419)  msg = 'Session expired (419). Please refresh page.';
                else if (xhr.status === 422) {
                    try {
                        let json = xhr.responseJSON || JSON.parse(xhr.responseText);
                        msg = json.errors ? Object.values(json.errors).flat().join(' | ') : (json.message || 'Validation failed.');
                    } catch(ex) { msg = 'Validation error — check required parameters.'; }
                }
                else if (xhr.status === 500) {
                    try {
                        let json = xhr.responseJSON || JSON.parse(xhr.responseText);
                        msg = 'Server error (500): ' + (json.message || 'Check logs.');
                    } catch(ex) { msg = 'Server internal error (500).'; }
                }
                else if (xhr.status === 504 || xhr.status === 524)
                    msg = 'Gateway timeout — processing took too long.';
                else
                    msg = 'Error ' + xhr.status + ': ' + (xhr.statusText || 'Unknown');

                showToast(msg, 'error');
            }
        });
    });
});
</script>
@endpush
