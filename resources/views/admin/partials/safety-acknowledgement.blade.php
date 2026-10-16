@php
    // One notice replaced the old two-step consent flow, and accepting it stamps
    // popup_1 and popup_2 together. It counts as acknowledged only when both
    // are set; show the later time.
    $ackFirst = $ackUser->popup_1_updated_at;
    $ackSecond = $ackUser->popup_2_updated_at;
    $ackAt = ($ackFirst && $ackSecond) ? ($ackFirst->greaterThan($ackSecond) ? $ackFirst : $ackSecond) : null;
@endphp
<div class="mb-2">
    <strong>Safety information acknowledged at:</strong>
    @if ($ackAt)
        <span class="text-success">{{ $ackAt->format('d M Y, h:i A') }}</span>
    @else
        <span class="text-danger">Not Verified</span>
    @endif
</div>
