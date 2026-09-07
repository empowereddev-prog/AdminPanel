@extends('layout.headerFooter')
@section('content')
<div class="content-wrapper">
    <div class="content">
        <div class="row">
            <div class="col-xl-12">
                <div class="card card-default">
                    <div class="card-header">
                        <h2>Upload Image</h2>
                    </div>
                    <div class="card-body">
                        <form action="{{ url('admin/product-image-store') }}" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="product_id" value="{{ $id }}">
                            @csrf
                            <div class="row gx-0">
                                <div class="col-sm-6">
                                <div class="upload-img-area">
                                    <div class="form-group doc-add-container dis-boot">
                                        <label class="file-input" for="file">Select Image(s)<span class="astric">*</span></label>
                                        <input type="file" name="image_name[]" id="file" accept="image/*" multiple onchange="updateSelectedFiles(this)">
                                        @if ($errors->has('image_name'))
                                        <div class="text-danger small mt-1">
                                            {{$errors->first('image_name') }}
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                
                                <p><i><strong>Note:</strong> Please select image formats such as JPEG, PNG, JPG, GIF, and SVG.</i></p>
                            </div>

                                </div>
                                <div class="col-sm-6">
                                    <div class="upload-button-section">
                                    <button type="submit" class="btn btn-primary btn-pill">Upload</button>
                            <a href="{{ route('products.index') }}"><button type="button" class="btn btn-light btn-pill">Back</button></a>

                                    </div>
                             
                                </div>
                            </div>
                           
                           
                            <div id="selectedFiles"></div>
                        </form>
                        <hr>
                        <form action="{{ url('/admin/product-images-destroy') }}" method="post" id="deleteForm">
                            @csrf
                            <div class="select-all-item">
                            <div class="image-item">
                                    <span>
                                        <input type="checkbox" id="selectAll" class="image-checkbox">
                                        <label for="selectAll">Select All</label>
                                    </span>
                                </div>
                            </div>
                            <div class="uploaded-images">
                                @if($images->isEmpty())
                                <p>No images uploaded yet.</p>
                                @else
                               
                                @foreach($images as $image)
                                <div class="image-item">
                                    <span>
                                        <input type="checkbox" name="selectedImages[]" value="{{ $image->id }}" class="image-checkbox">
                                    </span>
                                    <img src="{{ asset('uploads/upload_product_images/' . $image->image_name) }}" alt="{{ $image->original_image_name }}" class="image-thumbnail" height="25%" width="25%">
                                </div>
                                @endforeach
                                @endif
                            </div>
                            <div class="image-upload-delete-btn" style="display: none;">
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete the selected images?')">Delete Selected</button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

<script>
    function updateSelectedFiles(input) {
        var selectedFilesDiv = document.getElementById('selectedFiles');
        selectedFilesDiv.innerHTML = ''; // Clear the previous file names

        for (var i = 0; i < input.files.length; i++) {
            var file = input.files[i];
            var fileName = document.createElement('p');
            fileName.textContent = file.name;
            selectedFilesDiv.appendChild(fileName);
        }
    }

    function toggleDeleteButtonVisibility() {
        var deleteButtonContainer = document.querySelector('.image-upload-delete-btn');
        var checkboxes = document.querySelectorAll('.image-checkbox:not(#selectAll)');
        var anyCheckboxChecked = Array.from(checkboxes).some(checkbox => checkbox.checked);

        if (anyCheckboxChecked) {
            deleteButtonContainer.style.display = 'block';
        } else {
            deleteButtonContainer.style.display = 'none';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        var selectAllCheckbox = document.getElementById('selectAll');
        var checkboxes = document.querySelectorAll('.image-checkbox:not(#selectAll)');

        selectAllCheckbox.addEventListener('change', function() {
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = selectAllCheckbox.checked;
            });
            toggleDeleteButtonVisibility();
        });

        checkboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                toggleDeleteButtonVisibility();
                if (!checkbox.checked) {
                    selectAllCheckbox.checked = false;
                }
            });
        });

        toggleDeleteButtonVisibility();
    });
</script>