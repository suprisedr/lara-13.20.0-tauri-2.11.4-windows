@extends('layouts.public')

@section('title', $company->registered_name . ' — Inbox: ' . $account->email_address)
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .act-doc {
            background:#fff; border:1px solid #ddd;
            font-family:'DejaVu Sans',Helvetica,Arial,sans-serif;
            color:#000; font-size:0.78rem; line-height:1.45;
            margin-bottom:1.5rem;
        }

        .mgmt-btn {
            display:inline-flex; align-items:center; gap:0.4rem;
            background:#fff; border:1px solid #000; color:#000;
            font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.06em;
            padding:0.4rem 0.9rem; text-decoration:none; cursor:pointer;
            font-family:inherit; transition:background 0.15s, color 0.15s;
            height:2rem; box-sizing:border-box; border-radius:0;
        }
        .mgmt-btn:hover { background:#000; color:#fff; }
        .mgmt-btn.primary { background:#000; color:#fff; }
        .mgmt-btn.primary:hover { background:#333; }
        .mgmt-btn.sm { font-size:0.62rem; padding:0.28rem 0.7rem; height:1.7rem; gap:0.3rem; }

        /* ── Channel header ── */
        .af-header {
            display:flex; align-items:center; justify-content:space-between;
            padding:1rem 1.35rem; border-bottom:1.5px solid #000;
            gap:0.75rem; flex-wrap:wrap;
        }
        .af-header-left { display:flex; align-items:center; gap:0.6rem; }
        .af-header-icon { font-size:1rem; color:#000; line-height:1; }
        .af-header-title {
            font-weight:700; font-size:0.85rem; text-transform:uppercase;
            letter-spacing:0.07em; color:#000;
        }
        .af-header-count {
            display:inline-flex; align-items:center; justify-content:center;
            background:#000; color:#fff;
            font-size:0.58rem; font-weight:700; letter-spacing:0.05em;
            min-width:1.25rem; height:1.25rem; padding:0 0.35rem; border-radius:0;
        }
        .af-header-right { display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap; }

        .af-search-wrap { position:relative; display:flex; align-items:center; }
        .af-search-input {
            height:1.9rem; border:1px solid #ccc; border-radius:0;
            padding:0 1.5rem 0 0.6rem; font-size:0.72rem; font-family:inherit;
            color:#000; background:#fff; width:190px; box-sizing:border-box;
        }
        .af-search-input:focus { outline:none; border-color:#000; }
        .af-search-clear {
            position:absolute; right:0.35rem; background:none; border:none;
            cursor:pointer; font-size:0.9rem; color:#999; line-height:1; padding:0;
            display:none;
        }
        .af-search-clear:hover { color:#000; }
        .af-search-count { font-size:0.62rem; color:#6b7280; white-space:nowrap; }

        .af-tabs { display:flex; }
        .af-tab {
            padding:0 0.85rem; font-size:0.6rem; font-weight:700;
            letter-spacing:0.08em; text-transform:uppercase;
            color:#000; background:#fff; border:1px solid #000;
            cursor:pointer; transition:background 0.15s, color 0.15s;
            height:1.9rem; font-family:inherit;
            display:inline-flex; align-items:center;
        }
        .af-tab + .af-tab { border-left:none; }
        .af-tab.active { background:#000; color:#fff; }
        .af-tab:hover:not(.active) { background:#f5f5f5; }

        /* ── Sync interval ── */
        .af-sync-form {
            display:flex; align-items:center; gap:0.3rem;
        }
        .af-sync-label {
            font-size:0.6rem; font-weight:700; color:#555;
            text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap;
        }
        .af-sync-input {
            border:1px solid #ccc; border-radius:0;
            padding:0.18rem 0.35rem; font-size:0.65rem; color:#000;
            background:#fff; outline:none; font-family:inherit;
            width:3rem; text-align:center; height:1.9rem; box-sizing:border-box;
            -moz-appearance:textfield;
        }
        .af-sync-input::-webkit-inner-spin-button,
        .af-sync-input::-webkit-outer-spin-button { -webkit-appearance:none; margin:0; }
        .af-sync-input:focus { border-color:#000; }

        /* ── Feed area ── */
        .af-feed { max-height:60vh; overflow-y:auto; }
        .af-feed::-webkit-scrollbar { width:4px; }
        .af-feed::-webkit-scrollbar-track { background:transparent; }
        .af-feed::-webkit-scrollbar-thumb { background:#ccc; }

        .af-divider {
            display:flex; align-items:center; gap:0.7rem;
            padding:0.65rem 1.35rem 0.3rem;
            color:#555; font-size:0.58rem; font-weight:700;
            letter-spacing:0.09em; text-transform:uppercase;
        }
        .af-divider::before, .af-divider::after {
            content:''; flex:1; height:1px; background:#ddd;
        }

        /* ── Compact row ── */
        .af-row {
            display:flex; align-items:center; gap:0.6rem;
            padding:0.5rem 1.35rem;
            cursor:pointer; position:relative;
            transition:background 0.1s;
            border-left:3px solid transparent;
            border-bottom:1px solid #eee;
        }
        .af-row:last-child { border-bottom:none; }
        .af-row:hover { background:#fafafa; }
        .af-row.status-matched       { border-left-color:#15803d; }
        .af-row.status-new_supplier   { border-left-color:#b45309; }
        .af-row.status-pending_review { border-left-color:#999; }
        .af-row.unreviewed { background:#fffef5; }
        .af-row.unreviewed:hover { background:#fefce8; }
        .af-row mark.af-hl { background:#fef08a; border-radius:2px; padding:0 1px; font-weight:inherit; }

        .af-src {
            flex-shrink:0; width:1.55rem; height:1.55rem;
            display:flex; align-items:center; justify-content:center;
            font-size:0.55rem; font-weight:800; letter-spacing:0.05em;
            background:#fff; border:1px solid #000;
            text-transform:uppercase;
        }
        .af-src.src-matched       { color:#15803d; border-color:#15803d; }
        .af-src.src-new_supplier   { color:#b45309; border-color:#b45309; }
        .af-src.src-pending_review { color:#555; border-color:#999; }

        .af-row-body {
            flex:1; min-width:0; display:flex; flex-direction:column; gap:0.1rem;
        }
        .af-row-title {
            font-size:0.79rem; font-weight:600; color:#000;
            white-space:nowrap; overflow:hidden; text-overflow:ellipsis; line-height:1.3;
        }
        .af-row-sender {
            font-size:0.62rem; color:#888;
            white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
        }

        .af-row-badges { display:flex; align-items:center; gap:0.3rem; flex-shrink:0; }

        .af-badge {
            font-size:0.55rem; font-weight:700;
            padding:0.1rem 0.45rem; border-radius:0;
            border:1px solid #000; background:#fff;
            text-transform:uppercase; letter-spacing:0.08em;
        }
        .badge-matched       { color:#15803d; border-color:#15803d; }
        .badge-new_supplier   { color:#b45309; border-color:#b45309; }
        .badge-pending_review { color:#555; border-color:#999; }
        .badge-attach { color:#000; border-color:#000; }

        .af-row-time {
            flex-shrink:0; font-size:0.65rem; color:#888;
            min-width:3.5rem; text-align:right;
            font-family:'Courier New',monospace;
        }

        .af-row-actions {
            position:absolute; right:1rem; display:none;
            align-items:center; gap:0.3rem;
            background:#fff; border:1px solid #000;
            box-shadow:0 2px 6px rgba(0,0,0,0.12);
            padding:0.2rem 0.3rem; border-radius:0;
        }
        .af-row:hover .af-row-actions { display:flex; }

        .af-row-btn {
            background:none; border:none; cursor:pointer;
            padding:0.2rem 0.45rem; font-family:inherit;
            font-size:0.58rem; font-weight:700; letter-spacing:0.06em;
            text-transform:uppercase; color:#555;
            transition:background 0.1s, color 0.1s;
            white-space:nowrap;
        }
        .af-row-btn:hover { background:#f5f5f5; color:#000; }
        .af-row-btn.btn-confirm { color:#15803d; }
        .af-row-btn.btn-confirm:hover { background:#15803d; color:#fff; }
        .af-row-btn.btn-dismiss { color:#b91c1c; }
        .af-row-btn.btn-dismiss:hover { background:#b91c1c; color:#fff; }

        /* ── Expanded detail ── */
        .af-detail {
            display:none;
            margin:0 1.35rem 0 3.85rem;
            border-left:2px solid #000;
            padding:0.6rem 0 0.7rem 1rem;
            border-bottom:1px solid #eee;
            background:#fafafa;
        }
        .af-detail.open { display:block; }

        .af-detail-body {
            font-size:0.78rem; color:#1b1b18; line-height:1.55; margin:0 0 0.55rem;
        }
        .af-detail-footer {
            display:flex; align-items:center; flex-wrap:wrap; gap:0.5rem;
        }
        .af-detail-ref {
            display:inline-flex; align-items:center; gap:0.35rem;
            font-size:0.6rem; font-weight:700; color:#000;
            background:#fff; border:1px solid #000;
            padding:0.12rem 0.5rem;
            text-transform:uppercase; letter-spacing:0.07em;
            text-decoration:none;
        }
        .af-detail-ref:hover { background:#000; color:#fff; }
        .af-detail-meta { font-size:0.65rem; color:#888; }
        .af-detail-sep {
            width:3px; height:3px; border-radius:50%;
            background:#bbb; flex-shrink:0;
        }
        .af-detail-attachments {
            display:flex; flex-wrap:wrap; gap:0.35rem; margin-top:0.4rem;
        }
        .af-detail-file {
            display:inline-flex; align-items:center; gap:0.3rem;
            font-size:0.6rem; font-weight:700; color:#555;
            background:#fff; border:1px solid #ccc;
            padding:0.12rem 0.5rem;
            text-transform:uppercase; letter-spacing:0.05em;
        }

        /* ── Pagination ── */
        .af-pagination {
            display:flex; align-items:center; justify-content:center;
            gap:0.35rem; padding:0.85rem 1.35rem; border-top:1px solid #ddd;
        }
        .af-pagination a, .af-pagination span {
            display:inline-flex; align-items:center; justify-content:center;
            min-width:1.85rem; height:1.85rem;
            font-size:0.7rem; font-weight:700;
            text-decoration:none; color:#555;
            border:1px solid #ccc; background:#fff;
            padding:0 0.55rem; border-radius:0; font-family:inherit;
        }
        .af-pagination a:hover { border-color:#000; color:#000; }
        .af-pagination span.current { background:#000; color:#fff; border-color:#000; }

        /* ── Empty state ── */
        .af-empty {
            display:flex; flex-direction:column; align-items:center; justify-content:center;
            color:#888; gap:0.5rem; padding:3rem 1rem;
        }
        .af-empty-icon { font-size:2rem; color:#bbb; line-height:1; }
        .af-empty-title {
            font-size:0.85rem; font-weight:700; color:#000;
            text-transform:uppercase; letter-spacing:0.07em;
        }
        .af-empty-sub { font-size:0.74rem; }

        @keyframes flashIn {
            from { background:#fef9c3; }
            to   { background:transparent; }
        }
    </style>
@endpush

@section('content')
    <div class="co-wrap">

        @include('companies._topbar')

        <div class="co-body">
            @include('companies._sidebar')

            <main class="co-main">

                @if (session('success'))
                    <div style="background:#dcfce7;border:1px solid #bbf7d0;color:#15803d;padding:0.75rem 1.25rem;font-size:0.855rem;font-weight:600;margin-bottom:1.25rem;">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="co-section-heading"><p class="co-section-label">{{ $account->email_address }}</p><h2>Supplier Inbox</h2></div>

                <div class="act-doc">

                    {{-- Header --}}
                    <div class="af-header">
                        <div class="af-header-left">
                            <span class="af-header-icon">✉</span>
                            <span class="af-header-title">Inbox</span>
                            @if ($emails->total() > 0)
                                <span class="af-header-count">{{ $emails->total() }}</span>
                            @endif
                        </div>
                        <div class="af-header-right">
                            <div class="af-search-wrap">
                                <input type="text" class="af-search-input" id="af-search-input" placeholder="Search emails…" autocomplete="off" oninput="emailsFilter(this.value)">
                                <button class="af-search-clear" id="af-search-clear" onclick="emailsFilter('');document.getElementById('af-search-input').value='';" title="Clear">&times;</button>
                            </div>
                            <span class="af-search-count" id="af-search-count"></span>

                            <div class="af-tabs">
                                <a href="{{ route('companies.email-accounts.emails', [$company, $account]) }}"
                                   class="af-tab {{ !request('status') ? 'active' : '' }}" style="text-decoration:none;">All</a>
                                <a href="{{ route('companies.email-accounts.emails', [$company, $account, 'status' => 'new_supplier']) }}"
                                   class="af-tab {{ request('status') === 'new_supplier' ? 'active' : '' }}" style="text-decoration:none;">New</a>
                                <a href="{{ route('companies.email-accounts.emails', [$company, $account, 'status' => 'pending_review']) }}"
                                   class="af-tab {{ request('status') === 'pending_review' ? 'active' : '' }}" style="text-decoration:none;">Pending</a>
                                <a href="{{ route('companies.email-accounts.emails', [$company, $account, 'status' => 'matched']) }}"
                                   class="af-tab {{ request('status') === 'matched' ? 'active' : '' }}" style="text-decoration:none;">Matched</a>
                            </div>

                            <form method="POST" action="{{ route('companies.email-accounts.sync-interval', [$company, $account]) }}" class="af-sync-form">
                                @csrf
                                @method('PATCH')
                                <span class="af-sync-label">Sync</span>
                                <input type="number" name="sync_interval_minutes" class="af-sync-input" value="{{ $account->sync_interval_minutes }}" min="1" max="1440">
                                <span class="af-sync-label">min</span>
                                <button type="submit" class="mgmt-btn sm">Save</button>
                            </form>

                            <a href="{{ route('companies.email-accounts.index', $company) }}" class="mgmt-btn sm" style="text-decoration:none;">← Back</a>
                        </div>
                    </div>

                    {{-- Feed --}}
                    <div class="af-feed">
                        @if ($emails->isEmpty())
                            <div class="af-empty">
                                <div class="af-empty-icon">✉</div>
                                <div class="af-empty-title">No emails yet</div>
                                <div class="af-empty-sub">New emails will appear here automatically via IMAP sync.</div>
                            </div>
                        @else
                            @php
                                $grouped = $emails->groupBy(function($e) {
                                    $d = $e->received_at;
                                    return $d->isToday() ? 'Today' : ($d->isYesterday() ? 'Yesterday' : $d->format('d M Y'));
                                });
                            @endphp
                            @foreach ($grouped as $label => $group)
                                <div class="af-divider">{{ $label }}</div>
                                @foreach ($group as $email)
                                    @php
                                        $initials = strtoupper(mb_substr($email->from_name ?: $email->from_email, 0, 2));
                                        $statusVal = $email->status->value;
                                        $displayName = $email->from_name ?: explode('@', $email->from_email)[0];
                                        $hasDetail = $email->ai_summary || $email->supplier || $email->attachments->isNotEmpty();
                                    @endphp
                                    <div class="af-row status-{{ $statusVal }} {{ !$email->is_reviewed ? 'unreviewed' : '' }}"
                                         id="email-{{ $email->id }}"
                                         onclick="toggleDetail({{ $email->id }}, event)">
                                        <div class="af-src src-{{ $statusVal }}">{{ $initials }}</div>
                                        <div class="af-row-body">
                                            <span class="af-row-title">{{ $email->subject ?: '(No subject)' }}</span>
                                            <span class="af-row-sender">{{ $displayName }} &lt;{{ $email->from_email }}&gt;</span>
                                        </div>
                                        <div class="af-row-badges">
                                            <span class="af-badge badge-{{ $statusVal }}">{{ $email->status->label() }}</span>
                                            @if ($email->attachments->isNotEmpty())
                                                <span class="af-badge badge-attach">{{ $email->attachments->count() }} {{ Str::plural('file', $email->attachments->count()) }}</span>
                                            @endif
                                        </div>
                                        <span class="af-row-time">{{ $email->received_at->format('g:i A') }}</span>

                                        @if (!$email->is_reviewed)
                                            <div class="af-row-actions">
                                                @if ($email->status === \App\Enums\SupplierEmailStatus::NewSupplier)
                                                    <button class="af-row-btn btn-confirm" onclick="reviewEmail({{ $email->id }}, 'confirm', event)">
                                                        {{ $email->supplier ? '✓ Confirm' : '+ Create Supplier' }}
                                                    </button>
                                                @endif
                                                <button class="af-row-btn btn-dismiss" onclick="reviewEmail({{ $email->id }}, 'dismiss', event)">✕ Dismiss</button>
                                            </div>
                                        @endif
                                    </div>
                                    @if ($hasDetail)
                                        <div class="af-detail" id="detail-{{ $email->id }}">
                                            @if ($email->ai_summary)
                                                <p class="af-detail-body">
                                                    <span style="font-size:0.6rem;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:#555;">AI Summary</span><br>
                                                    {{ $email->ai_summary }}
                                                </p>
                                            @endif
                                            <div class="af-detail-footer">
                                                @if ($email->supplier)
                                                    <a href="{{ route('companies.suppliers.show', [$company, $email->supplier]) }}" class="af-detail-ref">
                                                        <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                                        {{ $email->supplier->name }}
                                                        @if (!$email->supplier->is_confirmed) — Unconfirmed @endif
                                                    </a>
                                                    <span class="af-detail-sep"></span>
                                                @elseif ($email->status === \App\Enums\SupplierEmailStatus::NewSupplier)
                                                    <span class="af-detail-ref" style="color:#b45309;border-color:#b45309;">New sender — confirm to create supplier</span>
                                                    <span class="af-detail-sep"></span>
                                                @endif
                                                <span class="af-detail-meta">{{ $email->received_at->format('d M Y, g:i A') }}</span>
                                                <span class="af-detail-sep"></span>
                                                <span class="af-detail-meta">{{ $email->from_email }}</span>
                                            </div>
                                            @if ($email->attachments->isNotEmpty())
                                                <div class="af-detail-attachments">
                                                    @foreach ($email->attachments as $att)
                                                        <span class="af-detail-file">
                                                            <svg width="9" height="9" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                                                            {{ $att->filename }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                @endforeach
                            @endforeach
                        @endif
                    </div>

                    {{-- Pagination --}}
                    @if ($emails->hasPages())
                        <div class="af-pagination">
                            @if ($emails->onFirstPage())
                                <span style="opacity:0.3;">&lsaquo;</span>
                            @else
                                <a href="{{ $emails->appends(request()->query())->previousPageUrl() }}">&lsaquo;</a>
                            @endif

                            @foreach ($emails->getUrlRange(max(1, $emails->currentPage() - 3), min($emails->lastPage(), $emails->currentPage() + 3)) as $page => $url)
                                @if ($page == $emails->currentPage())
                                    <span class="current">{{ $page }}</span>
                                @else
                                    <a href="{{ $url . '&' . http_build_query(request()->except('page')) }}">{{ $page }}</a>
                                @endif
                            @endforeach

                            @if ($emails->hasMorePages())
                                <a href="{{ $emails->appends(request()->query())->nextPageUrl() }}">&rsaquo;</a>
                            @else
                                <span style="opacity:0.3;">&rsaquo;</span>
                            @endif
                        </div>
                    @endif

                </div>{{-- /act-doc --}}
            </main>
        </div>
    </div>

    <script>
        (function () {
            var accountId = {{ $account->id }};
            var companySlug = @json($company->slug);
            var csrfToken = '{{ csrf_token() }}';

            function escHtml(s) {
                var d = document.createElement('div');
                d.textContent = s || '';
                return d.innerHTML;
            }

            // ── Search / filter ──
            window.emailsFilter = function (raw) {
                var q = raw.trim().toLowerCase();
                var clearBtn = document.getElementById('af-search-clear');
                var countLabel = document.getElementById('af-search-count');
                clearBtn.style.display = q ? 'inline' : 'none';

                var rows = document.querySelectorAll('.af-row');
                var matches = 0;

                rows.forEach(function (row) {
                    row.querySelectorAll('.af-hl').forEach(function (m) { m.outerHTML = m.textContent; });

                    if (!q) { row.style.display = ''; return; }

                    var title = (row.querySelector('.af-row-title') || {}).textContent || '';
                    var sender = (row.querySelector('.af-row-sender') || {}).textContent || '';
                    var haystack = title + ' ' + sender;

                    if (haystack.toLowerCase().indexOf(q) === -1) {
                        row.style.display = 'none';
                        return;
                    }

                    row.style.display = '';
                    matches++;
                });

                document.querySelectorAll('.af-divider').forEach(function (div) {
                    var sibling = div.nextElementSibling;
                    var hasVisible = false;
                    while (sibling && !sibling.classList.contains('af-divider')) {
                        if (sibling.classList.contains('af-row') && sibling.style.display !== 'none') {
                            hasVisible = true; break;
                        }
                        sibling = sibling.nextElementSibling;
                    }
                    div.style.display = hasVisible ? '' : 'none';
                });

                countLabel.textContent = q ? (matches + ' match' + (matches !== 1 ? 'es' : '')) : '';
            };

            // ── Toggle detail panel ──
            window.toggleDetail = function (id, e) {
                if (e.target.closest('.af-row-actions')) return;
                var d = document.getElementById('detail-' + id);
                if (d) d.classList.toggle('open');
            };

            // ── Review actions (confirm / dismiss) ──
            window.reviewEmail = function (id, action, e) {
                e.stopPropagation();
                fetch('/companies/' + companySlug + '/email-accounts/emails/' + id + '/review', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-HTTP-Method-Override': 'PATCH',
                    },
                    body: JSON.stringify({ action: action }),
                }).then(function (res) {
                    if (!res.ok) return;
                    var row = document.getElementById('email-' + id);
                    if (!row) return;

                    row.classList.remove('unreviewed');
                    var actions = row.querySelector('.af-row-actions');
                    if (actions) actions.remove();

                    if (action === 'confirm') {
                        var badge = row.querySelector('.af-badge');
                        if (badge) {
                            badge.className = 'af-badge badge-matched';
                            badge.textContent = 'MATCHED';
                        }
                        row.className = row.className.replace(/status-\w+/, 'status-matched');
                        var src = row.querySelector('.af-src');
                        if (src) src.className = 'af-src src-matched';
                    }
                });
            };

            // ── Real-time new emails via Reverb ──
            function buildRow(e) {
                var initials = (e.from_name || e.from_email).substring(0, 2).toUpperCase();
                var displayName = e.from_name || e.from_email.split('@')[0];
                var unreviewed = !e.is_reviewed ? ' unreviewed' : '';

                var badgesHtml = '<span class="af-badge badge-' + e.status + '">' + escHtml(e.status_label) + '</span>';
                if (e.attachment_count > 0) {
                    badgesHtml += ' <span class="af-badge badge-attach">' + e.attachment_count + ' ' + (e.attachment_count === 1 ? 'file' : 'files') + '</span>';
                }

                var actionsHtml = '';
                if (!e.is_reviewed) {
                    var confirmBtn = '';
                    if (e.status === 'new_supplier') {
                        var btnLabel = e.supplier_id ? '✓ Confirm' : '+ Create Supplier';
                        confirmBtn = '<button class="af-row-btn btn-confirm" onclick="reviewEmail(' + e.id + ', \'confirm\', event)">' + btnLabel + '</button>';
                    }
                    actionsHtml = '<div class="af-row-actions">' + confirmBtn +
                        '<button class="af-row-btn btn-dismiss" onclick="reviewEmail(' + e.id + ', \'dismiss\', event)">✕ Dismiss</button></div>';
                }

                var detailHtml = '';
                if (e.ai_summary || e.supplier_name) {
                    detailHtml = '<div class="af-detail" id="detail-' + e.id + '">';
                    if (e.ai_summary) {
                        detailHtml += '<p class="af-detail-body"><span style="font-size:0.6rem;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:#555;">AI Summary</span><br>' + escHtml(e.ai_summary) + '</p>';
                    }
                    detailHtml += '<div class="af-detail-footer">';
                    if (e.supplier_name) {
                        detailHtml += '<a href="/companies/' + companySlug + '/suppliers/' + e.supplier_id + '" class="af-detail-ref">' + escHtml(e.supplier_name) + '</a><span class="af-detail-sep"></span>';
                    }
                    detailHtml += '<span class="af-detail-meta">' + escHtml(e.from_email) + '</span>';
                    detailHtml += '</div></div>';
                }

                return '<div class="af-row status-' + e.status + unreviewed + '" id="email-' + e.id + '" onclick="toggleDetail(' + e.id + ', event)" style="animation:flashIn 1s ease">' +
                    '<div class="af-src src-' + e.status + '">' + initials + '</div>' +
                    '<div class="af-row-body"><span class="af-row-title">' + escHtml(e.subject || '(No subject)') + '</span>' +
                    '<span class="af-row-sender">' + escHtml(displayName) + ' &lt;' + escHtml(e.from_email) + '&gt;</span></div>' +
                    '<div class="af-row-badges">' + badgesHtml + '</div>' +
                    '<span class="af-row-time">' + (e.received_time || '') + '</span>' +
                    actionsHtml + '</div>' + detailHtml;
            }

            function onEmail(data) {
                if (data.account_id !== accountId) return;

                var feed = document.querySelector('.af-feed');
                if (!feed) return;

                if (document.getElementById('email-' + data.email.id)) return;

                var empty = feed.querySelector('.af-empty');
                if (empty) empty.remove();

                var dateLabel = 'Today';
                var divider = null;
                feed.querySelectorAll('.af-divider').forEach(function (d) {
                    if (d.textContent.trim() === dateLabel) divider = d;
                });

                if (!divider) {
                    divider = document.createElement('div');
                    divider.className = 'af-divider';
                    divider.textContent = dateLabel;
                    feed.prepend(divider);
                }

                divider.insertAdjacentHTML('afterend', buildRow(data.email));

                var badge = document.querySelector('.af-header-count');
                if (badge) {
                    badge.textContent = parseInt(badge.textContent) + 1;
                } else {
                    var left = document.querySelector('.af-header-left');
                    if (left) {
                        var b = document.createElement('span');
                        b.className = 'af-header-count';
                        b.textContent = '1';
                        left.appendChild(b);
                    }
                }
            }

            window.addEventListener('echo:ready', function () {
                window.Echo.private('company.{{ $company->id }}')
                    .listen('.email.synced', onEmail);
            });
        })();
    </script>
@endsection
