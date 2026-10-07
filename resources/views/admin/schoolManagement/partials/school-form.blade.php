@php
    $editing = isset($data);
    $schoolData = $data ?? null;
    $schoolValue = function ($field, $fallback = null) use ($editing, $schoolData) {
        return old($field, $editing ? $schoolData->{$field} : $fallback);
    };
@endphp
<style>
    .school-form-page { max-width:1120px; margin:0 auto; }
    .school-page-heading { display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:16px; margin-bottom:24px; }
    .school-page-heading h2 { font-size:26px; font-weight:700; color:#243b55; margin:0 0 8px; }
    .school-page-heading p { margin:0; color:#6b7d91; font-size:14px; }
    .school-form-section { border:1px solid #e0e7f0; border-radius:12px; background:#fff; padding:24px; margin-bottom:20px; }
    .school-section-heading { display:flex; gap:12px; align-items:flex-start; padding-bottom:18px; margin-bottom:20px; border-bottom:1px solid #eef2f7; }
    .school-section-icon { display:flex; align-items:center; justify-content:center; width:40px; height:40px; flex-shrink:0; border-radius:10px; background:#edf3fc; color:#3267b2; font-size:22px; }
    .school-section-heading h3 { font-size:18px; font-weight:700; color:#243b55; margin:0 0 5px; }
    .school-section-heading p { font-size:13px; color:#6b7d91; margin:0; }
    .school-form-page .form-group { margin-bottom:20px; }
    .school-form-page label { font-weight:600; font-size:14px; color:#33485f; margin-bottom:8px; }
    .school-form-page .form-control { min-height:44px; border-color:#dce4ee; border-radius:7px; font-size:14px; }
    .school-form-page .form-control:focus { border-color:#7aa9e0; box-shadow:0 0 0 3px rgba(61,117,185,.1); }
    .school-form-page .form-control[readonly] { background:#f4f7fb; color:#65778d; }
    .school-form-page small { display:block; margin-top:7px; line-height:1.5; }
    .school-form-required { color:#b84b49; }
    .school-import-box { padding:18px; background:#f8fafc; border:1px dashed #c9d6e6; border-radius:9px; }
    .school-import-actions { display:flex; flex-wrap:wrap; align-items:center; gap:12px; margin-top:14px; }
    .school-import-actions label { margin:0; font-weight:400; }
    .school-import-actions label:focus-within { outline:3px solid #9dc6f5; outline-offset:3px; }
    .school-file-name { font-size:13px; color:#65778d; overflow-wrap:anywhere; }
    .school-form-actions { display:flex; justify-content:flex-end; align-items:center; flex-wrap:wrap; gap:12px; padding:20px 0 8px; }
    .school-form-actions .btn { padding:11px 24px; border-radius:7px; }
    .school-form-page .alert { border-radius:8px; }
    @media(max-width:575px) { .school-form-section { padding:18px 16px; } .school-page-heading h2 { font-size:23px; } .school-form-actions .btn { flex:1; text-align:center; } }
</style>
<div class="school-form-page">
    <div class="school-page-heading">
        <div>
            <div class="small text-muted mb-2"><a href="{{ route('school.index') }}">School Management</a> / {{ $editing ? 'Edit School' : 'Add School' }}</div>
            <h2>{{ $editing ? 'Edit School' : 'Add School' }}</h2>
            <p>{{ $editing ? 'Update school details, account creation mode and subscription settings.' : 'Set up a school and choose how student accounts will be created.' }}</p>
        </div>
        <a href="{{ route('school.index') }}" class="btn btn-outline-secondary"><i class="mdi mdi-arrow-left mr-1" aria-hidden="true"></i>Back to schools</a>
    </div>
    @if ($errors->any()) <div class="alert alert-danger" role="alert">Please correct the highlighted fields and save again.</div> @endif
    @if (session('error')) <div class="alert alert-danger" role="alert">{{ session('error') }}</div> @endif
    @include('admin.schoolManagement.partials.teacher-import-report')
    <form id="{{ $editing ? 'schoolEditForm' : 'bannerForm' }}" action="{{ $editing ? route('school.update', $data->id) : route('school.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @if ($editing) @method('PUT') @endif
        <section class="school-form-section" aria-labelledby="school-details-heading">
            <div class="school-section-heading">
                <span class="school-section-icon"><i class="mdi mdi-school" aria-hidden="true"></i></span>
                <div><h3 id="school-details-heading">School details</h3><p>Basic information and the school's contact email.</p></div>
            </div>
            <div class="row">
                <div class="col-md-6"><div class="form-group">
                    <label for="school_name">School name <span class="school-form-required">*</span></label>
                    <input id="school_name" name="school_name" class="form-control @error('school_name') is-invalid @enderror" value="{{ old('school_name', $editing ? $data->name : '') }}" placeholder="Enter school name" required maxlength="100">
                    @error('school_name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div></div>
                <div class="col-md-6"><div class="form-group">
                    <label for="school_code">School code</label>
                    <input id="school_code" name="school_code" class="form-control" value="{{ $editing ? $data->school_code : $schoolCode }}" readonly>
                    <small class="text-muted">Unique code assigned to this school.</small>
                    @error('school_code') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div></div>
                <div class="col-md-6"><div class="form-group">
                    <label for="school_email">School email <span class="school-form-required">*</span></label>
                    <input type="email" id="school_email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ $schoolValue('email', '') }}" placeholder="school@example.com" required maxlength="100" {{ $editing ? 'readonly' : '' }}>
                    <small class="text-muted">Receives school notifications and student credentials.</small>
                    @error('email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div></div>
                @if ($editing)
                <div class="col-md-6"><div class="form-group">
                    <label for="school_status">Status <span class="school-form-required">*</span></label>
                    <select id="school_status" name="status" class="form-control" required>
                        <option value="active" {{ $schoolValue('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ $schoolValue('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div></div>
                @endif
            </div>
        </section>
        <section class="school-form-section" aria-labelledby="school-accounts-heading">
            <div class="school-section-heading">
                <span class="school-section-icon"><i class="mdi mdi-account-multiple" aria-hidden="true"></i></span>
                <div><h3 id="school-accounts-heading">Accounts &amp; capacity</h3><p>Choose the account relationship and manage available places.</p></div>
            </div>
            @include('admin.schoolManagement.partials.account-mode')
            <div class="row mt-3">
                <div class="col-md-6"><div class="form-group">
                    <label for="max_limit">Parent limit <span class="text-muted font-weight-normal">(optional)</span></label>
                    <input type="number" min="1" max="100000000" id="max_limit" name="max_limit" class="form-control" value="{{ $schoolValue('max_limit') }}" placeholder="Unlimited">
                    <small class="text-muted">Caps parent accounts only. Leave blank for unlimited.</small>
                    @error('max_limit') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div></div>
                <div class="col-md-6"><div class="form-group">
                    <label for="child_seat_limit">Child places <span class="text-muted font-weight-normal">(optional)</span></label>
                    <input type="number" min="1" max="100000000" id="child_seat_limit" name="child_seat_limit" class="form-control" value="{{ $schoolValue('child_seat_limit') }}" placeholder="Unlimited">
                    <small class="text-muted">{{ $editing ? 'Currently used: '.($childSeatsUsed ?? 0).'. Existing accounts are retained when reducing capacity.' : 'Total child places available to this school. Leave blank for unlimited.' }}</small>
                    @error('child_seat_limit') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div></div>
                <div class="col-md-6"><div class="form-group">
                    <label for="per_parent_child_limit">Children per parent <span class="text-muted font-weight-normal">(optional)</span></label>
                    <input type="number" min="1" max="50" id="per_parent_child_limit" name="per_parent_child_limit" class="form-control" value="{{ $schoolValue('per_parent_child_limit', 3) }}" placeholder="Unlimited">
                    <small class="text-muted">Leave blank for unlimited within the school's child places.</small>
                    @error('per_parent_child_limit') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div></div>
            </div>
        </section>
        <section class="school-form-section" aria-labelledby="school-subscription-heading">
            <div class="school-section-heading">
                <span class="school-section-icon"><i class="mdi mdi-credit-card-outline" aria-hidden="true"></i></span>
                <div><h3 id="school-subscription-heading">Subscription</h3><p>Set the subscription term and optional school price.</p></div>
            </div>
            <div class="row">
                <div class="col-md-6"><div class="form-group">
                    <label for="subscription_type">Subscription type <span class="school-form-required">*</span></label>
                    <select id="subscription_type" name="subscription_type" class="form-control" required>
                        <option value="">Select subscription type</option>
                        @foreach (['monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'yearly' => 'Yearly'] as $value => $label)
                            <option value="{{ $value }}" {{ $schoolValue('subscription_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('subscription_type') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div></div>
                <div class="col-md-6"><div class="form-group">
                    <label for="price">Price (SGD) <span class="text-muted font-weight-normal">(optional)</span></label>
                    <input type="number" step="0.01" min="0" max="999999.99" id="price" name="price" class="form-control" value="{{ $schoolValue('price') }}" placeholder="0.00">
                    <small class="text-muted">Displayed in the school's payment history.</small>
                    @error('price') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div></div>
            </div>
        </section>
        <section class="school-form-section" aria-labelledby="school-imports-heading">
            <div class="school-section-heading">
                <span class="school-section-icon"><i class="mdi mdi-file-excel" aria-hidden="true"></i></span>
                <div><h3 id="school-imports-heading">Account imports</h3><p>Optional spreadsheet uploads. Download a template before adding your data.</p></div>
            </div>
            <div class="row">
                <div class="col-md-12"><div class="school-import-box mb-3">
                    <label for="student_excel">Import parent list</label>
                    <p class="small text-muted mb-0">One parent account per row. Parents receive their login details by email.</p>
                    <div class="school-import-actions">
                        <label class="btn btn-outline-secondary" for="student_excel">Choose File<input type="file" class="sr-only" name="student_excel" id="student_excel" accept=".xlsx,.xls"></label>
                        <span id="student_excel_name" class="school-file-name" aria-live="polite">No file selected</span>
                        <a href="{{ route('download.sample.excel') }}" class="btn btn-outline-primary btn-sm"><i class="mdi mdi-download mr-1" aria-hidden="true"></i>Download parent template</a>
                    </div>
                    @error('student_excel') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
                </div></div>
                @if (config('scope.school_extras'))
                <div class="col-md-12"><div class="school-import-box">
                    <label for="staff_excel">Import staff / teachers</label>
                    <p class="small text-muted mb-0">Name and email are required. Phone details are optional; usernames are generated automatically.</p>
                    <div class="school-import-actions">
                        <label class="btn btn-outline-secondary" for="staff_excel">Choose File<input type="file" class="sr-only" name="staff_excel" id="staff_excel" accept=".xlsx,.xls"></label>
                        <span id="staff_excel_name" class="school-file-name" aria-live="polite">No file selected</span>
                        <a href="{{ route('download.sample.staff.excel') }}" class="btn btn-outline-primary btn-sm"><i class="mdi mdi-download mr-1" aria-hidden="true"></i>Download staff template</a>
                    </div>
                    @error('staff_excel') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
                </div></div>
                @endif
            </div>
            <p id="school-independent-import-note" class="text-muted small mb-0" hidden>Independent students are imported from School Details after saving the school.</p>
        </section>
        <div class="school-form-actions">
            <a href="{{ route('school.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary" id="school-save-button">{{ $editing ? 'Save Changes' : 'Create School' }}</button>
        </div>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    ['student_excel', 'staff_excel'].forEach(function (id) {
        const input = document.getElementById(id);
        if (!input) return;
        input.addEventListener('change', function () {
            document.getElementById(id + '_name').textContent = this.files.length ? this.files[0].name : 'No file selected';
        });
    });
    const mode = document.getElementById('account_mode');
    function showImportNote() {
        document.getElementById('school-independent-import-note').hidden = (mode.dataset.mode || mode.value) !== 'independent';
    }
    mode.addEventListener('change', showImportNote);
    showImportNote();
    document.getElementById('{{ $editing ? 'schoolEditForm' : 'bannerForm' }}').addEventListener('submit', function () {
        const button = document.getElementById('school-save-button');
        button.disabled = true;
        button.textContent = 'Saving…';
    });
});
</script>
