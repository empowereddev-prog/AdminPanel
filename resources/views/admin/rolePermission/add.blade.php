@extends('layout.headerFooter')

@push('styles')
    {{-- Styles have been adjusted for a more compact layout --}}
    <style>
        /* Custom styles for a more compact design */
        .access-permission-container {
            background-color: #f8f9fa;
            padding: 1.5rem;
            /* Reduced padding */
            border-radius: .5rem;
        }

        .permission-card {
            border-radius: .5rem;
            background-color: #fff;
            border: 1px solid #dee2e6;
        }

        .permission-card .card-header {
            background-color: #f1f3f5;

        }

        .permission-card .card-body {
            padding: 1.25rem;
            /* Adjusted padding */
        }

        .permission-item,
        .child-item {
            display: block;

        }

        .permission-item:last-child,
        .child-item:last-child {
            margin-bottom: 0;
        }

        .card-header .permission-item label {
            font-weight: 600;
            color: #212529;
            font-size: 14px;
            /* Controlled font size */
        }

        /* Reduced Indentation for children and options */
        .permission-item.parent-option,
        .child-item.child-name {
            padding-left: 1rem;
            /* Reduced indentation */
        }

        .child-item.child-option {
            padding-left: 2rem;
            /* Reduced indentation */
        }

        .custom-checkbox {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            /* Reduced gap between checkbox and label */
        }

        .custom-checkbox label {
            margin-bottom: 0;
            font-weight: 500;
            color: #555;
            font-size: 12px;
            /* Controlled font size for a cleaner look */
        }
    </style>
@endpush

