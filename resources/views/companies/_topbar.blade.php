<div class="co-topbar">
    <div>
        <div style="flex-shrink:0;display:inline-flex;align-items:center;justify-content:center;margin-right:4pt;">
            <img src="{{ asset('storage/images/chainbook-icon-light.png') }}" alt="" style="width:18pt;height:18pt;object-fit:contain;display:block;">
        </div>
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
        @php $__graceSub = auth()->user()?->activeSubscription(); @endphp
        @if ($__graceSub?->isCancelled())
            <span style="display:inline-flex;align-items:center;font-size:5.5pt;font-weight:700;padding:2pt 6pt;color:#fde68a;border:1px solid #d97706;white-space:nowrap;" title="Access ends {{ $__graceSub->current_period_end?->format('d M Y') }}">
                {{ $__graceSub->daysRemaining() }} {{ Str::plural('day', $__graceSub->daysRemaining()) }} left
            </span>
        @endif
        <a href="{{ route('subscriptions.manage') }}" style="display:inline-flex;align-items:center;gap:2pt;font-size:6pt;font-weight:600;padding:2pt 6pt;text-decoration:none;border:1px solid #6b5b8a;color:#c4b5fd;background:transparent;transition:background 0.15s,color 0.15s;white-space:nowrap;"
            onmouseover="this.style.background='rgba(255,255,255,0.12)';this.style.color='#fff'"
            onmouseout="this.style.background='transparent';this.style.color='#c4b5fd'">
            <svg width="9" height="9" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/>
            </svg>
            Subscription
        </a>
        <form method="POST" action="{{ route('logout') }}" style="margin:0;">
            @csrf
            <button type="submit" style="display:inline-flex;align-items:center;gap:2pt;font-size:6pt;font-weight:600;padding:2pt 6pt;text-decoration:none;border:1px solid #6b5b8a;color:#c4b5fd;background:transparent;cursor:pointer;transition:background 0.15s,color 0.15s;white-space:nowrap;font-family:inherit;"
                onmouseover="this.style.background='rgba(255,255,255,0.12)';this.style.color='#fff'"
                onmouseout="this.style.background='transparent';this.style.color='#c4b5fd'">
                <svg width="9" height="9" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                Log Out
            </button>
        </form>
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
