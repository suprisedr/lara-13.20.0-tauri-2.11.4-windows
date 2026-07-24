@extends('layouts.public')

@section('title', 'Troubleshooting')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    <style>
        :root {
            --ts-purple: #4c1d95;
            --ts-purple-light: #7c3aed;
            --ts-purple-bg: #f5f3ff;
            --ts-purple-border: #c4b5fd;
            --ts-purple-muted: #8b7aad;
            --ts-purple-faint: #ede9fe;
            --ts-purple-deep: #6b5b8a;
            --ts-red: #b91c1c;
            --ts-red-bg: #fee2e2;
            --ts-green: #15803d;
            --ts-green-bg: #dcfce7;
            --ts-amber: #92400e;
            --ts-amber-bg: #fef9c3;
            --ts-text: #23282d;
            --ts-font: Helvetica, Arial, "DejaVu Sans", sans-serif;
            --ts-mono: "DejaVu Sans Mono", "Courier New", monospace;
        }

        .ts-wrap { min-height: 100vh; background: #f6f9fc; font-family: var(--ts-font); display: flex; flex-direction: column; padding-top: 2.5rem; }

        /* ── Topbar (reuses co-topbar pattern) ── */
        .ts-topbar {
            background: var(--ts-purple);
            padding: 5pt 16pt;
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
            display: flex; align-items: center; gap: 8pt;
            flex-shrink: 0;
        }
        .ts-topbar-inner { position: relative; z-index: 1; display: flex; align-items: center; gap: 8pt; width: 100%; }
        .ts-topbar-back {
            font-size: 6.5pt; font-weight: 600; letter-spacing: 0.04em;
            color: var(--ts-purple-border); text-decoration: none;
            display: inline-flex; align-items: center; gap: 2pt;
            padding: 2pt 4pt; transition: color 0.15s; white-space: nowrap;
        }
        .ts-topbar-back:hover { color: #fff; }
        .ts-topbar-divider { width: 1px; height: 10pt; background: var(--ts-purple-deep); flex-shrink: 0; }
        .ts-topbar-title { font-size: 8pt; font-weight: 700; color: #fff; margin: 0; letter-spacing: -0.01em; white-space: nowrap; }
        .ts-topbar-meta { font-size: 6pt; color: #a78bfa; margin: 0; white-space: nowrap; }
        .ts-topbar-actions { margin-left: auto; display: flex; align-items: center; gap: 5pt; }

        .ts-topbar-status {
            display: inline-flex; align-items: center; gap: 3pt;
            font-size: 5.5pt; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.08em; padding: 2pt 6pt;
            border: 1px solid var(--ts-purple-deep); color: var(--ts-purple-border);
            transition: all 0.3s;
        }
        .ts-topbar-status.live { border-color: var(--ts-green); color: #86efac; }
        .ts-topbar-status .ts-status-dot {
            width: 5pt; height: 5pt; border-radius: 50%; flex-shrink: 0;
            background: var(--ts-purple-deep); transition: background 0.3s;
        }
        .ts-topbar-status.live .ts-status-dot {
            background: var(--ts-green);
            animation: ts-pulse 1.4s ease-in-out infinite;
        }
        @keyframes ts-pulse { 0%,100% { opacity: 1; } 50% { opacity: 0.35; } }

        .ts-topbar-btn {
            display: inline-flex; align-items: center; gap: 2pt;
            font-size: 6pt; font-weight: 600; padding: 2pt 6pt;
            text-decoration: none; border: 1px solid var(--ts-purple-deep);
            color: var(--ts-purple-border); background: transparent;
            cursor: pointer; transition: background 0.15s, color 0.15s;
            white-space: nowrap; font-family: inherit;
        }
        .ts-topbar-btn:hover { background: rgba(255,255,255,0.1); color: #fff; }

        @media (max-width: 760px) {
            .ts-topbar { padding: 4pt 8pt; }
            .ts-topbar-meta { display: none; }
        }

        /* ── Content ── */
        .ts-content { max-width: 1280px; margin: 0 auto; padding: 12pt 16pt; width: 100%; }
        @media (max-width: 760px) { .ts-content { padding: 8pt 6pt; } }

        /* ── Toast ── */
        .ts-toast-area { position: fixed; top: 36pt; right: 12pt; z-index: 200; display: flex; flex-direction: column; gap: 4pt; }
        .ts-toast {
            padding: 4pt 8pt; font-size: 6.5pt; font-weight: 600;
            border: 1px solid; display: flex; align-items: center; gap: 4pt;
            animation: ts-toast-in 0.25s ease-out; font-family: var(--ts-font);
            max-width: 240pt;
        }
        .ts-toast.success { background: var(--ts-green-bg); border-color: #bbf7d0; color: var(--ts-green); }
        .ts-toast.error { background: var(--ts-red-bg); border-color: #fecaca; color: var(--ts-red); }
        .ts-toast.info { background: var(--ts-purple-bg); border-color: var(--ts-purple-border); color: var(--ts-purple); }
        @keyframes ts-toast-in { from { opacity: 0; transform: translateX(20pt); } to { opacity: 1; transform: translateX(0); } }

        /* ── Section card ── */
        .ts-card {
            background: #fff; border: 1px solid var(--ts-purple-border);
            font-size: 7pt; line-height: 1.45; margin-bottom: 10pt;
        }
        .ts-card-header {
            border-bottom: 1.5pt solid var(--ts-purple);
            padding: 6pt 10pt; display: flex; align-items: center;
            justify-content: space-between; gap: 6pt; flex-wrap: wrap;
        }
        .ts-card-header-left { display: flex; align-items: center; gap: 5pt; }
        .ts-card-header-icon { font-size: 9pt; color: var(--ts-purple); line-height: 1; }
        .ts-card-header-title {
            font-size: 7pt; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.07em; color: var(--ts-purple);
        }
        .ts-card-header-count {
            display: inline-flex; align-items: center; justify-content: center;
            background: var(--ts-purple); color: #fff;
            font-size: 5.5pt; font-weight: 700; min-width: 10pt; height: 10pt; padding: 0 3pt;
        }
        .ts-card-header-right { display: flex; align-items: center; gap: 4pt; flex-wrap: wrap; }
        .ts-card-body { padding: 0; }

        /* ── Overview grid (circuits + queues side by side) ── */
        .ts-overview-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10pt; }
        @media (max-width: 900px) { .ts-overview-grid { grid-template-columns: 1fr; } }

        /* ── Circuit breaker cards ── */
        .ts-circuits-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(130pt, 1fr)); gap: 5pt; padding: 8pt 10pt; }
        .ts-circuit {
            display: flex; align-items: center; gap: 6pt;
            padding: 6pt 8pt; border: 1px solid var(--ts-purple-border);
            background: #fff; transition: border-color 0.3s, background 0.3s;
            position: relative; overflow: hidden;
        }
        .ts-circuit::before {
            content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 3px;
            background: var(--ts-green); transition: background 0.3s;
        }
        .ts-circuit.state-open::before { background: var(--ts-red); }
        .ts-circuit.state-half_open::before { background: var(--ts-amber); }
        .ts-circuit.state-open { border-color: #fecaca; background: #fef2f2; }
        .ts-circuit.state-half_open { border-color: #fde68a; background: #fffbeb; }

        .ts-circuit-info { flex: 1; min-width: 0; }
        .ts-circuit-name { font-size: 6.5pt; font-weight: 600; color: var(--ts-purple); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .ts-circuit-state-label { font-size: 5pt; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 700; margin-top: 1pt; }
        .ts-circuit-state-label.st-closed { color: var(--ts-green); }
        .ts-circuit-state-label.st-open { color: var(--ts-red); }
        .ts-circuit-state-label.st-half_open { color: var(--ts-amber); }

        .ts-circuit-dot {
            width: 7pt; height: 7pt; border-radius: 50%; flex-shrink: 0;
            transition: background 0.3s, box-shadow 0.3s;
        }
        .ts-circuit-dot.dot-closed { background: var(--ts-green); box-shadow: 0 0 0 2pt rgba(21,128,61,0.15); }
        .ts-circuit-dot.dot-open { background: var(--ts-red); box-shadow: 0 0 0 2pt rgba(185,28,28,0.15); animation: ts-pulse 1s ease-in-out infinite; }
        .ts-circuit-dot.dot-half_open { background: #d97706; box-shadow: 0 0 0 2pt rgba(217,119,6,0.15); animation: ts-pulse 2s ease-in-out infinite; }

        /* ── Queue bars ── */
        .ts-queues-list { padding: 8pt 10pt; }
        .ts-queue-row { display: flex; align-items: center; gap: 6pt; padding: 4pt 0; border-bottom: 0.4pt solid var(--ts-purple-faint); }
        .ts-queue-row:last-child { border-bottom: none; }
        .ts-queue-name { font-size: 6.5pt; font-weight: 600; color: var(--ts-purple); flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .ts-queue-bar-wrap { width: 80pt; height: 6pt; background: var(--ts-purple-faint); flex-shrink: 0; overflow: hidden; }
        .ts-queue-bar { height: 100%; background: var(--ts-purple-light); transition: width 0.5s ease; min-width: 1px; }
        .ts-queue-count { font-size: 6pt; font-weight: 700; color: var(--ts-purple); font-family: var(--ts-mono); min-width: 20pt; text-align: right; flex-shrink: 0; }
        .ts-queue-empty { padding: 12pt 10pt; text-align: center; color: var(--ts-purple-muted); font-size: 6.5pt; }

        /* ── Failed jobs feed ── */
        .ts-jobs-feed { max-height: 50vh; overflow-y: auto; }
        .ts-jobs-feed::-webkit-scrollbar { width: 4px; }
        .ts-jobs-feed::-webkit-scrollbar-thumb { background: var(--ts-purple-border); }

        .ts-job-row {
            display: flex; align-items: center; gap: 5pt;
            padding: 5pt 10pt; cursor: pointer; position: relative;
            transition: background 0.1s;
            border-left: 3px solid var(--ts-red); border-bottom: 0.4pt solid var(--ts-purple-faint);
        }
        .ts-job-row:last-child { border-bottom: none; }
        .ts-job-row:hover { background: var(--ts-purple-bg); }

        .ts-job-icon {
            flex-shrink: 0; width: 12pt; height: 12pt;
            display: flex; align-items: center; justify-content: center;
            font-size: 5pt; font-weight: 700; letter-spacing: 0.05em;
            background: var(--ts-red-bg); border: 1px solid var(--ts-red); color: var(--ts-red);
        }
        .ts-job-name { flex: 1; min-width: 0; font-size: 7pt; font-weight: 600; color: var(--ts-purple); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .ts-job-queue {
            font-size: 5pt; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.06em; padding: 1pt 4pt;
            border: 1px solid var(--ts-purple-border); color: var(--ts-purple-muted);
            flex-shrink: 0;
        }
        .ts-job-time { flex-shrink: 0; font-size: 5.5pt; color: var(--ts-purple-muted); font-family: var(--ts-mono); min-width: 50pt; text-align: right; }

        .ts-job-actions {
            position: absolute; right: 8pt; display: none;
            align-items: center; gap: 2pt;
            background: #fff; border: 1px solid var(--ts-purple);
            box-shadow: 0 2px 6px rgba(22,53,92,0.12);
            padding: 2pt 3pt;
        }
        .ts-job-row:hover .ts-job-actions { display: flex; }
        .ts-job-btn {
            background: none; border: none; cursor: pointer;
            padding: 2pt 4pt; font-family: inherit;
            font-size: 5.5pt; font-weight: 700; letter-spacing: 0.06em;
            text-transform: uppercase; color: var(--ts-purple-deep);
            transition: background 0.1s, color 0.1s; white-space: nowrap;
        }
        .ts-job-btn:hover { background: var(--ts-purple-bg); color: var(--ts-purple); }
        .ts-job-btn.btn-retry { color: var(--ts-green); }
        .ts-job-btn.btn-retry:hover { background: var(--ts-green); color: #fff; }
        .ts-job-btn.btn-delete { color: var(--ts-red); }
        .ts-job-btn.btn-delete:hover { background: var(--ts-red); color: #fff; }

        /* Job detail (expandable) */
        .ts-job-detail {
            display: none; margin: 0 10pt 0 25pt;
            border-left: 2px solid var(--ts-red); padding: 5pt 0 6pt 8pt;
            border-bottom: 0.4pt solid var(--ts-purple-faint); background: #fef2f2;
        }
        .ts-job-detail.open { display: block; }
        .ts-job-detail-exception {
            font-size: 6pt; font-family: var(--ts-mono); color: var(--ts-red);
            white-space: pre-wrap; word-break: break-all; line-height: 1.5;
            max-height: 180pt; overflow-y: auto;
        }
        .ts-job-detail-exception::-webkit-scrollbar { width: 3px; }
        .ts-job-detail-exception::-webkit-scrollbar-thumb { background: #fecaca; }

        .ts-jobs-empty { padding: 16pt 10pt; text-align: center; color: var(--ts-purple-muted); }
        .ts-jobs-empty-icon { font-size: 16pt; color: var(--ts-purple-border); margin-bottom: 3pt; }
        .ts-jobs-empty-title { font-size: 7pt; font-weight: 700; color: var(--ts-purple); text-transform: uppercase; letter-spacing: 0.07em; }
        .ts-jobs-empty-sub { font-size: 6.5pt; color: var(--ts-purple-muted); }

        /* ── Log viewer ── */
        .ts-log-toolbar {
            display: flex; align-items: center; gap: 4pt; padding: 5pt 10pt;
            border-bottom: 0.4pt solid var(--ts-purple-faint); flex-wrap: wrap;
        }
        .ts-log-level-chip {
            font-size: 5pt; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.06em; padding: 1.5pt 5pt;
            border: 1px solid var(--ts-purple-border); color: var(--ts-purple-muted);
            background: #fff; cursor: pointer; transition: all 0.15s; font-family: inherit;
        }
        .ts-log-level-chip:hover { border-color: var(--ts-purple); color: var(--ts-purple); }
        .ts-log-level-chip.active { background: var(--ts-purple); color: #fff; border-color: var(--ts-purple); }
        .ts-log-level-chip.chip-error.active { background: var(--ts-red); border-color: var(--ts-red); }
        .ts-log-level-chip.chip-warning.active { background: #b45309; border-color: #b45309; }
        .ts-log-level-chip.chip-critical.active { background: #7f1d1d; border-color: #7f1d1d; }

        .ts-log-search {
            height: 14pt; border: 1px solid var(--ts-purple-border);
            padding: 0 5pt; font-size: 6pt; font-family: inherit;
            color: var(--ts-purple); background: #fff; width: 120pt; box-sizing: border-box;
            margin-left: auto;
        }
        .ts-log-search:focus { outline: none; border-color: var(--ts-purple); }
        .ts-log-search-count { font-size: 5pt; color: var(--ts-purple-muted); margin-left: 2pt; }

        .ts-log-feed { max-height: 55vh; overflow-y: auto; font-family: var(--ts-mono); }
        .ts-log-feed::-webkit-scrollbar { width: 4px; }
        .ts-log-feed::-webkit-scrollbar-track { background: transparent; }
        .ts-log-feed::-webkit-scrollbar-thumb { background: var(--ts-purple-border); }

        .ts-log-line {
            padding: 2.5pt 10pt; border-bottom: 0.3pt solid var(--ts-purple-faint);
            display: flex; gap: 6pt; align-items: baseline; font-size: 6pt;
            transition: background 0.15s;
        }
        .ts-log-line:hover { background: var(--ts-purple-bg); }
        .ts-log-line:last-child { border-bottom: none; }
        .ts-log-line.new-entry { animation: ts-log-flash 1.2s ease-out; }
        @keyframes ts-log-flash { 0% { background: #fef9c3; } 100% { background: transparent; } }

        .ts-log-date { color: var(--ts-purple-border); white-space: nowrap; flex-shrink: 0; font-size: 5.5pt; }
        .ts-log-level-tag {
            font-weight: 700; white-space: nowrap; flex-shrink: 0; width: 38pt;
            font-size: 5pt; text-align: center; padding: 0.5pt 0;
        }
        .ts-log-level-tag.lvl-error, .ts-log-level-tag.lvl-critical { color: var(--ts-red); }
        .ts-log-level-tag.lvl-warning { color: var(--ts-amber); }
        .ts-log-level-tag.lvl-info { color: var(--ts-purple-deep); }
        .ts-log-level-tag.lvl-debug { color: #6b7280; }
        .ts-log-msg { color: var(--ts-text); word-break: break-word; flex: 1; }
        .ts-log-msg mark { background: #fef08a; padding: 0 1px; font-weight: inherit; }

        .ts-log-empty { padding: 16pt 10pt; text-align: center; color: var(--ts-purple-muted); font-size: 6.5pt; font-family: var(--ts-font); }

        /* Auto-scroll toggle */
        .ts-autoscroll-toggle {
            display: inline-flex; align-items: center; gap: 3pt;
            font-size: 5.5pt; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.06em; color: var(--ts-purple-muted);
            cursor: pointer; user-select: none; padding: 1.5pt 5pt;
            border: 1px solid var(--ts-purple-border); background: #fff;
            transition: all 0.15s; font-family: inherit;
        }
        .ts-autoscroll-toggle:hover { border-color: var(--ts-purple); color: var(--ts-purple); }
        .ts-autoscroll-toggle.active { background: var(--ts-purple); color: #fff; border-color: var(--ts-purple); }

        /* ── Shared buttons ── */
        .ts-btn {
            display: inline-flex; align-items: center; gap: 3pt;
            background: #fff; border: 1px solid var(--ts-purple-border); color: var(--ts-purple);
            font-size: 6pt; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.06em; padding: 3pt 7pt; cursor: pointer;
            font-family: inherit; transition: background 0.15s, color 0.15s;
        }
        .ts-btn:hover { background: var(--ts-purple-bg); }
        .ts-btn.primary { background: var(--ts-purple-light); color: #fff; border-color: var(--ts-purple-light); }
        .ts-btn.primary:hover { background: #6d28d9; }
        .ts-btn.danger { border-color: var(--ts-red); color: var(--ts-red); }
        .ts-btn.danger:hover { background: var(--ts-red); color: #fff; }

        /* ── Last updated stamp ── */
        .ts-last-updated { font-size: 5pt; color: var(--ts-purple-muted); letter-spacing: 0.04em; }
    </style>
@endpush

@section('content')
<div class="ts-wrap">

    {{-- Topbar --}}
    <div class="ts-topbar">
        <div class="ts-topbar-inner">
            <a href="{{ route('dashboard') }}" class="ts-topbar-back">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
                Dashboard
            </a>
            <div class="ts-topbar-divider"></div>
            <h1 class="ts-topbar-title">Agent Operations</h1>
            <p class="ts-topbar-meta">Circuits &middot; Jobs &middot; Queues &middot; Logs</p>

            <div class="ts-topbar-actions">
                <span class="ts-last-updated" id="ts-last-updated"></span>
                <button class="ts-topbar-status live" id="ts-live-toggle" onclick="TS.toggleLive()" title="Toggle live updates">
                    <span class="ts-status-dot"></span>
                    <span id="ts-live-label">Live</span>
                </button>
                <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                    @csrf
                    <button type="submit" class="ts-topbar-btn">
                        <svg width="9" height="9" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        Log Out
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Toast area --}}
    <div class="ts-toast-area" id="ts-toast-area"></div>

    <div class="ts-content">

        {{-- Row 1: Circuits + Queues side by side --}}
        <div class="ts-overview-grid">

            {{-- Circuit Breakers --}}
            <div class="ts-card">
                <div class="ts-card-header">
                    <div class="ts-card-header-left">
                        <span class="ts-card-header-icon">⚡</span>
                        <span class="ts-card-header-title">Circuit Breakers</span>
                        <span class="ts-card-header-count" id="ts-circuits-count">{{ count($circuitStates) }}</span>
                    </div>
                </div>
                <div class="ts-card-body">
                    <div class="ts-circuits-grid" id="ts-circuits-grid">
                        @foreach ($circuitStates as $service => $info)
                            <div class="ts-circuit state-{{ $info['state'] }}" data-service="{{ $service }}">
                                <div class="ts-circuit-info">
                                    <div class="ts-circuit-name">{{ $info['label'] }}</div>
                                    <div class="ts-circuit-state-label st-{{ $info['state'] }}">{{ str_replace('_', ' ', $info['state']) }}</div>
                                </div>
                                <div class="ts-circuit-dot dot-{{ $info['state'] }}"></div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Active Queues --}}
            <div class="ts-card">
                <div class="ts-card-header">
                    <div class="ts-card-header-left">
                        <span class="ts-card-header-icon">⏳</span>
                        <span class="ts-card-header-title">Active Queues</span>
                    </div>
                    <div class="ts-card-header-right">
                        <span class="ts-last-updated" id="ts-queues-updated"></span>
                    </div>
                </div>
                <div class="ts-card-body">
                    <div id="ts-queues-list">
                        @if ($queuedJobs->isEmpty())
                            <div class="ts-queue-empty">No jobs currently pending</div>
                        @else
                            <div class="ts-queues-list">
                                @foreach ($queuedJobs as $row)
                                    <div class="ts-queue-row">
                                        <span class="ts-queue-name">{{ $row->queue }}</span>
                                        <div class="ts-queue-bar-wrap">
                                            <div class="ts-queue-bar" style="width: {{ min(100, $row->total * 5) }}%"></div>
                                        </div>
                                        <span class="ts-queue-count">{{ $row->total }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Row 2: Failed Jobs --}}
        <div class="ts-card">
            <div class="ts-card-header">
                <div class="ts-card-header-left">
                    <span class="ts-card-header-icon">⚠</span>
                    <span class="ts-card-header-title">Failed Jobs</span>
                    <span class="ts-card-header-count" id="ts-failed-count">{{ $failedJobs->count() }}</span>
                </div>
                <div class="ts-card-header-right">
                    @if ($failedJobs->count() > 0)
                        <button class="ts-btn primary" id="ts-retry-all-btn" onclick="TS.retryAll()">Retry All</button>
                    @endif
                </div>
            </div>
            <div class="ts-card-body">
                <div class="ts-jobs-feed" id="ts-jobs-feed">
                    @if ($failedJobs->isEmpty())
                        <div class="ts-jobs-empty" id="ts-jobs-empty">
                            <div class="ts-jobs-empty-icon">✓</div>
                            <div class="ts-jobs-empty-title">All Clear</div>
                            <div class="ts-jobs-empty-sub">No failed jobs — AI agents are running smoothly</div>
                        </div>
                    @else
                        @foreach ($failedJobs as $job)
                            <div class="ts-job-row" id="job-{{ $job['id'] }}" onclick="TS.toggleJobDetail({{ $job['id'] }})">
                                <div class="ts-job-icon">✕</div>
                                <span class="ts-job-name">{{ $job['display_name'] }}</span>
                                <span class="ts-job-queue">{{ $job['queue'] }}</span>
                                <span class="ts-job-time">{{ $job['failed_at'] }}</span>
                                <div class="ts-job-actions">
                                    <button class="ts-job-btn btn-retry" onclick="TS.retryJob({{ $job['id'] }}, event)">↻ Retry</button>
                                    <button class="ts-job-btn btn-delete" onclick="TS.deleteJob({{ $job['id'] }}, event)">✕ Delete</button>
                                </div>
                            </div>
                            <div class="ts-job-detail" id="job-detail-{{ $job['id'] }}">
                                <pre class="ts-job-detail-exception">{{ $job['exception_full'] }}</pre>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        {{-- Row 3: Logs --}}
        <div class="ts-card">
            <div class="ts-card-header">
                <div class="ts-card-header-left">
                    <span class="ts-card-header-icon">▶</span>
                    <span class="ts-card-header-title">System Log</span>
                </div>
                <div class="ts-card-header-right">
                    <button class="ts-autoscroll-toggle active" id="ts-autoscroll-btn" onclick="TS.toggleAutoScroll()">↓ Auto-scroll</button>
                </div>
            </div>
            <div class="ts-card-body">
                {{-- Log toolbar --}}
                <div class="ts-log-toolbar">
                    <button class="ts-log-level-chip active" data-level="all" onclick="TS.filterLevel('all', this)">All</button>
                    <button class="ts-log-level-chip chip-error" data-level="ERROR" onclick="TS.filterLevel('ERROR', this)">Error</button>
                    <button class="ts-log-level-chip chip-critical" data-level="CRITICAL" onclick="TS.filterLevel('CRITICAL', this)">Critical</button>
                    <button class="ts-log-level-chip chip-warning" data-level="WARNING" onclick="TS.filterLevel('WARNING', this)">Warning</button>
                    <button class="ts-log-level-chip" data-level="INFO" onclick="TS.filterLevel('INFO', this)">Info</button>
                    <button class="ts-log-level-chip" data-level="DEBUG" onclick="TS.filterLevel('DEBUG', this)">Debug</button>
                    <input type="text" class="ts-log-search" id="ts-log-search" placeholder="Search logs…" oninput="TS.searchLogs(this.value)">
                    <span class="ts-log-search-count" id="ts-log-search-count"></span>
                </div>

                {{-- Log feed --}}
                <div class="ts-log-feed" id="ts-log-feed">
                    @if (empty($logLines))
                        <div class="ts-log-empty" id="ts-log-empty">No log entries found</div>
                    @else
                        @foreach ($logLines as $line)
                            <div class="ts-log-line" data-level="{{ $line['level'] }}">
                                <span class="ts-log-date">{{ $line['date'] }}</span>
                                <span class="ts-log-level-tag lvl-{{ strtolower($line['level']) }}">{{ $line['level'] }}</span>
                                <span class="ts-log-msg">{{ $line['message'] }}</span>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
var TS = (function () {
    var csrfToken  = @json(csrf_token());
    var feedUrl    = @json(route('troubleshooting.feed'));
    var retryUrl   = @json(url('/troubleshooting/jobs'));
    var deleteUrl  = @json(url('/troubleshooting/jobs'));
    var retryAllUrl = @json(route('troubleshooting.jobs.retry-all'));

    var isLive = true;
    var autoScroll = true;
    var pollTimer = null;
    var pollMs = 3000;
    var activeLevel = 'all';
    var searchQuery = '';
    var previousLogSignature = '';

    function escHtml(str) {
        var d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    function toast(msg, type) {
        var area = document.getElementById('ts-toast-area');
        var el = document.createElement('div');
        el.className = 'ts-toast ' + (type || 'info');
        el.innerHTML = escHtml(msg);
        area.appendChild(el);
        setTimeout(function () {
            el.style.transition = 'opacity 0.3s';
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 300);
        }, 3500);
    }

    function updateTimestamp() {
        var now = new Date();
        var t = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        var el = document.getElementById('ts-last-updated');
        if (el) el.textContent = 'Updated ' + t;
    }

    // ── Circuit breakers ──
    function renderCircuits(states) {
        var grid = document.getElementById('ts-circuits-grid');
        if (!grid || !states) return;

        Object.keys(states).forEach(function (service) {
            var info = states[service];
            var card = grid.querySelector('[data-service="' + service + '"]');
            if (!card) return;

            var oldState = card.className.match(/state-(\w+)/);
            oldState = oldState ? oldState[1] : 'closed';
            var newState = info.state;

            if (oldState !== newState) {
                card.className = 'ts-circuit state-' + newState;
                var stLabel = card.querySelector('.ts-circuit-state-label');
                if (stLabel) {
                    stLabel.className = 'ts-circuit-state-label st-' + newState;
                    stLabel.textContent = newState.replace('_', ' ');
                }
                var dot = card.querySelector('.ts-circuit-dot');
                if (dot) dot.className = 'ts-circuit-dot dot-' + newState;

                if (newState === 'open') {
                    toast(info.label + ' circuit opened', 'error');
                } else if (newState === 'closed' && oldState === 'open') {
                    toast(info.label + ' circuit recovered', 'success');
                }
            }
        });
    }

    // ── Queues ──
    function renderQueues(queuedJobs) {
        var container = document.getElementById('ts-queues-list');
        if (!container) return;

        if (!queuedJobs || queuedJobs.length === 0) {
            container.innerHTML = '<div class="ts-queue-empty">No jobs currently pending</div>';
            return;
        }

        var maxCount = Math.max.apply(null, queuedJobs.map(function (r) { return r.total; }));
        var html = '<div class="ts-queues-list">';
        queuedJobs.forEach(function (row) {
            var pct = maxCount > 0 ? Math.min(100, (row.total / maxCount) * 100) : 0;
            html += '<div class="ts-queue-row">'
                + '<span class="ts-queue-name">' + escHtml(row.queue) + '</span>'
                + '<div class="ts-queue-bar-wrap"><div class="ts-queue-bar" style="width:' + pct + '%"></div></div>'
                + '<span class="ts-queue-count">' + row.total + '</span>'
                + '</div>';
        });
        html += '</div>';
        container.innerHTML = html;
    }

    // ── Failed jobs ──
    function renderJobs(failedJobs) {
        var feed = document.getElementById('ts-jobs-feed');
        var countEl = document.getElementById('ts-failed-count');
        if (!feed) return;

        if (countEl) countEl.textContent = failedJobs.length;

        if (failedJobs.length === 0) {
            feed.innerHTML = '<div class="ts-jobs-empty" id="ts-jobs-empty">'
                + '<div class="ts-jobs-empty-icon">✓</div>'
                + '<div class="ts-jobs-empty-title">All Clear</div>'
                + '<div class="ts-jobs-empty-sub">No failed jobs — AI agents are running smoothly</div>'
                + '</div>';
            var retryAllBtn = document.getElementById('ts-retry-all-btn');
            if (retryAllBtn) retryAllBtn.style.display = 'none';
            return;
        }

        var existingIds = {};
        feed.querySelectorAll('.ts-job-row').forEach(function (row) {
            var id = row.id.replace('job-', '');
            existingIds[id] = true;
        });

        var html = '';
        failedJobs.forEach(function (job) {
            html += '<div class="ts-job-row" id="job-' + job.id + '" onclick="TS.toggleJobDetail(' + job.id + ')">'
                + '<div class="ts-job-icon">✕</div>'
                + '<span class="ts-job-name">' + escHtml(job.display_name) + '</span>'
                + '<span class="ts-job-queue">' + escHtml(job.queue) + '</span>'
                + '<span class="ts-job-time">' + escHtml(job.failed_at) + '</span>'
                + '<div class="ts-job-actions">'
                + '<button class="ts-job-btn btn-retry" onclick="TS.retryJob(' + job.id + ', event)">↻ Retry</button>'
                + '<button class="ts-job-btn btn-delete" onclick="TS.deleteJob(' + job.id + ', event)">✕ Delete</button>'
                + '</div></div>'
                + '<div class="ts-job-detail" id="job-detail-' + job.id + '">'
                + '<pre class="ts-job-detail-exception">' + escHtml(job.exception_full) + '</pre>'
                + '</div>';
        });
        feed.innerHTML = html;
    }

    // ── Logs ──
    function renderLogs(logLines) {
        var feed = document.getElementById('ts-log-feed');
        if (!feed) return;

        var sig = logLines.length > 0 ? logLines[0].date + logLines[0].message.substring(0, 30) : '';
        var isNew = sig !== previousLogSignature;
        previousLogSignature = sig;

        if (logLines.length === 0) {
            feed.innerHTML = '<div class="ts-log-empty" id="ts-log-empty">No log entries found</div>';
            return;
        }

        var html = '';
        logLines.forEach(function (line, i) {
            var level = line.level.toLowerCase();
            var visible = (activeLevel === 'all' || line.level === activeLevel);
            var msg = line.message;

            if (searchQuery) {
                var re = new RegExp('(' + searchQuery.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
                if (!re.test(msg)) visible = false;
                else msg = escHtml(msg).replace(new RegExp('(' + searchQuery.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi'), '<mark>$1</mark>');
            } else {
                msg = escHtml(msg);
            }

            html += '<div class="ts-log-line' + (isNew && i === 0 ? ' new-entry' : '') + '" data-level="' + line.level + '"'
                + (visible ? '' : ' style="display:none"') + '>'
                + '<span class="ts-log-date">' + escHtml(line.date) + '</span>'
                + '<span class="ts-log-level-tag lvl-' + level + '">' + escHtml(line.level) + '</span>'
                + '<span class="ts-log-msg">' + msg + '</span>'
                + '</div>';
        });
        feed.innerHTML = html;

        if (autoScroll) {
            feed.scrollTop = 0;
        }

        updateSearchCount();
    }

    // ── Poll ──
    function poll() {
        fetch(feedUrl, { headers: { 'Accept': 'application/json' } })
            .then(function (res) { return res.ok ? res.json() : Promise.reject(); })
            .then(function (data) {
                renderCircuits(data.circuitStates);
                renderQueues(data.queuedJobs);
                renderJobs(data.failedJobs);
                renderLogs(data.logLines);
                updateTimestamp();
            })
            .catch(function () { /* retry on next tick */ });
    }

    function startPolling() {
        poll();
        pollTimer = setInterval(poll, pollMs);
    }

    function stopPolling() {
        if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
    }

    // ── Visibility handling ──
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            stopPolling();
        } else if (isLive) {
            startPolling();
        }
    });

    // ── Public API ──
    function toggleLive() {
        isLive = !isLive;
        var btn = document.getElementById('ts-live-toggle');
        var label = document.getElementById('ts-live-label');
        if (isLive) {
            btn.classList.add('live');
            label.textContent = 'Live';
            startPolling();
        } else {
            btn.classList.remove('live');
            label.textContent = 'Paused';
            stopPolling();
        }
    }

    function toggleAutoScroll() {
        autoScroll = !autoScroll;
        var btn = document.getElementById('ts-autoscroll-btn');
        btn.classList.toggle('active', autoScroll);
    }

    function toggleJobDetail(id) {
        var detail = document.getElementById('job-detail-' + id);
        if (detail) detail.classList.toggle('open');
    }

    function retryJob(id, e) {
        if (e) e.stopPropagation();
        fetch(retryUrl + '/' + id + '/retry', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.ok) {
                removeJobRow(id);
                toast(data.message, 'success');
            } else {
                toast(data.message || 'Retry failed', 'error');
            }
        })
        .catch(function () { toast('Network error', 'error'); });
    }

    function deleteJob(id, e) {
        if (e) e.stopPropagation();
        fetch(deleteUrl + '/' + id, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.ok) {
                removeJobRow(id);
                toast(data.message, 'success');
            } else {
                toast(data.message || 'Delete failed', 'error');
            }
        })
        .catch(function () { toast('Network error', 'error'); });
    }

    function retryAll() {
        fetch(retryAllUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.ok) {
                toast(data.message, 'success');
                setTimeout(poll, 800);
            } else {
                toast(data.message || 'Retry all failed', 'error');
            }
        })
        .catch(function () { toast('Network error', 'error'); });
    }

    function removeJobRow(id) {
        var row = document.getElementById('job-' + id);
        var detail = document.getElementById('job-detail-' + id);
        if (row) {
            row.style.transition = 'opacity 0.3s, max-height 0.3s';
            row.style.opacity = '0';
            row.style.maxHeight = '0';
            row.style.overflow = 'hidden';
            setTimeout(function () {
                row.remove();
                if (detail) detail.remove();
                var countEl = document.getElementById('ts-failed-count');
                if (countEl) {
                    var n = parseInt(countEl.textContent) - 1;
                    countEl.textContent = Math.max(0, n);
                    if (n <= 0) {
                        var feed = document.getElementById('ts-jobs-feed');
                        feed.innerHTML = '<div class="ts-jobs-empty"><div class="ts-jobs-empty-icon">✓</div>'
                            + '<div class="ts-jobs-empty-title">All Clear</div>'
                            + '<div class="ts-jobs-empty-sub">No failed jobs — AI agents are running smoothly</div></div>';
                    }
                }
            }, 350);
        }
        if (detail) detail.style.display = 'none';
    }

    function filterLevel(level, btn) {
        activeLevel = level;
        document.querySelectorAll('.ts-log-level-chip').forEach(function (chip) {
            chip.classList.toggle('active', chip.dataset.level === level);
        });
        applyLogFilters();
    }

    function searchLogs(q) {
        searchQuery = q.trim();
        applyLogFilters();
    }

    function applyLogFilters() {
        var lines = document.querySelectorAll('.ts-log-line');
        var visibleCount = 0;
        var re = searchQuery ? new RegExp('(' + searchQuery.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi') : null;

        lines.forEach(function (line) {
            var level = line.dataset.level;
            var levelMatch = (activeLevel === 'all' || level === activeLevel);

            var msgEl = line.querySelector('.ts-log-msg');
            var originalText = msgEl.textContent;
            var searchMatch = !searchQuery || originalText.toLowerCase().includes(searchQuery.toLowerCase());

            if (levelMatch && searchMatch) {
                line.style.display = '';
                visibleCount++;
                if (re) {
                    msgEl.innerHTML = escHtml(originalText).replace(re, '<mark>$1</mark>');
                } else {
                    msgEl.textContent = originalText;
                }
            } else {
                line.style.display = 'none';
            }
        });

        updateSearchCount(visibleCount, lines.length);
    }

    function updateSearchCount(visible, total) {
        var el = document.getElementById('ts-log-search-count');
        if (!el) return;
        if (searchQuery || activeLevel !== 'all') {
            visible = visible !== undefined ? visible : document.querySelectorAll('.ts-log-line:not([style*="display: none"])').length;
            total = total !== undefined ? total : document.querySelectorAll('.ts-log-line').length;
            el.textContent = visible + '/' + total;
        } else {
            el.textContent = '';
        }
    }

    // ── Init ──
    if (isLive) startPolling();
    updateTimestamp();

    // ── Keyboard shortcuts ──
    document.addEventListener('keydown', function (e) {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
        if (e.key === 'l' || e.key === 'L') toggleLive();
        if (e.key === 's' || e.key === 'S') toggleAutoScroll();
    });

    return {
        toggleLive: toggleLive,
        toggleAutoScroll: toggleAutoScroll,
        toggleJobDetail: toggleJobDetail,
        retryJob: retryJob,
        deleteJob: deleteJob,
        retryAll: retryAll,
        filterLevel: filterLevel,
        searchLogs: searchLogs
    };
})();
</script>
@endpush
