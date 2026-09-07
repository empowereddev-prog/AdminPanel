@extends('layout.headerFooter')

@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="title_left">
                <h2>Add Video & Webinars</h2>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <ul class="admin-breadcrumb">
                        <li>
                            <a href="{{ route('video-other.index') }}">Video & Webinars</a>
                        </li>
                        <li class="active">
                            <a href="#">Add Video & Webinars</a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-12">
                    <div class="card card-default">
                        <div id="page-loader"
                            style="display:none; position:fixed; top:0; left:0; width:100%; height:100%;
                        background:rgba(255,255,255,0.7); z-index:9999; text-align:center; padding-top:20%;">
                            <div class="spinner-border text-primary" role="status" style="width:3rem; height:3rem;">
                                <span class="sr-only">Loading...</span>
                            </div>
                            <p class="mt-2 text-dark">Please wait...</p>
                        </div>
                        <div class="card-body">
                            <form id="knowledgeForm" action="{{ route('video-other.store') }}"
                                enctype="multipart/form-data" method="post">
                                @csrf

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
                                                  <option value="" data-color="">Select Category</option>
                                                    @foreach ($category as $value)
                                                    <option value="{{ $value->id }}"
                                                    data-color="{{ $value->color }}"
                                                    data-title_color="{{ $value->title_color }}"
                                                    {{ old('category', $knowledgeBase->category_id ?? '') == $value->id ? 'selected' : '' }}>
                                                    {{ $value->category_name }}
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
                                           <option value="yes" {{ old('is_featured') == 'yes' ? 'selected' : '' }}>Yes</option>
                                          <option value="no" {{ old('is_featured') == 'no' ? 'selected' : '' }}>No</option>
                                         </select>
                                        @error('is_featured')
                                           <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                      </div>
                                     </div>

                                    <div class="row mb-3">
                                     <div class="col-md-6">
                                        <label for="featured_key">Featured Key</label>
                                          <input type="text" class="form-control" name="featured_key"
                                            id="featured_key" placeholder="Enter Featured Key"
                                           value="{{ old('featured_key') }}">
                                           @error('featured_key')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                           @enderror
                                        </div>
                                     </div>

                                   <div class="row mb-3" id="ageRangeWrapper" style="display:none;">
                                       <div class="col-md-6">
                                        <label for="age_range">Select Age Range</label>
                                         <select class="form-control" id="age_range" name="age_range">
                                            <option value="">Select Age Range</option>
                                            <option value="11-14" {{ old('age_range') == '11-14' ? 'selected' : '' }}>Age(11-14)</option>
                                            <option value="15-18" {{ old('age_range') == '15-18' ? 'selected' : '' }}>Age(15-18)</option>
                                            <option value="11-18" {{ old('age_range') == '11-18' ? 'selected' : '' }}>Age(11-18)</option>
                                         </select>
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
                                                <label for="title">Title </label>
                                                <input type="text" class="form-control" name="title" id="title"
                                                    placeholder="Enter Video Title" value="{{ old('title') }}">
                                                @error('title')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            @php
                                                $initialColor = old('color', $knowledgeBase->category->color ?? '');
                                                $initialTitleColor = old('title_color', $knowledgeBase->category->title_color ?? '');
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
                                                        class="form-control" value="{{ old('written_by') }}"
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
                                                <label for="description">Description </label>
                                                <textarea class="form-control" id="description" name="description" rows="4" placeholder="Enter Video Description">{{ old('description') }}</textarea>
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
                                            <div class="col-md-6">
                                                <label for="media">Upload Video</label>
                                                <input type="file" class="form-control" name="media"
                                                    id="media" accept="video/*">
                                                @error('media')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror

                                                <div id="videoPreviewWrapper" class="mt-2" style="display:none;">
                                                    <p class="mb-1">Preview:</p>
                                                    <video id="videoPreview" controls
                                                        style="max-width: 300px; border:1px solid #ccc; border-radius:5px;">
                                                        <source src="" type="video/mp4">
                                                        Your browser does not support the video tag.
                                                    </video>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <label for="thumbnail">Upload Thumbnail</label>
                                                <input type="file" class="form-control" name="thumbnail"
                                                    id="thumbnail" accept="image/*">
                                                @error('thumbnail')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror

                                                <div id="thumbnailPreviewWrapper" class="mt-2"
                                                    style="display:none;">
                                                    <p class="mb-1">Preview:</p>
                                                    <img id="thumbnailPreview" src="" alt="Thumbnail Preview"
                                                        style="max-width: 200px; border:1px solid #ccc; border-radius:5px;" />
                                                </div>
                                            </div>
                                            <div class="col-md-4 mt-4">
                                                <label for="ratio_type">Ratio Type</label>
                                                <select name="ratio_type" id="ratio_type" class="form-control">
                                                    <option value="">Select Ratio Type</option>
                                                    <option value="landscape"
                                                        {{ old('ratio_type') == 'landscape' ? 'selected' : '' }}>
                                                        Landscape
                                                    </option>
                                                    <option value="portrait"
                                                        {{ old('ratio_type') == 'portrait' ? 'selected' : '' }}>
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

                                {{-- Actions and Upload Progress Bar Framework --}}
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
                                    <button type="submit" id="submit-btn" class="btn btn-primary btn-pill">Submit</button>
                                    <a href="{{ route('video-other.index') }}" class="btn btn-primary btn-pill mr-2">Cancel</a>
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
            // Media Video Preview Handler
            $('#media').on('change', function(event) {
                let input = event.target;
                if (input.files && input.files[0]) {
                    let fileURL = URL.createObjectURL(input.files[0]);
                    $('#videoPreview source').attr('src', fileURL);
                    $('#videoPreview')[0].load();
                    $('#videoPreviewWrapper').show();
                } else {
                    $('#videoPreviewWrapper').hide();
                    $('#videoPreview source').attr('src', '');
                }
            });

            // Media Image Thumbnail Preview Handler
            $('#thumbnail').on('change', function(event) {
                let input = event.target;
                if (input.files && input.files[0]) {
                    let reader = new FileReader();
                    reader.onload = function(e) {
                        $('#thumbnailPreview').attr('src', e.target.result);
                        $('#thumbnailPreviewWrapper').show();
                    };
                    reader.readAsDataURL(input.files[0]);
                } else {
                    $('#thumbnailPreviewWrapper').hide();
                    $('#thumbnailPreview').attr('src', '');
                }
            });

            // Dynamic Categorized Colors Mapper
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

            // Schools Selector Engine Configuration
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

            // Target Profiles Wrapper Display Toggler
            function toggleAgeRange() {
                const userType = $('input[name="user_type"]:checked').val();
                if (userType === 'child') {
                    $('#ageRangeWrapper').show();
                    $('#points').closest('.col-md-6').show();
                    $('.point').val('');
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

            // Initialize Classic Text Editor (CKEditor) safely
            if (typeof ClassicEditor !== 'undefined') {
                ClassicEditor.create(document.querySelector('#description')).catch(error => console.error(error));
                ClassicEditor.create(document.querySelector('#description_chinese')).catch(error => console.error(error));
            }

            // Elegant Dynamic Toast Alerts Layer
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

            // Real-time Upload Performance Progression Processing Engine
            $('#knowledgeForm').on('submit', function (e) {
                e.preventDefault();
                e.stopPropagation();

                // Frontend validation logic to filter out empty fields BEFORE sending file buffers
                let category   = $('#category').val();
                let isFeatured = $('#is_featured').val();
                let title      = $.trim($('#title').val());
                let writtenBy  = $.trim($('#written_by').val());
                let ratioType  = $('#ratio_type').val();

                if (!category) { showToast('The category field is required.', 'error'); return false; }
                if (!isFeatured) { showToast('The featured field is required.', 'error'); return false; }
                if (!title) { showToast('The title field is required.', 'error'); return false; }
                if (title.length < 3) { showToast('The title must be at least 3 characters.', 'error'); return false; }
                if (!writtenBy) { showToast('The written by field is required.', 'error'); return false; }
                if (!ratioType) { showToast('The ratio type field is required.', 'error'); return false; }

                let $form    = $(this);
                let formData = new FormData(this);
                let $btn     = $('#submit-btn');

                $btn.prop('disabled', true).text('Uploading...');
                showProgress(0);
                showToast('Processing media contents upload, please wait...', 'info');

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
                                if (pct >= 100) showToast('File received by the server, finishing processing...', 'info');
                            }
                        }, false);
                        return xhr;
                    },
                    success: function (res) {
                        hideProgress();
                        $btn.prop('disabled', false).text('Submit');
                        showToast('Video content generated successfully!', 'success');
                        setTimeout(() => { window.location.href = "{{ route('video-other.index') }}"; }, 1500);
                    },
                    error: function (xhr) {
                        hideProgress();
                        $btn.prop('disabled', false).text('Submit');
                        console.error('Upload Status Error:', xhr.status);

                        let msg = 'An unexpected submission exception occurred.';
                        if (xhr.status === 0)         msg = 'Network timeout occurred during data sync.';
                        else if (xhr.status === 413)  msg = 'Payload too large. Server dropped transaction.';
                        else if (xhr.status === 419)  msg = 'CSRF security token expired. Reload page.';
                        else if (xhr.status === 422) {
                            try {
                                let json = xhr.responseJSON || JSON.parse(xhr.responseText);
                                msg = json.errors ? Object.values(json.errors).flat().join(' | ') : (json.message || 'Validation rejected.');
                            } catch(ex) { msg = 'Form inputs failed validation check.'; }
                        }
                        else if (xhr.status === 500)  msg = 'Internal application error (500). Processing failed.';
                        else if (xhr.status === 504)  msg = 'Upstream processing gateway timed out.';

                        showToast(msg, 'error');
                    }
                });
            });
        });
    </script>
@endpush
