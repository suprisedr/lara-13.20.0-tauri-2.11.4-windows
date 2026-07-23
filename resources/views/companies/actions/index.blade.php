@extends('layouts.public')

@section('title', $company->registered_name . ' — Actions')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        /* ── Channel header ── */
        .af-header {
            display:flex; align-items:center; justify-content:space-between;
            padding:8pt 10pt; border-bottom:1pt solid #c4b5fd;
            gap:6pt; flex-wrap:wrap;
        }
        .af-header-left { display:flex; align-items:center; gap:5pt; }
        .af-header-icon { font-size:9pt; color:#4c1d95; line-height:1; }
        .af-header-title {
            font-weight:700; font-size:7.5pt; text-transform:uppercase;
            letter-spacing:0.07em; color:#4c1d95;
        }
        .af-header-count {
            display:inline-flex; align-items:center; justify-content:center;
            background:#4c1d95; color:#fff;
            font-size:5.5pt; font-weight:700; letter-spacing:0.05em;
            min-width:10pt; height:10pt; padding:0 3pt; border-radius:0;
        }
        .af-header-right { display:flex; align-items:center; gap:4pt; flex-wrap:wrap; }

        .af-filter {
            font-size:6pt; padding:0 5pt;
            border:1px solid #c4b5fd; border-radius:0;
            background:#fff; color:#4c1d95; font-family:inherit; cursor:pointer;
            height:16pt; text-transform:uppercase; letter-spacing:0.06em; font-weight:700;
        }
        .af-filter:focus { outline:none; border-color:#4c1d95; }
        .af-search-wrap { position:relative; display:flex; align-items:center; }
        .af-search-input {
            height:16pt; border:1px solid #c4b5fd; border-radius:0;
            padding:0 12pt 0 5pt; font-size:6.5pt; font-family:inherit;
            color:#4c1d95; background:#fff; width:140pt; box-sizing:border-box;
        }
        .af-search-input:focus { outline:none; border-color:#4c1d95; }
        .af-search-clear {
            position:absolute; right:3pt; background:none; border:none;
            cursor:pointer; font-size:8pt; color:#8b7aad; line-height:1; padding:0;
            display:none;
        }
        .af-search-clear:hover { color:#4c1d95; }
        .af-search-count {
            font-size:5.5pt; color:#6b5b8a; white-space:nowrap;
        }
        .af-row mark.af-hl { background:#fef08a; border-radius:2px; padding:0 1px; font-weight:inherit; }

        .af-tabs { display:flex; }
        .af-tab {
            padding:0 7pt; font-size:5.5pt; font-weight:700;
            letter-spacing:0.08em; text-transform:uppercase;
            color:#4c1d95; background:#fff; border:1px solid #4c1d95;
            cursor:pointer; transition:background 0.15s, color 0.15s;
            height:16pt; font-family:inherit;
        }
        .af-tab + .af-tab { border-left:none; }
        .af-tab.active { background:#4c1d95; color:#fff; }
        .af-tab:hover:not(.active) { background:#f5f3ff; }

        /* ── Feed area ── */
        .af-feed { max-height:60vh; overflow-y:auto; }
        .af-feed::-webkit-scrollbar { width:4px; }
        .af-feed::-webkit-scrollbar-track { background:transparent; }
        .af-feed::-webkit-scrollbar-thumb { background:#c4b5fd; }

        .af-divider {
            display:flex; align-items:center; gap:6pt;
            padding:5pt 10pt 2pt;
            color:#6b5b8a; font-size:5.5pt; font-weight:700;
            letter-spacing:0.09em; text-transform:uppercase;
        }
        .af-divider::before, .af-divider::after {
            content:''; flex:1; height:1px; background:#c4b5fd;
        }

        /* ── Compact row ── */
        .af-row {
            display:flex; align-items:center; gap:5pt;
            padding:4pt 10pt;
            cursor:pointer; position:relative;
            transition:background 0.1s;
            border-left:3px solid transparent;
            border-bottom:0.4pt solid #ddd6fe;
        }
        .af-row:last-child { border-bottom:none; }
        .af-row:hover { background:#faf5ff; }
        .af-row.prio-high   { border-left-color:#b91c1c; }
        .af-row.prio-medium { border-left-color:#b45309; }
        .af-row.prio-low    { border-left-color:#8b7aad; }
        .af-row.is-resolved { opacity:0.5; border-left-color:#15803d; }
        .af-row.is-resolved .af-row-title {
            text-decoration:line-through; color:#8b7aad;
        }

        .af-src {
            flex-shrink:0; width:12pt; height:12pt;
            display:flex; align-items:center; justify-content:center;
            font-size:5pt; font-weight:700; letter-spacing:0.05em;
            background:#fff; border:1px solid #4c1d95;
        }
        .af-src.src-manual { color:#4c1d95; border-color:#4c1d95; }
        .af-src.src-ai     { color:#92400e; border-color:#92400e; }
        .af-src.src-system { color:#1d4ed8; border-color:#1d4ed8; }

        .af-row-title {
            flex:1; min-width:0;
            font-size:7pt; font-weight:600; color:#4c1d95;
            white-space:nowrap; overflow:hidden; text-overflow:ellipsis; line-height:1.3;
        }

        .af-row-badges { display:flex; align-items:center; gap:2pt; flex-shrink:0; }

        .af-badge {
            font-size:5pt; font-weight:700;
            padding:1pt 4pt; border-radius:0;
            border:1px solid #4c1d95; background:#fff;
            text-transform:uppercase; letter-spacing:0.08em;
        }
        .badge-high   { color:#b91c1c; border-color:#b91c1c; }
        .badge-medium { color:#b45309; border-color:#b45309; }
        .badge-low    { color:#6b5b8a; border-color:#8b7aad; }
        .badge-ai     { color:#92400e; border-color:#92400e; }
        .badge-system { color:#1d4ed8; border-color:#1d4ed8; }

        .af-row-time {
            flex-shrink:0; font-size:6pt; color:#8b7aad;
            min-width:28pt; text-align:right;
            font-family:'Courier New',monospace;
        }

        .af-row-actions {
            position:absolute; right:8pt; display:none;
            align-items:center; gap:2pt;
            background:#fff; border:1px solid #4c1d95;
            box-shadow:0 2px 6px rgba(22,53,92,0.12);
            padding:2pt 3pt; border-radius:0;
        }
        .af-row:hover .af-row-actions { display:flex; }

        .af-row-btn {
            background:none; border:none; cursor:pointer;
            padding:2pt 4pt; font-family:inherit;
            font-size:5.5pt; font-weight:700; letter-spacing:0.06em;
            text-transform:uppercase; color:#6b5b8a;
            transition:background 0.1s, color 0.1s;
            white-space:nowrap;
        }
        .af-row-btn:hover { background:#f5f3ff; color:#4c1d95; }
        .af-row-btn.btn-resolve { color:#15803d; }
        .af-row-btn.btn-resolve:hover { background:#15803d; color:#fff; }
        .af-row-btn.btn-reopen { color:#1d4ed8; }
        .af-row-btn.btn-reopen:hover { background:#1d4ed8; color:#fff; }
        .af-row-btn.btn-delete { color:#b91c1c; }
        .af-row-btn.btn-delete:hover { background:#b91c1c; color:#fff; }

        /* ── Expanded detail ── */
        .af-detail {
            display:none;
            margin:0 10pt 0 30pt;
            border-left:2px solid #4c1d95;
            padding:5pt 0 6pt 8pt;
            border-bottom:0.4pt solid #ddd6fe;
            background:#faf5ff;
        }
        .af-detail.open { display:block; }

        .af-detail-body {
            font-size:7pt; color:#23282d; line-height:1.55; margin:0 0 4pt;
        }
        .af-detail-footer {
            display:flex; align-items:center; flex-wrap:wrap; gap:4pt;
        }
        .af-detail-ref {
            display:inline-flex; align-items:center; gap:3pt;
            font-size:5.5pt; font-weight:700; color:#4c1d95;
            background:#fff; border:1px solid #4c1d95;
            padding:1pt 4pt;
            text-transform:uppercase; letter-spacing:0.07em;
        }
        .af-detail-meta { font-size:6pt; color:#8b7aad; }
        .af-detail-sep {
            width:3px; height:3px; border-radius:50%;
            background:#c4b5fd; flex-shrink:0;
        }

        /* ── Pagination ── */
        .af-pagination {
            display:flex; align-items:center; justify-content:center;
            gap:3pt; padding:7pt 10pt; border-top:1pt solid #c4b5fd;
        }
        .af-pagination a, .af-pagination span {
            display:inline-flex; align-items:center; justify-content:center;
            min-width:15pt; height:15pt;
            font-size:6.5pt; font-weight:700;
            text-decoration:none; color:#6b5b8a;
            border:1px solid #c4b5fd; background:#fff;
            padding:0 4pt; border-radius:0; font-family:inherit;
        }
        .af-pagination a:hover { border-color:#4c1d95; color:#4c1d95; }
        .af-pagination span.current { background:#4c1d95; color:#fff; border-color:#4c1d95; }

        /* ── Compose bar ── */
        .af-compose { padding:7pt 10pt; border-top:1pt solid #c4b5fd; }

        .af-compose-trigger {
            display:flex; align-items:center; gap:4pt;
            border:1px dashed #8b7aad; padding:4pt 7pt;
            cursor:text; color:#6b5b8a; font-size:6.5pt;
            background:#faf5ff; user-select:none; font-weight:700;
            text-transform:uppercase; letter-spacing:0.06em;
            transition:border-color 0.15s, background 0.15s, color 0.15s;
        }
        .af-compose-trigger:hover { border-color:#4c1d95; color:#4c1d95; background:#fff; }

        .af-compose-form {
            display:none; flex-direction:column; gap:4pt;
            border:1px solid #4c1d95; padding:7pt; background:#faf5ff;
        }
        .af-compose-form.open { display:flex; }

        .af-compose-row {
            display:grid; grid-template-columns:1fr 100pt; gap:4pt;
        }

        .af-compose-input, .af-compose-select, .af-compose-textarea {
            font-size:7pt; padding:3pt 5pt;
            border:1px solid #c4b5fd; border-radius:0;
            color:#4c1d95; font-family:inherit; background:#fff; box-sizing:border-box;
        }
        .af-compose-input:focus,
        .af-compose-select:focus,
        .af-compose-textarea:focus { outline:none; border-color:#4c1d95; }

        .af-compose-input  { height:16pt; }
        .af-compose-select { height:16pt; cursor:pointer; text-transform:uppercase; font-weight:700; letter-spacing:0.05em; font-size:6pt; }
        .af-compose-textarea { resize:vertical; min-height:20pt; line-height:1.45; }

        .af-compose-footer {
            display:flex; align-items:center; justify-content:space-between;
            gap:6pt; flex-wrap:wrap;
        }
        .af-compose-hint { font-size:5.5pt; color:#8b7aad; letter-spacing:0.04em; }
        .af-compose-btns { display:flex; gap:4pt; }

        .af-btn-cancel {
            background:none; border:none; padding:0; cursor:pointer;
            font-family:inherit; font-size:6pt; font-weight:700;
            color:#6b5b8a; text-decoration:underline;
            text-transform:uppercase; letter-spacing:0.06em;
        }
        .af-btn-cancel:hover { color:#4c1d95; }

        .af-kbd {
            background:#fff; border:1px solid #c4b5fd; border-radius:0;
            padding:0 2pt; font-size:5.5pt; font-family:'Courier New',monospace;
            color:#6b5b8a;
        }

        /* ── Empty state ── */
        .af-empty {
            display:flex; flex-direction:column; align-items:center; justify-content:center;
            color:#8b7aad; gap:4pt; padding:24pt 8pt;
        }
        .af-empty-icon { font-size:16pt; color:#c4b5fd; line-height:1; }
        .af-empty-title {
            font-size:7.5pt; font-weight:700; color:#4c1d95;
            text-transform:uppercase; letter-spacing:0.07em;
        }
        .af-empty-sub { font-size:6.5pt; }

        /* ── Export dropdown ── */
        .af-export-menu {
            display:none; position:absolute; right:0; top:100%; margin-top:2px;
            background:#fff; border:1px solid #c4b5fd; z-index:20; min-width:90pt;
            box-shadow:0 4px 16px rgba(22,53,92,0.12);
        }
        .af-export-menu a {
            display:block; padding:3pt 6pt;
            font-size:6pt; font-weight:700; letter-spacing:0.06em;
            text-transform:uppercase; color:#4c1d95; text-decoration:none;
        }
        .af-export-menu a:hover { background:#f5f3ff; }
        .af-export-menu a + a { border-top:0.4pt solid #ddd6fe; }
    </style>
@endpush

@section('content')
    <div class="co-wrap">
        @include('companies._topbar')

        <div class="co-body">
            @include('companies._sidebar')

            <main class="co-main">

                @if (session('success'))
                    <div style="background:#dcfce7;border:1px solid #bbf7d0;color:#15803d;padding:4pt 8pt;font-size:7pt;font-weight:600;margin-bottom:10pt;">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="co-section-heading"><p class="co-section-label">Company Actions</p><h2>Actions &amp; Flags</h2></div>

                <div class="reg-doc">

                    {{-- Header --}}
                    <div class="af-header">
                        <div class="af-header-left">
                            <span class="af-header-icon">⚑</span>
                            <span class="af-header-title">Feed</span>
                            @if (request('tab', 'open') === 'open' && $open->total() > 0)
                                <span class="af-header-count">{{ $open->total() }}</span>
                            @endif
                        </div>
                        <div class="af-header-right">
                            <div class="af-search-wrap">
                                <input type="text" class="af-search-input" id="af-search-input" placeholder="Search actions…" autocomplete="off" oninput="actionsFilter(this.value)">
                                <button class="af-search-clear" id="af-search-clear" onclick="actionsFilter('');document.getElementById('af-search-input').value='';" title="Clear">&times;</button>
                            </div>
                            <span class="af-search-count" id="af-search-count"></span>
                            <select class="af-filter" onchange="filterPriority(this.value)">
                                <option value="">All priorities</option>
                                <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>High</option>
                                <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>Medium</option>
                                <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>Low</option>
                            </select>
                            <select class="af-filter" onchange="filterSource(this.value)">
                                <option value="">All sources</option>
                                <option value="manual" {{ request('source') === 'manual' ? 'selected' : '' }}>Manual</option>
                                <option value="ai-agent" {{ request('source') === 'ai-agent' ? 'selected' : '' }}>AI Agent</option>
                                <option value="system" {{ request('source') === 'system' ? 'selected' : '' }}>System</option>
                            </select>
                            <div class="af-tabs">
                                <button class="af-tab {{ request('tab', 'open') === 'open' ? 'active' : '' }}" onclick="switchTab('open')">Open</button>
                                <button class="af-tab {{ request('tab') === 'resolved' ? 'active' : '' }}" onclick="switchTab('resolved')">Resolved</button>
                            </div>
                            <div style="position:relative;display:inline-block;" id="export-wrap">
                                <button class="reg-btn" onclick="document.getElementById('export-menu').style.display=document.getElementById('export-menu').style.display==='block'?'none':'block'" type="button">↓ Export</button>
                                <div id="export-menu" class="af-export-menu">
                                    <a href="{{ route('companies.actions.export', [$company, 'tab' => request('tab', 'open'), 'format' => 'xlsx']) }}">Excel (.xlsx)</a>
                                    <a href="{{ route('companies.actions.export', [$company, 'tab' => request('tab', 'open'), 'format' => 'csv']) }}">CSV (.csv)</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Feed --}}
                    @php
                        $items = request('tab') === 'resolved' ? $resolved : $open;
                        $dateField = request('tab') === 'resolved' ? 'resolved_at' : 'created_at';
                        $isResolved = request('tab') === 'resolved';
                    @endphp

                    <div class="af-feed">
                        @if ($items->isEmpty())
                            <div class="af-empty">
                                <div class="af-empty-icon">{{ $isResolved ? '◯' : '✓' }}</div>
                                <div class="af-empty-title">{{ $isResolved ? 'Nothing resolved yet' : 'All clear' }}</div>
                                <div class="af-empty-sub">{{ $isResolved ? 'Resolved actions will appear here.' : 'No open actions — use the compose bar below.' }}</div>
                            </div>
                        @else
                            @php
                                $grouped = $items->groupBy(function($a) use ($dateField) {
                                    $d = $a->$dateField;
                                    return $d->isToday() ? 'Today' : ($d->isYesterday() ? 'Yesterday' : $d->format('d M Y'));
                                });
                            @endphp
                            @foreach ($grouped as $label => $actions)
                                <div class="af-divider">{{ $label }}</div>
                                @foreach ($actions as $action)
                                    @php
                                        $src = $action->source === 'ai-agent' ? 'ai' : ($action->source === 'system' ? 'system' : 'manual');
                                        $srcLabel = $src === 'ai' ? 'AI' : ($src === 'system' ? 'SYS' : 'ME');
                                        $hasDetail = $action->body || $action->related_type;
                                    @endphp
                                    <div class="af-row prio-{{ $action->priority }} {{ $isResolved ? 'is-resolved' : '' }}"
                                         id="action-{{ $action->id }}"
                                         onclick="toggleDetail({{ $action->id }}, event)">
                                        <div class="af-src src-{{ $src }}">{{ $srcLabel }}</div>
                                        <span class="af-row-title">{{ $action->title }}</span>
                                        <div class="af-row-badges">
                                            <span class="af-badge badge-{{ $action->priority }}">{{ $action->priority }}</span>
                                            @if ($action->source === 'ai-agent')
                                                <span class="af-badge badge-ai">AI</span>
                                            @elseif ($action->source === 'system')
                                                <span class="af-badge badge-system">SYS</span>
                                            @endif
                                        </div>
                                        <span class="af-row-time">{{ $action->$dateField->format('g:i A') }}</span>

                                        <div class="af-row-actions">
                                            @if ($isResolved)
                                                <button class="af-row-btn btn-reopen" onclick="reopenAction({{ $action->id }}, event)">↩ Reopen</button>
                                            @else
                                                <button class="af-row-btn btn-resolve" onclick="resolveAction({{ $action->id }}, event)">✓</button>
                                            @endif
                                            <button class="af-row-btn btn-delete" onclick="deleteAction({{ $action->id }}, event)">✕</button>
                                        </div>
                                    </div>
                                    @if ($hasDetail)
                                        <div class="af-detail" id="detail-{{ $action->id }}">
                                            @if ($action->body)
                                                <p class="af-detail-body">{{ $action->body }}</p>
                                            @endif
                                            <div class="af-detail-footer">
                                                @if ($action->related_type)
                                                    <span class="af-detail-ref">
                                                        <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                                                        {{ $action->related_type }}{{ $action->related_id ? ' #'.$action->related_id : '' }}
                                                    </span>
                                                    <span class="af-detail-sep"></span>
                                                @endif
                                                <span class="af-detail-meta">{{ $action->created_at->format('d M Y, g:i A') }}</span>
                                                @if ($action->resolved_at)
                                                    <span class="af-detail-sep"></span>
                                                    <span class="af-detail-meta">Resolved {{ $action->resolved_at->format('d M Y, g:i A') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            @endforeach
                        @endif
                    </div>

                    {{-- Pagination --}}
                    @if ($items->hasPages())
                        <div class="af-pagination">
                            @if ($items->onFirstPage())
                                <span style="opacity:0.3;">&lsaquo;</span>
                            @else
                                <a href="{{ $items->appends(request()->query())->previousPageUrl() }}">&lsaquo;</a>
                            @endif

                            @foreach ($items->getUrlRange(max(1, $items->currentPage() - 3), min($items->lastPage(), $items->currentPage() + 3)) as $page => $url)
                                @if ($page == $items->currentPage())
                                    <span class="current">{{ $page }}</span>
                                @else
                                    <a href="{{ $url . '&' . http_build_query(request()->except('page')) }}">{{ $page }}</a>
                                @endif
                            @endforeach

                            @if ($items->hasMorePages())
                                <a href="{{ $items->appends(request()->query())->nextPageUrl() }}">&rsaquo;</a>
                            @else
                                <span style="opacity:0.3;">&rsaquo;</span>
                            @endif
                        </div>
                    @endif

                    {{-- Compose --}}
                    <div class="af-compose">
                        <div class="af-compose-trigger" id="compose-trigger" onclick="openCompose()">
                            <span style="font-size:8pt;color:#8b7aad;font-weight:400;line-height:1;">＋</span>
                            Add an action…
                        </div>
                        <form class="af-compose-form" id="compose-form" onsubmit="return submitAction(event)">
                            <div class="af-compose-row">
                                <input type="text" name="title" class="af-compose-input" placeholder="Action title…" required maxlength="255" id="compose-title">
                                <select name="priority" class="af-compose-select">
                                    <option value="high">High</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="low">Low</option>
                                </select>
                            </div>
                            <textarea name="body" class="af-compose-textarea" placeholder="Details (optional)…" rows="2"></textarea>
                            <div class="af-compose-footer">
                                <span class="af-compose-hint"><kbd class="af-kbd">Esc</kbd> cancel</span>
                                <div class="af-compose-btns">
                                    <button type="button" class="af-btn-cancel" onclick="closeCompose()">Cancel</button>
                                    <button type="submit" class="reg-btn primary">Add Action</button>
                                </div>
                            </div>
                        </form>
                    </div>

                </div>{{-- /reg-doc --}}
            </main>
        </div>
    </div>

    <script>
        const baseUrl = '{{ route('companies.actions.index', $company) }}';

        function buildUrl(params) {
            const url = new URL(window.location.href);
            Object.entries(params).forEach(([k, v]) => {
                if (v) url.searchParams.set(k, v);
                else url.searchParams.delete(k);
            });
            url.searchParams.delete('page');
            return url.toString();
        }

        function switchTab(tab)      { window.location = buildUrl({ tab }); }
        function filterPriority(val)  { window.location = buildUrl({ priority: val }); }
        function filterSource(val)    { window.location = buildUrl({ source: val }); }

        function actionsFilter(raw) {
            const q = raw.trim().toLowerCase();
            const clearBtn   = document.getElementById('af-search-clear');
            const countLabel = document.getElementById('af-search-count');
            clearBtn.style.display = q ? 'inline' : 'none';

            const re = q ? new RegExp('(' + q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi') : null;
            const rows = document.querySelectorAll('.af-row');
            let matches = 0;

            rows.forEach(row => {
                // Strip prior highlights
                row.querySelectorAll('.af-hl').forEach(m => { m.outerHTML = m.textContent; });

                if (!q) { row.style.display = ''; return; }

                const titleEl = row.querySelector('.af-row-title');
                const bodyEl  = row.querySelector('.af-detail-body');
                const haystack = (titleEl?.textContent || '') + ' ' + (bodyEl?.textContent || '');

                if (!haystack.toLowerCase().includes(q)) {
                    row.style.display = 'none';
                    return;
                }

                row.style.display = '';
                matches++;

                // Highlight in title
                [titleEl, bodyEl].forEach(el => {
                    if (!el) return;
                    re.lastIndex = 0;
                    if (!re.test(el.textContent)) return;
                    re.lastIndex = 0;
                    const walker = document.createTreeWalker(el, NodeFilter.SHOW_TEXT);
                    const nodes = [];
                    let n;
                    while ((n = walker.nextNode())) nodes.push(n);
                    nodes.forEach(tn => {
                        re.lastIndex = 0;
                        if (!re.test(tn.textContent)) return;
                        re.lastIndex = 0;
                        const span = document.createElement('span');
                        span.innerHTML = tn.textContent.replace(re, '<mark class="af-hl">$1</mark>');
                        tn.parentNode.replaceChild(span, tn);
                    });
                    re.lastIndex = 0;
                });
            });

            // Hide date-dividers that have no visible rows after them
            document.querySelectorAll('.af-divider').forEach(div => {
                let sibling = div.nextElementSibling;
                let hasVisible = false;
                while (sibling && !sibling.classList.contains('af-divider')) {
                    if (sibling.classList.contains('af-row') && sibling.style.display !== 'none') {
                        hasVisible = true; break;
                    }
                    sibling = sibling.nextElementSibling;
                }
                div.style.display = hasVisible ? '' : 'none';
            });

            countLabel.textContent = q ? (matches + ' match' + (matches !== 1 ? 'es' : '')) : '';
        }

        function toggleDetail(id, e) {
            if (e.target.closest('.af-row-actions')) return;
            const d = document.getElementById('detail-' + id);
            if (d) d.classList.toggle('open');
        }

        function openCompose() {
            document.getElementById('compose-trigger').style.display = 'none';
            document.getElementById('compose-form').classList.add('open');
            document.getElementById('compose-title').focus();
        }

        function closeCompose() {
            document.getElementById('compose-form').classList.remove('open');
            document.getElementById('compose-trigger').style.display = 'flex';
        }

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') { closeCompose(); document.getElementById('export-menu').style.display='none'; }
        });
        document.addEventListener('click', e => {
            if (!e.target.closest('#export-wrap')) document.getElementById('export-menu').style.display='none';
        });

        const csrfToken = '{{ csrf_token() }}';
        const storeUrl  = '{{ route('companies.actions.store', $company) }}';

        function removeRow(id) {
            const el = document.getElementById('action-' + id);
            const detail = document.getElementById('detail-' + id);
            if (el) {
                el.style.transition = 'opacity 0.3s, max-height 0.3s';
                el.style.opacity = '0';
                el.style.maxHeight = '0';
                el.style.overflow = 'hidden';
                setTimeout(() => {
                    el.remove();
                    if (detail) detail.remove();
                    cleanDividers();
                }, 350);
            }
            if (detail) detail.style.display = 'none';
            updateCount(-1);
        }

        function updateCount(delta) {
            const badge = document.querySelector('.af-header-count');
            if (!badge) return;
            const n = parseInt(badge.textContent) + delta;
            if (n <= 0) badge.remove(); else badge.textContent = n;
        }

        function cleanDividers() {
            document.querySelectorAll('.af-divider').forEach(div => {
                let sibling = div.nextElementSibling;
                let hasRow = false;
                while (sibling && !sibling.classList.contains('af-divider')) {
                    if (sibling.classList.contains('af-row') && sibling.style.display !== 'none') { hasRow = true; break; }
                    sibling = sibling.nextElementSibling;
                }
                div.style.display = hasRow ? '' : 'none';
            });
        }

        async function resolveAction(id, e) {
            e.stopPropagation();
            const res = await fetch(baseUrl + '/' + id + '/resolve', {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            });
            if (res.ok) removeRow(id);
        }

        async function reopenAction(id, e) {
            e.stopPropagation();
            const res = await fetch(baseUrl + '/' + id + '/reopen', {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            });
            if (res.ok) removeRow(id);
        }

        async function deleteAction(id, e) {
            e.stopPropagation();
            const res = await fetch(baseUrl + '/' + id, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            });
            if (res.ok) removeRow(id);
        }

        async function submitAction(e) {
            e.preventDefault();
            const form = document.getElementById('compose-form');
            const title    = form.querySelector('[name=title]').value.trim();
            const priority = form.querySelector('[name=priority]').value;
            const body     = form.querySelector('[name=body]').value.trim();
            if (!title) return false;

            const res = await fetch(storeUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ title, priority, body: body || null }),
            });

            if (!res.ok) return false;

            const data = await res.json();
            const a = data.action;

            // Build new row HTML
            const rowHtml = `
                <div class="af-row prio-${a.priority}" id="action-${a.id}" onclick="toggleDetail(${a.id}, event)">
                    <div class="af-src src-manual">ME</div>
                    <span class="af-row-title">${escHtml(a.title)}</span>
                    <div class="af-row-badges">
                        <span class="af-badge badge-${a.priority}">${a.priority}</span>
                    </div>
                    <span class="af-row-time">${a.created_at}</span>
                    <div class="af-row-actions">
                        <button class="af-row-btn btn-resolve" onclick="resolveAction(${a.id}, event)">✓</button>
                        <button class="af-row-btn btn-delete" onclick="deleteAction(${a.id}, event)">✕</button>
                    </div>
                </div>
                ${a.body ? `<div class="af-detail" id="detail-${a.id}"><p class="af-detail-body">${escHtml(a.body)}</p></div>` : ''}
            `;

            // Find or create the date divider
            const feed = document.querySelector('.af-feed');
            const empty = feed.querySelector('.af-empty');
            if (empty) empty.remove();

            let divider = null;
            feed.querySelectorAll('.af-divider').forEach(d => {
                if (d.textContent.trim() === a.date_label) divider = d;
            });

            if (!divider) {
                divider = document.createElement('div');
                divider.className = 'af-divider';
                divider.textContent = a.date_label;
                feed.prepend(divider);
            }

            if (!document.getElementById('action-' + a.id)) {
                divider.insertAdjacentHTML('afterend', rowHtml);
                updateCount(1);
            }

            // Reset form
            form.querySelector('[name=title]').value = '';
            form.querySelector('[name=body]').value = '';
            form.querySelector('[name=priority]').value = 'medium';
            closeCompose();

            return false;
        }

        function escHtml(str) {
            const d = document.createElement('div');
            d.textContent = str;
            return d.innerHTML;
        }

        // ── Real-time updates via Reverb ──
        window.addEventListener('echo:ready', function () {
            window.Echo.private('company.{{ $company->id }}')
                .listen('.action.updated', (e) => {
                    const a = e.action;
                    const currentTab = new URL(window.location.href).searchParams.get('tab') || 'open';

                    if (e.type === 'deleted') {
                        const el = document.getElementById('action-' + a.id);
                        if (el) removeRow(a.id);
                        return;
                    }

                    if (e.type === 'resolved' && currentTab === 'open') {
                        const el = document.getElementById('action-' + a.id);
                        if (el) removeRow(a.id);
                        return;
                    }

                    if (e.type === 'reopened' && currentTab === 'resolved') {
                        const el = document.getElementById('action-' + a.id);
                        if (el) removeRow(a.id);
                        return;
                    }

                    if (e.type === 'created' && currentTab === 'open') {
                        if (document.getElementById('action-' + a.id)) return;

                        const src = a.source === 'ai-agent' ? 'ai' : (a.source === 'system' ? 'system' : 'manual');
                        const srcLabel = src === 'ai' ? 'AI' : (src === 'system' ? 'SYS' : 'ME');

                        let badges = `<span class="af-badge badge-${a.priority}">${a.priority}</span>`;
                        if (a.source === 'ai-agent') badges += ' <span class="af-badge badge-ai">AI</span>';
                        else if (a.source === 'system') badges += ' <span class="af-badge badge-system">SYS</span>';

                        const rowHtml = `
                            <div class="af-row prio-${a.priority}" id="action-${a.id}" onclick="toggleDetail(${a.id}, event)">
                                <div class="af-src src-${src}">${srcLabel}</div>
                                <span class="af-row-title">${escHtml(a.title)}</span>
                                <div class="af-row-badges">${badges}</div>
                                <span class="af-row-time">${a.created_at}</span>
                                <div class="af-row-actions">
                                    <button class="af-row-btn btn-resolve" onclick="resolveAction(${a.id}, event)">✓</button>
                                    <button class="af-row-btn btn-delete" onclick="deleteAction(${a.id}, event)">✕</button>
                                </div>
                            </div>
                            ${a.body ? `<div class="af-detail" id="detail-${a.id}"><p class="af-detail-body">${escHtml(a.body)}</p></div>` : ''}
                        `;

                        const feed = document.querySelector('.af-feed');
                        const empty = feed.querySelector('.af-empty');
                        if (empty) empty.remove();

                        let divider = null;
                        feed.querySelectorAll('.af-divider').forEach(d => {
                            if (d.textContent.trim() === a.date_label) divider = d;
                        });

                        if (!divider) {
                            divider = document.createElement('div');
                            divider.className = 'af-divider';
                            divider.textContent = a.date_label;
                            feed.prepend(divider);
                        }

                        divider.insertAdjacentHTML('afterend', rowHtml);
                        updateCount(1);

                        // Flash the new row
                        const newEl = document.getElementById('action-' + a.id);
                        if (newEl) {
                            newEl.style.background = '#fef9c3';
                            setTimeout(() => { newEl.style.transition = 'background 1s'; newEl.style.background = ''; }, 100);
                        }
                    }
                });
        });
    </script>
@endsection
