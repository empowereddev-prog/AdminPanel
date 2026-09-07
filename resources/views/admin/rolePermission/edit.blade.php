@extends('layout.headerFooter')

@push('styles')
{{-- Styles have been adjusted for a more compact layout --}}
<style>
    /* Custom styles for a more compact design */
    .access-permission-container {
        background-color: #f8f9fa;
        padding: 1.2rem; /* Reduced padding */
        border-radius: .5rem;
    }
    
    .permission-card {
        border-radius: .5rem;
        background-color: #fff;
        border: 1px solid #dee2e6;
    }
    .permission-card .card-header {
        background-color: #e0eaf3;
        
    }

    .permission-card .card-body {
        padding: 1.25rem; /* Adjusted padding */
    }
    
    .permission-item, .child-item {
        display: block;
          /* Reduced margin */
    }
    .permission-item:last-child, .child-item:last-child {
        margin-bottom: 0;
    }
    
    .card-header .permission-item label {
        font-weight: 600;
        color: #212529;
        font-size: 14px;  
    }
    
    /* Reduced Indentation for children and options */
    .permission-item.parent-option,
    .child-item.child-name {
        padding-left: 1rem;  
    }

    .child-item.child-option {
        padding-left: 2rem; 
    }

    .custom-checkbox {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;  
    }
    .custom-checkbox label {
        margin-bottom: 0;
        font-weight: 500;
        color: #555;
        font-size: 12px; 
    }

</style>
@endpush

