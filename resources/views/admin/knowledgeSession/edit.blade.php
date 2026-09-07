@extends('layout.headerFooter')

@section('content')
    <div class="content-wrapper">
        <div class="content">
            <div class="title_left">
                <h2>Edit Articles</h2>
            </div>
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="row">
                <div class="col-sm-12">
                    <ul class="admin-breadcrumb">
                        <li>
                            <a href="{{ route('knowledgeSession.index') }}">Article</a>
                        </li>
                        <li class="active">
                            <a href="#">Edit Article</a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-12">
                    <div class="card card-default">
                        <div class="card-body">
                            <form id="knowledgeForm" action="{{ route('knowledgeSession.update', $data->id) }}"
                                enctype="multipart/form-data" method="post">
                                @csrf
                                @method('PUT')
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0">Basic Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="form-group col-md-6 mb-3">
                                                <label for="category">Category</label>
                                                <select class="form-control select2" name="category" id="category">
                                                    @foreach ($categories as $value)
                                                        <option value="{{ $value['id'] }}"
                                                            class="{{ $data->category_id == $value['id'] ? 'bg-success text-white' : '' }}"
                                                            data-color="{{ $value->color }}"
                                                            data-title_color="{{ $value->title_color }}"
                                                            {{ $data->category_id == $value['id'] ? 'selected' : '' }}>
                                                            {{ $value['category_name'] }}</option>
                                                    @endforeach
                                                </select>
                                                @error('category')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="form-group col-md-6 mb-3">
                                                <label for="session_type">Session Type</label>
                                                <select class="form-control select2" name="session_type" id="session_type">
                                                    <option value="">Select Session Type</option>
                                                    <!-- <option value="online">Online</option>
                                                                                                                                                                                                                <option value="offline">Offline</option>
                                                                                                                                                                                                                <option value="hybrid">Hybrid</option> -->
                                                    {{-- <option value="online"
                                                        {{ $data->session_type == 'online' ? 'selected' : '' }}>Online
                                                    </option>
                                                    <option value="offline"
                                                        {{ $data->session_type == 'offline' ? 'selected' : '' }}>Offline
                                                    </option>
                                                    <option value="hybrid"
                                                        {{ $data->session_type == 'hybrid' ? 'selected' : '' }}>Hybrid
                                                    </option> --}}
                                                    <option value="article"
                                                        {{ $data->session_type == 'article' ? 'selected' : '' }}>Article
                                                    </option>
                                                </select>
                                                @error('session_type')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="form-group col-md-6 mb-3">
                                                <label for="title">Title </label>
                                                <input type="text" class="form-control" name="title" id="title"
                                                    placeholder="Enter Session Title"
                                                    value="{{ $data->title ? $data->title : '' }}">
                                                @error('title')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-6">
                                                <label for="written_by">Written By</label>
                                                <input type="text" class="form-control" name="written_by" id="written_by"
                                                    placeholder="Enter Written By"
                                                    value="{{ $data->written_by ? $data->written_by : '' }}">
                                                @error('written_by')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="form-group col-md-6 mb-3" id="session_link_container"
                                                style="display: none;">
                                                <label for="link">Session Link</label>
                                                <input type="text" class="form-control" name="link" id="link"
                                                    placeholder="Enter Session Link"
                                                    value="{{ $data->link ?? old('link') }}">
                                                @if ($errors->has('link'))
                                                    <div class="text-danger small mt-1">
                                                        {{ $errors->first('link') }}
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="form-group col-md-6 mb-3" id="venue_container"
                                                style="display: none;">
                                                <label for="venue">Venue</label>
                                                <input type="text" class="form-control" name="venue" id="venue"
                                                    placeholder="Enter Venue" value="{{ $data->venue ?? '' }}">
                                                @error('venue')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>


                                            @php
                                                // This logic to get the initial color remains the same
                                                $initialColor = old('color', $knowledgeBase->category->color ?? '');
                                                $initialTitleColor = old(
                                                    'title_color',
                                                    $knowledgeBase->category->title_color ?? '',
                                                );
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

                                            <div class="col-md-6" id="featured_key">
                                                <label for="featured_key">Featured Key</label>
                                                <input type="text" class="form-control point" name="featured_key"
                                                    id="featured_key" placeholder="Enter Featured Key"
                                                    value="{{ old('featured_key', $data->featured_key) }}">
                                                @error('featured_key')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="form-group col-12 mt-2">
                                                <label for="description">Description</label>
                                                <textarea class="form-control" id="description" name="description" placeholder="Enter Description">{!! old('description', $data->description ?? '') !!}</textarea>
                                                @error('description')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Chinese Content --}}
                                {{-- <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Chinese Content</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="form-group col-md-6 mb-3">
                                            <label for="title_chinese">Title (In Chinese)</label>
                                            <input type="text" class="form-control" name="title_chinese" id="title_chinese" placeholder="Enter Title" value="{{$data->title_chinese ? $data->title_chinese : old('title_chinese')}}">
                                            @error('title_chinese')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="form-group col-md-6 mb-3">
                                            <label for="description_chinese">Description (In Chinese)</label>
                                            <textarea class="form-control" name="description_chinese" id="description_chinese" placeholder="Enter Description">{{ old('description_chinese', $data->description_chinese ?? '') }}</textarea>
                                            @error('description_chinese')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div> --}}
                                <div class="card mb-4">
                                    <div class="card-header">
                                        Suggested Articles
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-md-12">
                                                <label for="article_ids">Select Suggested Articles
                                                    <small>(Optional)</small></label>
                                                <select class="form-control select2" name="article_ids[]"
                                                    id="article_ids" multiple>
                                                    @foreach ($allArticles as $article)
                                                        <option value="{{ $article->id }}"
                                                            {{ in_array($article->id, $selectedArticleIds) ? 'selected' : '' }}>
                                                            {{ $article->title }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('article_ids')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                {{-- Media & Audience --}}
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0">Media & Audience</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="form-group col-md-6 mb-3">
                                                <div class="form-group">
                                                    <label for="exampleFormControlFile1">Image</label>
                                                    <input type="file" class="form-control-file" name="banner_image"
                                                        id="exampleFormControlFile1" value="{{ $data->banner_image }}">
                                                    @if ($errors->has('banner_image'))
                                                        <div class="text-danger small mt-1">
                                                            {{ $errors->first('banner_image') }}
                                                        </div>
                                                    @endif

                                                </div>
                                                <br>
                                                {{-- <img src="{{ asset('assets/images/' . $data->banner_image) }}"
                                                    height="60" width="60" alt="image"> --}}
                                                <img src="{{ getImagePathUrl($data->banner_image, 'assets/images') }}"
                                                    height="60" width="60" alt="image">
                                            </div>

                                            {{-- <div class="form-group col-md-6 mb-3">
                                                <label>User Type</label><br>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="user_type"
                                                        id="user_type_child" value="child"
                                                        {{ old('user_type', $data->user_type) == 'child' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="user_type_child">Child</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="user_type"
                                                        id="user_type_adult" value="parent"
                                                        {{ old('user_type', $data->user_type) == 'parent' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="user_type_adult">Parent</label>
                                                </div>
                                                @error('user_type')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div> --}}

                                            <div class="form-group col-md-6 mb-3" id="ageRangeWrapper">
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

                                            {{-- <div class="form-group col-md-6 mb-3" id="isFeaturedWrapper">
                                                <label for="is_featured">Select as Featured</label>
                                                <select class="form-control select2" id="is_featured d-none" name="is_featured">
                                                    <option value="">Select as featured</option>
                                                    <option value="yes"
                                                        {{ $data->is_featured == 'yes' ? 'selected' : '' }}>Yes</option>
                                                    <option value="no"
                                                        {{ $data->is_featured == 'no' ? 'selected' : '' }}>No</option>
                                                </select>
                                                @error('is_featured')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div> --}}

                                        </div>
                                    </div>
                                </div>

                                {{-- Date & Time --}}
                                <div class="card mb-4" id="sessionDateTimeCard">
                                    <div class="card-header">
                                        <h5 class="mb-0">Session Date & Time</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="form-group col-md-6 mb-3">
                                                <label for="session_date">Session Date</label>
                                                <input type="text" class="form-control" id="session_date"
                                                    name="session_date" min="{{ date('Y-m-d') }}"
                                                    value="{{ $data->session_date ? $data->session_date : '' }}">
                                                @error('session_date')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="form-group col-md-6 mb-3">
                                                <label for="session_time">Session Time</label>
                                                <input type="text" class="form-control" id="session_time"
                                                    name="session_time"
                                                    value="{{ $data->session_time ? $data->session_time : '' }}">
                                                @error('session_time')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="form-group col-12">
                                        <label for="exampleFormControlFile1">Status</label>
                                        <select name="status" class="form-control" id="optionSelect">
                                            <option value="">Select Status</option>
                                            <option value="active" {{ $data->status == 'active' ? 'selected' : '' }}>
                                                Active</option>
                                            <option value="inactive" {{ $data->status == 'inactive' ? 'selected' : '' }}>
                                                Inactive</option>
                                        </select>
                                        @if ($errors->has('status'))
                                            <div class="text-danger small mt-1">
                                                {{ $errors->first('status') }}
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Buttons --}}
                                <div class="form-footer text-end">
                                    <button type="submit" class="btn btn-primary btn-pill">Update</button>
                                    <a href="{{ route('knowledgeSession.index') }}">
                                        <button type="button" class="btn btn-primary btn-pill mr-2">Cancel</button>
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
<style>
    /* Select2 ka container height aur wrapping fix */
    .select2-container--default .select2-selection--multiple {
        min-height: 38px;
        height: auto !important;
        overflow: visible !important;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        padding: 5px;
    }

    /* Selected tags ke beech spacing */
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        margin: 3px 5px 3px 0;
    }

    #category {
        width: 100% !important;
    }

    .note-editor .note-editable ul {
        list-style: disc;
        margin-bottom: 1rem;
        padding-left: 2rem;
    }

    .note-editor .note-editable ul li {
        list-style-type: disc;
    }

    .note-editor .note-editable ol {
        list-style: decimal;
        margin-bottom: 1rem;
        padding-left: 2rem;
    }

    .note-editor .note-editable ol li {
        list-style-type: decimal;
    }
</style>
@push('scripts')
    <script>
        // CKEDITOR.replace('description', {
        //     height: 100,
        //     removePlugins: 'image',
        // });
        $(document).ready(function() {
            $('#article_ids').select2({
                placeholder: "Select suggested articles",
                allowClear: true,
                minimumResultsForSearch: 0 // search enable
            });

            // $('#description').summernote({
            //     height: 300,
            //     dialogsInBody: true,
            //     disableResizeEditor: true,
            //     fontNames: [
            //         'Arial',
            //         'Helvetica',
            //         'Comic Sans MS',
            //         'Courier New',
            //         'Merriweather',
            //         'Times New Roman',
            //         'Verdana'
            //     ],

            // fontNamesIgnoreCheck: [
            //     'Arial',
            //     'Helvetica',
            //     'Comic Sans MS',
            //     'Courier New',
            //     'Merriweather',
            //     'Times New Roman',
            //     'Verdana'
            // ],
            //     toolbar: [
            //         ['style', ['style']],
            //         ['font', ['bold', 'italic', 'underline', 'strikethrough', 'superscript',
            //             'subscript', 'clear'
            //         ]],
            //         ['fontname', ['fontname']],
            //         ['fontsize', ['fontsize']],
            //         ['color', ['color']],
            //         ['para', ['ul', 'ol', 'paragraph', 'height']],
            //         ['insert', ['link', 'table', 'hr']],

            //     ]
            // });

            $('#description').summernote({
                height: 300,
                dialogsInBody: true,
                disableResizeEditor: true,
                codeviewFilter: false,
                codeviewIframeFilter: false,
                fontNames: [
                    'Arial',
                    'Helvetica',
                    'Comic Sans MS',
                    'Courier New',
                    'Merriweather',
                    'Times New Roman',
                    'Verdana',
                    'sans-serif'
                ],
                fontNamesIgnoreCheck: [
                    'Arial',
                    'Helvetica',
                    'Comic Sans MS',
                    'Courier New',
                    'Merriweather',
                    'Times New Roman',
                    'Verdana',
                    'sans-serif'
                ],
                toolbar: [
                    // ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline', 'strikethrough']],
                    ['fontname', ['fontname']],
                    // ['fontsize', ['fontsize']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link', 'table']]
                ],
                callbacks: {
                    onInit: function() {
                        // 🔥 FORCE LOAD CONTENT WITH STYLES
                        var content = `{!! old('description', $data->description ?? '') !!}`;
                        $('#description').summernote('code', content);
                    }
                }
            });

            function updateColor() {
                var selectedOption = $('#category').find('option:selected');

                // Get both data attributes
                var color = selectedOption.data('color');
                var titleColor = selectedOption.data('title_color');

                // Target each unique ID with the correct value
                $('#color-display').val(color);
                $('#title-color-display').val(titleColor); // <-- Corrected this line
            }
            $('#category').on('change', function() {
                updateColor();
            });
            updateColor();


            $('.first-level').addClass('in');

        })
        $(document).ready(function() {
            $('#school').select2({
                placeholder: "Select schools",
                allowClear: true
            });
            // $('#age').select2({
            //     placeholder: "Select age range",
            //     allowClear: true
            // });

        });

        $(document).ready(function() {
            // function toggleSessionFields() {
            //     let sessionType = $('#session_type').val();

            //     if (sessionType === 'online') {
            //         $('#session_link_container').show();
            //         $('#venue_container').hide();
            //     } else if (sessionType === 'offline') {
            //         $('#session_link_container').hide();
            //         $('#venue_container').show();
            //     } else if (sessionType === 'hybrid') {
            //         $('#session_link_container').show();
            //         $('#venue_container').show();
            //     } else {
            //         $('#session_link_container').hide();
            //         $('#venue_container').hide();
            //     }
            // }
            // function toggleSessionFields() {
            //     let sessionType = $('#session_type').val();

            //     if (sessionType === 'online') {
            //         $('#session_link_container').show();
            //         $('#venue_container').hide();
            //         $('#venue').val(''); // Clear venue
            //     } else if (sessionType === 'offline') {
            //         $('#session_link_container').hide();
            //         $('#venue_container').show();
            //         $('#link').val(''); // Clear link
            //     } else if (sessionType === 'hybrid') {
            //         $('#session_link_container').show();
            //         $('#venue_container').show();
            //     } else {
            //         $('#session_link_container').hide();
            //         $('#venue_container').hide();
            //         $('#link').val('');
            //         $('#venue').val('');
            //     }
            // }
            function toggleSessionFields() {
                let sessionType = $('#session_type').val();

                if (sessionType === 'online') {
                    $('#session_link_container').show();
                    $('#venue_container').hide();
                    $('#venue').val('');
                    $('#sessionDateTimeCard').show();
                } else if (sessionType === 'offline') {
                    $('#session_link_container').hide();
                    $('#link').val('');
                    $('#venue_container').show();
                    $('#sessionDateTimeCard').show();
                } else if (sessionType === 'hybrid') {
                    $('#session_link_container').show();
                    $('#venue_container').show();
                    $('#sessionDateTimeCard').show();
                } else if (sessionType === 'article') {
                    $('#session_link_container').hide();
                    $('#venue_container').hide();
                    $('#link').val('');
                    $('#venue').val('');
                    $('#sessionDateTimeCard').hide(); // <-- ye pura section hide hoga
                } else {
                    $('#session_link_container').hide();
                    $('#venue_container').hide();
                    $('#link').val('');
                    $('#venue').val('');
                    $('#sessionDateTimeCard').hide();
                }
            }



            // Run on page load in case of form repopulation
            toggleSessionFields();

            // Run on change event
            $('#session_type').change(function() {
                toggleSessionFields();
            });

            function validateTimeField() {
                const sessionDate = $('#session_date').val();
                const sessionTime = $('#session_time').val();
                const now = new Date();

                if (sessionDate === now.toISOString().split('T')[0]) {
                    const currentTime = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes()
                        .toString().padStart(2, '0');
                    // console.log(curentTime);
                    $('#session_time').attr('min', currentTime);
                } else {
                    $('#session_time').removeAttr('min');
                }
            }

            // Run once on page load
            validateTimeField();

            // When the date changes
            $('#session_date').on('change', function() {
                validateTimeField();
            });
            // Show/hide age range field based on user type
            function toggleAgeRange() {
                const userType = $('input[name="user_type"]:checked').val();
                if (userType === 'child') {
                    $('#ageRangeWrapper').show();
                } else {
                    $('#ageRangeWrapper').hide();
                    $('#age_range').val(''); // clear age if not child
                }
            }

            // Initial call on page load
            toggleAgeRange();

            // Event listener on radio change
            $('input[name="user_type"]').change(function() {
                toggleAgeRange();
            });

            function toggleIsFeatured() {
                const userType = $('input[name="user_type"]:checked').val();
                if (userType === 'child') {
                    $('#isFeaturedWrapper').hide();
                    $('#is_featured').val('');
                } else {
                    $('#isFeaturedWrapper').show();
                }
            }

            // Call on page load
            toggleIsFeatured();

            // Call on user type change
            $('input[name="user_type"]').change(function() {
                toggleIsFeatured();
            });

        });
    </script>
@endpush
