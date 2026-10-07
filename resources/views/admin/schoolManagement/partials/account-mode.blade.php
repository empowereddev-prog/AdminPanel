@php
    $currentMode = old('account_mode', isset($data) ? ($data->account_mode ?? 'linked') : 'linked');
    $canChangeMode = (int) auth()->user()->user_role_id === 1;
@endphp
<div class="form-group mt-3">
    <label for="account_mode">Account Mode</label>
    @if ($canChangeMode)
        <select name="account_mode" id="account_mode" class="form-control">
            <option value="linked" {{ $currentMode === 'linked' ? 'selected' : '' }}>Parent-linked Child Accounts</option>
            <option value="independent" {{ $currentMode === 'independent' ? 'selected' : '' }}>Independent Child Accounts</option>
        </select>
    @else
        <input id="account_mode" type="text" class="form-control" data-mode="{{ $currentMode }}"
            value="{{ $currentMode === 'independent' ? 'Independent Child Accounts' : 'Parent-linked Child Accounts' }}" readonly>
    @endif
    <small class="text-muted">Linked: children belong to parent accounts. Independent: school creates students without a parent link. Applies to future creation; existing accounts remain intact.</small>
    <div id="child-mode-notice" class="alert alert-info mt-2" hidden>
        Save the school, then use Import Students on School Details to create independent accounts.
    </div>
    @error('account_mode') <div class="text-danger small">{{ $message }}</div> @enderror
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const mode = document.getElementById('account_mode');
    function updateModeFields() {
        const child = (mode.dataset.mode || mode.value) === 'independent';
        ['student_excel', 'max_limit', 'per_parent_child_limit'].forEach(function (name) {
            const field = document.querySelector('[name="' + name + '"]');
            if (!field) return;
            const group = field.closest('[class*="col-md-"]');
            if (group) group.hidden = child;
            field.disabled = child;
        });
        document.getElementById('child-mode-notice').hidden = !child;
    }
    mode.addEventListener('change', updateModeFields);
    updateModeFields();
});
</script>
