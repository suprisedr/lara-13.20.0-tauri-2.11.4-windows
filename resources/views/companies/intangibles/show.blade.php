@extends('layouts.public')

@section('title', $asset->name . ' — Intangible Asset')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .inv-mgmt-bar { display:flex; align-items:center; justify-content:space-between; gap:8pt; flex-wrap:wrap; margin-bottom:12pt; }
        .inv-mgmt-bar a, .inv-mgmt-bar .mgmt-back { font-size:7pt; color:#7a90a5; text-decoration:none; display:inline-flex; align-items:center; gap:3pt; transition:color 0.15s; background:none; border:none; cursor:pointer; font-family:inherit; }
        .inv-mgmt-bar a:hover, .inv-mgmt-bar .mgmt-back:hover { color:#16355c; }
        .mgmt-btn { display:inline-flex; align-items:center; gap:3pt; background:#fff; border:1px solid #c9dff0; color:#16355c; font-size:6.5pt; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; padding:4pt 7pt; text-decoration:none; cursor:pointer; font-family:inherit; transition:background 0.15s, color 0.15s; }
        .mgmt-btn:hover { background:#eef6fc; color:#16355c; }
        .mgmt-btn.primary { background:#0079c8; color:#fff; }
        .mgmt-btn.primary:hover { background:#005f9e; }
        .mgmt-btn.danger { border-color:#dc2626; color:#dc2626; }
        .mgmt-btn.danger:hover { background:#dc2626; color:#fff; }
        .cust-doc { background:#fff; border:1px solid #c9dff0; font-family:Helvetica,Arial,"DejaVu Sans",sans-serif; color:#16355c; font-size:7pt; line-height:1.45; }
        .cust-doc-body { padding:16pt 18pt; }
        .cust-header-table { width:100%; border-collapse:collapse; margin-bottom:6pt; }
        .doc-title { font-size:11pt; font-weight:800; text-align:right; margin-bottom:2pt; letter-spacing:0.04em; }
        .doc-meta-line { text-align:right; font-size:7pt; }
        .status-box { display:inline-block; font-weight:700; text-transform:uppercase; border:1px solid #16355c; padding:0.08rem 4pt; font-size:5pt; letter-spacing:0.08em; margin-top:3pt; }
        .status-box.disposed { color:#dc2626; border-color:#dc2626; }
        .divider { border:none; border-top:1.5pt solid #16355c; margin:8pt 0 10pt; }
        .summary-table { width:100%; border-collapse:collapse; margin-bottom:2pt; }
        .summary-table td { padding:0 10pt 0 0; font-size:7pt; vertical-align:top; }
        .summary-table .lbl { display:block; font-weight:700; font-size:5pt; text-transform:uppercase; letter-spacing:0.08em; margin-bottom:1.5pt; }
        .summary-table .amt { font-size:8pt; font-weight:800; }
        .section-header { font-weight:700; font-size:7.5pt; text-transform:uppercase; letter-spacing:0.06em; border-bottom:1.5px solid #16355c; padding-bottom:2pt; margin-bottom:4pt; }
        .info-section { margin-top:12pt; }
        table.cust-items-table { width:100%; border-collapse:collapse; }
        table.cust-items-table thead td { font-weight:700; font-size:6.5pt; text-transform:uppercase; letter-spacing:0.04em; border-bottom:1.5px solid #16355c; padding-bottom:4pt; }
        table.cust-items-table thead td.amt { text-align:right; }
        table.cust-items-table tbody td { padding:4pt 0; font-size:7pt; border-bottom:1px solid #ddebf5; vertical-align:middle; }
        table.cust-items-table tbody td.amt { text-align:right; font-family:"DejaVu Sans Mono",monospace; white-space:nowrap; }
        table.cust-items-table tbody tr:last-child td { border-bottom:none; }
        table.cust-items-table tbody tr:hover td { background:#eef6fc; }
        .ev-chip { display:inline-block; font-size:5pt; font-weight:800; letter-spacing:0.05em; text-transform:uppercase; padding:0.1rem 4pt; border-radius:0; white-space:nowrap; }
        .action-section { margin-top:12pt; }
        .action-panel { border-bottom:1px solid #ddebf5; }
        .action-panel-head { display:flex; align-items:center; justify-content:space-between; padding:5pt 0; cursor:pointer; user-select:none; }
        .action-panel-title { font-size:7pt; font-weight:700; display:flex; align-items:center; gap:4pt; }
        .action-panel-body { display:none; padding-bottom:8pt; }
        .action-panel.open .action-panel-body { display:block; }
        .action-panel-chevron { font-size:6pt; color:#7a90a5; transition:transform 0.15s; }
        .action-panel.open .action-panel-chevron { transform:rotate(180deg); }
        .af-row { display:flex; flex-wrap:wrap; gap:4pt 6pt; align-items:flex-end; margin-bottom:5pt; }
        .af-field { flex:1; min-width:100pt; }
        .af-field.wide { flex:2; min-width:150pt; }
        .af-field label { display:block; font-size:5pt; font-weight:700; letter-spacing:0.07em; text-transform:uppercase; color:#7a90a5; margin-bottom:0.2rem; }
        .af-field input, .af-field select { width:100%; border:1px solid #c9dff0; padding:3pt 4pt; font-size:7pt; font-family:inherit; color:#16355c; box-sizing:border-box; background:#fff; }
        .af-field input:disabled { background:#eef6fc; color:#7a90a5; }
        .af-field input:focus, .af-field select:focus { outline:none; border-color:#0079c8; }
        .af-hint { font-size:6pt; color:#7a90a5; margin-bottom:4pt; line-height:1.4; }
        .as-field label { display:block; font-size:5pt; font-weight:700; letter-spacing:0.07em; text-transform:uppercase; color:#7a90a5; margin-bottom:0.2rem; }
        .as-field input, .as-field select { width:100%; border:1px solid #c9dff0; padding:3pt 4pt; font-size:7pt; font-family:inherit; color:#16355c; background:#fff; outline:none; box-sizing:border-box; height:16pt; border-radius:0; }
        .as-field input:focus, .as-field select:focus { border-color:#0079c8; }
        @media (max-width:640px) {
            .cust-doc-body { padding:10pt 8pt; }
            .cust-header-table, .cust-header-table tr, .cust-header-table td { display:block; width:100%!important; text-align:left!important; }
            .doc-title, .doc-meta-line { text-align:left!important; }
            .summary-table, .summary-table tr, .summary-table td { display:block; width:100%!important; padding:0 0 6pt; }
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
                    $today    = now()->format('Y-m-d');
                    $accAmort = $asset->accumulatedAmortisation($today);
                    $accImp   = (float)($asset->accumulated_impairment ?? 0);
                    $revSur   = (float)($asset->revaluation_surplus ?? 0);
                    $nbv      = $asset->netBookValue($today);
                    $policy   = $asset->intangibleClass?->accounting_policy ?? 'cost';
                    $life     = $asset->useful_life_years ?? $asset->intangibleClass?->useful_life_years;
                    $indef    = $asset->isIndefiniteLife();
                    $goodwill = $asset->isGoodwill();
                @endphp

                {{-- Management bar --}}
                <div class="inv-mgmt-bar">
<div style="display:flex;align-items:center;gap:4pt;flex-wrap:wrap;">
                        @unless ($asset->isDisposed())
                            <button type="button" class="mgmt-btn" onclick="togglePanel('capitalise')">+ Capitalise</button>
                            @if ($policy === 'revaluation' && ! $goodwill)
                                <button type="button" class="mgmt-btn" onclick="togglePanel('revalue')">Revalue</button>
                            @endif
                            <button type="button" class="mgmt-btn" onclick="togglePanel('impair')">Impair</button>
                            @if ($accImp > 0 && ! $goodwill)
                                <button type="button" class="mgmt-btn" onclick="togglePanel('reverse')">Reverse Imp.</button>
                            @endif
                            <button type="button" class="mgmt-btn danger" onclick="togglePanel('dispose')">Dispose</button>
                        @endunless
                        <button type="button" class="mgmt-btn primary" onclick="openEditModal()">Edit Intangible</button>
                    </div>
                </div>

                {{-- Document --}}
                <div class="cust-doc">
                    <div class="cust-doc-body">

                        {{-- Header --}}
                        <table class="cust-header-table">
                            <tr>
                                <td style="vertical-align:top;width:55%;">
                                    <div style="font-size:11pt;font-weight:800;letter-spacing:-0.01em;">
                                        {{ $asset->name }}
                                        @if ($indef)
                                            <span style="font-size:5pt;font-weight:700;color:#92400e;background:#fef3c7;padding:0.1rem 4pt;border:1px solid #fde68a;margin-left:4pt;letter-spacing:0.08em;text-transform:uppercase;">Indefinite life</span>
                                        @endif
                                    </div>
                                    @if ($asset->reference)
                                        <div style="font-size:7pt;color:#7a90a5;margin-top:0.2rem;">{{ $asset->reference }}</div>
                                    @endif
                                </td>
                                <td style="vertical-align:top;width:45%;">
                                    <div class="doc-title">INTANGIBLE PROFILE</div>
                                    <div class="doc-meta-line">Class: <strong>{{ $asset->intangibleClass?->name ?? 'Unclassified' }}</strong></div>
                                    <div class="doc-meta-line">Policy: <strong>{{ ucfirst($policy) }} model</strong></div>
                                    @if ($asset->category)
                                        <div class="doc-meta-line">Category: <strong>{{ $asset->category }}</strong></div>
                                    @endif
                                    <div style="text-align:right;">
                                        <span class="status-box {{ $asset->isDisposed() ? 'disposed' : '' }}">
                                            {{ $asset->isDisposed() ? 'Disposed' : 'Active' }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </table>

                        {{-- Meta row --}}
                        <table style="width:100%;border-collapse:collapse;font-size:7pt;margin-bottom:4pt;">
                            <tr>
                                <td style="vertical-align:top;width:50%;">
                                    <div><span style="font-weight:700;display:inline-block;width:130px;">Acquired</span>{{ $asset->acquisition_date->format('d M Y') }}</div>
                                    <div><span style="font-weight:700;display:inline-block;width:130px;">Useful life</span>{{ $indef ? 'Indefinite (no amortisation)' : ($life ? rtrim(rtrim(number_format((float)$life,2),'0'),'.').' years' : '—') }}</div>
                                    <div><span style="font-weight:700;display:inline-block;width:130px;">Method</span>{{ $indef ? '— (impairment-only)' : (\App\Models\IntangibleAsset::METHODS[$asset->amortisation_method ?? $asset->intangibleClass?->amortisation_method] ?? 'Straight-line') }}</div>
                                    @if ($asset->notes)
                                        <div style="margin-top:3pt;color:#7a90a5;font-style:italic;">{{ $asset->notes }}</div>
                                    @endif
                                </td>
                                <td style="vertical-align:top;width:50%;text-align:right;">
                                    @if ($asset->isDisposed())
                                        <div>Disposed: <strong>{{ $asset->disposal_date?->format('d M Y') }}</strong></div>
                                        <div>Proceeds: <strong>R {{ number_format((float)($asset->disposal_proceeds??0),2) }}</strong></div>
                                    @endif
                                    <div>Residual value: <strong>R {{ number_format((float)($asset->residual_value??0),2) }}</strong></div>
                                    <div>Original cost: <strong>R {{ number_format((float)$asset->cost,2) }}</strong></div>
                                </td>
                            </tr>
                        </table>

                        <hr class="divider">

                        {{-- Summary --}}
                        <table class="summary-table">
                            <tr>
                                <td>
                                    <span class="lbl">Cost</span>
                                    <span class="amt">R {{ number_format((float)$asset->cost, 2) }}</span>
                                </td>
                                @unless ($indef)
                                <td>
                                    <span class="lbl">Acc. Amortisation</span>
                                    <span class="amt">R {{ number_format($accAmort, 2) }}</span>
                                </td>
                                @endunless
                                @if ($accImp != 0)
                                <td>
                                    <span class="lbl">Acc. Impairment</span>
                                    <span class="amt">R {{ number_format($accImp, 2) }}</span>
                                </td>
                                @endif
                                @if ($revSur != 0)
                                <td>
                                    <span class="lbl">Reval. Surplus (OCI)</span>
                                    <span class="amt">R {{ number_format($revSur, 2) }}</span>
                                </td>
                                @endif
                                <td>
                                    <span class="lbl">Net Book Value</span>
                                    <span class="amt">R {{ number_format($nbv, 2) }}</span>
                                </td>
                            </tr>
                        </table>

                        {{-- IAS 38 Movement History --}}
                        <div class="info-section">
                            <div class="section-header">IAS 38 Movement History</div>
                            <div id="history-loading" style="color:#7a90a5;font-size:7pt;font-style:italic;padding:6pt 0;">Loading history…</div>
                            <div id="history-empty"   style="display:none;color:#7a90a5;font-size:7pt;font-style:italic;padding:6pt 0;">No events recorded yet for this intangible.</div>
                            <table class="cust-items-table" id="history-table" style="display:none;">
                                <thead>
                                    <tr>
                                        <td style="width:18%;">Event</td>
                                        <td style="width:13%;">Date</td>
                                        <td style="width:37%;">Description</td>
                                        <td style="width:18%;">Journal</td>
                                        <td class="amt" style="width:14%;">Amount</td>
                                    </tr>
                                </thead>
                                <tbody id="history-tbody"></tbody>
                            </table>
                        </div>

                        {{-- IAS 38 Actions --}}
                        @unless ($asset->isDisposed())
                        <div class="action-section">
                            <div class="section-header">Record a Movement</div>
                            <p style="font-size:6.5pt;color:#7a90a5;margin:3pt 0 6pt;">Select an action above or click a heading below. AI posts the journal automatically after you save.</p>

                            {{-- Capitalise --}}
                            <div class="action-panel" id="panel-capitalise">
                                <div class="action-panel-head" onclick="togglePanel('capitalise')">
                                    <span class="action-panel-title">Capitalise subsequent cost <small style="font-weight:400;color:#7a90a5;">(IAS 38.18 — extends future economic benefits)</small></span>
                                    <span class="action-panel-chevron">▼</span>
                                </div>
                                <div class="action-panel-body">
                                    <p class="af-hint">IAS 38 generally requires subsequent costs to be expensed (IAS 38.20). Use this only for a major upgrade that extends the asset's useful life or capabilities (e.g. major software version upgrade).</p>
                                    <form method="POST" action="{{ route('companies.intangibles.capitalise', [$company, $asset]) }}">
                                        @csrf
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>Amount (R)</label>
                                                <input type="number" name="amount" step="0.01" min="0.01" required placeholder="0.00">
                                            </div>
                                            <div class="af-field">
                                                <label>Date</label>
                                                <input type="date" name="date" required value="{{ now()->format('Y-m-d') }}">
                                            </div>
                                            <div class="af-field wide">
                                                <label>Description</label>
                                                <input type="text" name="description" maxlength="255" placeholder="e.g. SAP major version upgrade">
                                            </div>
                                        </div>
                                        <div style="display:flex;justify-content:flex-end;">
                                            <button type="submit" class="mgmt-btn primary">Save</button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            @if ($policy === 'revaluation' && ! $goodwill)
                            {{-- Revalue --}}
                            <div class="action-panel" id="panel-revalue">
                                <div class="action-panel-head" onclick="togglePanel('revalue')">
                                    <span class="action-panel-title">Revalue intangible <small style="font-weight:400;color:#7a90a5;">(IAS 38.75 — active market required)</small></span>
                                    <span class="action-panel-chevron">▼</span>
                                </div>
                                <div class="action-panel-body">
                                    <p class="af-hint">IAS 38.75 only permits revaluation where fair value is determinable by reference to an <em>active market</em>. Most intangibles do not qualify.</p>
                                    <form method="POST" action="{{ route('companies.intangibles.revalue', [$company, $asset]) }}">
                                        @csrf
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>Current carrying amount</label>
                                                <input type="text" disabled value="R {{ number_format($nbv, 2) }}">
                                            </div>
                                            <div class="af-field">
                                                <label>New carrying amount (R)</label>
                                                <input type="number" name="new_carrying_amount" step="0.01" min="0.01" required placeholder="0.00">
                                            </div>
                                            <div class="af-field">
                                                <label>Effective date</label>
                                                <input type="date" name="date" required value="{{ now()->format('Y-m-d') }}">
                                            </div>
                                        </div>
                                        <div style="display:flex;justify-content:flex-end;">
                                            <button type="submit" class="mgmt-btn primary">Save</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            @endif

                            {{-- Impair --}}
                            <div class="action-panel" id="panel-impair">
                                <div class="action-panel-head" onclick="togglePanel('impair')">
                                    <span class="action-panel-title">Record impairment <small style="font-weight:400;color:#7a90a5;">(IAS 36{{ $indef ? ' — required annually' : '' }})</small></span>
                                    <span class="action-panel-chevron">▼</span>
                                </div>
                                <div class="action-panel-body">
                                    <p class="af-hint">Write down to recoverable amount (higher of FVLCTS and VIU). @if ($indef) Indefinite-life intangibles and goodwill must be tested annually (IAS 36.10). @endif For revaluation-model intangibles the OCI surplus is exhausted first.</p>
                                    <form method="POST" action="{{ route('companies.intangibles.impair', [$company, $asset]) }}">
                                        @csrf
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>Current carrying amount</label>
                                                <input type="text" disabled value="R {{ number_format($nbv, 2) }}">
                                            </div>
                                            <div class="af-field">
                                                <label>Impairment amount (R)</label>
                                                <input type="number" name="impairment_amount" step="0.01" min="0.01" max="{{ $nbv }}" required placeholder="0.00">
                                            </div>
                                            <div class="af-field">
                                                <label>Date</label>
                                                <input type="date" name="date" required value="{{ now()->format('Y-m-d') }}">
                                            </div>
                                            <div class="af-field wide">
                                                <label>Reason (optional)</label>
                                                <input type="text" name="reason" maxlength="500" placeholder="e.g. technological obsolescence, market exit">
                                            </div>
                                        </div>
                                        <div style="display:flex;justify-content:flex-end;">
                                            <button type="submit" class="mgmt-btn primary">Save</button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            @if ($accImp > 0 && ! $goodwill)
                            {{-- Reverse impairment --}}
                            <div class="action-panel" id="panel-reverse">
                                <div class="action-panel-head" onclick="togglePanel('reverse')">
                                    <span class="action-panel-title">Reverse impairment <small style="font-weight:400;color:#7a90a5;">(IAS 36.114)</small></span>
                                    <span class="action-panel-chevron">▼</span>
                                </div>
                                <div class="action-panel-body">
                                    <p class="af-hint">Capped at accumulated impairment. IAS 36.124 prohibits reversing impairments of goodwill — this action is hidden for goodwill items.</p>
                                    <form method="POST" action="{{ route('companies.intangibles.reverse-impairment', [$company, $asset]) }}">
                                        @csrf
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>Accumulated impairment</label>
                                                <input type="text" disabled value="R {{ number_format($accImp, 2) }}">
                                            </div>
                                            <div class="af-field">
                                                <label>Reversal amount (R)</label>
                                                <input type="number" name="reversal_amount" step="0.01" min="0.01" max="{{ $accImp }}" required placeholder="0.00">
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
                            @endif

                            {{-- Dispose --}}
                            <div class="action-panel" id="panel-dispose">
                                <div class="action-panel-head" onclick="togglePanel('dispose')">
                                    <span class="action-panel-title">Dispose intangible <small style="font-weight:400;color:#7a90a5;">(IAS 38.112)</small></span>
                                    <span class="action-panel-chevron">▼</span>
                                </div>
                                <div class="action-panel-body">
                                    <p class="af-hint">Derecognise the intangible. Gain/loss = proceeds − carrying amount, posted to P&L. Use R 0 for retired/abandoned items.</p>
                                    <form method="POST" action="{{ route('companies.intangibles.dispose', [$company, $asset]) }}"
                                        onsubmit="return false" data-confirm-label="Intangible Assets" data-ajax-confirm data-confirm-title="Dispose Intangible" data-confirm-body="Dispose {{ addslashes($asset->name) }}? This will derecognise the intangible and post the disposal journal." data-confirm-text="Dispose" data-confirm-danger="1">
                                        @csrf
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>Carrying amount</label>
                                                <input type="text" disabled value="R {{ number_format($nbv, 2) }}">
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
                                            <button type="submit" class="mgmt-btn danger">Save</button>
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

    {{-- History JS --}}
    <script>
        const historyUrl = @json(route('companies.intangibles.history', [$company, $asset]));

        function escHtml(s) {
            return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }

        const journalStatusStyle = {
            pending: { label: 'Journal pending', color: '#92400e' },
            posted:  { label: 'Journal posted',  color: '#065f46' },
            failed:  { label: 'Journal failed',  color: '#b91c1c' },
        };

        async function retryPosting(btn, url) {
            btn.disabled = true;
            btn.textContent = '…';
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const cell = btn.closest('[data-journal-status-cell]');
                if (cell) {
                    cell.querySelector('[data-journal-status]').textContent = journalStatusStyle.pending.label;
                    cell.querySelector('[data-journal-status]').style.color  = journalStatusStyle.pending.color;
                    btn.remove();
                }
                showToast('Retry queued — posting in progress', '#1d4ed8');
            } catch {
                showToast('Retry failed — please try again', '#b91c1c');
                btn.disabled = false;
                btn.textContent = 'Retry';
            }
        }

        async function loadHistory() {
            const tbody  = document.getElementById('history-tbody');
            const table  = document.getElementById('history-table');
            const empty  = document.getElementById('history-empty');
            const loading= document.getElementById('history-loading');

            try {
                const res  = await fetch(historyUrl, { headers: { Accept: 'application/json' } });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();

                loading.style.display = 'none';

                if (!data.events || data.events.length === 0) {
                    empty.style.display = 'block';
                    return;
                }

                tbody.innerHTML = '';
                const fmt = v => Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                data.events.forEach(ev => {
                    const js = journalStatusStyle[ev.journal_status] || journalStatusStyle.pending;
                    const retryBtn = ev.retry_url
                        ? `<button onclick="retryPosting(this,'${escHtml(ev.retry_url)}')" style="margin-left:4pt;font-size:5pt;font-weight:700;padding:0.15rem 4pt;border-radius:4px;border:1px solid #b91c1c;color:#b91c1c;background:transparent;cursor:pointer;opacity:0;transition:opacity 0.15s;" class="retry-posting-btn">Retry</button>`
                        : '';
                    const tr = document.createElement('tr');
                    tr.dataset.eventId = ev.id;
                    tr.innerHTML = `
                        <td><span class="ev-chip" style="background:${escHtml(ev.bg)};color:${escHtml(ev.color)};">${escHtml(ev.label)}</span></td>
                        <td style="font-family:'DejaVu Sans Mono',monospace;font-size:6.5pt;">${escHtml(ev.date || '—')}</td>
                        <td>${escHtml(ev.description || '')}</td>
                        <td data-journal-status-cell style="white-space:nowrap;">
                            ${ev.transaction_url
                                ? `<a href="${escHtml(ev.transaction_url)}" style="font-size:5pt;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:${js.color};text-decoration:underline;cursor:pointer;" data-journal-status>${js.label}</a>`
                                : `<span data-journal-status style="font-size:5pt;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:${js.color};">${js.label}</span>`
                            }${retryBtn}
                        </td>
                        <td class="amt">${ev.amount ? 'R ' + fmt(ev.amount) : '—'}</td>
                    `;
                    if (ev.retry_url) {
                        tr.addEventListener('mouseenter', () => tr.querySelector('.retry-posting-btn')?.style.setProperty('opacity','1'));
                        tr.addEventListener('mouseleave', () => tr.querySelector('.retry-posting-btn')?.style.setProperty('opacity','0'));
                    }
                    tbody.appendChild(tr);
                });

                table.style.display = 'table';
            } catch (e) {
                loading.style.display = 'none';
                empty.textContent = 'Could not load history: ' + e.message;
                empty.style.display = 'block';
            }
        }

        loadHistory();

        // ── Real-time posting status via Reverb ──────────────────
        window.addEventListener('echo:ready', function () {
            window.Echo.private('company.{{ $company->id }}')
                .listen('.posting.status.updated', (e) => {
                    if (e.entity_type !== 'intangible_event') return;
                    const rows = document.querySelectorAll('#history-tbody tr[data-event-id="' + e.entity_id + '"]');
                    rows.forEach(row => {
                        const cell = row.querySelector('[data-journal-status]');
                        if (!cell) return;
                        const js = journalStatusStyle[e.status] || journalStatusStyle.pending;
                        cell.textContent = js.label;
                        cell.style.color = js.color;
                        if (e.status !== 'failed') {
                            row.querySelector('.retry-posting-btn')?.remove();
                        }
                    });
                    if (e.status === 'posted' || e.status === 'failed') {
                        const toast = document.createElement('div');
                        toast.style.cssText = 'position:fixed;bottom:12pt;right:12pt;padding:6pt 10pt;border-radius:6px;font-size:0.8rem;font-weight:600;color:#fff;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,0.15);transition:opacity 0.4s;background:' + (e.status==='posted'?'#065f46':'#b91c1c');
                        toast.textContent = e.label;
                        document.body.appendChild(toast);
                        setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 400); }, 4000);
                        loadHistory();
                    }
                });
        });

        function togglePanel(id) {
            const el = document.getElementById('panel-' + id);
            if (!el) return;
            const isOpen = el.classList.contains('open');
            document.querySelectorAll('.action-panel.open').forEach(p => p.classList.remove('open'));
            if (!isOpen) {
                el.classList.add('open');
                el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }

        function showToast(msg, color) {
            const t = document.createElement('div');
            t.style.cssText = 'position:fixed;bottom:12pt;right:12pt;padding:6pt 10pt;border-radius:6px;font-size:0.8rem;font-weight:600;color:#fff;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,0.15);transition:opacity 0.4s;background:' + color;
            t.textContent = msg;
            document.body.appendChild(t);
            setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 400); }, 4000);
        }

        async function submitActionForm(form) {
            const btn = form.querySelector('[type=submit]');
            if (btn) { btn.disabled = true; btn.textContent = 'Saving…'; }
            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    redirect: 'follow',
                });
                if (res.ok || res.redirected) {
                    document.querySelectorAll('.action-panel.open').forEach(p => p.classList.remove('open'));
                    showToast('Saved — journal posting in progress', '#1d4ed8');
                    form.reset();
                } else {
                    showToast('Error saving action', '#b91c1c');
                }
            } catch {
                showToast('Network error', '#b91c1c');
            } finally {
                if (btn) { btn.disabled = false; btn.textContent = 'Save'; }
            }
        }

        document.querySelectorAll('.action-panel form').forEach(form => {
            form.addEventListener('submit', async function (e) {
                e.preventDefault();
                if (form.dataset.confirmTitle) {
                    window.showConfirmModal({
                        label:       form.dataset.confirmLabel ?? '',
                        title:       form.dataset.confirmTitle,
                        body:        form.dataset.confirmBody ?? '',
                        confirmText: form.dataset.confirmText ?? 'Confirm',
                        danger:      !!form.dataset.confirmDanger,
                        onConfirm:   () => submitActionForm(form),
                    });
                } else {
                    await submitActionForm(form);
                }
            });
        });
    </script>

    {{-- Edit intangible modal --}}
    <div id="edit-modal"
        style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:9999;align-items:center;justify-content:center;padding:16pt 8pt;overflow-y:auto;">
        <div style="background:#fff;width:100%;max-width:640px;border:1px solid #16355c;border-radius:0;box-shadow:0 20px 60px rgba(0,0,0,0.4);overflow:hidden;margin:0 auto;">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:9pt 12pt;border-bottom:1.5px solid #16355c;">
                <h3 style="font-size:1.05rem;font-weight:800;letter-spacing:0.04em;margin:0;text-transform:uppercase;color:#16355c;">Edit Intangible</h3>
                <button onclick="closeEditModal()"
                    style="background:none;border:none;font-size:1.4rem;line-height:1;color:#16355c;cursor:pointer;">&times;</button>
            </div>

            <form method="POST" action="{{ route('companies.intangibles.update', [$company, $asset]) }}">
                @csrf
                @method('PATCH')
                <div style="padding:10pt 12pt;">

                @if ($errors->any())
                    <div style="background:#fee2e2;border:1px solid #fca5a5;color:#b91c1c;padding:5pt 7pt;font-size:6.5pt;margin-bottom:8pt;border-radius:0;">
                        <strong>Please fix the following:</strong>
                        <ul style="margin:3pt 0 0 8pt;padding:0;">
                            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:4pt 7pt;margin-bottom:8pt;">
                    <div class="as-field" style="grid-column:span 2;">
                        <label>Intangible name</label>
                        <input type="text" name="name" value="{{ old('name', $asset->name) }}" required>
                    </div>
                    <div class="as-field">
                        <label>Reference</label>
                        <input type="text" name="reference" value="{{ old('reference', $asset->reference) }}">
                    </div>
                    <div class="as-field">
                        <label>Intangible class</label>
                        <select name="intangible_class_id">
                            <option value="">— Unclassified —</option>
                            @foreach ($intangibleClasses as $cls)
                                <option value="{{ $cls->id }}" @selected(old('intangible_class_id', $asset->intangible_class_id) == $cls->id)>{{ $cls->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="as-field">
                        <label>Category</label>
                        <input type="text" name="category" value="{{ old('category', $asset->category) }}">
                    </div>
                    <div class="as-field">
                        <label>Acquisition date</label>
                        <input type="date" name="acquisition_date" value="{{ old('acquisition_date', $asset->acquisition_date->format('Y-m-d')) }}" required>
                    </div>
                    <div class="as-field">
                        <label>Cost (R)</label>
                        <input type="number" name="cost" value="{{ old('cost', (float)$asset->cost) }}" min="0" step="0.01" required>
                    </div>
                    <div class="as-field">
                        <label>Residual value (R)</label>
                        <input type="number" name="residual_value" value="{{ old('residual_value', (float)($asset->residual_value ?? 0)) }}" min="0" step="0.01">
                    </div>
                    <div class="as-field">
                        <label>Useful life (years)</label>
                        <input type="number" name="useful_life_years" value="{{ old('useful_life_years', $asset->useful_life_years) }}" min="0" max="999.99" step="0.01" placeholder="from class">
                    </div>
                    <div class="as-field" style="display:flex;align-items:flex-end;">
                        <label style="display:inline-flex;align-items:center;gap:4pt;font-size:0.74rem;color:#16355c;">
                            <input type="checkbox" name="useful_life_indefinite" value="1" @checked(old('useful_life_indefinite', $asset->useful_life_indefinite))>
                            Indefinite life (IAS 38.107)
                        </label>
                    </div>
                    <div class="as-field">
                        <label>Amortisation method</label>
                        <select name="amortisation_method">
                            @foreach (\App\Models\IntangibleAsset::METHODS as $key => $label)
                                <option value="{{ $key }}" @selected(old('amortisation_method', $asset->amortisation_method) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="as-field" style="grid-column:1/-1;">
                        <label>Notes</label>
                        <input type="text" name="notes" value="{{ old('notes', $asset->notes) }}" placeholder="Any additional detail">
                    </div>
                </div>
                <div style="display:flex;gap:5pt;justify-content:flex-end;padding-top:4pt;border-top:1px solid #c9dff0;">
                    <button type="button" onclick="closeEditModal()"
                        style="padding:4pt 9pt;border:1px solid #c9dff0;font-size:6.5pt;color:#16355c;background:#fff;cursor:pointer;font-weight:600;border-radius:0;">Cancel</button>
                    <button type="submit"
                        style="padding:4pt 10pt;background:#0079c8;color:#fff;border:1px solid #0079c8;font-size:6.5pt;font-weight:700;cursor:pointer;border-radius:0;">Save changes</button>
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
        @if ($errors->any()) openEditModal(); @endif
    </script>
@endsection
