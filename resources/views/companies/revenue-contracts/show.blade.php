@extends('layouts.public')

@section('title', $revenueContract->name . ' — Revenue Contract')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .inv-mgmt-bar { display:flex;align-items:center;justify-content:space-between;gap:8pt;flex-wrap:wrap;margin-bottom:12pt; }
        .mgmt-btn { display:inline-flex;align-items:center;gap:3pt;background:#fff;border:1px solid #9ec1f5;color:#1a345b;font-size:6.5pt;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;padding:4pt 7pt;cursor:pointer;font-family:inherit;transition:background 0.15s,color 0.15s; }
        .mgmt-btn:hover { background:#f4fafc; }
        .mgmt-btn.primary { background:#005bf0;color:#fff; }
        .mgmt-btn.primary:hover { background:#005f9e; }
        .cust-doc { background:#fff;border:1px solid #9ec1f5;color:#1a345b;font-size:7pt;line-height:1.45; }
        .cust-doc-body { padding:16pt 18pt; }
        .cust-header-table { width:100%;border-collapse:collapse;margin-bottom:6pt; }
        .doc-title { font-size:11pt;font-weight:800;text-align:right;margin-bottom:2pt;letter-spacing:0.04em; }
        .doc-meta-line { text-align:right;font-size:7pt; }
        .status-box { display:inline-block;font-weight:700;text-transform:uppercase;border:1px solid #1a345b;padding:0.08rem 4pt;font-size:5pt;letter-spacing:0.08em;margin-top:3pt; }
        .divider { border:none;border-top:1.5pt solid #1a345b;margin:8pt 0 10pt; }
        .summary-table { width:100%;border-collapse:collapse;margin-bottom:2pt; }
        .summary-table td { padding:0 10pt 0 0;font-size:7pt;vertical-align:top; }
        .summary-table .lbl { display:block;font-weight:700;font-size:5pt;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:1.5pt; }
        .summary-table .amt { font-size:8pt;font-weight:800; }
        .section-header { font-weight:700;font-size:7.5pt;text-transform:uppercase;letter-spacing:0.06em;border-bottom:1.5px solid #1a345b;padding-bottom:2pt;margin-bottom:4pt;color:#1a345b; }
        .action-section { margin-top:12pt; }
        .action-panel { border-bottom:1px solid #d3e2f5; }
        .action-panel-head { display:flex;align-items:center;justify-content:space-between;padding:5pt 0;cursor:pointer;user-select:none; }
        .action-panel-title { font-size:7pt;font-weight:700;display:flex;align-items:center;gap:4pt; }
        .action-panel-body { display:none;padding-bottom:8pt; }
        .action-panel.open .action-panel-body { display:block; }
        .action-panel-chevron { font-size:6pt;color:#6f869b;transition:transform 0.15s; }
        .action-panel.open .action-panel-chevron { transform:rotate(180deg); }
        .af-row { display:flex;flex-wrap:wrap;gap:4pt 6pt;align-items:flex-end;margin-bottom:5pt; }
        .af-field { flex:1;min-width:100pt; }
        .af-field label { display:block;font-size:5pt;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;color:#6f869b;margin-bottom:0.2rem; }
        .af-field input,.af-field select { width:100%;border:1px solid #9ec1f5;padding:3pt 4pt;font-size:7pt;font-family:inherit;color:#1a345b;box-sizing:border-box;background:#fff; }
        .af-field input:disabled { background:#f4fafc;color:#6f869b; }
        .af-field input:focus,.af-field select:focus { outline:none;border-color:#005bf0; }
        .af-hint { font-size:6pt;color:#6f869b;margin-bottom:4pt;line-height:1.4; }
        .history-row { display:flex;align-items:flex-start;gap:6pt;padding:5pt 0;border-bottom:1px solid #d3e2f5;font-size:7pt; }
        .history-row:last-child { border-bottom:none; }
        .history-badge { display:inline-block;padding:0.15rem 5pt;font-size:5pt;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;white-space:nowrap; }
        .history-date { color:#6f869b;font-size:6.5pt;white-space:nowrap;min-width:56pt; }
        .history-desc { flex:1; }
        .history-amt { font-family:monospace;white-space:nowrap;font-weight:700; }
        .posting-status { display:inline-block;font-size:5pt;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;padding:1pt 3pt;margin-left:4pt; }
        .as-field label { display:block;font-size:5pt;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;color:#6f869b;margin-bottom:0.2rem; }
        .as-field input,.as-field select { width:100%;border:1px solid #9ec1f5;padding:3pt 5pt;font-size:7pt;font-family:inherit;color:#1a345b;background:#fff;outline:none;box-sizing:border-box;height:16pt; }
        @media (max-width:640px) { .cust-doc-body { padding:10pt 8pt; } }
    </style>
@endpush

@section('content')
    <div class="co-wrap">
        @include('companies._topbar')
        <div class="co-body">
            @include('companies._sidebar')
            <main class="co-main">

                @if (session('success'))
                    <div style="background:#dcfce7;border:1px solid #bbf7d0;color:#15803d;padding:6pt 10pt;font-size:7.5pt;font-weight:600;margin-bottom:10pt;">{{ session('success') }}</div>
                @endif

                @php
                    $recognised = $revenueContract->totalRevenueRecognised();
                    $obligations = $revenueContract->performanceObligations;
                @endphp

                <div class="inv-mgmt-bar">
                    <div style="display:flex;align-items:center;gap:4pt;flex-wrap:wrap;">
                        @if ($revenueContract->status === 'active')
                            <button type="button" class="mgmt-btn" onclick="togglePanel('add-obligation')">+ Obligation</button>
                            <button type="button" class="mgmt-btn" onclick="togglePanel('recognise')">Recognise Revenue</button>
                            <button type="button" class="mgmt-btn" onclick="togglePanel('advance')">Advance Receipt</button>
                            <button type="button" class="mgmt-btn" onclick="togglePanel('release')">Release Liability</button>
                            <button type="button" class="mgmt-btn primary" onclick="openEditModal()">Edit</button>
                        @endif
                    </div>
                </div>

                <div class="cust-doc">
                    <div class="cust-doc-body">
                        <table class="cust-header-table">
                            <tr>
                                <td style="vertical-align:top;width:55%;">
                                    <div style="font-size:11pt;font-weight:800;">{{ $revenueContract->name }}</div>
                                    @if ($revenueContract->contract_reference)
                                        <div style="font-size:7pt;color:#6f869b;margin-top:0.2rem;">{{ $revenueContract->contract_reference }}</div>
                                    @endif
                                    <div style="font-size:7pt;margin-top:0.2rem;">Customer: <strong>{{ $revenueContract->customer_name }}</strong></div>
                                </td>
                                <td style="vertical-align:top;width:45%;">
                                    <div class="doc-title">REVENUE CONTRACT</div>
                                    <div class="doc-meta-line">IFRS 15</div>
                                    <div style="text-align:right;">
                                        <span class="status-box">{{ ucfirst($revenueContract->status) }}</span>
                                    </div>
                                </td>
                            </tr>
                        </table>

                        <table style="width:100%;border-collapse:collapse;font-size:7pt;margin-bottom:4pt;">
                            <tr>
                                <td style="width:50%;">
                                    <div><span style="font-weight:700;display:inline-block;width:160px;">Contract date</span>{{ $revenueContract->contract_date->format('d M Y') }}</div>
                                </td>
                                <td style="width:50%;text-align:right;">
                                    @if ($revenueContract->notes)
                                        <div style="color:#6f869b;font-style:italic;font-size:6.5pt;">{{ $revenueContract->notes }}</div>
                                    @endif
                                </td>
                            </tr>
                        </table>

                        <hr class="divider">

                        <table class="summary-table">
                            <tr>
                                <td>
                                    <span class="lbl">Transaction Price</span>
                                    <span class="amt">R {{ number_format((float) $revenueContract->total_transaction_price, 2) }}</span>
                                </td>
                                <td>
                                    <span class="lbl">Revenue Recognised</span>
                                    <span class="amt" style="color:#005bf0;">R {{ number_format($recognised, 2) }}</span>
                                </td>
                                <td>
                                    <span class="lbl">Remaining</span>
                                    <span class="amt">R {{ number_format((float) $revenueContract->total_transaction_price - $recognised, 2) }}</span>
                                </td>
                            </tr>
                        </table>

                        {{-- Performance Obligations --}}
                        <div style="margin-top:12pt;">
                            <div class="section-header">Performance Obligations (Step 2)</div>
                            @if ($obligations->isEmpty())
                                <p style="color:#6f869b;font-style:italic;font-size:6.5pt;">No performance obligations added yet.</p>
                            @else
                                <div style="overflow-x:auto;">
                                    <table class="reg-table">
                                        <thead>
                                            <tr>
                                                <th style="width:25%;">Name</th>
                                                <th class="amt" style="width:12%;">Standalone</th>
                                                <th class="amt" style="width:12%;">Allocated</th>
                                                <th style="width:10%;">Method</th>
                                                <th class="amt" style="width:12%;">Recognised</th>
                                                <th class="amt" style="width:8%;">% Complete</th>
                                                <th style="width:10%;text-align:center;">Status</th>
                                                <th style="width:3%;"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($obligations as $ob)
                                                <tr>
                                                    <td style="font-weight:700;">{{ $ob->name }}</td>
                                                    <td class="amt">{{ number_format((float) $ob->standalone_price, 2) }}</td>
                                                    <td class="amt">{{ number_format((float) ($ob->allocated_price ?? $ob->standalone_price), 2) }}</td>
                                                    <td style="font-size:6pt;">{{ ucfirst(str_replace('_', ' ', $ob->recognition_method)) }}</td>
                                                    <td class="amt" style="color:#005bf0;">{{ number_format((float) ($ob->revenue_recognised ?? 0), 2) }}</td>
                                                    <td class="amt">{{ number_format((float) ($ob->percentage_complete ?? 0), 1) }}%</td>
                                                    <td style="text-align:center;">
                                                        <span class="reg-status {{ $ob->status === 'satisfied' ? 'disposed' : '' }}">{{ ucfirst(str_replace('_', ' ', $ob->status)) }}</span>
                                                    </td>
                                                    <td style="text-align:right;">
                                                        <div class="reg-row-actions">
                                                            <button type="button" class="reg-row-dots">&#x22EE;</button>
                                                            <div class="reg-row-menu">
                                                                <form method="POST" action="{{ route('companies.revenue-contracts.obligations.destroy', [$company, $revenueContract, $ob]) }}"
                                                                    onsubmit="return false" data-confirm-label="Revenue" data-confirm-title="Remove Obligation" data-confirm-body="Remove {{ addslashes($ob->name) }}?" data-confirm-text="Remove" data-confirm-danger="1">
                                                                    @csrf @method('DELETE')
                                                                    <button type="submit" class="menu-item danger">Remove</button>
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

                        {{-- History --}}
                        <div style="margin-top:12pt;">
                            <div class="section-header">Event History</div>
                            <div id="history-container">
                                <p style="color:#6f869b;font-style:italic;font-size:6.5pt;">Loading history…</p>
                            </div>
                        </div>

                        {{-- Actions --}}
                        @if ($revenueContract->status === 'active')
                        <div class="action-section">
                            <div class="section-header">IFRS 15 Actions</div>

                            {{-- Add obligation --}}
                            <div class="action-panel" id="panel-add-obligation">
                                <div class="action-panel-head" onclick="togglePanel('add-obligation')">
                                    <span class="action-panel-title">Add performance obligation <small style="font-weight:400;color:#6f869b;">(Step 2)</small></span>
                                    <span class="action-panel-chevron">&#9660;</span>
                                </div>
                                <div class="action-panel-body">
                                    <form method="POST" action="{{ route('companies.revenue-contracts.obligations.store', [$company, $revenueContract]) }}">
                                        @csrf
                                        <div class="af-row">
                                            <div class="af-field" style="flex:2;">
                                                <label>Name</label>
                                                <input type="text" name="name" required placeholder="e.g. Software licence, Implementation services">
                                            </div>
                                            <div class="af-field">
                                                <label>Standalone price (R)</label>
                                                <input type="number" name="standalone_price" step="0.01" min="0" required>
                                            </div>
                                            <div class="af-field">
                                                <label>Allocated price (R)</label>
                                                <input type="number" name="allocated_price" step="0.01" min="0" placeholder="Auto if blank">
                                            </div>
                                        </div>
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>Recognition method</label>
                                                <select name="recognition_method" required>
                                                    <option value="point_in_time">Point in time</option>
                                                    <option value="over_time">Over time</option>
                                                </select>
                                            </div>
                                            <div class="af-field">
                                                <label>Over-time method</label>
                                                <select name="over_time_method">
                                                    <option value="">— N/A —</option>
                                                    <option value="output">Output</option>
                                                    <option value="input">Input</option>
                                                    <option value="cost_to_cost">Cost-to-cost</option>
                                                    <option value="units">Units delivered</option>
                                                    <option value="time">Straight-line (time)</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div style="display:flex;justify-content:flex-end;">
                                            <button type="submit" class="mgmt-btn primary">Add</button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            {{-- Recognise revenue --}}
                            <div class="action-panel" id="panel-recognise">
                                <div class="action-panel-head" onclick="togglePanel('recognise')">
                                    <span class="action-panel-title">Recognise revenue <small style="font-weight:400;color:#6f869b;">(Step 5)</small></span>
                                    <span class="action-panel-chevron">&#9660;</span>
                                </div>
                                <div class="action-panel-body">
                                    <p class="af-hint">Revenue is recognised when (or as) the entity satisfies a performance obligation by transferring a promised good or service to the customer.</p>
                                    <form method="POST" action="{{ route('companies.revenue-contracts.recognise-revenue', [$company, $revenueContract]) }}"
                                        onsubmit="return false" data-confirm-label="Revenue" data-ajax-confirm data-confirm-title="Recognise Revenue" data-confirm-body="Recognise revenue for this contract?" data-confirm-text="Save">
                                        @csrf
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>Performance obligation</label>
                                                <select name="performance_obligation_id" required>
                                                    @foreach ($obligations->where('status', '!=', 'satisfied') as $ob)
                                                        <option value="{{ $ob->id }}">{{ $ob->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="af-field">
                                                <label>Amount (R)</label>
                                                <input type="number" name="amount" step="0.01" min="0.01" required>
                                            </div>
                                            <div class="af-field">
                                                <label>Date</label>
                                                <input type="date" name="date" required value="{{ now()->format('Y-m-d') }}">
                                            </div>
                                        </div>
                                        <div style="display:flex;justify-content:flex-end;">
                                            <button type="submit" class="mgmt-btn primary">Save</button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            {{-- Advance receipt --}}
                            <div class="action-panel" id="panel-advance">
                                <div class="action-panel-head" onclick="togglePanel('advance')">
                                    <span class="action-panel-title">Advance receipt <small style="font-weight:400;color:#6f869b;">(contract liability)</small></span>
                                    <span class="action-panel-chevron">&#9660;</span>
                                </div>
                                <div class="action-panel-body">
                                    <p class="af-hint">When payment is received before performance, a contract liability is recognised. Dr Bank / Cr Contract Liability.</p>
                                    <form method="POST" action="{{ route('companies.revenue-contracts.advance-receipt', [$company, $revenueContract]) }}"
                                        onsubmit="return false" data-confirm-label="Revenue" data-ajax-confirm data-confirm-title="Advance Receipt" data-confirm-body="Record advance receipt?" data-confirm-text="Save">
                                        @csrf
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>Amount (R)</label>
                                                <input type="number" name="amount" step="0.01" min="0.01" required>
                                            </div>
                                            <div class="af-field">
                                                <label>Date</label>
                                                <input type="date" name="date" required value="{{ now()->format('Y-m-d') }}">
                                            </div>
                                        </div>
                                        <div style="display:flex;justify-content:flex-end;">
                                            <button type="submit" class="mgmt-btn primary">Save</button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            {{-- Release liability --}}
                            <div class="action-panel" id="panel-release">
                                <div class="action-panel-head" onclick="togglePanel('release')">
                                    <span class="action-panel-title">Release liability to revenue <small style="font-weight:400;color:#6f869b;">(IFRS 15.106)</small></span>
                                    <span class="action-panel-chevron">&#9660;</span>
                                </div>
                                <div class="action-panel-body">
                                    <p class="af-hint">When a performance obligation is satisfied for which advance payment was received, release the contract liability to revenue. Dr Contract Liability / Cr Revenue.</p>
                                    <form method="POST" action="{{ route('companies.revenue-contracts.release-liability', [$company, $revenueContract]) }}"
                                        onsubmit="return false" data-confirm-label="Revenue" data-ajax-confirm data-confirm-title="Release Liability" data-confirm-body="Release contract liability to revenue?" data-confirm-text="Save">
                                        @csrf
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>Performance obligation</label>
                                                <select name="performance_obligation_id" required>
                                                    @foreach ($obligations->where('status', '!=', 'satisfied') as $ob)
                                                        <option value="{{ $ob->id }}">{{ $ob->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="af-field">
                                                <label>Amount (R)</label>
                                                <input type="number" name="amount" step="0.01" min="0.01" required>
                                            </div>
                                            <div class="af-field">
                                                <label>Date</label>
                                                <input type="date" name="date" required value="{{ now()->format('Y-m-d') }}">
                                            </div>
                                        </div>
                                        <div style="display:flex;justify-content:flex-end;">
                                            <button type="submit" class="mgmt-btn primary">Save</button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                        </div>
                        @endif

                    </div>
                </div>

            </main>
        </div>
    </div>

    <script>
        function togglePanel(id) {
            var el = document.getElementById('panel-' + id);
            if (!el) return;
            var isOpen = el.classList.contains('open');
            document.querySelectorAll('.action-panel.open').forEach(function(p) { p.classList.remove('open'); });
            if (!isOpen) el.classList.add('open');
        }
        function showToast(msg, color) {
            var t = document.createElement('div');
            t.style.cssText = 'position:fixed;bottom:12pt;right:12pt;padding:6pt 10pt;border-radius:6px;font-size:0.8rem;font-weight:600;color:#fff;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,0.15);transition:opacity 0.4s;background:' + color;
            t.textContent = msg;
            document.body.appendChild(t);
            setTimeout(function() { t.style.opacity = '0'; setTimeout(function() { t.remove(); }, 400); }, 4000);
        }
        async function submitActionForm(form) {
            var btn = form.querySelector('[type=submit]');
            if (btn) { btn.disabled = true; btn.textContent = 'Saving…'; }
            try {
                var res = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, redirect: 'follow' });
                if (res.ok || res.redirected) { showToast('Saved — journal posting in progress', '#1d4ed8'); setTimeout(function() { window.location.reload(); }, 1200); }
                else { showToast('Error saving action', '#b91c1c'); }
            } catch (err) { showToast('Network error', '#b91c1c'); }
            finally { if (btn) { btn.disabled = false; btn.textContent = 'Save'; } }
        }
        document.querySelectorAll('.action-panel form[data-ajax-confirm]').forEach(function(form) {
            form.addEventListener('submit', async function (e) {
                e.preventDefault();
                if (form.dataset.confirmTitle && typeof window.showConfirmModal === 'function') {
                    window.showConfirmModal({ label: form.dataset.confirmLabel || '', title: form.dataset.confirmTitle, body: form.dataset.confirmBody || '', confirmText: form.dataset.confirmText || 'Confirm', danger: false, onConfirm: function() { submitActionForm(form); } });
                } else { await submitActionForm(form); }
            });
        });

        const jsStatusColors = { posted: { bg: '#dcfce7', color: '#166534' }, failed: { bg: '#fee2e2', color: '#991b1b' }, pending: { bg: '#fef3c7', color: '#92400e' } };
        const jsStatusLabels = { posted: 'Journal posted', failed: 'Journal failed', pending: 'Journal pending' };
        async function retryPosting(btn, url) {
            btn.disabled = true;
            try {
                const res = await fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' } });
                if (!res.ok) throw new Error();
                btn.remove(); showToast('Retry queued', '#1d4ed8');
            } catch { showToast('Retry failed', '#b91c1c'); btn.disabled = false; }
        }
        (function() {
            var container = document.getElementById('history-container');
            fetch('{{ route("companies.revenue-contracts.history", [$company, $revenueContract]) }}')
                .then(r => r.json())
                .then(events => {
                    if (!events.length) { container.innerHTML = '<p style="color:#6f869b;font-style:italic;font-size:6.5pt;">No events yet.</p>'; return; }
                    container.innerHTML = '';
                    events.forEach(function(e) {
                        var sc = jsStatusColors[e.journal_status] || jsStatusColors.pending;
                        var sl = jsStatusLabels[e.journal_status] || e.journal_status;
                        var div = document.createElement('div');
                        div.className = 'history-row';
                        div.innerHTML =
                            '<span class="history-date">' + e.date + '</span>' +
                            '<span class="history-badge" style="background:' + e.bg + ';color:' + e.color + ';">' + e.label + '</span>' +
                            '<span class="history-desc">' + e.description + '</span>' +
                            '<span class="history-amt">R ' + e.amount + '</span>' +
                            (e.transaction_url
                                ? '<a href="' + e.transaction_url + '" class="posting-status" style="background:' + sc.bg + ';color:' + sc.color + ';text-decoration:underline;">' + sl + '</a>'
                                : '<span class="posting-status" style="background:' + sc.bg + ';color:' + sc.color + ';">' + sl + '</span>') +
                            (e.retry_url ? '<button onclick="retryPosting(this,\'' + e.retry_url + '\')" style="margin-left:4pt;font-size:5pt;font-weight:700;padding:0.15rem 4pt;border:1px solid #b91c1c;color:#b91c1c;background:transparent;cursor:pointer;">Retry</button>' : '');
                        container.appendChild(div);
                    });
                })
                .catch(function() { container.innerHTML = '<p style="color:#b91c1c;font-size:6.5pt;">Failed to load history.</p>'; });
        })();
    </script>

    @if ($revenueContract->status === 'active')
    <div id="edit-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:9999;align-items:center;justify-content:center;padding:16pt 8pt;">
        <div style="background:#fff;width:100%;max-width:480px;border:1px solid #1a345b;box-shadow:0 20px 60px rgba(0,0,0,0.4);margin:0 auto;">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:9pt 12pt;border-bottom:1.5px solid #1a345b;">
                <h3 style="font-size:1.05rem;font-weight:800;margin:0;text-transform:uppercase;color:#1a345b;">Edit Contract</h3>
                <button onclick="closeEditModal()" style="background:none;border:none;font-size:1.4rem;color:#1a345b;cursor:pointer;">&times;</button>
            </div>
            <form method="POST" action="{{ route('companies.revenue-contracts.update', [$company, $revenueContract]) }}">
                @csrf @method('PATCH')
                <div style="padding:10pt 12pt;">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:5pt 7pt;margin-bottom:8pt;">
                        <div class="as-field" style="grid-column:1/-1;"><label>Name</label><input type="text" name="name" required value="{{ $revenueContract->name }}"></div>
                        <div class="as-field"><label>Reference</label><input type="text" name="contract_reference" value="{{ $revenueContract->contract_reference }}"></div>
                        <div class="as-field"><label>Customer</label><input type="text" name="customer_name" required value="{{ $revenueContract->customer_name }}"></div>
                        <div class="as-field"><label>Transaction price (R)</label><input type="number" name="total_transaction_price" step="0.01" min="0" required value="{{ $revenueContract->total_transaction_price }}"></div>
                        <div class="as-field" style="grid-column:1/-1;"><label>Notes</label><input type="text" name="notes" value="{{ $revenueContract->notes }}" maxlength="500"></div>
                    </div>
                    <div style="display:flex;gap:5pt;justify-content:flex-end;padding-top:4pt;border-top:1px solid #9ec1f5;">
                        <button type="button" onclick="closeEditModal()" style="padding:4pt 9pt;border:1px solid #9ec1f5;font-size:6.5pt;color:#1a345b;background:#fff;cursor:pointer;font-weight:600;">Cancel</button>
                        <button type="submit" style="padding:4pt 10pt;background:#005bf0;color:#fff;border:1px solid #005bf0;font-size:6.5pt;font-weight:700;cursor:pointer;">Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <script>
        function openEditModal()  { document.getElementById('edit-modal').style.display = 'flex'; }
        function closeEditModal() { document.getElementById('edit-modal').style.display = 'none'; }
        document.getElementById('edit-modal').addEventListener('click', function(e) { if (e.target === this) closeEditModal(); });
    </script>
    @endif
    @include('companies._row-actions')
@endsection
