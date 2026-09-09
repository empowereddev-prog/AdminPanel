<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Share</h5>
    </div>
    <div class="card-body">
        @php
            $shareUrl = $canonicalUrl ?? \App\Services\DeepLinkService::canonicalUrl($deeplinkType, (int) $shareId);
            $schemeUrl = \App\Services\DeepLinkService::customSchemeUrl($deeplinkType, (int) $shareId);
            $isPublished = ($shareStatus ?? 'inactive') === 'active';
        @endphp
        <p class="text-muted mb-2">
            {{ $isPublished ? 'This HTTPS link opens the app after Universal / App Links are verified.' : 'URL is reserved. Resolve API returns unpublished until status is Active.' }}
        </p>
        <label>Canonical link</label>
        <div class="input-group mb-3">
            <input type="text" class="form-control" id="deeplink-canonical-{{ $deeplinkType }}-{{ (int) $shareId }}" readonly value="{{ $shareUrl }}">
            <button type="button" class="btn btn-outline-primary" id="deeplink-copy-{{ $deeplinkType }}-{{ (int) $shareId }}">Copy</button>
        </div>
        <p class="small mb-1">Debug scheme (device with the app installed): <code>{{ $schemeUrl }}</code></p>
        <p class="small text-muted mb-0">Do not put tokens in this URL. Audience and publish status are enforced by the app after login.</p>
    </div>
</div>
<script>
    (function () {
        var btn = document.getElementById('deeplink-copy-{{ $deeplinkType }}-{{ (int) $shareId }}');
        var input = document.getElementById('deeplink-canonical-{{ $deeplinkType }}-{{ (int) $shareId }}');
        if (!btn || !input) return;
        btn.addEventListener('click', function () {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(input.value);
            } else {
                input.select();
                document.execCommand('copy');
            }
            btn.textContent = 'Copied';
            setTimeout(function () { btn.textContent = 'Copy'; }, 1500);
        });
    })();
</script>
