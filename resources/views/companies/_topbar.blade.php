<div class="co-topbar">
    <div>
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
    @if (!empty($topbarActions))
        <div class="co-topbar-actions">{!! $topbarActions !!}</div>
    @endif
</div>
