@extends('layout.headerFooter')

@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="title_left">
                <h2>Edit Child Video Content</h2>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <ul class="admin-breadcrumb">
                        <li>
                            <a href="{{ route('knowledge-base-child.index') }}">Video Content</a>
                        </li>
                        <li class="active">
                            <a href="#">Edit Video Content</a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-12">
                    <div class="card card-default">
                        <div class="card-body">
                            <form id="knowledgeForm" action="{{ route('knowledge-base-child.update', $data->id) }}"
                                enctype="multipart/form-data" method="post">
                                @csrf
                                @method('PUT')

                                {{-- Basic Information --}}
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5>Basic Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="category">Category</label>
                                                <select class="form-control select2" name="category" id="category">
                                                    <option value="">Select Category</option>
                                                    @foreach ($categories as $value)
                                                        <option value="{{ $value['id'] }}" data-color="{{ $value->color }}"
                                                            data-title_color="{{ $value->title_color }}"
                                                            {{ $data->category_id == $value['id'] ? 'selected' : '' }}>
                                                            {{ $value['category_name'] }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('category')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-6">
                                                <label for="is_featured">Select as Featured</label>
                                                <select class="form-control select2" id="is_featured" name="is_featured">
                                                    <option value="">Select as featured</option>
                                                    <option value="yes"
                                                        {{ $data->is_featured == 'yes' ? 'selected' : '' }}>Yes</option>
                                                    <option value="no"
                                                        {{ $data->is_featured == 'no' ? 'selected' : '' }}>No</option>
                                                </select>
                                                @error('is_featured')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label>User Type</label><br>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="user_type"
                                                        id="user_type_child" value="child"
                                                        {{ old('user_type', $data->user_type) == 'child' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="user_type_child">Child</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="user_type"
                                                        id="user_type_both" value="both"
                                                        {{ old('user_type', $data->user_type) == 'both' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="user_type_both">Both</label>
                                                </div>
                                                @error('user_type')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6" id="ageRangeWrapper">
                                                <label for="age_range">Select Age Range</label>
                                                <select class="form-control" id="age_range" name="age_range">
                                                    <option value="">Select Age Range</option>
                                                    <option value="11-14" {{ $data->age == '11-14' ? 'selected' : '' }}>
                                                        Age(11-14)</option>
                                                    <option value="15-18" {{ $data->age == '15-18' ? 'selected' : '' }}>
                                                        Age(15-18)</option>
                                                    <option value="11-18" {{ $data->age == '11-18' ? 'selected' : '' }}>
                                                        Age(11-18)</option>
                                                </select>
                                                @error('age_range')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-6">
                                                <label for="school_id">Select School (Optional)</label>

                                                @php
                                                    $selectedSchools = old('school_id', $data->school_id ?? []);
                                                    $selectedSchools = is_array($selectedSchools)
                                                        ? $selectedSchools
                                                        : [];
                                                @endphp

                                                <select class="form-control select2" name="school_id[]" id="school_id"
                                                    multiple>

                                                    <option value="all">Select All</option>

                                                    @foreach ($schools as $s)
                                                        <option value="{{ $s['id'] }}"
                                                            {{ in_array($s['id'], $selectedSchools) ? 'selected' : '' }}>
                                                            {{ $s['name'] }}
                                                        </option>
                                                    @endforeach

                                                </select>

                                                @error('school_id')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>

                                        </div>

                                        <div class="row mb-3 mt-2">
                                            <div class="col-md-6" id="featured_key_wrapper">
                                                <label for="featured_key">Featured Key</label>
                                                <input type="text" class="form-control point" name="featured_key"
                                                    id="featured_key" placeholder="Enter Featured Key"
                                                    value="{{ old('featured_key', $data->featured_key) }}">
                                                @error('featured_key')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                {{-- Title Content --}}
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5>Title Content</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="title">Title</label>
                                                <input type="text" class="form-control" name="title" id="title"
                                                    placeholder="Enter Video Title" value="{{ $data->title ?? '' }}">
                                                @error('title')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            @php
                                                $initialColor = old('color', $data->color ?? '');
                                                $initialTitleColor = old('title_color', $data->title_color ?? '');
                                            @endphp
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="title-color-display">Title Color</label>
                                                    <input type="text" name="title_color" id="title-color-display"
                                                        class="form-control" value="{{ $initialTitleColor }}"
                                                        placeholder="Enter Title Color" readonly>
                                                    @if ($errors->has('title_color'))
                                                        <div class="text-danger small mt-1">
                                                            {{ $errors->first('title_color') }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="color-display">Color</label>
                                                    <input type="text" name="color" id="color-display"
                                                        class="form-control" value="{{ $initialColor }}"
                                                        placeholder="Enter Title Color" readonly>
                                                    @if ($errors->has('title_color'))
                                                        <div class="text-danger small mt-1">
                                                            {{ $errors->first('title_color') }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="form-group">
                                                    <label for="written_by">Written By</label>
                                                    <input type="text" name="written_by" id="written_by"
                                                        class="form-control"
                                                        value="{{ old('written_by', $data->written_by) }}"
                                                        placeholder="Enter Written By">
                                                    @if ($errors->has('written_by'))
                                                        <div class="text-danger small mt-1">
                                                            {{ $errors->first('written_by') }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Description Content --}}
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5>Description Content</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-md-12">
                                                <label for="description">Description</label>
                                                {{-- Added matching element ID tag attribute for CKEditor stabilization --}}
                                                <textarea class="form-control" id="description" name="description" rows="4" placeholder="Enter Video Description">{{ $data->description ?? '' }}</textarea>
                                                @error('description')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Media Upload --}}
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5>Upload</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            {{-- Video Upload --}}
                                            <div class="col-md-6">
                                                <label for="media">Upload Video</label>
                                                <input type="file" class="form-control" name="media"
                                                    id="media" accept="video/*">
                                                @error('media')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror

                                                <div class="mt-2">
                                                    <p>Video Preview:</p>
                                                    <video id="videoPlayer" width="300" controls
                                                        style="border:1px solid #ccc; border-radius:5px;">
                                                        <source
                                                            src="{{ !empty($data->video_link) ? getImagePathUrl($data->video_link, 'assets/video') : '' }}"
                                                            type="video/mp4">
                                                        Your browser does not support the video tag.
                                                    </video>
                                                </div>
                                            </div>

                                            {{-- Thumbnail Upload --}}
                                            <div class="col-md-6">
                                                <label for="thumbnail">Upload Thumbnail</label>
                                                <input type="file" class="form-control" name="thumbnail"
                                                    id="thumbnail" accept="image/*">
                                                @error('thumbnail')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror

                                                <div class="mt-2">
                                                    <p>Thumbnail Preview:</p>
                                                    <img id="thumbnailPreview"
                                                        src="{{ !empty($data->thumbnail) ? getImagePathUrl($data->thumbnail, 'assets/images') : '' }}"
                                                        alt="Thumbnail Preview"
                                                        style="height:100px; border:1px solid #ccc; border-radius:5px;">
                                                </div>
                                            </div>
                                            <div class="col-md-4 mt-4">
                                                <label for="ratio_type">Ratio Type</label>
                                                <select name="ratio_type" id="ratio_type" class="form-control">
                                                    <option value="">Select Ratio Type</option>
                                                    <option value="landscape"
                                                        {{ old('ratio_type', $data->ratio_type) == 'landscape' ? 'selected' : '' }}>
                                                        Landscape
                                                    </option>
                                                    <option value="portrait"
                                                        {{ old('ratio_type', $data->ratio_type) == 'portrait' ? 'selected' : '' }}>
                                                        Portrait
                                                    </option>
                                                </select>
                                                @error('ratio_type')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>

                                        </div>
                                    </div>

                                </div>
                                <div class="card-body">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="optionSelect">Status</label>
                                            <select name="status" class="form-control" id="status">
                                                <option value="">Select Status</option>
                                                <option value="active"
                                                    {{ $data->status == 'active' ? 'selected' : '' }}>Active</option>
                                                <option value="inactive"
                                                    {{ $data->status == 'inactive' ? 'selected' : '' }}>Inactive
                                                </option>
                                            </select>
                                            @if ($errors->has('status'))
                                                <div class="text-danger small mt-1">
                                                    {{ $errors->first('status') }}
                                                </div>
                                            @endif
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
                                    <a href="{{ route('knowledge-base-child.index') }}" class="btn btn-primary btn-pill mr-2">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

<script src="https://cdn.ckeditor.com/ckeditor5/23.0.0/classic/ckeditor.js"></script>

@push('scripts')
    <script>
        $(document).ready(function() {

            $('#media').on('change', function(event) {
                let input = event.target;
                if (input.files && input.files[0]) {
                    let fileURL = URL.createObjectURL(input.files[0]);
                    $('#videoPlayer source').attr('src', fileURL);
                    $('#videoPlayer')[0].load();
                }
            });

            // Live thumbnail preview
            $('#thumbnail').on('change', function(event) {
                let input = event.target;
                if (input.files && input.files[0]) {
                    let reader = new FileReader();
                    reader.onload = function(e) {
                        $('#thumbnailPreview').attr('src', e.target.result);
                    };
                    reader.readAsDataURL(input.files[0]);
                }
            });

            function updateColor() {
                var selectedOption = $('#category').find('option:selected');
                var color = selectedOption.data('color');
                var titleColor = selectedOption.data('title_color');

                $('#color-display').val(color);
                $('#title-color-display').val(titleColor);
            }
            $('#category').on('change', function() {
                updateColor();
            });
            updateColor();

            $('.first-level').addClass('in');

            if (typeof ClassicEditor !== 'undefined') {
                $('#description').each(function() {
                    ClassicEditor.create(this).catch(error => console.error(error));
                });
            }

            function toggleAgeRange() {
                const userType = $('input[name="user_type"]:checked').val();
                if (userType === 'child') {
                    $('#ageRangeWrapper').show();
                    $('#points').closest('.col-md-6').show();
                } else {
                    $('#ageRangeWrapper').hide();
                    $('#age_range').val('');
                    $('#points').closest('.col-md-6').hide();
                    $('#points').val('1');
                }
            }

            toggleAgeRange();

            $('input[name="user_type"]').change(function() {
                toggleAgeRange();
            });
        });

        $(document).ready(function() {
            if ($.fn.select2) {
                $('#school_id').select2({
                    placeholder: "Select Schools",
                    allowClear: true,
                    closeOnSelect: false,
                    templateResult: formatState,
                    templateSelection: formatState
                });
            }

            function formatState(state) {
                if (!state.id) return state.text;
                let selected = $('#school_id').val() || [];
                let isChecked = selected.includes(state.id) ? "checked" : "";
                return $(
                    '<span><input type="checkbox" ' + isChecked + ' style="margin-right:8px;" /> ' + state.text + '</span>'
                );
            }

            $('#school_id').on('select2:select select2:unselect', function(e) {
                let allValues = [];
                $('#school_id option').each(function() {
                    let val = $(this).val();
                    if (val !== 'all') { allValues.push(val); }
                });

                let selected = $(this).val() || [];
                if (e.params.data.id === 'all') {
                    if (selected.includes('all')) {
                        $('#school_id').val(['all', ...allValues]).trigger('change');
                    } else {
                        $('#school_id').val(null).trigger('change');
                    }
                } else {
                    selected = selected.filter(val => val !== 'all');
                    if (selected.length === allValues.length) {
                        $('#school_id').val(['all', ...allValues]).trigger('change');
                    } else {
                        $('#school_id').val(selected).trigger('change');
                    }
                }
            });

            $('#school_id').on('change', function() {
                setTimeout(() => {
                    $('#school_id').select2('close');
                    $('#school_id').select2('open');
                }, 0);
            });

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

            $('#knowledgeForm').on('submit', function (e) {
                e.preventDefault();
                e.stopPropagation();

                let category   = $('#category').val();
                let isFeatured = $('#is_featured').val();
                let userType   = $('input[name="user_type"]:checked').val();
                let ageRange   = $('#age_range').val();
                let title      = $.trim($('#title').val());
                let writtenBy  = $.trim($('#written_by').val());
                let ratioType  = $('#ratio_type').val();
                let status     = $('#status').val();

                if (!category) { showToast('The category field is required.', 'error'); return false; }
                if (!isFeatured) { showToast('The featured field is required.', 'error'); return false; }
                if (!userType) { showToast('The user type field is required.', 'error'); return false; }
                if (userType === 'child' && !ageRange) { showToast('The age range field is required for children profiles.', 'error'); return false; }
                if (!title) { showToast('The title field is required.', 'error'); return false; }
                if (title.length < 3) { showToast('The title must be at least 3 characters.', 'error'); return false; }
                if (!writtenBy) { showToast('The written by field is required.', 'error'); return false; }
                if (!ratioType) { showToast('The ratio type field is required.', 'error'); return false; }
                if (!status) { showToast('The status field is required.', 'error'); return false; }

                let $form    = $(this);
                let formData = new FormData(this);
                let $btn     = $('#submit-btn');

                $btn.prop('disabled', true).text('Uploading...');
                showProgress(0);
                showToast('Processing updates and files, please wait...', 'info');

                $.ajax({
                    url:         $form.attr('action'),
                    type:        'POST',
                    data:        formData,
                    processData: false,
                    contentType: false,
                    timeout:     0,
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
                                if (pct >= 100) showToast('File updates transferred, finishing processing...', 'info');
                            }
                        }, false);
                        return xhr;
                    },
                    success: function (res) {
                        hideProgress();
                        $btn.prop('disabled', false).text('Update');
                        showToast('Video resource updated successfully!', 'success');
                        setTimeout(() => { window.location.href = "{{ route('knowledge-base-child.index') }}"; }, 1500);
                    },
                    error: function (xhr) {
                        hideProgress();
                        $btn.prop('disabled', false).text('Update');
                        console.error('Update Request Fail Code:', xhr.status);

                        let msg = 'An unexpected update operational error occurred.';
                        if (xhr.status === 0)         msg = 'Network connection interrupted during file sync.';
                        else if (xhr.status === 413)  msg = 'Payload size limit exceeded. Server rejected stream.';
                        else if (xhr.status === 419)  msg = 'Security session expired (419). Please reload.';
                        else if (xhr.status === 422) {
                            try {
                                let json = xhr.responseJSON || JSON.parse(xhr.responseText);
                                msg = json.errors ? Object.values(json.errors).flat().join(' | ') : (json.message || 'Validation error.');
                            } catch(ex) { msg = 'Form parameters failed backend validations.'; }
                        }
                        else if (xhr.status === 500)  msg = 'Internal server exception (500). Process terminated.';
                        else if (xhr.status === 504)  msg = 'Upstream gateway processing timeout.';

                        showToast(msg, 'error');
                    }
                });
            });
        });
    </script>
@endpush
