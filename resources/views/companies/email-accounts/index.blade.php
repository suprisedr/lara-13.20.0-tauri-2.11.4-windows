@extends('layouts.public')

@section('title', $company->registered_name . ' — Email Accounts')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .ea-provider-icon {
            width:22px; height:22px; display:flex; align-items:center; justify-content:center;
            font-weight:800; font-size:0.5rem; color:#fff; flex-shrink:0;
        }
        .ea-provider-icon.gmail   { background:#ea4335; }
        .ea-provider-icon.outlook { background:#0078d4; }
        .ea-provider-icon.imap    { background:#6b7280; }
        .ea-provider-icon.pop     { background:#8b5cf6; }

        .ea-status {
            display:inline-block; padding:1pt 4pt;
            font-size:5.5pt; font-weight:700; letter-spacing:0.06em; text-transform:uppercase;
        }
        .ea-status.active   { background:#dcfce7; color:#166534; }
        .ea-status.inactive { background:#fee2e2; color:#991b1b; }
        .ea-status.error    { background:#fef3c7; color:#92400e; }

        .ea-error-text { color:#dc2626; font-size:5.5pt; display:block; margin-top:1pt; }

        .ea-meta { font-size:6pt; color:#8b7aad; }

        .ea-actions { display:flex; align-items:center; gap:0.25rem; }

        .list-search-wrap { position:relative; display:flex; align-items:center; gap:3pt; }
        .list-search-input {
            height:16pt; border:1px solid #c4b5fd;
            padding:0 12pt 0 5pt; font-size:6.5pt; font-family:Helvetica, Arial, "DejaVu Sans", sans-serif;
            color:#4c1d95; background:#fff; width:150pt; box-sizing:border-box;
        }
        .list-search-input:focus { outline:none; border-color:#4c1d95; }
        .list-search-clear {
            position:absolute; right:3pt; background:none; border:none;
            cursor:pointer; font-size:9pt; color:#8b7aad; line-height:1; padding:0; display:none;
        }
        .list-search-clear:hover { color:#4c1d95; }
        .list-search-count { font-size:6pt; color:#6b5b8a; white-space:nowrap; }
        mark.ls-hl { background:#fef08a; border-radius:2px; padding:0 1px; font-weight:inherit; }
    </style>
@endpush

@section('content')
    <div class="co-wrap">

        @include('companies._topbar')

        <div class="co-body">
            @include('companies._sidebar')

            <main class="co-main">

                @if (session('success'))
                    <div style="background:#dcfce7;border:1px solid #bbf7d0;color:#15803d;padding:4pt 8pt;font-size:7pt;font-weight:600;margin-bottom:12pt;">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div style="background:#fee2e2;border:1px solid #fecaca;color:#b91c1c;padding:4pt 8pt;font-size:7pt;font-weight:600;margin-bottom:12pt;">
                        {{ session('error') }}
                    </div>
                @endif

                <div class="reg-doc">
                    <div class="reg-doc-body">
                        <div class="reg-mgmt-bar">
                            <div>
                                <div class="reg-doc-title">Email Accounts</div>
                                <div class="reg-doc-subtitle">Integrations</div>
                            </div>
                            <div style="display:flex;align-items:center;gap:6pt;">
                                @if (!$accounts->isEmpty())
                                    <div class="list-search-wrap">
                                        <input type="text" class="list-search-input" id="ls-input" placeholder="Search accounts…" autocomplete="off" oninput="listSearch(this,'ea-row')">
                                        <button class="list-search-clear" id="ls-clear" onclick="listSearch(null,'ea-row',true)" title="Clear">&times;</button>
                                    </div>
                                    <span class="list-search-count" id="ls-count"></span>
                                @endif
                                <a href="{{ route('companies.email-accounts.create', $company) }}" class="reg-btn primary">
                                    <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                        <line x1="12" y1="5" x2="12" y2="19" />
                                        <line x1="5" y1="12" x2="19" y2="12" />
                                    </svg>
                                    Link Account
                                </a>
                            </div>
                        </div>

                        @if ($accounts->isEmpty())
                            <div class="reg-empty-state">
                                <svg width="28" height="28" fill="none" stroke="#a78bfa" stroke-width="1.5"
                                    stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"
                                    style="display:block;margin:0 auto 0.75rem;">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                    <polyline points="22,6 12,13 2,6"/>
                                </svg>
                                <p class="reg-empty-title">No email accounts linked</p>
                                <p>Link a Gmail, Outlook, or IMAP account to automatically receive supplier emails.</p>
                                <a href="{{ route('companies.email-accounts.create', $company) }}" class="reg-btn primary">
                                    Link Email Account
                                </a>
                            </div>
                        @else
                            <div style="overflow-x:auto;">
                                <table class="reg-table">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>Email Address</th>
                                            <th>Provider</th>
                                            <th>Last Synced</th>
                                            <th>Status</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($accounts as $account)
                                            <tr class="ea-row" data-search="{{ strtolower($account->email_address . ' ' . $account->provider->value) }}">
                                                <td style="width:28px;">
                                                    <div class="ea-provider-icon {{ $account->provider->value }}">
                                                        {{ strtoupper(substr($account->provider->value, 0, 2)) }}
                                                    </div>
                                                </td>
                                                <td style="font-weight:700;">
                                                    {{ $account->email_address }}
                                                    @if ($account->last_error)
                                                        <span class="ea-error-text">{{ \Illuminate\Support\Str::limit($account->last_error, 60) }}</span>
                                                    @endif
                                                </td>
                                                <td>{{ $account->provider->label() }}</td>
                                                <td class="dim">
                                                    @if ($account->last_synced_at)
                                                        {{ $account->last_synced_at->diffForHumans() }}
                                                    @else
                                                        Never
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="ea-status {{ $account->is_active ? ($account->last_error ? 'error' : 'active') : 'inactive' }}">
                                                        {{ $account->is_active ? ($account->last_error ? 'Error' : 'Active') : 'Inactive' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="reg-row-actions">
                                                        <button class="reg-row-dots" onclick="toggleRowMenu(this)" title="Actions">&#x2026;</button>
                                                        <div class="reg-row-menu">
                                                            <a href="{{ route('companies.email-accounts.emails', [$company, $account]) }}">Inbox</a>
                                                            <form method="POST" action="{{ route('companies.email-accounts.destroy', [$company, $account]) }}"
                                                                style="margin:0;" onsubmit="return confirm('Disconnect this email account?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="menu-item danger">Disconnect</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script>
        function listSearch(inputEl, rowClass, clear) {
            const input = inputEl ?? document.getElementById('ls-input');
            if (clear) { input.value = ''; }
            const q = input.value.trim().toLowerCase();
            document.getElementById('ls-clear').style.display = q ? 'inline' : 'none';
            const re = q ? new RegExp('(' + q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi') : null;
            const rows = document.querySelectorAll('tr.' + rowClass);
            let matches = 0;
            rows.forEach(row => {
                row.querySelectorAll('.ls-hl').forEach(m => { m.outerHTML = m.textContent; });
                if (!q) { row.style.display = ''; return; }
                if (!(row.dataset.search || '').includes(q)) { row.style.display = 'none'; return; }
                row.style.display = ''; matches++;
                Array.from(row.querySelectorAll('td')).slice(0, 3).forEach(td => {
                    re.lastIndex = 0;
                    const walker = document.createTreeWalker(td, NodeFilter.SHOW_TEXT);
                    const nodes = []; let n;
                    while ((n = walker.nextNode())) nodes.push(n);
                    nodes.forEach(tn => {
                        re.lastIndex = 0;
                        if (!re.test(tn.textContent)) return;
                        re.lastIndex = 0;
                        const span = document.createElement('span');
                        span.innerHTML = tn.textContent.replace(re, '<mark class="ls-hl">$1</mark>');
                        tn.parentNode.replaceChild(span, tn);
                    });
                    re.lastIndex = 0;
                });
            });
            document.getElementById('ls-count').textContent = q ? (matches + ' match' + (matches !== 1 ? 'es' : '')) : '';
        }

        function toggleRowMenu(btn) {
            const menu = btn.nextElementSibling;
            document.querySelectorAll('.reg-row-menu.open').forEach(m => { if (m !== menu) m.classList.remove('open'); });
            menu.classList.toggle('open');
        }
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.reg-row-actions')) {
                document.querySelectorAll('.reg-row-menu.open').forEach(m => m.classList.remove('open'));
            }
        });
    </script>
@endsection
