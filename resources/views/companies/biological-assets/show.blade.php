@extends('layouts.public')

@section('title', $biologicalAsset->name . ' — Biological Asset')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .inv-mgmt-bar { display:flex;align-items:center;justify-content:space-between;gap:8pt;flex-wrap:wrap;margin-bottom:12pt; }
        .inv-mgmt-bar a,.inv-mgmt-bar .mgmt-back { font-size:7pt;color:#6f869b;text-decoration:none;display:inline-flex;align-items:center;gap:3pt;transition:color 0.15s;background:none;border:none;cursor:pointer;font-family:inherit; }
        .inv-mgmt-bar a:hover,.inv-mgmt-bar .mgmt-back:hover { color:#1a345b; }
        .mgmt-btn { display:inline-flex;align-items:center;gap:3pt;background:#fff;border:1px solid #9ec1f5;color:#1a345b;font-size:6.5pt;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;padding:4pt 7pt;text-decoration:none;cursor:pointer;font-family:inherit;transition:background 0.15s,color 0.15s; }
        .mgmt-btn:hover { background:#f4fafc;color:#1a345b; }
        .mgmt-btn.primary { background:#005bf0;color:#fff; }
        .mgmt-btn.primary:hover { background:#005f9e; }
        .mgmt-btn.danger { border-color:#dc2626;color:#dc2626; }
        .mgmt-btn.danger:hover { background:#dc2626;color:#fff; }
        .cust-doc { background:#fff;border:1px solid #9ec1f5;font-family: "Century Gothic", "URW Gothic", "Avant Garde", Futura, "Avenir Next", Avenir, "Trebuchet MS", Helvetica, Arial, "DejaVu Sans", sans-serif;color:#1a345b;font-size:7pt;line-height:1.45; }
        .cust-doc-body { padding:16pt 18pt; }
        .cust-header-table { width:100%;border-collapse:collapse;margin-bottom:6pt; }
        .doc-title { font-size:11pt;font-weight:800;text-align:right;margin-bottom:2pt;letter-spacing:0.04em; }
        .doc-meta-line { text-align:right;font-size:7pt; }
        .status-box { display:inline-block;font-weight:700;text-transform:uppercase;border:1px solid #1a345b;padding:0.08rem 4pt;font-size:5pt;letter-spacing:0.08em;margin-top:3pt; }
        .status-box.disposed { color:#dc2626;border-color:#dc2626; }
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
        .af-field.wide { flex:2;min-width:150pt; }
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
        .history-amt { font-family:"DejaVu Sans Mono",monospace;white-space:nowrap;font-weight:700; }
        .as-field label { display:block;font-size:5pt;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;color:#6f869b;margin-bottom:0.2rem; }
        .as-field input,.as-field select { width:100%;border:1px solid #9ec1f5;padding:3pt 5pt;font-size:7pt;font-family:inherit;color:#1a345b;background:#fff;outline:none;box-sizing:border-box;height:16pt;border-radius:0; }
        .as-field input:focus,.as-field select:focus { border-color:#005bf0; }
        .posting-status { display:inline-block;font-size:5pt;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;padding:1pt 3pt;margin-left:4pt; }
        @media (max-width:640px) {
            .cust-doc-body { padding:10pt 8pt; }
            .cust-header-table,.cust-header-table tr,.cust-header-table td { display:block;width:100%!important;text-align:left!important; }
            .doc-title,.doc-meta-line { text-align:left!important; }
            .summary-table,.summary-table tr,.summary-table td { display:block;width:100%!important;padding:0 0 6pt; }
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
                @if (session('error'))
                    <div style="background:#fee2e2;border:1px solid #fca5a5;color:#b91c1c;padding:6pt 10pt;font-size:7.5pt;font-weight:600;margin-bottom:10pt;">
                        {{ session('error') }}
                    </div>
                @endif

                @php
                    $ca = $biologicalAsset->carryingAmount();
                @endphp

                {{-- Management bar --}}
                <div class="inv-mgmt-bar">
<div style="display:flex;align-items:center;gap:4pt;flex-wrap:wrap;">
                        @unless ($biologicalAsset->isDisposed())
                            <button type="button" class="mgmt-btn" onclick="togglePanel('fair-value')">Fair Value</button>
                            <button type="button" class="mgmt-btn" onclick="togglePanel('harvest')">Harvest</button>
                            <button type="button" class="mgmt-btn" onclick="togglePanel('natural-increase')">+ Natural</button>
                            <button type="button" class="mgmt-btn" onclick="togglePanel('mortality')">Mortality</button>
                            <button type="button" class="mgmt-btn danger" onclick="togglePanel('dispose')">Dispose</button>
                            <button type="button" class="mgmt-btn primary" onclick="openEditModal()">Edit</button>
                        @endunless
                    </div>
                </div>

                {{-- Document --}}
                <div class="cust-doc">
                    <div class="cust-doc-body">

                        {{-- Header --}}
                        <table class="cust-header-table">
                            <tr>
                                <td style="vertical-align:top;width:55%;">
                                    <div style="font-size:11pt;font-weight:800;letter-spacing:-0.01em;">{{ $biologicalAsset->name }}</div>
                                    @if ($biologicalAsset->reference)
                                        <div style="font-size:7pt;color:#6f869b;margin-top:0.2rem;">{{ $biologicalAsset->reference }}</div>
                                    @endif
                                </td>
                                <td style="vertical-align:top;width:45%;">
                                    <div class="doc-title">BIOLOGICAL ASSET</div>
                                    <div class="doc-meta-line">Class: <strong>{{ $biologicalAsset->biologicalAssetClass?->name ?? 'Unclassified' }}</strong></div>
                                    @if ($biologicalAsset->location)
                                        <div class="doc-meta-line">Location: <strong>{{ $biologicalAsset->location }}</strong></div>
                                    @endif
                                    <div style="text-align:right;">
                                        <span class="status-box {{ $biologicalAsset->isDisposed() ? 'disposed' : '' }}">
                                            {{ $biologicalAsset->isDisposed() ? 'Disposed' : 'Active' }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </table>

                        {{-- Meta --}}
                        <table style="width:100%;border-collapse:collapse;font-size:7pt;margin-bottom:4pt;">
                            <tr>
                                <td style="vertical-align:top;width:50%;">
                                    <div><span style="font-weight:700;display:inline-block;width:140px;">Acquired</span>{{ $biologicalAsset->acquisition_date->format('d M Y') }}</div>
                                    <div><span style="font-weight:700;display:inline-block;width:140px;">Quantity</span>{{ number_format((float) $biologicalAsset->quantity, 2) }} {{ $biologicalAsset->unit }}</div>
                                    @if ($biologicalAsset->fair_value_date)
                                        <div><span style="font-weight:700;display:inline-block;width:140px;">Last FV date</span>{{ $biologicalAsset->fair_value_date->format('d M Y') }}</div>
                                    @endif
                                </td>
                                <td style="vertical-align:top;width:50%;text-align:right;">
                                    @if ($biologicalAsset->isDisposed())
                                        <div>Disposed: <strong>{{ $biologicalAsset->disposal_date?->format('d M Y') }}</strong></div>
                                        <div>Proceeds: <strong>R {{ number_format((float) ($biologicalAsset->disposal_proceeds ?? 0), 2) }}</strong></div>
                                    @endif
                                    @if ($biologicalAsset->notes)
                                        <div style="color:#6f869b;font-style:italic;font-size:6.5pt;margin-top:0.3rem;">{{ $biologicalAsset->notes }}</div>
                                    @endif
                                </td>
                            </tr>
                        </table>

                        <hr class="divider">

                        {{-- Summary --}}
                        <table class="summary-table">
                            <tr>
                                <td>
                                    <span class="lbl">Cost</span>
                                    <span class="amt">R {{ number_format((float) $biologicalAsset->cost, 2) }}</span>
                                </td>
                                <td>
                                    <span class="lbl">Fair Value</span>
                                    <span class="amt" style="color:#005bf0;">{{ $biologicalAsset->fair_value !== null ? 'R ' . number_format((float) $biologicalAsset->fair_value, 2) : "\u{2014}" }}</span>
                                </td>
                                <td>
                                    <span class="lbl">Cumulative FV Gain/Loss</span>
                                    <span class="amt" style="color:{{ (float) $biologicalAsset->fair_value_gain_loss >= 0 ? '#166534' : '#dc2626' }};">
                                        R {{ number_format((float) $biologicalAsset->fair_value_gain_loss, 2) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="lbl">Carrying Amount</span>
                                    <span class="amt">R {{ number_format($ca, 2) }}</span>
                                </td>
                            </tr>
                        </table>

                        {{-- Movement history --}}
                        <div style="margin-top:12pt;">
                            <div class="section-header">Movement History</div>
                            <div id="history-container">
                                <p style="color:#6f869b;font-style:italic;font-size:6.5pt;">Loading history…</p>
                            </div>
                        </div>

                        {{-- IAS 41 Actions --}}
                        @unless ($biologicalAsset->isDisposed())
                        <div class="action-section">
                            <div class="section-header">Record a Movement</div>
                            <p style="font-size:6.5pt;color:#6f869b;margin:3pt 0 6pt;">Select an action above or click a heading below. AI posts the journal automatically after you save.</p>

                            {{-- Fair Value Adjustment --}}
                            <div class="action-panel" id="panel-fair-value">
                                <div class="action-panel-head" onclick="togglePanel('fair-value')">
                                    <span class="action-panel-title">Fair value adjustment <small style="font-weight:400;color:#6f869b;">(IAS 41.12)</small></span>
                                    <span class="action-panel-chevron">&#9660;</span>
                                </div>
                                <div class="action-panel-body">
                                    <p class="af-hint">Re-measure the biological asset at fair value less costs to sell. The gain or loss is recognised in profit or loss for the period (IAS 41.26).</p>
                                    <form method="POST" action="{{ route('companies.biological-assets.fair-value-adjust', [$company, $biologicalAsset]) }}"
                                        onsubmit="return false" data-confirm-label="Biological Assets" data-ajax-confirm data-confirm-title="Fair Value Adjustment" data-confirm-body="Record fair value adjustment for {{ addslashes($biologicalAsset->name) }}?" data-confirm-text="Save" data-confirm-danger="0">
                                        @csrf
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>Current carrying amount</label>
                                                <input type="text" disabled value="R {{ number_format($ca, 2) }}">
                                            </div>
                                            <div class="af-field">
                                                <label>New fair value (R)</label>
                                                <input type="number" name="new_fair_value" step="0.01" min="0.01" required placeholder="FVLCTS">
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

                            {{-- Harvest --}}
                            <div class="action-panel" id="panel-harvest">
                                <div class="action-panel-head" onclick="togglePanel('harvest')">
                                    <span class="action-panel-title">Harvest <small style="font-weight:400;color:#6f869b;">(IAS 41.13)</small></span>
                                    <span class="action-panel-chevron">&#9660;</span>
                                </div>
                                <div class="action-panel-body">
                                    <p class="af-hint">Agricultural produce is measured at fair value less costs to sell at point of harvest. After harvest, IAS 2 (Inventories) applies. Dr Agricultural Produce (inventory) / Cr Biological Asset.</p>
                                    <form method="POST" action="{{ route('companies.biological-assets.harvest', [$company, $biologicalAsset]) }}"
                                        onsubmit="return false" data-confirm-label="Biological Assets" data-ajax-confirm data-confirm-title="Record Harvest" data-confirm-body="Record harvest from {{ addslashes($biologicalAsset->name) }}?" data-confirm-text="Harvest" data-confirm-danger="0">
                                        @csrf
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>Quantity harvested ({{ $biologicalAsset->unit }})</label>
                                                <input type="number" name="quantity" step="0.01" min="0.01" required>
                                            </div>
                                            <div class="af-field">
                                                <label>FVLCTS at harvest (R)</label>
                                                <input type="number" name="fair_value_at_harvest" step="0.01" min="0.01" required>
                                            </div>
                                            <div class="af-field">
                                                <label>Date</label>
                                                <input type="date" name="date" required value="{{ now()->format('Y-m-d') }}">
                                            </div>
                                        </div>
                                        <div class="af-row">
                                            <div class="af-field wide">
                                                <label>Description (optional)</label>
                                                <input type="text" name="description" maxlength="255" placeholder="e.g. Timber harvest Block A">
                                            </div>
                                        </div>
                                        <div style="display:flex;justify-content:flex-end;">
                                            <button type="submit" class="mgmt-btn primary">Save</button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            {{-- Natural Increase --}}
                            <div class="action-panel" id="panel-natural-increase">
                                <div class="action-panel-head" onclick="togglePanel('natural-increase')">
                                    <span class="action-panel-title">Natural increase <small style="font-weight:400;color:#6f869b;">(births / growth)</small></span>
                                    <span class="action-panel-chevron">&#9660;</span>
                                </div>
                                <div class="action-panel-body">
                                    <p class="af-hint">Record births, calving, or other natural increases in quantity. The fair value change is captured separately via the fair value adjustment action.</p>
                                    <form method="POST" action="{{ route('companies.biological-assets.natural-increase', [$company, $biologicalAsset]) }}"
                                        onsubmit="return false" data-confirm-label="Biological Assets" data-ajax-confirm data-confirm-title="Natural Increase" data-confirm-body="Record natural increase for {{ addslashes($biologicalAsset->name) }}?" data-confirm-text="Save" data-confirm-danger="0">
                                        @csrf
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>Quantity increase ({{ $biologicalAsset->unit }})</label>
                                                <input type="number" name="quantity" step="0.01" min="0.01" required>
                                            </div>
                                            <div class="af-field">
                                                <label>Date</label>
                                                <input type="date" name="date" required value="{{ now()->format('Y-m-d') }}">
                                            </div>
                                            <div class="af-field wide">
                                                <label>Notes (optional)</label>
                                                <input type="text" name="notes" maxlength="500" placeholder="e.g. Spring calving — 12 calves">
                                            </div>
                                        </div>
                                        <div style="display:flex;justify-content:flex-end;">
                                            <button type="submit" class="mgmt-btn primary">Save</button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            {{-- Mortality --}}
                            <div class="action-panel" id="panel-mortality">
                                <div class="action-panel-head" onclick="togglePanel('mortality')">
                                    <span class="action-panel-title">Mortality / loss <small style="font-weight:400;color:#6f869b;">(deaths, disease, etc.)</small></span>
                                    <span class="action-panel-chevron">&#9660;</span>
                                </div>
                                <div class="action-panel-body">
                                    <p class="af-hint">Record deaths, disease losses, or other quantity decreases not related to harvest or sale. The fair value impact is captured separately via fair value adjustment.</p>
                                    <form method="POST" action="{{ route('companies.biological-assets.mortality', [$company, $biologicalAsset]) }}"
                                        onsubmit="return false" data-confirm-label="Biological Assets" data-ajax-confirm data-confirm-title="Mortality / Loss" data-confirm-body="Record mortality/loss for {{ addslashes($biologicalAsset->name) }}?" data-confirm-text="Save" data-confirm-danger="0">
                                        @csrf
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>Quantity lost ({{ $biologicalAsset->unit }})</label>
                                                <input type="number" name="quantity" step="0.01" min="0.01" required>
                                            </div>
                                            <div class="af-field">
                                                <label>Date</label>
                                                <input type="date" name="date" required value="{{ now()->format('Y-m-d') }}">
                                            </div>
                                            <div class="af-field wide">
                                                <label>Notes (optional)</label>
                                                <input type="text" name="notes" maxlength="500" placeholder="e.g. Lightning strike — 3 head lost">
                                            </div>
                                        </div>
                                        <div style="display:flex;justify-content:flex-end;">
                                            <button type="submit" class="mgmt-btn primary">Save</button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            {{-- Dispose --}}
                            <div class="action-panel" id="panel-dispose">
                                <div class="action-panel-head" onclick="togglePanel('dispose')">
                                    <span class="action-panel-title">Dispose / sell <small style="font-weight:400;color:#6f869b;">(IAS 41.27)</small></span>
                                    <span class="action-panel-chevron">&#9660;</span>
                                </div>
                                <div class="action-panel-body">
                                    <p class="af-hint">Derecognise the biological asset on sale or retirement. Gain/loss = proceeds minus carrying amount.</p>
                                    <form method="POST" action="{{ route('companies.biological-assets.dispose', [$company, $biologicalAsset]) }}"
                                        onsubmit="return false" data-confirm-label="Biological Assets" data-ajax-confirm data-confirm-title="Dispose Biological Asset" data-confirm-body="Dispose {{ addslashes($biologicalAsset->name) }}? This will derecognise the asset." data-confirm-text="Dispose" data-confirm-danger="1">
                                        @csrf
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>Carrying amount</label>
                                                <input type="text" disabled value="R {{ number_format($ca, 2) }}">
                                            </div>
                                            <div class="af-field">
                                                <label>Disposal proceeds (R)</label>
                                                <input type="number" name="disposal_proceeds" step="0.01" min="0" value="0" required>
                                            </div>
                                            <div class="af-field">
                                                <label>Disposal date</label>
                                                <input type="date" name="disposal_date" required value="{{ now()->format('Y-m-d') }}">
                                            </div>
                                        </div>
                                        <div style="display:flex;justify-content:flex-end;">
                                            <button type="submit" class="mgmt-btn danger">Dispose</button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                        </div>{{-- /.action-section --}}
                        @endunless

                    </div>{{-- /.cust-doc-body --}}
                </div>{{-- /.cust-doc --}}

            </main>
        </div>
    </div>

    <script>
        function togglePanel(id) {
            var el = document.getElementById('panel-' + id);
            if (!el) return;
            var isOpen = el.classList.contains('open');
            document.querySelectorAll('.action-panel.open').forEach(function(p) { p.classList.remove('open'); });
            if (!isOpen) {
                el.classList.add('open');
                el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
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
                var res = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    redirect: 'follow',
                });
                if (res.ok || res.redirected) {
                    showToast('Saved — journal posting in progress', '#1d4ed8');
                    setTimeout(function() { window.location.reload(); }, 1200);
                } else {
                    showToast('Error saving action', '#b91c1c');
                }
            } catch (err) {
                showToast('Network error', '#b91c1c');
            } finally {
                if (btn) { btn.disabled = false; btn.textContent = 'Save'; }
            }
        }

        document.querySelectorAll('.action-panel form').forEach(function(form) {
            form.addEventListener('submit', async function (e) {
                e.preventDefault();
                if (form.dataset.confirmTitle && typeof window.showConfirmModal === 'function') {
                    window.showConfirmModal({
                        label:       form.dataset.confirmLabel || '',
                        title:       form.dataset.confirmTitle,
                        body:        form.dataset.confirmBody || '',
                        confirmText: form.dataset.confirmText || 'Confirm',
                        danger:      !!form.dataset.confirmDanger,
                        onConfirm:   function() { submitActionForm(form); },
                    });
                } else {
                    await submitActionForm(form);
                }
            });
        });

        // Load history
        const jsStatusColors = { posted: { bg: '#dcfce7', color: '#166534' }, failed: { bg: '#fee2e2', color: '#991b1b' }, pending: { bg: '#fef3c7', color: '#92400e' } };
        const jsStatusLabels = { posted: 'Journal posted', failed: 'Journal failed', pending: 'Journal pending' };

        async function retryPosting(btn, url) {
            btn.disabled = true;
            btn.textContent = '…';
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const badge = btn.closest('.history-row')?.querySelector('.posting-status');
                if (badge) {
                    badge.style.background = jsStatusColors.pending.bg;
                    badge.style.color = jsStatusColors.pending.color;
                    badge.textContent = jsStatusLabels.pending;
                }
                btn.remove();
                showToast('Retry queued — posting in progress', '#1d4ed8');
            } catch {
                showToast('Retry failed — please try again', '#b91c1c');
                btn.disabled = false;
                btn.textContent = 'Retry';
            }
        }

        (function() {
            var container = document.getElementById('history-container');
            fetch('{{ route("companies.biological-assets.history", [$company, $biologicalAsset]) }}')
                .then(r => r.json())
                .then(events => {
                    if (!events.length) { container.innerHTML = '<p style="color:#6f869b;font-style:italic;font-size:6.5pt;">No movement history yet.</p>'; return; }
                    container.innerHTML = '';
                    events.forEach(function(e) {
                        var sc = jsStatusColors[e.journal_status] || jsStatusColors.pending;
                        var sl = jsStatusLabels[e.journal_status] || e.journal_status;
                        var div = document.createElement('div');
                        div.className = 'history-row';
                        if (e.retry_url) {
                            div.addEventListener('mouseenter', function() { var b = div.querySelector('.retry-posting-btn'); if (b) b.style.opacity = '1'; });
                            div.addEventListener('mouseleave', function() { var b = div.querySelector('.retry-posting-btn'); if (b) b.style.opacity = '0'; });
                        }
                        div.innerHTML =
                            '<span class="history-date">' + e.date + '</span>' +
                            '<span class="history-badge" style="background:' + e.bg + ';color:' + e.color + ';">' + e.label + '</span>' +
                            '<span class="history-desc">' + e.description +
                                (e.quantity_change ? ' <span style="color:#6f869b;font-size:6pt;">(' + e.quantity_change + ')</span>' : '') +
                            '</span>' +
                            '<span class="history-amt">R ' + e.amount + '</span>' +
                            (e.transaction_url
                                ? '<a href="' + e.transaction_url + '" class="posting-status" style="background:' + sc.bg + ';color:' + sc.color + ';text-decoration:underline;cursor:pointer;">' + sl + '</a>'
                                : '<span class="posting-status" style="background:' + sc.bg + ';color:' + sc.color + ';">' + sl + '</span>') +
                            (e.retry_url ? '<button onclick="retryPosting(this,\'' + e.retry_url + '\')" class="retry-posting-btn" style="margin-left:4pt;font-size:5pt;font-weight:700;padding:0.15rem 4pt;border-radius:4px;border:1px solid #b91c1c;color:#b91c1c;background:transparent;cursor:pointer;opacity:0;transition:opacity 0.15s;">Retry</button>' : '');
                        container.appendChild(div);
                    });
                })
                .catch(function() { container.innerHTML = '<p style="color:#b91c1c;font-size:6.5pt;">Failed to load history.</p>'; });
        })();

        @if (class_exists('Illuminate\Support\Facades\Broadcast'))
        // Reverb real-time updates
        if (typeof window.Echo !== 'undefined') {
            window.Echo.channel('company.{{ $company->id }}')
                .listen('.posting.status', function(e) {
                    if (e.entity_type === 'biological_asset_event') {
                        showToast(e.message || 'Posting updated', e.status === 'posted' ? '#166534' : '#b91c1c');
                        setTimeout(function() { window.location.reload(); }, 1500);
                    }
                });
        }
        @endif
    </script>

    {{-- Edit modal --}}
    @unless ($biologicalAsset->isDisposed())
    <div id="edit-modal"
        style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:9999;align-items:center;justify-content:center;padding:16pt 8pt;overflow-y:auto;">
        <div style="background:#fff;width:100%;max-width:520px;border:1px solid #1a345b;border-radius:0;box-shadow:0 20px 60px rgba(0,0,0,0.4);overflow:hidden;margin:0 auto;">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:9pt 12pt;border-bottom:1.5px solid #1a345b;">
                <h3 style="font-size:1.05rem;font-weight:800;letter-spacing:0.04em;margin:0;text-transform:uppercase;color:#1a345b;">Edit Biological Asset</h3>
                <button onclick="closeEditModal()"
                    style="background:none;border:none;font-size:1.4rem;line-height:1;color:#1a345b;cursor:pointer;">&times;</button>
            </div>

            <form method="POST" action="{{ route('companies.biological-assets.update', [$company, $biologicalAsset]) }}">
                @csrf
                @method('PATCH')
                <div style="padding:10pt 12pt;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:5pt 7pt;margin-bottom:8pt;">
                    <div class="as-field" style="grid-column:1/-1;">
                        <label>Name</label>
                        <input type="text" name="name" required value="{{ old('name', $biologicalAsset->name) }}">
                    </div>
                    <div class="as-field">
                        <label>Class</label>
                        <select name="biological_asset_class_id">
                            <option value="">— None —</option>
                            @foreach ($company->biologicalAssetClasses()->orderBy('sort_order')->get() as $c)
                                <option value="{{ $c->id }}" {{ $biologicalAsset->biological_asset_class_id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="as-field">
                        <label>Reference</label>
                        <input type="text" name="reference" value="{{ old('reference', $biologicalAsset->reference) }}">
                    </div>
                    <div class="as-field">
                        <label>Location</label>
                        <input type="text" name="location" value="{{ old('location', $biologicalAsset->location) }}">
                    </div>
                    <div class="as-field">
                        <label>Quantity</label>
                        <input type="number" name="quantity" step="0.01" min="0" required value="{{ old('quantity', $biologicalAsset->quantity) }}">
                    </div>
                    <div class="as-field">
                        <label>Unit</label>
                        <input type="text" name="unit" required value="{{ old('unit', $biologicalAsset->unit) }}">
                    </div>
                    <div class="as-field" style="grid-column:1/-1;">
                        <label>Notes</label>
                        <input type="text" name="notes" value="{{ old('notes', $biologicalAsset->notes) }}" maxlength="500">
                    </div>
                </div>
                <div style="display:flex;gap:5pt;justify-content:flex-end;padding-top:4pt;border-top:1px solid #9ec1f5;">
                    <button type="button" onclick="closeEditModal()"
                        style="padding:4pt 9pt;border:1px solid #9ec1f5;font-size:6.5pt;color:#1a345b;background:#fff;cursor:pointer;font-weight:600;border-radius:0;">Cancel</button>
                    <button type="submit"
                        style="padding:4pt 10pt;background:#005bf0;color:#fff;border:1px solid #005bf0;font-size:6.5pt;font-weight:700;cursor:pointer;border-radius:0;">Save changes</button>
                </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal()  { document.getElementById('edit-modal').style.display = 'flex'; }
        function closeEditModal() { document.getElementById('edit-modal').style.display = 'none'; }
        document.getElementById('edit-modal').addEventListener('click', function(e) {
            if (e.target === this) closeEditModal();
        });
    </script>
    @endunless
@endsection
