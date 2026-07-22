@extends('layouts.public')

@section('title', $heldForSale->asset->name . ' — Held for Sale')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .inv-mgmt-bar { display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem; }
        .inv-mgmt-bar a,.inv-mgmt-bar .mgmt-back { font-size:0.78rem;color:#6b7280;text-decoration:none;display:inline-flex;align-items:center;gap:0.3rem;transition:color 0.15s;background:none;border:none;cursor:pointer;font-family:inherit; }
        .inv-mgmt-bar a:hover,.inv-mgmt-bar .mgmt-back:hover { color:#000; }
        .mgmt-btn { display:inline-flex;align-items:center;gap:0.4rem;background:#fff;border:1px solid #000;color:#000;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;padding:0.4rem 0.85rem;text-decoration:none;cursor:pointer;font-family:inherit;transition:background 0.15s,color 0.15s; }
        .mgmt-btn:hover { background:#000;color:#fff; }
        .mgmt-btn.primary { background:#000;color:#fff; }
        .mgmt-btn.primary:hover { background:#333; }
        .mgmt-btn.danger { border-color:#dc2626;color:#dc2626; }
        .mgmt-btn.danger:hover { background:#dc2626;color:#fff; }
        .cust-doc { background:#fff;border:1px solid #ddd;font-family:'DejaVu Sans',Helvetica,Arial,sans-serif;color:#000;font-size:0.78rem;line-height:1.45; }
        .cust-doc-body { padding:2rem 2.25rem; }
        .cust-header-table { width:100%;border-collapse:collapse;margin-bottom:0.75rem; }
        .doc-title { font-size:1.3rem;font-weight:800;text-align:right;margin-bottom:0.25rem;letter-spacing:0.04em; }
        .doc-meta-line { text-align:right;font-size:0.78rem; }
        .status-box { display:inline-block;font-weight:700;text-transform:uppercase;border:1px solid #854d0e;color:#854d0e;padding:0.08rem 0.5rem;font-size:0.62rem;letter-spacing:0.08em;margin-top:0.35rem; }
        .status-box.sold { color:#065f46;border-color:#065f46; }
        .status-box.reversed { color:#6b7280;border-color:#6b7280; }
        .divider { border:none;border-top:2px solid #000;margin:1rem 0 1.25rem; }
        .summary-table { width:100%;border-collapse:collapse;margin-bottom:0.25rem; }
        .summary-table td { padding:0 1.25rem 0 0;font-size:0.78rem;vertical-align:top; }
        .summary-table .lbl { display:block;font-weight:700;font-size:0.62rem;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.15rem; }
        .summary-table .amt { font-size:0.95rem;font-weight:800; }
        .section-header { font-weight:700;font-size:0.85rem;text-transform:uppercase;letter-spacing:0.06em;border-bottom:1.5px solid #000;padding-bottom:0.25rem;margin-bottom:0.5rem; }
        .info-section { margin-top:1.5rem; }
        .action-section { margin-top:1.5rem; }
        .action-panel { border-bottom:1px solid #eee; }
        .action-panel-head { display:flex;align-items:center;justify-content:space-between;padding:0.65rem 0;cursor:pointer;user-select:none; }
        .action-panel-title { font-size:0.82rem;font-weight:700;display:flex;align-items:center;gap:0.5rem; }
        .action-panel-body { display:none;padding-bottom:1rem; }
        .action-panel.open .action-panel-body { display:block; }
        .action-panel-chevron { font-size:0.65rem;color:#9ca3af;transition:transform 0.15s; }
        .action-panel.open .action-panel-chevron { transform:rotate(180deg); }
        .af-row { display:flex;flex-wrap:wrap;gap:0.5rem 0.75rem;align-items:flex-end;margin-bottom:0.65rem; }
        .af-field { flex:1;min-width:130px; }
        .af-field.wide { flex:2;min-width:200px; }
        .af-field label { display:block;font-size:0.58rem;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;color:#555;margin-bottom:0.2rem; }
        .af-field input,.af-field select,.af-field textarea { width:100%;border:1px solid #ccc;padding:0.32rem 0.5rem;font-size:0.78rem;font-family:inherit;color:#000;box-sizing:border-box;background:#fff; }
        .af-field input:disabled { background:#f9f9f9;color:#999; }
        .af-field input:focus,.af-field select:focus,.af-field textarea:focus { outline:none;border-color:#000; }
        .af-hint { font-size:0.65rem;color:#888;margin-bottom:0.5rem;line-height:1.4; }
        .as-field label { display:block;font-size:0.6rem;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;color:#5e17eb;margin-bottom:0.2rem; }
        .as-field input,.as-field select,.as-field textarea { width:100%;border:1px solid rgba(94,23,235,0.25);padding:0.28rem 0.45rem;font-size:0.74rem;color:#1b1b18;background:#fff;outline:none;transition:border-color 0.15s;box-sizing:border-box; }
        .as-field input:focus,.as-field select:focus,.as-field textarea:focus { border-color:#5e17eb;box-shadow:0 0 0 3px rgba(94,23,235,0.08); }
        @media (max-width:640px) {
            .cust-doc-body { padding:1.25rem 1rem; }
            .cust-header-table,.cust-header-table tr,.cust-header-table td { display:block;width:100%!important;text-align:left!important; }
            .doc-title,.doc-meta-line { text-align:left!important; }
            .summary-table,.summary-table tr,.summary-table td { display:block;width:100%!important;padding:0 0 0.75rem; }
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
                @if (session('error'))
                    <div style="background:#fee2e2;border:1px solid #fca5a5;color:#b91c1c;padding:0.75rem 1.25rem;font-size:0.855rem;font-weight:600;margin-bottom:1.25rem;">
                        {{ session('error') }}
                    </div>
                @endif

                @php
                    $asset = $heldForSale->asset;
                    $ca    = $heldForSale->carryingAmount();
                @endphp

                {{-- Management bar --}}
                <div class="inv-mgmt-bar">
<div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
                        @if ($heldForSale->status === 'held_for_sale')
                            <button type="button" class="mgmt-btn" onclick="togglePanel('reverse')">Reverse to PPE</button>
                            <button type="button" class="mgmt-btn danger" onclick="togglePanel('dispose')">Record Sale</button>
                            <button type="button" class="mgmt-btn primary" onclick="openEditModal()">Edit Details</button>
                        @endif
                        <a href="{{ route('companies.assets.show', [$company, $asset]) }}" class="mgmt-btn">View PPE Asset</a>
                    </div>
                </div>

                {{-- Document --}}
                <div class="cust-doc">
                    <div class="cust-doc-body">

                        {{-- Header --}}
                        <table class="cust-header-table">
                            <tr>
                                <td style="vertical-align:top;width:55%;">
                                    <div style="font-size:1.3rem;font-weight:800;letter-spacing:-0.01em;">{{ $asset->name }}</div>
                                    @if ($asset->asset_tag)
                                        <div style="font-size:0.78rem;color:#555;margin-top:0.2rem;">{{ $asset->asset_tag }}</div>
                                    @endif
                                </td>
                                <td style="vertical-align:top;width:45%;">
                                    <div class="doc-title">HELD FOR SALE</div>
                                    <div class="doc-meta-line">PPE Class: <strong>{{ $asset->ppeClass?->name ?? 'Unclassified' }}</strong></div>
                                    @if ($asset->location)
                                        <div class="doc-meta-line">Location: <strong>{{ $asset->location }}</strong></div>
                                    @endif
                                    <div style="text-align:right;">
                                        @if ($heldForSale->status === 'sold')
                                            <span class="status-box sold">Sold</span>
                                        @elseif ($heldForSale->status === 'reversed')
                                            <span class="status-box reversed">Reversed</span>
                                        @else
                                            <span class="status-box">Held for Sale</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        </table>

                        {{-- Meta --}}
                        <table style="width:100%;border-collapse:collapse;font-size:0.78rem;margin-bottom:0.5rem;">
                            <tr>
                                <td style="vertical-align:top;width:50%;">
                                    <div><span style="font-weight:700;display:inline-block;width:160px;">Reclassified</span>{{ $heldForSale->reclassification_date->format('d M Y') }}</div>
                                    <div><span style="font-weight:700;display:inline-block;width:160px;">Originally acquired</span>{{ $asset->acquisition_date->format('d M Y') }}</div>
                                    @if ($heldForSale->expected_sale_date)
                                        <div><span style="font-weight:700;display:inline-block;width:160px;">Expected sale date</span>{{ $heldForSale->expected_sale_date->format('d M Y') }}</div>
                                    @endif
                                    @if ($heldForSale->buyer_details)
                                        <div style="margin-top:0.35rem;"><span style="font-weight:700;">Buyer:</span> {{ $heldForSale->buyer_details }}</div>
                                    @endif
                                    @if ($heldForSale->notes)
                                        <div style="margin-top:0.35rem;color:#555;font-style:italic;">{{ $heldForSale->notes }}</div>
                                    @endif
                                </td>
                                <td style="vertical-align:top;width:50%;text-align:right;">
                                    @if ($heldForSale->isSold())
                                        <div>Sold: <strong>{{ $heldForSale->disposal_date?->format('d M Y') }}</strong></div>
                                        <div>Proceeds: <strong>R {{ number_format((float)($heldForSale->disposal_proceeds ?? 0), 2) }}</strong></div>
                                    @endif
                                    <div>Original cost: <strong>R {{ number_format((float)$asset->cost, 2) }}</strong></div>
                                </td>
                            </tr>
                        </table>

                        <hr class="divider">

                        {{-- Summary --}}
                        <table class="summary-table">
                            <tr>
                                <td>
                                    <span class="lbl">Carrying Amt at Reclassification</span>
                                    <span class="amt">R {{ number_format((float)$heldForSale->carrying_amount_at_reclassification, 2) }}</span>
                                </td>
                                @if ($heldForSale->fair_value_less_costs_to_sell !== null)
                                <td>
                                    <span class="lbl">FVLCTS</span>
                                    <span class="amt" style="color:#7c3aed;">R {{ number_format((float)$heldForSale->fair_value_less_costs_to_sell, 2) }}</span>
                                </td>
                                @endif
                                @if ((float)$heldForSale->impairment_on_reclassification > 0)
                                <td>
                                    <span class="lbl">Write-down (IFRS 5.15)</span>
                                    <span class="amt" style="color:#b91c1c;">R {{ number_format((float)$heldForSale->impairment_on_reclassification, 2) }}</span>
                                </td>
                                @endif
                                <td>
                                    <span class="lbl">IFRS 5 Carrying Amount</span>
                                    <span class="amt">R {{ number_format($ca, 2) }}</span>
                                </td>
                            </tr>
                        </table>

                        {{-- IFRS 5 info --}}
                        <div class="info-section">
                            <div class="section-header">IFRS 5 Requirements</div>
                            <div style="font-size:0.72rem;color:#555;line-height:1.6;">
                                <p style="margin:0.25rem 0;">&#8226; Depreciation has <strong>ceased</strong> from the reclassification date.</p>
                                <p style="margin:0.25rem 0;">&#8226; Measured at the <strong>lower</strong> of carrying amount and fair value less costs to sell.</p>
                                <p style="margin:0.25rem 0;">&#8226; Presented <strong>separately</strong> as a current asset on the statement of financial position.</p>
                                <p style="margin:0.25rem 0;">&#8226; If criteria no longer met, asset returns to PPE at the lower of (a) carrying amount before reclassification (adjusted for depreciation that would have been recognised) and (b) recoverable amount.</p>
                            </div>
                        </div>

                        {{-- Actions --}}
                        @if ($heldForSale->status === 'held_for_sale')
                        <div class="action-section">
                            <div class="section-header">Actions</div>

                            {{-- Reverse to PPE --}}
                            <div class="action-panel" id="panel-reverse">
                                <div class="action-panel-head" onclick="togglePanel('reverse')">
                                    <span class="action-panel-title">Reverse to PPE <small style="font-weight:400;color:#888;">(IFRS 5.26-29)</small></span>
                                    <span class="action-panel-chevron">&#9660;</span>
                                </div>
                                <div class="action-panel-body">
                                    <p class="af-hint">If the held-for-sale criteria are no longer met, return the asset to PPE. The asset is measured at the lower of its carrying amount before reclassification (adjusted for depreciation) and its recoverable amount.</p>
                                    <form method="POST" action="{{ route('companies.held-for-sale.reverse', [$company, $heldForSale]) }}"
                                        onsubmit="return false" data-confirm-label="Held for Sale" data-ajax-confirm data-confirm-title="Reverse to PPE" data-confirm-body="Return {{ addslashes($asset->name) }} to the PPE register? Depreciation will resume." data-confirm-text="Reverse" data-confirm-danger="0">
                                        @csrf
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>Reversal date</label>
                                                <input type="date" name="reversal_date" required value="{{ now()->format('Y-m-d') }}">
                                            </div>
                                        </div>
                                        <div style="display:flex;justify-content:flex-end;">
                                            <button type="submit" class="mgmt-btn primary">Reverse</button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            {{-- Dispose / Sell --}}
                            <div class="action-panel" id="panel-dispose">
                                <div class="action-panel-head" onclick="togglePanel('dispose')">
                                    <span class="action-panel-title">Record sale <small style="font-weight:400;color:#888;">(IFRS 5.25)</small></span>
                                    <span class="action-panel-chevron">&#9660;</span>
                                </div>
                                <div class="action-panel-body">
                                    <p class="af-hint">Record the actual sale of the asset. Gain/loss = proceeds minus carrying amount, posted to P&amp;L.</p>
                                    <form method="POST" action="{{ route('companies.held-for-sale.dispose', [$company, $heldForSale]) }}"
                                        onsubmit="return false" data-confirm-label="Held for Sale" data-ajax-confirm data-confirm-title="Record Sale" data-confirm-body="Record sale of {{ addslashes($asset->name) }}? This will dispose the asset and post the disposal journal." data-confirm-text="Sell" data-confirm-danger="1">
                                        @csrf
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>IFRS 5 carrying amount</label>
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
                                            <button type="submit" class="mgmt-btn danger">Record Sale</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endif

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
            t.style.cssText = 'position:fixed;bottom:1.5rem;right:1.5rem;padding:0.75rem 1.25rem;border-radius:6px;font-size:0.8rem;font-weight:600;color:#fff;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,0.15);transition:opacity 0.4s;background:' + color;
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
                if (form.dataset.confirmTitle) {
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
    </script>

    {{-- Edit modal --}}
    @if ($heldForSale->status === 'held_for_sale')
    <div id="edit-modal"
        style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:9999;align-items:center;justify-content:center;">
        <div style="background:#fff;width:100%;max-width:520px;padding:1.75rem 1.75rem 1.5rem;position:relative;max-height:90vh;overflow-y:auto;margin:1rem;">
            <button onclick="closeEditModal()"
                style="position:absolute;top:1rem;right:1rem;background:none;border:none;font-size:1.3rem;color:#aaa;cursor:pointer;">&times;</button>
            <p style="font-size:0.62rem;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#854d0e;margin:0 0 0.25rem;">IFRS 5</p>
            <h3 style="font-size:1rem;font-weight:800;color:#1b1b18;margin:0 0 1.25rem;">Edit Held-for-Sale Details</h3>

            <form method="POST" action="{{ route('companies.held-for-sale.update', [$company, $heldForSale]) }}">
                @csrf
                @method('PATCH')
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.55rem 0.8rem;margin-bottom:1rem;">
                    <div class="as-field">
                        <label>FVLCTS (R)</label>
                        <input type="number" name="fair_value_less_costs_to_sell" value="{{ old('fair_value_less_costs_to_sell', $heldForSale->fair_value_less_costs_to_sell) }}" min="0" step="0.01">
                    </div>
                    <div class="as-field">
                        <label>Expected sale date</label>
                        <input type="date" name="expected_sale_date" value="{{ old('expected_sale_date', $heldForSale->expected_sale_date?->format('Y-m-d')) }}">
                    </div>
                    <div class="as-field" style="grid-column:1/-1;">
                        <label>Buyer details</label>
                        <input type="text" name="buyer_details" value="{{ old('buyer_details', $heldForSale->buyer_details) }}" maxlength="500">
                    </div>
                    <div class="as-field" style="grid-column:1/-1;">
                        <label>Notes</label>
                        <input type="text" name="notes" value="{{ old('notes', $heldForSale->notes) }}" maxlength="1000">
                    </div>
                </div>
                <div style="display:flex;gap:0.6rem;justify-content:flex-end;">
                    <button type="button" onclick="closeEditModal()"
                        style="padding:0.38rem 1rem;border:1px solid #d1d5db;font-size:0.75rem;color:#555;background:#fff;cursor:pointer;font-weight:600;">Cancel</button>
                    <button type="submit"
                        style="padding:0.38rem 1.1rem;background:#5e17eb;color:#fff;border:none;font-size:0.75rem;font-weight:700;cursor:pointer;">Save changes</button>
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
    @endif
@endsection
