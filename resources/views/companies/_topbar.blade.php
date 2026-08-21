<div class="co-topbar">
    <div>
        <div style="flex-shrink:0;display:inline-flex;align-items:center;justify-content:center;margin-right:4pt;">
            <img src="{{ asset('storage/images/chainbook-icon-light.png') }}" alt="" style="width:18pt;height:18pt;object-fit:contain;display:block;">
        </div>
        <div class="co-topbar-nav">
            <button type="button" onclick="history.back()" class="co-topbar-nav-btn" title="Go back">
                <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
            </button>
            <button type="button" onclick="history.forward()" class="co-topbar-nav-btn" title="Go forward">
                <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
        </div>
        <div class="co-topbar-divider"></div>
        <a href="{{ $backRoute ?? route('dashboard') }}" class="co-topbar-back">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
            {{ $backLabel ?? 'Dashboard' }}
        </a>
        <div class="co-topbar-divider"></div>
        <h1 class="co-topbar-title">{{ $company->registered_name }}</h1>
        @if (!empty($topbarMeta))
            <p class="co-topbar-meta">{!! $topbarMeta !!}</p>
        @endif
    </div>
    <div class="co-topbar-actions">
        @if (!empty($topbarActions))
            {!! $topbarActions !!}
        @endif
    </div>
</div>

{{-- ── Reverb real-time refresh ──────────────────────────────── --}}
<script>
(function () {
    var companyId = @json($company->id);
    var debounceTimer = null;
    var DEBOUNCE_MS = 800;

    function refreshMain() {
        var main = document.querySelector('.co-main');
        if (!main) return;

        // Don't refresh if user is typing in a form field
        var active = document.activeElement;
        if (active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA' || active.tagName === 'SELECT' || active.isContentEditable)) {
            return;
        }

        fetch(window.location.href, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-Reverb-Refresh': '1' },
            credentials: 'same-origin',
        })
        .then(function (res) { return res.text(); })
        .then(function (html) {
            var parser = new DOMParser();
            var doc = parser.parseFromString(html, 'text/html');
            var fresh = doc.querySelector('.co-main');
            if (fresh) {
                main.innerHTML = fresh.innerHTML;
                // Re-run any inline scripts in the refreshed content
                main.querySelectorAll('script').forEach(function (old) {
                    var s = document.createElement('script');
                    if (old.src) { s.src = old.src; } else { s.textContent = old.textContent; }
                    old.parentNode.replaceChild(s, old);
                });
            }
        })
        .catch(function () { /* silent — next event will retry */ });
    }

    function scheduleRefresh() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(refreshMain, DEBOUNCE_MS);
    }

    function subscribe() {
        if (typeof window.Echo === 'undefined') return;

        window.Echo.private('company.' + companyId)
            .listen('.record.changed', function () {
                scheduleRefresh();
            })
            .listen('.posting.status.updated', function () {
                scheduleRefresh();
            })
            .listen('.action.updated', function () {
                scheduleRefresh();
            });
    }

    if (typeof window.Echo !== 'undefined') {
        subscribe();
    } else {
        window.addEventListener('echo:ready', subscribe);
    }
})();
</script>