@section('content')
<div class="content-wrapper">
    <div class="content">
        {{-- Page Title and Breadcrumbs --}}
        <div class="title_left">
            <h2>Edit Role</h2>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <ul class="admin-breadcrumb">
                    <li><a href="{{ route('role.rolePermission') }}">Role Management</a></li>
                    <li class="active"><a href="#">Edit Role</a></li>
                </ul>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-12">
                @if(session('message'))
                    <div class="alert alert-danger">{{ session('message') }}</div>
                @endif
                
                <form action="{{ route('rolePermission.update', $user->id) }}" enctype="multipart/form-data" method="POST" onsubmit="return validateForm()">
                    @csrf
                    @method('PUT')
                    <input type="hidden" value="{{ $user->id }}" name="user_id">
                    <input type="hidden" name="user_type" value="2">

                    {{-- Role Details Card --}}
                    <div class="card card-default mb-4">
                        <div class="card-header"><h5 class="card-title mb-0">Role Details</h5></div>
                        <div class="card-body">
                            {{-- Name, Email, Phone inputs --}}
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name">Name</label>
                                        <input type="text" id="name" class="form-control" placeholder="Enter Your Name" name="name" value="{{ $user->name ?? old('name') }}">
                                        @error('name')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email</label>
                                        <input type="email" id="email" class="form-control" placeholder="Enter your email" name="email" value="{{ $user->email ?? old('email') }}" readonly>
                                        @error('email')<p class="text-danger mt-1">{{ $message }}</p>@enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="phone_no">Phone</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend" style="width: 35%;">
                                                <select name="code" id="code" class="form-control select2 w-100">
                                                    @foreach ($countrycode as $code)
                                                    <option value="{{ $code->country_code }}" {{ ($user->country_code ?? old('code')) == $code->country_code ? 'selected' : '' }}>
                                                        {{ $code->country }} ({{ $code->country_code }})
                                                    </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <input type="text" id="phone_no" class="form-control" name="phone_no" placeholder="Enter Your Number" value="{{ $user->phone_no ?? old('phone_no') }}">
                                        </div>
                                        @error('phone_no')<p class="text-danger mt-1 small">{{ $message }}</p>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="status">Status</label>
                                        <select name="status" class="form-control" id="status">
                                            <option value="active" {{ ($user->status ?? old('status')) == 'active' ? 'selected' : '' }}>Active</option>
                                            <option value="inactive" {{ ($user->status ?? old('status')) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                        </select>
                                        @error('status')<div class="text-danger mt-1 small">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Access Permissions Section --}}
                    <div class="access-permission-container">
                        <h5 class="mb-4">Access Permission:</h5>
                        @php
                            foreach ($Permission_user as $value) {
                                $is_m[$value['menu_id']]['modify'] = $value['is_modify'] ?? '';
                                $is_m[$value['menu_id']]['view'] = $value['is_view'] ?? '';
                            }
                            use Illuminate\Support\Arr;
                            $menuid = Arr::pluck($Permission_user, 'menu_id');
                            $data_menu = getMenuData();
                        @endphp

                        <div class="row">
                            <?php
                            foreach ($data_menu as $main_menu) {
                                if (in_array($main_menu['id'], ['1', '4', '11', '12', '14'])) continue;

                                switch ($main_menu['id']) {
                                    case 2:  $menuname = 'user_management'; break;
                                    case 3:  $menuname = 'school_management'; break;
                                    case 5:  $menuname = 'content'; break;
                                    case 13: $menuname = 'avatar'; break;
                                    case 15: $menuname = 'management'; break;
                                    case 19: $menuname = 'quiz_management'; break;
                                    case 23: $menuname = 'payment'; break;
                                    case 24: $menuname = 'mood_tracker'; break;
                                    case 25: $menuname = 'child_mood_tracker'; break;
                                    case 26: $menuname = 'product'; break;
                                    case 27: $menuname = 'meet_team'; break;
                                    case 28: $menuname = 'audit_log'; break;
                                    default: $menuname = 'misc';
                                }
                                $child_menu = menu($main_menu['id']);
                                $has_children = !empty($child_menu);
                                $is_complex = in_array($main_menu['id'], [2,3,5,13,15,19,23,24,25,26,27,28]);
                            ?>
                            <div class="col-md-6 mb-4">
                                <div id="_<?php echo $main_menu['id']; ?>" class="card h-100 permission-card parent-li">
                                    <div class="card-header">
                                        <div class="permission-item custom-checkbox">
                                            @if(!$is_complex && !$has_children)
                                                <input class="taskrow_id1 icheckbox_flat-green parent-chk" type="checkbox" <?php echo in_array($main_menu['id'], $menuid) ? 'checked' : ''; ?> name="parent[]" value="<?php echo $main_menu['id']; ?>" id="id_<?php echo $main_menu['id']; ?>">
                                            @else
                                                <input class="child-chk menu-sub-<?php echo $main_menu['id']; ?> menu-child" data-id="<?php echo $main_menu['id']; ?>" type="checkbox" <?php echo in_array($main_menu['id'], $menuid) ? 'checked' : ''; ?> name="<?php echo $menuname; ?>[]" value="<?php echo $main_menu['id']; ?>" id="id_<?php echo $main_menu['id']; ?>">
                                            @endif
                                            <label for="id_<?php echo $main_menu['id']; ?>"><?php echo $main_menu['menu_name']; ?></label>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        @if($has_children)
                                            {{-- RENDER CHILDREN --}}
                                            <?php foreach ($child_menu as $child) { ?>
                                                <div class="child-item child-name custom-checkbox">
                                                    <input class="child-chk menu-sub-<?php echo $child['id']; ?> menu-child" data-id="<?php echo $child['id']; ?>" type="checkbox" <?php echo in_array($child['id'], $menuid) ? 'checked' : ''; ?> name="<?php echo $menuname; ?>[]" value="<?php echo $child['id']; ?>" id="id_<?php echo $child['id']; ?>">
                                                    <label for="id_<?php echo $child['id']; ?>"><?php echo $child['menu_name']; ?></label>
                                                </div>
                                                <div class="child-item child-option custom-checkbox">
                                                    <input class="child-chk menu-view menu-view-<?php echo $child['id']; ?>" data-id="<?php echo $child['id']; ?>" type="checkbox" <?php echo ($is_m[$child['id']]['view'] ?? '') == 'yes' ? 'checked' : ''; ?> name="<?php echo $menuname; ?>_view[]" value="yes">
                                                    <label>View</label>
                                                </div>
                                                <div class="child-item child-option custom-checkbox">
                                                    <input class="c-mod menu-modify-<?php echo $child['id']; ?>" data-id="<?php echo $child['id']; ?>" type="checkbox" <?php echo ($is_m[$child['id']]['modify'] ?? '') == 'yes' ? 'checked' : ''; ?> name="<?php echo $menuname; ?>_modify[]" value="yes">
                                                    <input type="hidden" class="check-<?php echo $child['id']; ?>" name="<?php echo $menuname; ?>_modify1[]" value="{{ (($is_m[$child['id']]['modify'] ?? '') == 'yes') ? 'yes' : 'no' }}">
                                                    <label>Modify</label>
                                                </div>
                                            <?php } ?>
                                        @else
                                            {{-- RENDER PARENT'S OWN VIEW/MODIFY --}}
                                            @if($is_complex)
                                                <div class="permission-item parent-option custom-checkbox">
                                                    <input class="child-chk menu-view menu-view-<?php echo $main_menu['id']; ?>" data-id="<?php echo $main_menu['id']; ?>" type="checkbox" <?php echo ($is_m[$main_menu['id']]['view'] ?? '') == 'yes' ? 'checked' : ''; ?> name="<?php echo $menuname; ?>_view[]" value="yes">
                                                    <label>View</label>
                                                </div>
                                                <div class="permission-item parent-option custom-checkbox">
                                                    <input class="c-mod menu-modify-<?php echo $main_menu['id']; ?>" data-id="<?php echo $main_menu['id']; ?>" type="checkbox" <?php echo ($is_m[$main_menu['id']]['modify'] ?? '') == 'yes' ? 'checked' : ''; ?> name="<?php echo $menuname; ?>_modify[]" value="yes">
                                                    <input type="hidden" class="check-<?php echo $main_menu['id']; ?>" name="<?php echo $menuname; ?>_modify1[]" value="{{ (($is_m[$main_menu['id']]['modify'] ?? '') == 'yes') ? 'yes' : 'no' }}">
                                                    <label>Modify</label>
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                    

                    <div class="form-footer mt-4 text-right">
                        <button type="submit" class="btn btn-primary btn-pill mr-2">Update</button>
                        <a href="{{ route('role.rolePermission') }}" class="btn btn-light" style="border-radius: 50px;">Cancel</a>
                    </div>
                </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- Scripts remain the same as they handle the logic correctly --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
<script>
    function validateForm() {
        var checkboxes = document.querySelectorAll('.child-chk, .parent-chk');
        var isChecked = false;
        checkboxes.forEach(function(checkbox) {
            if (checkbox.checked) {
                isChecked = true;
            }
        });
        if (!isChecked) {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: 'At least one permission must be selected.'
            });
            return false;
        }
        return true;
    }

   $(document).ready(function() {
        $('#code').select2();
        
        // Checkbox logic for parent-child interactions
        $('.menu-child').click(function(){
            var id = $(this).data('id');
            var $parentLi = $(this).closest('.parent-li');
            var $parentChk = $parentLi.find('.parent-chk');
             let parentId = [6, 7, 8].includes(id) ? 5 : [16, 17, 18].includes(id) ? 15 : [20, 21, 22].includes(id) ? 19 : null;
            
            if($(this).prop("checked")){
                $('.menu-view-' + id).prop("checked",true);
                $('.menu-modify-' + id).prop("checked",true);
                $parentChk.prop("checked", true);
                $('.check-' + id).val('yes');
                if(parentId) {
                    $('.check-' + parentId).val('yes');
                }
            }
            else{
                var parant=$(this).closest('.parent-li').find('.parent-chk').attr('id');
                setTimeout(function () {
                    var checkedNum = $('#'+parant).closest('.parent-li').find('input[type=checkbox]:checked').length;
                    if(checkedNum == 1 || checkedNum==0 ){
                        $('#'+parant).closest('.parent-li').find('.parent-chk').prop("checked",false);
                    }
                }, 400);
                $('.menu-view-' + id).prop("checked",false);
                $('.menu-modify-' + id).prop("checked",false);
                $('.check-' + id).val('no');
            }
        });

        $('.menu-child').click(function(){
            var id = $(this).data('id');
            if (id === 5 || id === 15 || id === 19) {
                let childIds = id === 5 ? [6, 7, 8] : id === 15 ? [16, 17, 18] : [20, 21, 22];    
                if ($(this).prop("checked")) {
                    childIds.forEach(menuId => {
                        $('.menu-child[data-id="' + menuId + '"]').prop("checked", true);
                        $('.menu-view-' + menuId).prop("checked", true);
                        $('.menu-modify-' + menuId).prop("checked", true);
                        $('.check-' + menuId).val('yes');
                    });
                } else {
                    childIds.forEach(menuId => {
                        $('.menu-child[data-id="' + menuId + '"]').prop("checked", false);
                        $('.menu-view-' + menuId).prop("checked", false);
                        $('.menu-modify-' + menuId).prop("checked", false);
                        $('.check-' + menuId).val('no');
                    });
                }
            } 
            else if ([6, 7, 8].includes(id) || [16, 17, 18].includes(id) || [20, 21, 22].includes(id)) {
                let parentId = [6, 7, 8].includes(id) ? 5 : [16, 17, 18].includes(id) ? 15 : 19;     
                if ($(this).prop("checked")) {
                    $('.menu-child[data-id="' + parentId + '"]').prop("checked", true);
                } else {
                    let childCheckboxes = (parentId === 5) ? [6,7,8] : (parentId === 15) ? [16,17,18] : [20,21,22];
                    let anyChecked = childCheckboxes.some(menuId => $('.menu-child[data-id="' + menuId + '"]').prop("checked"));
                    if (!anyChecked) {
                        $('.menu-child[data-id="' + parentId + '"]').prop("checked", false);
                    }
                }
            }
        });
        
        $('.c-mod').click(function(){
            var id = $(this).data('id');
            if ($(this).prop('checked')) {
                $('.menu-view-' + id).prop('checked', true);
                // Also check the main checkbox for this item
                if(!$('.menu-sub-' + id).prop('checked')){
                   $('.menu-sub-' + id).prop('checked', true).trigger('click');
                }
            }
        });

        $('.menu-view').click(function(){
             var id = $(this).data('id');
             if (!$(this).prop('checked')) {
                  $('.menu-modify-' + id).prop('checked', false);
             }
        });
   });
</script>
@endpush