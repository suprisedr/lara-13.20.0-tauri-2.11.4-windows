<div class="co-topbar">
    <div>
        <div style="background:#fff;padding:3pt;flex-shrink:0;display:inline-flex;align-items:center;justify-content:center;margin-right:4pt;">
            <img src="{{ asset('storage/images/chainbook-icon.png') }}" alt="" style="width:16pt;height:16pt;object-fit:contain;display:block;">
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
