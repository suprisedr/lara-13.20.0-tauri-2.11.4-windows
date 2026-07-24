@extends('layouts.public')

@section('title', $company->registered_name . ' — Inbox: ' . $account->email_address)
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        /* ── Inbox header ── */
        .inbox-header {
            display:flex; align-items:center; justify-content:space-between;
            padding:8pt 10pt; border-bottom:1.5px solid #000;
            gap:6pt; flex-wrap:wrap;
        }
        .inbox-header-left { display:flex; align-items:center; gap:6pt; }
        .inbox-title {
            font-weight:700; font-size:7.5pt; text-transform:uppercase;
            letter-spacing:0.07em; color:#000;
        }
        .inbox-count {
            display:inline-flex; align-items:center; justify-content:center;
            background:#000; color:#fff;
            font-size:5pt; font-weight:700; letter-spacing:0.05em;
            min-width:14pt; height:14pt; padding:0 3pt;
        }
        .inbox-header-right { display:flex; align-items:center; gap:5pt; flex-wrap:wrap; }

        .inbox-search-wrap { position:relative; display:flex; align-items:center; }
        .inbox-search-input {
            height:18pt; border:1px solid #ccc;
            padding:0 14pt 0 5pt; font-size:6.5pt; font-family:inherit;
            color:#000; background:#fff; width:160pt; box-sizing:border-box;
        }
        .inbox-search-input:focus { outline:none; border-color:#000; }
        .inbox-search-clear {
            position:absolute; right:3pt; background:none; border:none;
            cursor:pointer; font-size:9pt; color:#999; line-height:1; padding:0; display:none;
        }
        .inbox-search-clear:hover { color:#000; }
        .inbox-search-count { font-size:5.5pt; color:#6b7280; white-space:nowrap; }

        .inbox-tabs { display:flex; }
        .inbox-tab {
            padding:0 7pt; font-size:5.5pt; font-weight:700;
            letter-spacing:0.08em; text-transform:uppercase;
            color:#000; background:#fff; border:1px solid #000;
            cursor:pointer; transition:background 0.15s, color 0.15s;
            height:18pt; font-family:inherit;
            display:inline-flex; align-items:center; text-decoration:none;
        }
        .inbox-tab + .inbox-tab { border-left:none; }
        .inbox-tab.active { background:#000; color:#fff; }
        .inbox-tab:hover:not(.active) { background:#f5f5f5; }

        .inbox-sync-form { display:flex; align-items:center; gap:2pt; }
        .inbox-sync-label { font-size:5.5pt; font-weight:700; color:#555; text-transform:uppercase; letter-spacing:0.06em; white-space:nowrap; }
        .inbox-sync-input {
            border:1px solid #ccc; padding:1pt 3pt; font-size:6pt; color:#000;
            background:#fff; outline:none; font-family:inherit;
            width:25pt; text-align:center; height:18pt; box-sizing:border-box;
            -moz-appearance:textfield;
        }
        .inbox-sync-input::-webkit-inner-spin-button,
        .inbox-sync-input::-webkit-outer-spin-button { -webkit-appearance:none; margin:0; }
        .inbox-sync-input:focus { border-color:#000; }

        .inbox-btn {
            display:inline-flex; align-items:center; gap:3pt;
            background:#fff; border:1px solid #000; color:#000;
            font-size:5.5pt; font-weight:700; text-transform:uppercase; letter-spacing:0.06em;
            padding:0 7pt; text-decoration:none; cursor:pointer;
            font-family:inherit; transition:background 0.15s, color 0.15s;
            height:18pt; box-sizing:border-box;
        }
        .inbox-btn:hover { background:#000; color:#fff; }

        /* ── Feed ── */
        .inbox-feed { max-height:60vh; overflow-y:auto; }
        .inbox-feed::-webkit-scrollbar { width:4px; }
        .inbox-feed::-webkit-scrollbar-track { background:transparent; }
        .inbox-feed::-webkit-scrollbar-thumb { background:#ccc; }

        .inbox-divider {
            display:flex; align-items:center; gap:6pt;
            padding:6pt 10pt 3pt;
            color:#555; font-size:5.5pt; font-weight:700;
            letter-spacing:0.09em; text-transform:uppercase;
        }
        .inbox-divider::before, .inbox-divider::after { content:''; flex:1; height:1px; background:#ddd; }

        /* ── Email row ── */
        .inbox-row {
            display:flex; align-items:center; gap:6pt;
            padding:5pt 10pt; cursor:pointer; position:relative;
            transition:background 0.1s;
            border-left:3px solid transparent;
            border-bottom:1px solid #eee;
        }
        .inbox-row:last-child { border-bottom:none; }
        .inbox-row:hover { background:#fafafa; }
        .inbox-row.status-matched       { border-left-color:#15803d; }
        .inbox-row.status-new_supplier   { border-left-color:#b45309; }
        .inbox-row.status-pending_review { border-left-color:#999; }
        .inbox-row.unreviewed { background:#fffef5; }
        .inbox-row.unreviewed:hover { background:#fefce8; }

        .inbox-avatar {
            flex-shrink:0; width:18pt; height:18pt;
            display:flex; align-items:center; justify-content:center;
            font-size:5pt; font-weight:800; letter-spacing:0.05em;
            background:#fff; border:1px solid #000; text-transform:uppercase;
        }
        .inbox-avatar.src-matched       { color:#15803d; border-color:#15803d; }
        .inbox-avatar.src-new_supplier   { color:#b45309; border-color:#b45309; }
        .inbox-avatar.src-pending_review { color:#555; border-color:#999; }

        .inbox-row-body { flex:1; min-width:0; display:flex; flex-direction:column; gap:0.5pt; }
        .inbox-row-subject {
            font-size:7pt; font-weight:600; color:#000;
            white-space:nowrap; overflow:hidden; text-overflow:ellipsis; line-height:1.3;
        }
        .inbox-row-sender {
            font-size:5.5pt; color:#888;
            white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
        }

        .inbox-row-badges { display:flex; align-items:center; gap:2pt; flex-shrink:0; }
        .inbox-badge {
            font-size:5pt; font-weight:700;
            padding:1pt 4pt;
            border:1px solid #000; background:#fff;
            text-transform:uppercase; letter-spacing:0.08em;
        }
        .inbox-badge.badge-matched       { color:#15803d; border-color:#15803d; }
        .inbox-badge.badge-new_supplier   { color:#b45309; border-color:#b45309; }
        .inbox-badge.badge-pending_review { color:#555; border-color:#999; }
        .inbox-badge.badge-attach { color:#000; border-color:#000; }

        .inbox-row-time {
            flex-shrink:0; font-size:5.5pt; color:#888;
            min-width:30pt; text-align:right;
            font-family:'Courier New',monospace;
        }

        .inbox-row-actions {
            position:absolute; right:10pt; display:none;
            align-items:center; gap:2pt;
            background:#fff; border:1px solid #000;
            box-shadow:0 2px 6px rgba(0,0,0,0.12);
            padding:2pt 3pt;
        }
        .inbox-row:hover .inbox-row-actions { display:flex; }

        .inbox-action-btn {
            background:none; border:none; cursor:pointer;
            padding:2pt 4pt; font-family:inherit;
            font-size:5pt; font-weight:700; letter-spacing:0.06em;
            text-transform:uppercase; color:#555;
            transition:background 0.1s, color 0.1s;
            white-space:nowrap;
        }
        .inbox-action-btn:hover { background:#f5f5f5; color:#000; }
        .inbox-action-btn.btn-confirm { color:#15803d; }
        .inbox-action-btn.btn-confirm:hover { background:#15803d; color:#fff; }
        .inbox-action-btn.btn-dismiss { color:#b91c1c; }
        .inbox-action-btn.btn-dismiss:hover { background:#b91c1c; color:#fff; }

        /* ── Expanded detail ── */
        .inbox-detail {
            display:none; margin:0 10pt 0 37pt;
            border-left:2px solid #000; padding:5pt 0 6pt 10pt;
            border-bottom:1px solid #eee; background:#fafafa;
        }
        .inbox-detail.open { display:block; }

        .inbox-detail-body { font-size:7pt; color:#1b1b18; line-height:1.55; margin:0 0 5pt; }
        .inbox-detail-footer { display:flex; align-items:center; flex-wrap:wrap; gap:4pt; }
        .inbox-detail-ref {
            display:inline-flex; align-items:center; gap:3pt;
            font-size:5.5pt; font-weight:700; color:#000;
            background:#fff; border:1px solid #000; padding:1pt 4pt;
            text-transform:uppercase; letter-spacing:0.07em; text-decoration:none;
        }
        .inbox-detail-ref:hover { background:#000; color:#fff; }
        .inbox-detail-meta { font-size:5.5pt; color:#888; }
        .inbox-detail-sep { width:3px; height:3px; border-radius:50%; background:#bbb; flex-shrink:0; }
        .inbox-detail-attachments { display:flex; flex-wrap:wrap; gap:3pt; margin-top:4pt; }
        .inbox-detail-file {
            display:inline-flex; align-items:center; gap:3pt;
            font-size:5.5pt; font-weight:700; color:#555;
            background:#fff; border:1px solid #ccc; padding:1pt 4pt;
            text-transform:uppercase; letter-spacing:0.05em;
        }

        /* ── Pagination ── */
        .inbox-pagination {
            display:flex; align-items:center; justify-content:center;
            gap:3pt; padding:8pt 10pt; border-top:1px solid #ddd;
        }
        .inbox-pagination a, .inbox-pagination span {
            display:inline-flex; align-items:center; justify-content:center;
            min-width:18pt; height:18pt;
            font-size:6pt; font-weight:700;
            text-decoration:none; color:#555;
            border:1px solid #ccc; background:#fff;
            padding:0 4pt; font-family:inherit;
        }
        .inbox-pagination a:hover { border-color:#000; color:#000; }
        .inbox-pagination span.current { background:#000; color:#fff; border-color:#000; }

        /* ── Empty ── */
        .inbox-empty {
            display:flex; flex-direction:column; align-items:center; justify-content:center;
            color:#888; gap:4pt; padding:3rem 1rem;
        }
        .inbox-empty-icon { font-size:2rem; color:#bbb; line-height:1; }
        .inbox-empty-title { font-size:7.5pt; font-weight:700; color:#000; text-transform:uppercase; letter-spacing:0.07em; }
        .inbox-empty-sub { font-size:6.5pt; }

        @keyframes flashIn { from { background:#fef9c3; } to { background:transparent; } }
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

                <div class="reg-doc">
                    <div class="reg-doc-body" style="padding:0;">

                        <div class="inbox-header">
                            <div class="inbox-header-left">
                                <span style="font-size:9pt;color:#000;line-height:1;">✉</span>
                                <span class="inbox-title">{{ $account->email_address }}</span>
                                @if ($emails->total() > 0)
                                    <span class="inbox-count">{{ $emails->total() }}</span>
                                @endif
                            </div>
                            <div class="inbox-header-right">
                                <div class="inbox-search-wrap">
                                    <input type="text" class="inbox-search-input" id="inbox-search-input" placeholder="Search emails…" autocomplete="off" oninput="emailsFilter(this.value)">
                                    <button class="inbox-search-clear" id="inbox-search-clear" onclick="emailsFilter('');document.getElementById('inbox-search-input').value='';" title="Clear">&times;</button>
                                </div>
                                <span class="inbox-search-count" id="inbox-search-count"></span>

                                <div class="inbox-tabs">
                                    <a href="{{ route('companies.email-accounts.emails', [$company, $account]) }}"
                                       class="inbox-tab {{ !request('status') ? 'active' : '' }}">All</a>
                                    <a href="{{ route('companies.email-accounts.emails', [$company, $account, 'status' => 'new_supplier']) }}"
                                       class="inbox-tab {{ request('status') === 'new_supplier' ? 'active' : '' }}">New</a>
                                    <a href="{{ route('companies.email-accounts.emails', [$company, $account, 'status' => 'pending_review']) }}"
                                       class="inbox-tab {{ request('status') === 'pending_review' ? 'active' : '' }}">Pending</a>
                                    <a href="{{ route('companies.email-accounts.emails', [$company, $account, 'status' => 'matched']) }}"
                                       class="inbox-tab {{ request('status') === 'matched' ? 'active' : '' }}">Matched</a>
                                </div>

                                <form method="POST" action="{{ route('companies.email-accounts.sync-interval', [$company, $account]) }}" class="inbox-sync-form">
                                    @csrf
                                    @method('PATCH')
                                    <span class="inbox-sync-label">Sync</span>
                                    <input type="number" name="sync_interval_minutes" class="inbox-sync-input" value="{{ $account->sync_interval_minutes }}" min="1" max="1440">
                                    <span class="inbox-sync-label">min</span>
                                    <button type="submit" class="inbox-btn">Save</button>
                                </form>

                                <a href="{{ route('companies.email-accounts.index', $company) }}" class="inbox-btn" style="text-decoration:none;">← Back</a>
                            </div>
                        </div>

                        <div class="inbox-feed">
                            @if ($emails->isEmpty())
                                <div class="inbox-empty">
                                    <div class="inbox-empty-icon">✉</div>
                                    <div class="inbox-empty-title">No emails yet</div>
                                    <div class="inbox-empty-sub">New emails will appear here automatically via IMAP sync.</div>
                                </div>
                            @else
                                @php
                                    $grouped = $emails->groupBy(function($e) {
                                        $d = $e->received_at;
                                        return $d->isToday() ? 'Today' : ($d->isYesterday() ? 'Yesterday' : $d->format('d M Y'));
                                    });
                                @endphp
                                @foreach ($grouped as $label => $group)
                                    <div class="inbox-divider">{{ $label }}</div>
                                    @foreach ($group as $email)
                                        @php
                                            $initials = strtoupper(mb_substr($email->from_name ?: $email->from_email, 0, 2));
                                            $statusVal = $email->status->value;
                                            $displayName = $email->from_name ?: explode('@', $email->from_email)[0];
                                            $hasDetail = $email->ai_summary || $email->supplier || $email->attachments->isNotEmpty();
                                        @endphp
                                        <div class="inbox-row status-{{ $statusVal }} {{ !$email->is_reviewed ? 'unreviewed' : '' }}"
                                             id="email-{{ $email->id }}"
                                             onclick="toggleDetail({{ $email->id }}, event)">
                                            <div class="inbox-avatar src-{{ $statusVal }}">{{ $initials }}</div>
                                            <div class="inbox-row-body">
                                                <span class="inbox-row-subject">{{ $email->subject ?: '(No subject)' }}</span>
                                                <span class="inbox-row-sender">{{ $displayName }} &lt;{{ $email->from_email }}&gt;</span>
                                            </div>
                                            <div class="inbox-row-badges">
                                                <span class="inbox-badge badge-{{ $statusVal }}">{{ $email->status->label() }}</span>
                                                @if ($email->attachments->isNotEmpty())
                                                    <span class="inbox-badge badge-attach">{{ $email->attachments->count() }} {{ Str::plural('file', $email->attachments->count()) }}</span>
                                                @endif
                                            </div>
                                            <span class="inbox-row-time">{{ $email->received_at->format('g:i A') }}</span>

                                            @if (!$email->is_reviewed)
                                                <div class="inbox-row-actions">
                                                    @if ($email->status === \App\Enums\SupplierEmailStatus::NewSupplier)
                                                        <button class="inbox-action-btn btn-confirm" onclick="reviewEmail({{ $email->id }}, 'confirm', event)">
                                                            {{ $email->supplier ? '✓ Confirm' : '+ Create Supplier' }}
                                                        </button>
                                                    @endif
                                                    <button class="inbox-action-btn btn-dismiss" onclick="reviewEmail({{ $email->id }}, 'dismiss', event)">✕ Dismiss</button>
                                                </div>
                                            @endif
                                        </div>
                                        @if ($hasDetail)
                                            <div class="inbox-detail" id="detail-{{ $email->id }}">
                                                @if ($email->ai_summary)
                                                    <p class="inbox-detail-body">
                                                        <span style="font-size:5.5pt;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:#555;">AI Summary</span><br>
                                                        {{ $email->ai_summary }}
                                                    </p>
                                                @endif
                                                <div class="inbox-detail-footer">
                                                    @if ($email->supplier)
                                                        <a href="{{ route('companies.suppliers.show', [$company, $email->supplier]) }}" class="inbox-detail-ref">
                                                            <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                                            {{ $email->supplier->name }}
                                                            @if (!$email->supplier->is_confirmed) — Unconfirmed @endif
                                                        </a>
                                                        <span class="inbox-detail-sep"></span>
                                                    @elseif ($email->status === \App\Enums\SupplierEmailStatus::NewSupplier)
                                                        <span class="inbox-detail-ref" style="color:#b45309;border-color:#b45309;">New sender — confirm to create supplier</span>
                                                        <span class="inbox-detail-sep"></span>
                                                    @endif
                                                    <span class="inbox-detail-meta">{{ $email->received_at->format('d M Y, g:i A') }}</span>
                                                    <span class="inbox-detail-sep"></span>
                                                    <span class="inbox-detail-meta">{{ $email->from_email }}</span>
                                                </div>
                                                @if ($email->attachments->isNotEmpty())
                                                    <div class="inbox-detail-attachments">
                                                        @foreach ($email->attachments as $att)
                                                            <span class="inbox-detail-file">
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

                        @if ($emails->hasPages())
                            <div class="inbox-pagination">
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

                    </div>
                </div>

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

            window.emailsFilter = function (raw) {
                var q = raw.trim().toLowerCase();
                var clearBtn = document.getElementById('inbox-search-clear');
                var countLabel = document.getElementById('inbox-search-count');
                clearBtn.style.display = q ? 'inline' : 'none';

                var rows = document.querySelectorAll('.inbox-row');
                var matches = 0;

                rows.forEach(function (row) {
                    if (!q) { row.style.display = ''; return; }

                    var title = (row.querySelector('.inbox-row-subject') || {}).textContent || '';
                    var sender = (row.querySelector('.inbox-row-sender') || {}).textContent || '';
                    var haystack = title + ' ' + sender;

                    if (haystack.toLowerCase().indexOf(q) === -1) {
                        row.style.display = 'none';
                        return;
                    }

                    row.style.display = '';
                    matches++;
                });

                document.querySelectorAll('.inbox-divider').forEach(function (div) {
                    var sibling = div.nextElementSibling;
                    var hasVisible = false;
                    while (sibling && !sibling.classList.contains('inbox-divider')) {
                        if (sibling.classList.contains('inbox-row') && sibling.style.display !== 'none') {
                            hasVisible = true; break;
                        }
                        sibling = sibling.nextElementSibling;
                    }
                    div.style.display = hasVisible ? '' : 'none';
                });

                countLabel.textContent = q ? (matches + ' match' + (matches !== 1 ? 'es' : '')) : '';
            };

            window.toggleDetail = function (id, e) {
                if (e.target.closest('.inbox-row-actions')) return;
                var d = document.getElementById('detail-' + id);
                if (d) d.classList.toggle('open');
            };

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
                    var actions = row.querySelector('.inbox-row-actions');
                    if (actions) actions.remove();

                    if (action === 'confirm') {
                        var badge = row.querySelector('.inbox-badge');
                        if (badge) {
                            badge.className = 'inbox-badge badge-matched';
                            badge.textContent = 'MATCHED';
                        }
                        row.className = row.className.replace(/status-\w+/, 'status-matched');
                        var src = row.querySelector('.inbox-avatar');
                        if (src) src.className = 'inbox-avatar src-matched';
                    }
                });
            };

            function buildRow(e) {
                var initials = (e.from_name || e.from_email).substring(0, 2).toUpperCase();
                var displayName = e.from_name || e.from_email.split('@')[0];
                var unreviewed = !e.is_reviewed ? ' unreviewed' : '';

                var badgesHtml = '<span class="inbox-badge badge-' + e.status + '">' + escHtml(e.status_label) + '</span>';
                if (e.attachment_count > 0) {
                    badgesHtml += ' <span class="inbox-badge badge-attach">' + e.attachment_count + ' ' + (e.attachment_count === 1 ? 'file' : 'files') + '</span>';
                }

                var actionsHtml = '';
                if (!e.is_reviewed) {
                    var confirmBtn = '';
                    if (e.status === 'new_supplier') {
                        var btnLabel = e.supplier_id ? '✓ Confirm' : '+ Create Supplier';
                        confirmBtn = '<button class="inbox-action-btn btn-confirm" onclick="reviewEmail(' + e.id + ', \'confirm\', event)">' + btnLabel + '</button>';
                    }
                    actionsHtml = '<div class="inbox-row-actions">' + confirmBtn +
                        '<button class="inbox-action-btn btn-dismiss" onclick="reviewEmail(' + e.id + ', \'dismiss\', event)">✕ Dismiss</button></div>';
                }

                var detailHtml = '';
                if (e.ai_summary || e.supplier_name) {
                    detailHtml = '<div class="inbox-detail" id="detail-' + e.id + '">';
                    if (e.ai_summary) {
                        detailHtml += '<p class="inbox-detail-body"><span style="font-size:5.5pt;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:#555;">AI Summary</span><br>' + escHtml(e.ai_summary) + '</p>';
                    }
                    detailHtml += '<div class="inbox-detail-footer">';
                    if (e.supplier_name) {
                        detailHtml += '<a href="/companies/' + companySlug + '/suppliers/' + e.supplier_id + '" class="inbox-detail-ref">' + escHtml(e.supplier_name) + '</a><span class="inbox-detail-sep"></span>';
                    }
                    detailHtml += '<span class="inbox-detail-meta">' + escHtml(e.from_email) + '</span>';
                    detailHtml += '</div></div>';
                }

                return '<div class="inbox-row status-' + e.status + unreviewed + '" id="email-' + e.id + '" onclick="toggleDetail(' + e.id + ', event)" style="animation:flashIn 1s ease">' +
                    '<div class="inbox-avatar src-' + e.status + '">' + initials + '</div>' +
                    '<div class="inbox-row-body"><span class="inbox-row-subject">' + escHtml(e.subject || '(No subject)') + '</span>' +
                    '<span class="inbox-row-sender">' + escHtml(displayName) + ' &lt;' + escHtml(e.from_email) + '&gt;</span></div>' +
                    '<div class="inbox-row-badges">' + badgesHtml + '</div>' +
                    '<span class="inbox-row-time">' + (e.received_time || '') + '</span>' +
                    actionsHtml + '</div>' + detailHtml;
            }

            function onEmail(data) {
                if (data.account_id !== accountId) return;

                var feed = document.querySelector('.inbox-feed');
                if (!feed) return;

                if (document.getElementById('email-' + data.email.id)) return;

                var empty = feed.querySelector('.inbox-empty');
                if (empty) empty.remove();

                var dateLabel = 'Today';
                var divider = null;
                feed.querySelectorAll('.inbox-divider').forEach(function (d) {
                    if (d.textContent.trim() === dateLabel) divider = d;
                });

                if (!divider) {
                    divider = document.createElement('div');
                    divider.className = 'inbox-divider';
                    divider.textContent = dateLabel;
                    feed.prepend(divider);
                }

                divider.insertAdjacentHTML('afterend', buildRow(data.email));

                var badge = document.querySelector('.inbox-count');
                if (badge) {
                    badge.textContent = parseInt(badge.textContent) + 1;
                } else {
                    var left = document.querySelector('.inbox-header-left');
                    if (left) {
                        var b = document.createElement('span');
                        b.className = 'inbox-count';
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
