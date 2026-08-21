@extends('layouts.public')

@section('title', $deferredTaxItem->name . ' — Deferred Tax')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .inv-mgmt-bar { display:flex;align-items:center;justify-content:space-between;gap:8pt;flex-wrap:wrap;margin-bottom:12pt; }
        .mgmt-btn { display:inline-flex;align-items:center;gap:3pt;background:#fff;border:1px solid #9ec1f5;color:#1a345b;font-size:6.5pt;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;padding:4pt 7pt;cursor:pointer;font-family:inherit;transition:background 0.15s,color 0.15s; }
        .mgmt-btn:hover { background:#f4fafc; }
        .mgmt-btn.primary { background:#005bf0;color:#fff; }
        .mgmt-btn.primary:hover { background:#005f9e; }
        .mgmt-btn.danger { border-color:#dc2626;color:#dc2626; }
        .mgmt-btn.danger:hover { background:#dc2626;color:#fff; }
        .cust-doc { background:#fff;border:1px solid #9ec1f5;color:#1a345b;font-size:7pt;line-height:1.45; }
        .cust-doc-body { padding:16pt 18pt; }
        .cust-header-table { width:100%;border-collapse:collapse;margin-bottom:6pt; }
        .doc-title { font-size:11pt;font-weight:800;text-align:right;margin-bottom:2pt;letter-spacing:0.04em; }
        .doc-meta-line { text-align:right;font-size:7pt; }
        .status-box { display:inline-block;font-weight:700;text-transform:uppercase;border:1px solid #1a345b;padding:0.08rem 4pt;font-size:5pt;letter-spacing:0.08em;margin-top:3pt; }
        .status-box.closed { color:#dc2626;border-color:#dc2626; }
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
        .af-field input { width:100%;border:1px solid #9ec1f5;padding:3pt 4pt;font-size:7pt;font-family:inherit;color:#1a345b;box-sizing:border-box;background:#fff; }
        .af-field input:disabled { background:#f4fafc;color:#6f869b; }
        .af-field input:focus { outline:none;border-color:#005bf0; }
        .af-hint { font-size:6pt;color:#6f869b;margin-bottom:4pt;line-height:1.4; }
        .history-row { display:flex;align-items:flex-start;gap:6pt;padding:5pt 0;border-bottom:1px solid #d3e2f5;font-size:7pt; }
        .history-row:last-child { border-bottom:none; }
        .history-badge { display:inline-block;padding:0.15rem 5pt;font-size:5pt;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;white-space:nowrap; }
        .history-date { color:#6f869b;font-size:6.5pt;white-space:nowrap;min-width:56pt; }
        .history-desc { flex:1; }
        .history-amt { font-family:monospace;white-space:nowrap;font-weight:700; }
        .history-balance { font-size:6pt;color:#6f869b;white-space:nowrap; }
        .as-field label { display:block;font-size:5pt;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;color:#6f869b;margin-bottom:0.2rem; }
        .as-field input,.as-field select { width:100%;border:1px solid #9ec1f5;padding:3pt 5pt;font-size:7pt;font-family:inherit;color:#1a345b;background:#fff;outline:none;box-sizing:border-box;height:16pt; }
        .posting-status { display:inline-block;font-size:5pt;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;padding:1pt 3pt;margin-left:4pt; }
        @media (max-width:640px) {
            .cust-doc-body { padding:10pt 8pt; }
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
                    <div style="background:#dcfce7;border:1px solid #bbf7d0;color:#15803d;padding:6pt 10pt;font-size:7.5pt;font-weight:600;margin-bottom:10pt;">
                        {{ session('success') }}
                    </div>
                @endif

                @php $isActive = $deferredTaxItem->status === 'active'; @endphp

                <div class="inv-mgmt-bar">
                    <div style="display:flex;align-items:center;gap:4pt;flex-wrap:wrap;">
                        @if ($isActive)
                            <button type="button" class="mgmt-btn" onclick="togglePanel('remeasure')">Remeasure</button>
                            <button type="button" class="mgmt-btn danger" onclick="togglePanel('reverse')">Reverse</button>
                            <button type="button" class="mgmt-btn primary" onclick="openEditModal()">Edit</button>
                        @endif
                    </div>
                </div>

                <div class="cust-doc">
                    <div class="cust-doc-body">
                        <table class="cust-header-table">
                            <tr>
                                <td style="vertical-align:top;width:55%;">
                                    <div style="font-size:11pt;font-weight:800;">{{ $deferredTaxItem->name }}</div>
                                    <div style="font-size:7pt;color:#6f869b;margin-top:0.2rem;">{{ ucfirst(str_replace('_', ' ', $deferredTaxItem->source_type)) }}</div>
                                </td>
                                <td style="vertical-align:top;width:45%;">
                                    <div class="doc-title">DEFERRED TAX</div>
                                    <div class="doc-meta-line">Tax rate: <strong>{{ number_format((float) $deferredTaxItem->tax_rate, 2) }}%</strong></div>
                                    <div style="text-align:right;">
                                        <span class="status-box {{ $deferredTaxItem->status !== 'active' ? 'closed' : '' }}">
                                            {{ ucfirst($deferredTaxItem->status) }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </table>

                        <table style="width:100%;border-collapse:collapse;font-size:7pt;margin-bottom:4pt;">
                            <tr>
                                <td style="width:50%;">
                                    <div><span style="font-weight:700;display:inline-block;width:160px;">Source type</span>{{ ucfirst(str_replace('_', ' ', $deferredTaxItem->source_type)) }}</div>
                                    @if ($deferredTaxItem->source_id)
                                        <div><span style="font-weight:700;display:inline-block;width:160px;">Source ID</span>{{ $deferredTaxItem->source_id }}</div>
                                    @endif
                                    <div><span style="font-weight:700;display:inline-block;width:160px;">Tax rate</span>{{ number_format((float) $deferredTaxItem->tax_rate, 2) }}%</div>
                                    <div><span style="font-weight:700;display:inline-block;width:160px;">Taxable</span>{{ $deferredTaxItem->is_taxable ? 'Yes' : 'No' }}</div>
                                    <div><span style="font-weight:700;display:inline-block;width:160px;">Measurement date</span>{{ $deferredTaxItem->measurement_date->format('d M Y') }}</div>
                                </td>
                                <td style="width:50%;text-align:right;">
                                    @if ($deferredTaxItem->notes)
                                        <div style="color:#6f869b;font-style:italic;font-size:6.5pt;">{{ $deferredTaxItem->notes }}</div>
                                    @endif
                                </td>
                            </tr>
                        </table>

                        <hr class="divider">

                        <table class="summary-table">
                            <tr>
                                <td>
                                    <span class="lbl">Tax Base</span>
                                    <span class="amt">R {{ number_format((float) $deferredTaxItem->tax_base, 2) }}</span>
                                </td>
                                <td>
                                    <span class="lbl">Carrying Amount</span>
                                    <span class="amt">R {{ number_format((float) $deferredTaxItem->carrying_amount, 2) }}</span>
                                </td>
                                <td>
                                    <span class="lbl">Temporary Difference</span>
                                    <span class="amt">R {{ number_format((float) $deferredTaxItem->temporary_difference, 2) }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding-top:6pt;">
                                    <span class="lbl">DTA</span>
                                    <span class="amt" style="color:#005bf0;">R {{ number_format((float) ($deferredTaxItem->deferred_tax_asset ?? 0), 2) }}</span>
                                </td>
                                <td style="padding-top:6pt;">
                                    <span class="lbl">DTL</span>
                                    <span class="amt" style="color:#005bf0;">R {{ number_format((float) ($deferredTaxItem->deferred_tax_liability ?? 0), 2) }}</span>
                                </td>
                                <td style="padding-top:6pt;">
                                    <span class="lbl">Net Position</span>
                                    <span class="amt">R {{ number_format($deferredTaxItem->netPosition(), 2) }}</span>
                                </td>
                            </tr>
                        </table>

                        <div style="margin-top:12pt;">
                            <div class="section-header">Movement History</div>
                            <div id="history-container">
                                <p style="color:#6f869b;font-style:italic;font-size:6.5pt;">Loading history...</p>
                            </div>
                        </div>

                        @if ($isActive)
                        <div class="action-section">
                            <div class="section-header">IAS 12 Actions</div>

                            <div class="action-panel" id="panel-remeasure">
                                <div class="action-panel-head" onclick="togglePanel('remeasure')">
                                    <span class="action-panel-title">Remeasure <small style="font-weight:400;color:#6f869b;">(IAS 12.37)</small></span>
                                    <span class="action-panel-chevron">&#9660;</span>
                                </div>
                                <div class="action-panel-body">
                                    <p class="af-hint">The carrying amount of a deferred tax asset shall be reviewed at the end of each reporting period.</p>
                                    <form method="POST" action="{{ route('companies.deferred-tax.remeasure', [$company, $deferredTaxItem]) }}"
                                        onsubmit="return false" data-confirm-label="Deferred Tax" data-ajax-confirm data-confirm-title="Remeasure" data-confirm-body="Remeasure this deferred tax item?" data-confirm-text="Save">
                                        @csrf
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>New carrying amount (R)</label>
                                                <input type="number" name="new_carrying_amount" step="0.01" required>
                                            </div>
                                            <div class="af-field">
                                                <label>New tax base (R)</label>
                                                <input type="number" name="new_tax_base" step="0.01" required>
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

                            <div class="action-panel" id="panel-reverse">
                                <div class="action-panel-head" onclick="togglePanel('reverse')">
                                    <span class="action-panel-title">Reverse <small style="font-weight:400;color:#6f869b;">(IAS 12.41)</small></span>
                                    <span class="action-panel-chevron">&#9660;</span>
                                </div>
                                <div class="action-panel-body">
                                    <p class="af-hint">A deferred tax item shall be reversed when the temporary difference reverses.</p>
                                    <form method="POST" action="{{ route('companies.deferred-tax.reverse', [$company, $deferredTaxItem]) }}"
                                        onsubmit="return false" data-confirm-label="Deferred Tax" data-confirm-title="Reverse" data-confirm-body="Reverse this deferred tax item? This cannot be undone." data-confirm-text="Reverse" data-confirm-danger="1">
                                        @csrf
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>Date</label>
                                                <input type="date" name="date" required value="{{ now()->format('Y-m-d') }}">
                                            </div>
                                        </div>
                                        <div style="display:flex;justify-content:flex-end;">
                                            <button type="submit" class="mgmt-btn danger">Reverse</button>
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
            if (btn) { btn.disabled = true; btn.textContent = 'Saving...'; }
            try {
                var res = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, redirect: 'follow' });
                if (res.ok || res.redirected) { showToast('Saved — journal posting in progress', '#1d4ed8'); setTimeout(function() { window.location.reload(); }, 1200); }
                else { showToast('Error saving action', '#b91c1c'); }
            } catch (err) { showToast('Network error', '#b91c1c'); }
            finally { if (btn) { btn.disabled = false; btn.textContent = 'Save'; } }
        }

        document.querySelectorAll('.action-panel form').forEach(function(form) {
            form.addEventListener('submit', async function (e) {
                e.preventDefault();
                if (form.dataset.confirmTitle && typeof window.showConfirmModal === 'function') {
                    window.showConfirmModal({ label: form.dataset.confirmLabel || '', title: form.dataset.confirmTitle, body: form.dataset.confirmBody || '', confirmText: form.dataset.confirmText || 'Confirm', danger: !!form.dataset.confirmDanger, onConfirm: function() { submitActionForm(form); } });
                } else { await submitActionForm(form); }
            });
        });

        const jsStatusColors = { posted: { bg: '#dcfce7', color: '#166534' }, failed: { bg: '#fee2e2', color: '#991b1b' }, pending: { bg: '#fef3c7', color: '#92400e' } };
        const jsStatusLabels = { posted: 'Journal posted', failed: 'Journal failed', pending: 'Journal pending' };

        async function retryPosting(btn, url) {
            btn.disabled = true; btn.textContent = '...';
            try {
                const res = await fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' } });
                if (!res.ok) throw new Error();
                btn.remove(); showToast('Retry queued', '#1d4ed8');
            } catch { showToast('Retry failed', '#b91c1c'); btn.disabled = false; btn.textContent = 'Retry'; }
        }

        (function() {
            var container = document.getElementById('history-container');
            fetch('{{ route("companies.deferred-tax.history", [$company, $deferredTaxItem]) }}')
                .then(r => r.json())
                .then(events => {
                    if (!events.length) { container.innerHTML = '<p style="color:#6f869b;font-style:italic;font-size:6.5pt;">No history yet.</p>'; return; }
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
                            '<span class="history-balance">R ' + e.previous_balance + ' &rarr; R ' + e.new_balance + '</span>' +
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

    @if ($isActive)
    <div id="edit-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:9999;align-items:center;justify-content:center;padding:16pt 8pt;">
        <div style="background:#fff;width:100%;max-width:420px;border:1px solid #1a345b;box-shadow:0 20px 60px rgba(0,0,0,0.4);margin:0 auto;">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:9pt 12pt;border-bottom:1.5px solid #1a345b;">
                <h3 style="font-size:1.05rem;font-weight:800;margin:0;text-transform:uppercase;color:#1a345b;">Edit</h3>
                <button onclick="closeEditModal()" style="background:none;border:none;font-size:1.4rem;color:#1a345b;cursor:pointer;">&times;</button>
            </div>
            <form method="POST" action="{{ route('companies.deferred-tax.update', [$company, $deferredTaxItem]) }}">
                @csrf @method('PATCH')
                <div style="padding:10pt 12pt;">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:5pt 7pt;margin-bottom:8pt;">
                        <div class="as-field" style="grid-column:1/-1;">
                            <label>Name</label>
                            <input type="text" name="name" required value="{{ $deferredTaxItem->name }}">
                        </div>
                        <div class="as-field">
                            <label>Tax rate (%)</label>
                            <input type="number" name="tax_rate" step="0.01" min="0" max="100" required value="{{ $deferredTaxItem->tax_rate }}">
                        </div>
                        <div class="as-field" style="grid-column:1/-1;">
                            <label>Notes</label>
                            <input type="text" name="notes" value="{{ $deferredTaxItem->notes }}" maxlength="500">
                        </div>
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
@endsection