@section('content')
    <div class="content-wrapper">
        <div class="content">
            {{-- Page Title and Breadcrumbs --}}
            <div class="title_left">
                <h2>Add Role</h2>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <ul class="admin-breadcrumb">
                        <li><a href="{{ route('role.rolePermission') }}">Role Management</a></li>
                        <li class="active"><a href="#">Add Details</a></li>
                    </ul>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-12">
                    <form action="{{ route('rolePermission.store') }}" enctype="multipart/form-data" method="post"
                        onsubmit="return validateForm()">
                        @csrf
                        @method('Post')

                        {{-- Role Details Card --}}
                        <div class="card card-default mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Role Details</h5>
                            </div>
                            <div class="card-body">
                                {{-- Name, Email, Phone inputs --}}
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="name">Name</label>
                                            <input type="text" id="name" class="form-control"
                                                placeholder="Enter Your Name" name="name" value="{{ old('name') }}">
                                            @error('name')
                                                <div class="text-danger mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="email">Email</label>
                                            <input type="email" id="email" class="form-control"
                                                placeholder="Enter your email" name="email" value="{{ old('email') }}">
                                            @error('email')
                                                <p class="text-danger mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="phone_no">Phone</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend" style="width: 25%;">
                                            <select name="code" id="code" class="form-control select2 w-100">
                                                @foreach ($countrycode as $code)
                                                    <option value="{{ $code->country_code }}"
                                                        {{ old('code', request('code', '+65')) == $code->country_code ? 'selected' : '' }}>
                                                        {{ $code->country }} ({{ $code->country_code }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <input type="text" id="phone_no" class="form-control" name="phone_no"
                                            placeholder="Enter Your Number" value="{{ old('phone_no') }}">
                                    </div>
                                    @error('phone_no')
                                        <p class="text-danger mt-1 small">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Access Permissions Section --}}
                        <div class="access-permission-container">
                            <h5 class="mb-4">Access Permission:</h5>
                            @php
                                // Assuming $Permission_user might be empty on add page, but logic remains
                                $permissionUser = $Permission_user ?? [];
                                foreach ($permissionUser as $value) {
                                    $is_m[$value['menu_id']]['modify'] = $value['is_modify'] ?? '';
                                    $is_m[$value['menu_id']]['view'] = $value['is_view'] ?? '';
                                }
                                use Illuminate\Support\Arr;
                                $menuid = Arr::pluck($permissionUser, 'menu_id');
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
                                $is_complex = in_array($main_menu['id'], [2,3,5,13,15,19,23,24,25,
                                26,27,
                                28
                            ]);
                            ?>
                                <div class="col-md-6 mb-4">
                                    {{-- We use a DIV with parent-li class to maintain JS compatibility --}}
                                    <div id="_<?php echo $main_menu['id']; ?>" class="card h-100 permission-card parent-li">
                                        <div class="card-header">
                                            <div class="permission-item custom-checkbox">
                                                @if (!$is_complex && !$has_children)
                                                    <input class="taskrow_id1 icheckbox_flat-green parent-chk"
                                                        type="checkbox" <?php echo in_array($main_menu['id'], $menuid) ? 'checked' : ''; ?> name="parent[]"
                                                        value="<?php echo $main_menu['id']; ?>" id="id_<?php echo $main_menu['id']; ?>">
                                                @else
                                                    <input class="child-chk menu-sub-<?php echo $main_menu['id']; ?> menu-child"
                                                        data-id="<?php echo $main_menu['id']; ?>" type="checkbox" <?php echo in_array($main_menu['id'], $menuid) ? 'checked' : ''; ?>
                                                        name="<?php echo $menuname; ?>[]" value="<?php echo $main_menu['id']; ?>"
                                                        id="id_<?php echo $main_menu['id']; ?>">
                                                @endif
                                                <label for="id_<?php echo $main_menu['id']; ?>"><?php echo $main_menu['menu_name']; ?></label>

                                            </div>
                                        </div>
                                        <div class="card-body">
                                            @if ($has_children)
                                                {{-- RENDER CHILDREN --}}
                                                <?php foreach ($child_menu as $child) { ?>
                                                <div class="child-item child-name custom-checkbox">
                                                    <input class="child-chk menu-sub-<?php echo $child['id']; ?> menu-child"
                                                        data-id="<?php echo $child['id']; ?>" type="checkbox" <?php echo in_array($child['id'], $menuid) ? 'checked' : ''; ?>
                                                        name="<?php echo $menuname; ?>[]" value="<?php echo $child['id']; ?>"
                                                        id="id_<?php echo $child['id']; ?>">
                                                    <label for="id_<?php echo $child['id']; ?>"><?php echo $child['menu_name']; ?></label>
                                                </div>
                                                <div class="child-item child-option custom-checkbox">
                                                    <input class="child-chk menu-view menu-view-<?php echo $child['id']; ?>"
                                                        data-id="<?php echo $child['id']; ?>" type="checkbox" <?php echo ($is_m[$child['id']]['view'] ?? '') == 'yes' ? 'checked' : ''; ?>
                                                        name="<?php echo $menuname; ?>_view[]" value="yes">
                                                    <label>View</label>
                                                </div>
                                                <div class="child-item child-option custom-checkbox">
                                                    <input class="c-mod menu-modify-<?php echo $child['id']; ?>"
                                                        data-id="<?php echo $child['id']; ?>" type="checkbox" <?php echo ($is_m[$child['id']]['modify'] ?? '') == 'yes' ? 'checked' : ''; ?>
                                                        name="<?php echo $menuname; ?>_modify[]" value="yes">
                                                    <input type="hidden" class="check-{{ $child['id'] }}"
                                                        name="{{ $menuname }}_modify1[]" value="yes">
                                                    <label>Modify</label>
                                                </div>
                                                <?php } ?>
                                            @else
                                                {{-- RENDER PARENT'S OWN VIEW/MODIFY --}}
                                                @if ($is_complex)
                                                    <div class="permission-item parent-option custom-checkbox">
                                                        <input
                                                            class="child-chk menu-view menu-view-{{ $main_menu['id'] }}"
                                                            data-id="{{ $main_menu['id'] }}" type="checkbox"
                                                            name="{{ $menuname }}_view[]" value="yes"
                                                            {{ ($is_m[$main_menu['id']]['view'] ?? '') == 'yes' ? 'checked' : '' }}>
                                                        <label>View</label>
                                                    </div>

                                                    <div class="permission-item parent-option custom-checkbox">
                                                        <input class="c-mod menu-modify-{{ $main_menu['id'] }}"
                                                            data-id="{{ $main_menu['id'] }}" type="checkbox"
                                                            name="{{ $menuname }}_modify[]" value="yes"
                                                            {{ ($is_m[$main_menu['id']]['modify'] ?? '') == 'yes' ? 'checked' : '' }}>
                                                        <input type="hidden" class="check-{{ $main_menu['id'] }}"
                                                            name="{{ $menuname }}_modify1[]"
                                                            value="{{ ($is_m[$main_menu['id']]['modify'] ?? '') == 'yes' ? 'yes' : 'no' }}">
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
                                <button type="submit" class="btn btn-primary btn-pill mr-2">Submit</button>
                                <a href="{{ route('role.rolePermission') }}" class="btn btn-light"
                                    style="border-radius: 50px;">Cancel</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
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

            // Function to handle checking/unchecking all related checkboxes within a card
            function updateCardCheckboxes($card, checked) {
                // Select all child-chk, menu-view, and c-mod checkboxes within the card
                $card.find('.menu-child, .menu-view, .c-mod').prop('checked', checked);
                
                // Update hidden input values for modify checkboxes
                $card.find('.c-mod').each(function() {
                    let id = $(this).data('id');
                    $('.check-' + id).val(checked ? 'yes' : 'no');
                });
            }

            // --- Main Logic for Parent Checkbox Interaction ---
            // When a parent checkbox (top of the card, with parent-chk class) is clicked
            $('.parent-chk').on('change', function() {
                const $card = $(this).closest('.permission-card');
                const checked = $(this).is(':checked');
                updateCardCheckboxes($card, checked);
            });

            // --- Logic for Child Checkbox Interaction ---
            // When a menu-child (either a main menu with children or a direct child of a main menu) is clicked
            $('.menu-child').on('change', function() {
                let $this = $(this);
                let currentId = $this.data('id');
                let $parentCard = $this.closest('.permission-card');
                
                if ($this.is(':checked')) {
                    // If a child is checked, ensure the main parent-chk is checked
                    $parentCard.find('.parent-chk').prop('checked', true);

                    // Also check the specific view/modify options if they exist for this child, but let c-mod handle its hidden input
                    $parentCard.find('.menu-view-' + currentId).prop('checked', true);
                    // Only check modify if the menu-child itself is not one of the complex parents (which handle their children's modify separately)
                    if (![5, 15, 19].includes(currentId)) {
                        $parentCard.find('.menu-modify-' + currentId).prop('checked', true);
                        $parentCard.find('.check-' + currentId).val('yes');
                    }

                } else {
                    // If a child is unchecked, uncheck its view and modify options
                    $parentCard.find('.menu-view-' + currentId).prop('checked', false);
                    $parentCard.find('.menu-modify-' + currentId).prop('checked', false);
                    $parentCard.find('.check-' + currentId).val('no');

                    // If no other menu-child (including itself if it was a complex parent) in this card is checked, uncheck the parent-chk
                    if ($parentCard.find('.menu-child:checked').length === 0) {
                        $parentCard.find('.parent-chk').prop('checked', false);
                    }
                }

                // Handle cascading for specific complex menu groups (Content, Management, Quiz)
                // When a top-level menu-child (like Content, Management, Quiz) is checked/unchecked,
                // also affect its immediate sub-children.
                if ([5, 15, 19].includes(currentId)) {
                    let childIds = [];
                    if (currentId === 5) childIds = [6, 7, 8]; // Content children
                    else if (currentId === 15) childIds = [16, 17, 18]; // Management children
                    else if (currentId === 19) childIds = [20, 21, 22]; // Quiz children

                    childIds.forEach(menuId => {
                        $('.menu-child[data-id="' + menuId + '"]').prop('checked', $this.is(':checked'));
                        $('.menu-view-' + menuId).prop('checked', $this.is(':checked'));
                        $('.menu-modify-' + menuId).prop('checked', $this.is(':checked'));
                        $('.check-' + menuId).val($this.is(':checked') ? 'yes' : 'no');
                    });
                }
            });

            // --- Logic for Modify Checkbox Interaction ---
            // Modify auto-checks View and its direct menu-child
            $('.c-mod').on('change', function() {
                var id = $(this).data('id');
                if ($(this).prop('checked')) {
                    // When modify is checked, view must be checked
                    $('.menu-view-' + id).prop('checked', true);
                    // The main menu item for this ID must also be checked
                    $('.menu-child[data-id="' + id + '"]').prop('checked', true);
                    // Ensure the card's main parent-chk is also checked
                    $(this).closest('.permission-card').find('.parent-chk').prop('checked', true);
                    $('.check-' + id).val('yes');
                } else {
                    $('.check-' + id).val('no');
                }
            });

            // --- Logic for View Checkbox Interaction ---
            // Unchecking View disables Modify and potentially unchecks menu-child
            $('.menu-view').on('change', function() {
                var id = $(this).data('id');
                if (!$(this).prop('checked')) {
                    // If view is unchecked, modify must also be unchecked
                    $('.menu-modify-' + id).prop('checked', false);
                    $('.check-' + id).val('no');

                    // If view is unchecked, and modify is also unchecked, uncheck the main menu-child
                    // (This prevents the menu-child from remaining checked if both its view/modify are off)
                    if (!$('.c-mod[data-id="' + id + '"]').is(':checked')) {
                        $('.menu-child[data-id="' + id + '"]').prop('checked', false);
                    }

                    // Check if any other child or its view/modify options in this card are checked.
                    // If none are, uncheck the card's main parent-chk.
                    const $parentCard = $(this).closest('.permission-card');
                    if ($parentCard.find('.menu-child:checked, .menu-view:checked, .c-mod:checked').length === 0) {
                        $parentCard.find('.parent-chk').prop('checked', false);
                    }

                } else {
                    // If view is checked, ensure the menu-child for this item and the card's parent-chk are also checked
                    $('.menu-child[data-id="' + id + '"]').prop('checked', true);
                    $(this).closest('.permission-card').find('.parent-chk').prop('checked', true);
                }
            });
        });
    </script>
@endpush