@extends('layouts.public')

@section('title', $inventoryItem->name . ' — Inventory')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .inv-mgmt-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8pt;
            flex-wrap: wrap;
            margin-bottom: 12pt;
        }

        .inv-mgmt-bar a {
            font-size: 7pt;
            color: #6f869b;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 3pt;
            transition: color 0.15s;
        }

        .inv-mgmt-bar a:hover { color: #1a345b; }

        .mgmt-btn {
            display: inline-flex;
            align-items: center;
            gap: 3pt;
            background: #fff;
            border: 1px solid #9ec1f5;
            color: #1a345b;
            font-size: 6.5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 0.4rem 7pt;
            text-decoration: none;
            cursor: pointer;
            font-family: inherit;
            transition: background 0.15s, color 0.15s;
        }

        .mgmt-btn:hover { background: #f4fafc; color: #1a345b; }
        .mgmt-btn.primary { background: #005bf0; color: #fff; }
        .mgmt-btn.primary:hover { background: #005f9e; }

        .cust-doc {
            background: #fff;
            border: 1px solid #9ec1f5;
            font-family: "Century Gothic", "URW Gothic", "Avant Garde", Futura, "Avenir Next", Avenir, "Trebuchet MS", Helvetica, Arial, "DejaVu Sans", sans-serif;
            color: #1a345b;
            font-size: 7pt;
            line-height: 1.45;
            margin-bottom: 12pt;
        }

        .cust-doc-body { padding: 16pt 18pt; }

        .cust-header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6pt;
        }

        .doc-title {
            font-size: 11pt;
            font-weight: 800;
            text-align: right;
            margin-bottom: 2pt;
            letter-spacing: 0.04em;
        }

        .doc-meta-line { text-align: right; font-size: 7pt; }

        .status-box {
            display: inline-block;
            font-weight: 700;
            text-transform: uppercase;
            border: 1px solid #1a345b;
            padding: 0.08rem 4pt;
            font-size: 5pt;
            letter-spacing: 0.08em;
            margin-top: 3pt;
        }

        .status-box.inactive { color: #dc2626; border-color: #dc2626; }

        .divider {
            border: none;
            border-top: 1.5pt solid #1a345b;
            margin: 8pt 0 10pt;
        }

        .divider.light {
            border-top: 1px solid #9ec1f5;
            margin: 10pt 0;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2pt;
        }

        .summary-table td {
            padding: 0 10pt 0 0;
            font-size: 7pt;
            vertical-align: top;
        }

        .summary-table .lbl {
            display: block;
            font-weight: 700;
            font-size: 5pt;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 1.5pt;
        }

        .summary-table .amt {
            font-size: 8pt;
            font-weight: 800;
        }

        .section-header {
            font-weight: 700;
            font-size: 7.5pt;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            border-bottom: 1.5px solid #1a345b;
            padding-bottom: 2pt;
            margin-bottom: 4pt;
        }

        .info-section { margin-top: 12pt; }

        table.cust-items-table {
            width: 100%;
            border-collapse: collapse;
        }

        table.cust-items-table thead td {
            font-weight: 700;
            font-size: 6.5pt;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: 1.5px solid #1a345b;
            padding-bottom: 4pt;
        }

        table.cust-items-table thead td.amt { text-align: right; }

        table.cust-items-table tbody td {
            padding: 5pt 0;
            font-size: 7pt;
            border-bottom: 1px solid #d3e2f5;
            vertical-align: middle;
        }

        table.cust-items-table tbody td.amt {
            text-align: right;
            font-family: "DejaVu Sans Mono", monospace;
            white-space: nowrap;
        }

        table.cust-items-table tbody tr:last-child td { border-bottom: none; }
        table.cust-items-table tbody tr:hover td { background: #f4fafc; }

        .ev-chip {
            display: inline-block;
            font-size: 5pt;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            padding: 0.1rem 4pt;
            border-radius: 0;
            white-space: nowrap;
        }

        .ev-chip.receive            { background: #dcfce7; color: #166534; }
        .ev-chip.issue              { background: #fee2e2; color: #991b1b; }
        .ev-chip.adjust             { background: #fef9c3; color: #854d0e; }
        .ev-chip.transfer           { background: #dbeafe; color: #1e40af; }
        .ev-chip.write_down         { background: #fce7f3; color: #9d174d; }
        .ev-chip.reverse_write_down { background: #d1fae5; color: #065f46; }

        .action-section { margin-top: 12pt; }

        .action-panel { border-bottom: 1px solid #d3e2f5; }

        .action-panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 5pt 0;
            cursor: pointer;
            user-select: none;
        }

        .action-panel-title {
            font-size: 7pt;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 4pt;
        }

        .action-panel-body {
            display: none;
            padding-bottom: 8pt;
        }

        .action-panel.open .action-panel-body { display: block; }
        .action-panel-chevron { font-size: 6pt; color: #6f869b; transition: transform 0.15s; }
        .action-panel.open .action-panel-chevron { transform: rotate(180deg); }

        .af-row { display: flex; flex-wrap: wrap; gap: 4pt 6pt; align-items: flex-end; margin-bottom: 5pt; }
        .af-field { flex: 1; min-width: 100pt; }
        .af-field.wide { flex: 2; min-width: 150pt; }
        .af-field label { display: block; font-size: 5pt; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase; color: #6f869b; margin-bottom: 0.2rem; }
        .af-field input, .af-field select, .af-field textarea { width: 100%; border: 1px solid #9ec1f5; padding: 0.32rem 4pt; font-size: 7pt; font-family: inherit; color: #1a345b; box-sizing: border-box; background: #fff; }
        .af-field input:focus, .af-field select:focus, .af-field textarea:focus { outline: none; border-color: #005bf0; }
        .af-hint { font-size: 6pt; color: #6f869b; margin-bottom: 4pt; line-height: 1.4; }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 6pt 12pt;
        }

        .detail-grid .d-label {
            font-size: 5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #6f869b;
            margin-bottom: 1.5pt;
        }

        .detail-grid .d-value {
            font-size: 7pt;
            font-weight: 600;
            color: #1a345b;
        }

        @media (max-width: 640px) {
            .cust-doc-body { padding: 10pt 8pt; }
            .cust-header-table, .cust-header-table tr, .cust-header-table td { display:block; width:100%!important; text-align:left!important; }
            .doc-title, .doc-meta-line { text-align:left!important; }
            .summary-table, .summary-table tr, .summary-table td { display:block; width:100%!important; padding:0 0 6pt; }
            .detail-grid { grid-template-columns: 1fr 1fr; }
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
                    $typeLabels = [
                        'raw_material'   => 'Raw Materials',
                        'wip'            => 'Work in Progress',
                        'finished_goods' => 'Finished Goods',
                        'merchandise'    => 'Merchandise',
                        'consumable'     => 'Consumables',
                    ];
                    $landed      = (float) $inventoryItem->landedCost();
                    $stockVal    = $inventoryItem->stockValue();
                    $qtyAvail    = (float) $inventoryItem->quantityAvailable();
                    $accWriteDown = (float) $inventoryItem->accumulated_write_down;
                    $carryingAmt  = $inventoryItem->carryingAmount();
                    $nrvPerUnit   = $inventoryItem->nrv_per_unit;
                @endphp

                {{-- Management bar --}}
                <div class="inv-mgmt-bar">
<div style="display:flex;align-items:center;gap:4pt;flex-wrap:wrap;">
                        <button type="button" class="mgmt-btn" onclick="togglePanel('receive')">+ Receive</button>
                        <button type="button" class="mgmt-btn" onclick="togglePanel('issue')">Issue</button>
                        <button type="button" class="mgmt-btn" onclick="togglePanel('adjust')">Adjust</button>
                        <button type="button" class="mgmt-btn" onclick="togglePanel('transfer')">Transfer</button>
                    </div>
                </div>

                {{-- ── Item Header ─────────────────────────────────── --}}
                <div class="cust-doc">
                    <div class="cust-doc-body">
                        <table class="cust-header-table">
                            <tr>
                                <td style="width:50%;vertical-align:top;">
                                    <div style="font-size:6pt;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:#6f869b;margin-bottom:3pt;">
                                        {{ $company->registered_name }}
                                    </div>
                                    @if ($inventoryItem->sku)
                                        <div style="font-size:6.5pt;color:#6f869b;">SKU: {{ $inventoryItem->sku }}</div>
                                    @endif
                                    @if ($inventoryItem->inventory_type)
                                        <div style="margin-top:3pt;">
                                            <span style="font-size:6pt;font-weight:700;color:#005bf0;background:#f4fafc;padding:0.1rem 0.4rem;border:1px solid #9ec1f5;">
                                                {{ $typeLabels[$inventoryItem->inventory_type->value] ?? $inventoryItem->inventory_type->value }}
                                            </span>
                                        </div>
                                    @endif
                                </td>
                                <td style="width:50%;vertical-align:top;text-align:right;">
                                    <div class="doc-title">{{ $inventoryItem->name }}</div>
                                    <div class="doc-meta-line">{{ $inventoryItem->is_service ? 'Service Item' : 'Inventory Item' }}</div>
                                    <span class="status-box {{ !$inventoryItem->is_active ? 'inactive' : '' }}">
                                        {{ $inventoryItem->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                            </tr>
                        </table>

                        <hr class="divider">

                        {{-- Summary figures --}}
                        @if ($inventoryItem->is_service)
                            <table class="summary-table">
                                <tr>
                                    <td>
                                        <span class="lbl">Selling Price (R)</span>
                                        <span class="amt">{{ number_format((float)$inventoryItem->unit_price, 2) }}</span>
                                    </td>
                                    <td>
                                        <span class="lbl">VAT Rate</span>
                                        <span class="amt">{{ $inventoryItem->tax_rate !== null ? number_format((float)$inventoryItem->tax_rate, 0) . '%' : '—' }}</span>
                                    </td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            </table>
                        @else
                        <table class="summary-table">
                            <tr>
                                <td>
                                    <span class="lbl">Qty on Hand</span>
                                    <span class="amt">{{ number_format((float)$inventoryItem->quantity_on_hand, 2) }}</span>
                                </td>
                                <td>
                                    <span class="lbl">Qty Available</span>
                                    <span class="amt">{{ number_format($qtyAvail, 2) }}</span>
                                </td>
                                <td>
                                    <span class="lbl">Landed Cost (R)</span>
                                    <span class="amt">{{ number_format($landed, 2) }}</span>
                                </td>
                                <td>
                                    <span class="lbl">Stock Value (R)</span>
                                    <span class="amt">{{ number_format($stockVal, 2) }}</span>
                                </td>
                                <td>
                                    <span class="lbl">Carrying Amount (R)</span>
                                    <span class="amt" style="{{ $accWriteDown > 0 ? 'color:#9d174d;' : '' }}">{{ number_format($carryingAmt, 2) }}</span>
                                </td>
                            </tr>
                        </table>

                        @if ($nrvPerUnit !== null || $accWriteDown > 0)
                            <table class="summary-table" style="margin-top:6pt;">
                                <tr>
                                    <td>
                                        <span class="lbl">NRV per Unit (R)</span>
                                        <span class="amt" style="color:#9d174d;">{{ $nrvPerUnit !== null ? number_format((float)$nrvPerUnit, 2) : '—' }}</span>
                                    </td>
                                    <td>
                                        <span class="lbl">Acc. Write-Down (R)</span>
                                        <span class="amt" style="color:#9d174d;">{{ number_format($accWriteDown, 2) }}</span>
                                    </td>
                                    <td>
                                        <span class="lbl">Write-Down per Unit (R)</span>
                                        <span class="amt" style="color:#9d174d;">
                                            {{ (float)$inventoryItem->quantity_on_hand > 0 ? number_format($accWriteDown / (float)$inventoryItem->quantity_on_hand, 2) : '—' }}
                                        </span>
                                    </td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            </table>
                        @endif

                        @endif {{-- end !is_service summary --}}

                        <hr class="divider light">

                        @if (!$inventoryItem->is_service)
                        {{-- Detail grid --}}
                        <div class="info-section">
                            <div class="section-header">IAS 2 Cost Breakdown</div>
                            <div class="detail-grid" style="margin-top:6pt;">
                                <div>
                                    <div class="d-label">Purchase Cost</div>
                                    <div class="d-value">R {{ number_format((float)($inventoryItem->purchase_cost ?? 0), 2) }}</div>
                                </div>
                                <div>
                                    <div class="d-label">Freight In</div>
                                    <div class="d-value">R {{ number_format((float)($inventoryItem->freight_in ?? 0), 2) }}</div>
                                </div>
                                <div>
                                    <div class="d-label">Import Duties</div>
                                    <div class="d-value">R {{ number_format((float)($inventoryItem->import_duties ?? 0), 2) }}</div>
                                </div>
                                <div>
                                    <div class="d-label">Handling Costs</div>
                                    <div class="d-value">R {{ number_format((float)($inventoryItem->handling_costs ?? 0), 2) }}</div>
                                </div>
                                <div>
                                    <div class="d-label">Trade Discount</div>
                                    <div class="d-value" style="color:#dc2626;">(R {{ number_format((float)($inventoryItem->trade_discount ?? 0), 2) }})</div>
                                </div>
                                <div>
                                    <div class="d-label">Landed Cost per Unit</div>
                                    <div class="d-value" style="font-weight:800;">R {{ number_format($landed, 2) }}</div>
                                </div>
                            </div>
                        </div>

                        @endif {{-- end !is_service cost breakdown --}}

                        @if ($inventoryItem->description)
                            <hr class="divider light">
                            <div class="info-section">
                                <div class="section-header">Description</div>
                                <p style="margin:4pt 0 0;color:#1a345b;">{{ $inventoryItem->description }}</p>
                            </div>
                        @endif

                        @if (!$inventoryItem->is_service)
                        <hr class="divider light">

                        <div class="info-section">
                            <div class="section-header">Other Details</div>
                            <div class="detail-grid" style="margin-top:6pt;">
                                <div>
                                    <div class="d-label">VAT Rate</div>
                                    <div class="d-value">{{ $inventoryItem->tax_rate !== null ? number_format((float)$inventoryItem->tax_rate, 2) . '%' : '—' }}</div>
                                </div>
                                <div>
                                    <div class="d-label">Initial Quantity</div>
                                    <div class="d-value">{{ number_format((float)($inventoryItem->initial_quantity ?? 0), 2) }}</div>
                                </div>
                                <div>
                                    <div class="d-label">Qty Reserved</div>
                                    <div class="d-value">{{ number_format((float)($inventoryItem->quantity_reserved ?? 0), 2) }}</div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                @if (!$inventoryItem->is_service)
                {{-- ── IAS 2 Movement Schedule ──────────────────────── --}}
                @php
                    $initialQty  = (float)($inventoryItem->initial_quantity ?? 0);
                    $unitCost    = (float)($inventoryItem->purchase_cost ?: $inventoryItem->unit_price);

                    $received    = $movements->where('action', \App\Enums\StockMovementAction::Receive);
                    $issued      = $movements->where('action', \App\Enums\StockMovementAction::Issue);
                    $adjusted    = $movements->where('action', \App\Enums\StockMovementAction::Adjust);
                    $transferred = $movements->where('action', \App\Enums\StockMovementAction::Transfer);
                    $writeDowns  = $movements->where('action', \App\Enums\StockMovementAction::WriteDown);
                    $reversals   = $movements->where('action', \App\Enums\StockMovementAction::ReverseWriteDown);

                    $qtyReceived  = $received->sum(fn($m) => (float)$m->quantity);
                    $qtyIssued    = $issued->sum(fn($m) => abs((float)$m->quantity));
                    $qtyAdjusted  = $adjusted->sum(fn($m) => (float)$m->quantity);
                    $qtyTransfers = $transferred->count();

                    $costReceived = $received->sum(fn($m) => abs((float)$m->quantity) * (float)($m->unit_cost ?? $unitCost));
                    $costIssued   = $issued->sum(fn($m) => abs((float)$m->quantity) * (float)($m->unit_cost ?? $unitCost));
                    $costAdjusted = $adjusted->sum(fn($m) => (float)$m->quantity * (float)($m->unit_cost ?? $unitCost));

                    $totalWriteDown = $writeDowns->sum(fn($m) => (float)$m->unit_cost);
                    $totalReversal  = $reversals->sum(fn($m) => (float)$m->unit_cost);

                    $openingVal   = $initialQty * $unitCost;
                    $closingQty   = $initialQty + $qtyReceived - $qtyIssued + $qtyAdjusted;
                    $closingVal   = $openingVal + $costReceived - $costIssued + $costAdjusted;
                    $carryingVal  = $closingVal - $totalWriteDown + $totalReversal;
                @endphp

                <div class="cust-doc">
                    <div class="cust-doc-body">
                        <div class="doc-title" style="text-align:left;">IAS 2 Movement Schedule</div>
                        <p style="font-size:7pt;color:#6f869b;margin:0.2rem 0 6pt;">Inventory reconciliation per IAS 2.36(d) — quantities and cost of inventories recognised as an expense.</p>

                        <hr class="divider">

                        <table class="cust-items-table" style="table-layout:fixed;">
                            <colgroup>
                                <col style="width:60%;">
                                <col style="width:20%;">
                                <col style="width:20%;">
                            </colgroup>
                            <thead>
                                <tr>
                                    <td>Movement</td>
                                    <td class="amt">Units</td>
                                    <td class="amt">Cost (R)</td>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style="font-weight:700;">Opening stock (initial quantity)</td>
                                    <td class="amt">{{ number_format($initialQty, 2) }}</td>
                                    <td class="amt">{{ number_format($openingVal, 2) }}</td>
                                </tr>
                                <tr>
                                    <td style="padding-left:10pt;">
                                        <span class="ev-chip receive" style="margin-right:3pt;vertical-align:middle;">Receive</span>
                                        Purchases / goods received
                                    </td>
                                    <td class="amt" style="color:#166534;">+{{ number_format($qtyReceived, 2) }}</td>
                                    <td class="amt" style="color:#166534;">{{ number_format($costReceived, 2) }}</td>
                                </tr>
                                <tr>
                                    <td style="padding-left:10pt;">
                                        <span class="ev-chip issue" style="margin-right:3pt;vertical-align:middle;">Issue</span>
                                        Cost of sales / goods issued
                                    </td>
                                    <td class="amt" style="color:#dc2626;">({{ number_format($qtyIssued, 2) }})</td>
                                    <td class="amt" style="color:#dc2626;">({{ number_format($costIssued, 2) }})</td>
                                </tr>
                                @if ($qtyAdjusted != 0)
                                <tr>
                                    <td style="padding-left:10pt;">
                                        <span class="ev-chip adjust" style="margin-right:3pt;vertical-align:middle;">Adjust</span>
                                        Stock count adjustments
                                    </td>
                                    <td class="amt" style="{{ $qtyAdjusted < 0 ? 'color:#dc2626;' : 'color:#166534;' }}">
                                        {{ $qtyAdjusted >= 0 ? '+' : '' }}{{ number_format($qtyAdjusted, 2) }}
                                    </td>
                                    <td class="amt" style="{{ $costAdjusted < 0 ? 'color:#dc2626;' : 'color:#166534;' }}">
                                        {{ $costAdjusted >= 0 ? '' : '(' }}{{ number_format(abs($costAdjusted), 2) }}{{ $costAdjusted >= 0 ? '' : ')' }}
                                    </td>
                                </tr>
                                @endif
                                @if ($qtyTransfers > 0)
                                <tr>
                                    <td style="padding-left:10pt;">
                                        <span class="ev-chip transfer" style="margin-right:3pt;vertical-align:middle;">Transfer</span>
                                        Location transfers
                                    </td>
                                    <td class="amt" style="color:#6f869b;">{{ $qtyTransfers }} transfer{{ $qtyTransfers !== 1 ? 's' : '' }}</td>
                                    <td class="amt" style="color:#6f869b;">—</td>
                                </tr>
                                @endif
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td style="font-weight:800;">Closing stock (at cost)</td>
                                    <td class="amt" style="font-weight:800;">{{ number_format($closingQty, 2) }}</td>
                                    <td class="amt" style="font-weight:800;">{{ number_format($closingVal, 2) }}</td>
                                </tr>
                                @if ($totalWriteDown > 0 || $totalReversal > 0)
                                <tr>
                                    <td style="padding-left:10pt;">
                                        <span class="ev-chip write_down" style="margin-right:3pt;vertical-align:middle;">Write-Down</span>
                                        NRV write-down (IAS 2.34)
                                    </td>
                                    <td class="amt">—</td>
                                    <td class="amt" style="color:#9d174d;">({{ number_format($totalWriteDown, 2) }})</td>
                                </tr>
                                @endif
                                @if ($totalReversal > 0)
                                <tr>
                                    <td style="padding-left:10pt;">
                                        <span class="ev-chip reverse_write_down" style="margin-right:3pt;vertical-align:middle;">Reversal</span>
                                        Reversal of write-down (IAS 2.33)
                                    </td>
                                    <td class="amt">—</td>
                                    <td class="amt" style="color:#065f46;">{{ number_format($totalReversal, 2) }}</td>
                                </tr>
                                @endif
                                @if ($totalWriteDown > 0 || $totalReversal > 0)
                                <tr style="border-top:1.5pt solid #1a345b;">
                                    <td style="font-weight:800;">Carrying amount (lower of cost and NRV)</td>
                                    <td class="amt" style="font-weight:800;">{{ number_format($closingQty, 2) }}</td>
                                    <td class="amt" style="font-weight:800;color:#9d174d;">{{ number_format($carryingVal, 2) }}</td>
                                </tr>
                                @endif
                            </tfoot>
                        </table>
                    </div>
                </div>

                {{-- ── Stock Movement Actions ──────────────────────── --}}
                <div class="cust-doc">
                    <div class="cust-doc-body">
                        <div class="doc-title" style="text-align:left;">Record Movement</div>

                        <hr class="divider">

                        @if ($errors->any())
                            <div style="background:#fee2e2;border:1px solid #fca5a5;color:#b91c1c;padding:0.6rem 7pt;font-size:6.5pt;margin-bottom:7pt;">
                                <strong>Please fix the following:</strong>
                                <ul style="margin:0.3rem 0 0 8pt;padding:0;">
                                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="action-section">
                            {{-- Receive --}}
                            <div class="action-panel" id="panel-receive">
                                <div class="action-panel-head" onclick="togglePanel('receive')">
                                    <div class="action-panel-title">
                                        <span class="ev-chip receive">Receive</span>
                                        Goods received from supplier
                                    </div>
                                    <span class="action-panel-chevron">&#9660;</span>
                                </div>
                                <div class="action-panel-body">
                                    <form method="POST" action="{{ route('companies.inventory.movements.store', [$company, $inventoryItem]) }}">
                                        @csrf
                                        <input type="hidden" name="action" value="receive">
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>Quantity received</label>
                                                <input type="number" name="quantity" min="0.0001" step="0.0001" required>
                                            </div>
                                            <div class="af-field">
                                                <label>Unit cost (R)</label>
                                                <input type="number" name="unit_cost" min="0" step="0.01" value="{{ (float)($inventoryItem->purchase_cost ?? $inventoryItem->unit_price) }}">
                                            </div>
                                            <div class="af-field">
                                                <label>Date</label>
                                                <input type="date" name="moved_at" value="{{ now()->format('Y-m-d') }}" required>
                                            </div>
                                            <div class="af-field">
                                                <label>Reference</label>
                                                <input type="text" name="reference" placeholder="e.g. PO-001">
                                            </div>
                                        </div>
                                        <div class="af-row">
                                            <div class="af-field wide">
                                                <label>Notes</label>
                                                <input type="text" name="notes" placeholder="Optional notes">
                                            </div>
                                            <div style="display:flex;align-items:flex-end;">
                                                <button type="submit" class="mgmt-btn primary">Record Receive</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            {{-- Issue --}}
                            <div class="action-panel" id="panel-issue">
                                <div class="action-panel-head" onclick="togglePanel('issue')">
                                    <div class="action-panel-title">
                                        <span class="ev-chip issue">Issue</span>
                                        Goods issued / sold
                                    </div>
                                    <span class="action-panel-chevron">&#9660;</span>
                                </div>
                                <div class="action-panel-body">
                                    <form method="POST" action="{{ route('companies.inventory.movements.store', [$company, $inventoryItem]) }}">
                                        @csrf
                                        <input type="hidden" name="action" value="issue">
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>Quantity issued</label>
                                                <input type="number" name="quantity" min="0.0001" step="0.0001" required>
                                            </div>
                                            <div class="af-field">
                                                <label>Unit cost (R)</label>
                                                <input type="number" name="unit_cost" min="0" step="0.01" value="{{ (float)($inventoryItem->purchase_cost ?? $inventoryItem->unit_price) }}">
                                            </div>
                                            <div class="af-field">
                                                <label>Date</label>
                                                <input type="date" name="moved_at" value="{{ now()->format('Y-m-d') }}" required>
                                            </div>
                                            <div class="af-field">
                                                <label>Reference</label>
                                                <input type="text" name="reference" placeholder="e.g. INV-001">
                                            </div>
                                        </div>
                                        <div class="af-row">
                                            <div class="af-field wide">
                                                <label>Notes</label>
                                                <input type="text" name="notes" placeholder="Optional notes">
                                            </div>
                                            <div style="display:flex;align-items:flex-end;">
                                                <button type="submit" class="mgmt-btn primary">Record Issue</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            {{-- Adjust --}}
                            <div class="action-panel" id="panel-adjust">
                                <div class="action-panel-head" onclick="togglePanel('adjust')">
                                    <div class="action-panel-title">
                                        <span class="ev-chip adjust">Adjust</span>
                                        Stock count correction
                                    </div>
                                    <span class="action-panel-chevron">&#9660;</span>
                                </div>
                                <div class="action-panel-body">
                                    <p class="af-hint">Enter a positive number to increase stock or a negative number to decrease. E.g. counted 95 but system says 100 &rarr; enter &minus;5.</p>
                                    <form method="POST" action="{{ route('companies.inventory.movements.store', [$company, $inventoryItem]) }}">
                                        @csrf
                                        <input type="hidden" name="action" value="adjust">
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>Adjustment qty (+/&minus;)</label>
                                                <input type="number" name="quantity" step="0.0001" required>
                                            </div>
                                            <div class="af-field">
                                                <label>Date</label>
                                                <input type="date" name="moved_at" value="{{ now()->format('Y-m-d') }}" required>
                                            </div>
                                            <div class="af-field">
                                                <label>Reference</label>
                                                <input type="text" name="reference" placeholder="e.g. Stock count">
                                            </div>
                                        </div>
                                        <div class="af-row">
                                            <div class="af-field wide">
                                                <label>Notes</label>
                                                <input type="text" name="notes" placeholder="Reason for adjustment">
                                            </div>
                                            <div style="display:flex;align-items:flex-end;">
                                                <button type="submit" class="mgmt-btn primary">Record Adjustment</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            {{-- Transfer --}}
                            <div class="action-panel" id="panel-transfer">
                                <div class="action-panel-head" onclick="togglePanel('transfer')">
                                    <div class="action-panel-title">
                                        <span class="ev-chip transfer">Transfer</span>
                                        Location transfer (audit trail)
                                    </div>
                                    <span class="action-panel-chevron">&#9660;</span>
                                </div>
                                <div class="action-panel-body">
                                    <p class="af-hint">Records a transfer for audit purposes. No net quantity change at item level.</p>
                                    <form method="POST" action="{{ route('companies.inventory.movements.store', [$company, $inventoryItem]) }}">
                                        @csrf
                                        <input type="hidden" name="action" value="transfer">
                                        <input type="hidden" name="quantity" value="0">
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>Date</label>
                                                <input type="date" name="moved_at" value="{{ now()->format('Y-m-d') }}" required>
                                            </div>
                                            <div class="af-field">
                                                <label>Reference</label>
                                                <input type="text" name="reference" placeholder="e.g. Warehouse A → B">
                                            </div>
                                        </div>
                                        <div class="af-row">
                                            <div class="af-field wide">
                                                <label>Notes</label>
                                                <input type="text" name="notes" placeholder="Transfer details">
                                            </div>
                                            <div style="display:flex;align-items:flex-end;">
                                                <button type="submit" class="mgmt-btn primary">Record Transfer</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── NRV Write-Down Actions ─────────────────────── --}}
                <div class="cust-doc">
                    <div class="cust-doc-body">
                        <div class="doc-title" style="text-align:left;">NRV Write-Down (IAS 2.9)</div>
                        <p style="font-size:7pt;color:#6f869b;margin:0.2rem 0 6pt;">
                            Inventories shall be measured at the lower of cost and net realisable value.
                            @if ($inventoryItem->nrv_per_unit !== null)
                                <br>Current NRV: <strong>R{{ number_format((float)$inventoryItem->nrv_per_unit, 2) }}/unit</strong> — Accumulated write-down: <strong>R{{ number_format((float)$inventoryItem->accumulated_write_down, 2) }}</strong>
                            @endif
                        </p>

                        <hr class="divider">

                        <div class="action-section">
                            {{-- Write Down --}}
                            <div class="action-panel" id="panel-writedown">
                                <div class="action-panel-head" onclick="togglePanel('writedown')">
                                    <div class="action-panel-title">
                                        <span class="ev-chip write_down">Write-Down</span>
                                        Write down to net realisable value
                                    </div>
                                    <span class="action-panel-chevron">&#9660;</span>
                                </div>
                                <div class="action-panel-body">
                                    <form method="POST" action="{{ route('companies.inventory.write-down', [$company, $inventoryItem]) }}">
                                        @csrf
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>NRV per unit (R)</label>
                                                <input type="number" name="nrv_per_unit" min="0" step="0.01" required placeholder="Estimated selling price less costs to complete and sell">
                                            </div>
                                            <div class="af-field">
                                                <label>Date</label>
                                                <input type="date" name="date" value="{{ now()->format('Y-m-d') }}" required>
                                            </div>
                                            <div class="af-field">
                                                <label>Cost per unit (R)</label>
                                                <input type="text" value="{{ number_format((float)$inventoryItem->landedCost() ?: ((float)$inventoryItem->purchase_cost ?: (float)$inventoryItem->unit_price), 2) }}" disabled style="background:#f4fafc;">
                                            </div>
                                        </div>
                                        <div class="af-row">
                                            <div class="af-field wide">
                                                <label>Notes</label>
                                                <input type="text" name="notes" placeholder="Reason for write-down (e.g. obsolescence, damage, decline in selling price)">
                                            </div>
                                            <div style="display:flex;align-items:flex-end;">
                                                <button type="submit" class="mgmt-btn primary">Record Write-Down</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            {{-- Reverse Write-Down --}}
                            @if ((float)$inventoryItem->accumulated_write_down > 0)
                            <div class="action-panel" id="panel-reversewd">
                                <div class="action-panel-head" onclick="togglePanel('reversewd')">
                                    <div class="action-panel-title">
                                        <span class="ev-chip reverse_write_down">Reverse</span>
                                        Reverse write-down (NRV recovery — IAS 2.33)
                                    </div>
                                    <span class="action-panel-chevron">&#9660;</span>
                                </div>
                                <div class="action-panel-body">
                                    <form method="POST" action="{{ route('companies.inventory.reverse-write-down', [$company, $inventoryItem]) }}">
                                        @csrf
                                        <div class="af-row">
                                            <div class="af-field">
                                                <label>New NRV per unit (R)</label>
                                                <input type="number" name="new_nrv_per_unit" min="0" step="0.01" required placeholder="Revised NRV (higher than previous)">
                                            </div>
                                            <div class="af-field">
                                                <label>Date</label>
                                                <input type="date" name="date" value="{{ now()->format('Y-m-d') }}" required>
                                            </div>
                                            <div class="af-field">
                                                <label>Current write-down (R)</label>
                                                <input type="text" value="{{ number_format((float)$inventoryItem->accumulated_write_down, 2) }}" disabled style="background:#f4fafc;">
                                            </div>
                                        </div>
                                        <div class="af-row">
                                            <div class="af-field wide">
                                                <label>Notes</label>
                                                <input type="text" name="notes" placeholder="Reason for reversal (e.g. price recovery, market conditions improved)">
                                            </div>
                                            <div style="display:flex;align-items:flex-end;">
                                                <button type="submit" class="mgmt-btn primary">Record Reversal</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- ── Movement History ────────────────────────────── --}}
                <div class="cust-doc">
                    <div class="cust-doc-body">
                        <div class="doc-title" style="text-align:left;">Movement History</div>
                        <p style="font-size:7pt;color:#6f869b;margin:0.2rem 0 6pt;">{{ $movements->count() }} movement{{ $movements->count() !== 1 ? 's' : '' }} recorded.</p>

                        <hr class="divider">

                        @if ($movements->isEmpty())
                            <p style="color:#6f869b;font-style:italic;font-size:7pt;">No stock movements recorded yet.</p>
                        @else
                            <div style="overflow-x:auto;">
                                <table class="cust-items-table">
                                    <thead>
                                        <tr>
                                            <td style="width:11%;">Date</td>
                                            <td style="width:9%;">Action</td>
                                            <td class="amt" style="width:9%;">Qty</td>
                                            <td class="amt" style="width:9%;">Unit Cost</td>
                                            <td style="width:12%;">Reference</td>
                                            <td style="width:12%;">Invoice</td>
                                            <td style="width:22%;">Notes</td>
                                            <td style="width:16%;">Journal</td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $jsColors = ['pending'=>'#92400e','posted'=>'#065f46','failed'=>'#b91c1c'];
                                            $movTxnIds = $movements->pluck('transaction_id')->filter()->unique()->all();
                                            $movTxnMap = $movTxnIds ? \App\Models\Transaction::whereIn('id', $movTxnIds)->get()->keyBy('id') : collect();
                                        @endphp
                                        @foreach ($movements as $movement)
                                            @php $jsStatus = $movement->journal_status ?? 'pending'; $jsLabel = ['pending'=>'Journal pending','posted'=>'Journal posted','failed'=>'Journal failed'][$jsStatus] ?? 'Journal pending'; @endphp
                                            <tr data-movement-id="{{ $movement->id }}">
                                                <td style="white-space:nowrap;color:#6f869b;font-size:6.5pt;">
                                                    {{ $movement->moved_at->format('d M Y') }}
                                                </td>
                                                <td>
                                                    <span class="ev-chip {{ $movement->action->value }}">
                                                        {{ ucfirst($movement->action->value) }}
                                                    </span>
                                                </td>
                                                <td class="amt" style="{{ (float)$movement->quantity < 0 ? 'color:#dc2626;' : 'color:#166534;' }}">
                                                    {{ (float)$movement->quantity >= 0 ? '+' : '' }}{{ number_format((float)$movement->quantity, 2) }}
                                                </td>
                                                <td class="amt">
                                                    {{ $movement->unit_cost !== null ? 'R ' . number_format((float)$movement->unit_cost, 2) : '—' }}
                                                </td>
                                                <td style="color:#6f869b;font-size:6.5pt;">{{ $movement->reference ?? '—' }}</td>
                                                <td style="font-size:6.5pt;">
                                                    @if ($movement->invoice_id && $movement->invoice)
                                                        <a href="{{ route('companies.invoices.show', [$company, $movement->invoice]) }}"
                                                            style="color:#005bf0;font-weight:700;text-decoration:none;border-bottom:1px solid #005bf0;">
                                                            {{ $movement->invoice->invoice_number }}
                                                        </a>
                                                    @else
                                                        <span style="color:#6f869b;">—</span>
                                                    @endif
                                                </td>
                                                <td style="color:#6f869b;font-size:6.5pt;">{{ $movement->notes ?? '—' }}</td>
                                                <td>
                                                    @if ($movement->transaction_id && ($movTxn = $movTxnMap->get($movement->transaction_id)))
                                                        <a href="{{ route('companies.transactions', $company) }}?{{ http_build_query(['description' => $movTxn->reference, 'start_date' => substr($movTxn->transaction_date, 0, 10), 'end_date' => substr($movTxn->transaction_date, 0, 10), 'highlight' => $movement->transaction_id]) }}" data-journal-status style="font-size:5pt;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:{{ $jsColors[$jsStatus] ?? '#92400e' }};text-decoration:underline;">{{ $jsLabel }}</a>
                                                    @else
                                                        <span data-journal-status style="font-size:5pt;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:{{ $jsColors[$jsStatus] ?? '#92400e' }};">{{ $jsLabel }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
                @endif {{-- end !is_service stock cards --}}

            </main>
        </div>
    </div>

    <script>
        function togglePanel(name) {
            const panel = document.getElementById('panel-' + name);
            if (!panel) return;
            const wasOpen = panel.classList.contains('open');
            document.querySelectorAll('.action-panel').forEach(p => p.classList.remove('open'));
            if (!wasOpen) {
                panel.classList.add('open');
                panel.scrollIntoView({behavior: 'smooth', block: 'nearest'});
            }
        }

        function showToast(msg, color) {
            const t = document.createElement('div');
            t.style.cssText = 'position:fixed;bottom:1.5rem;right:1.5rem;padding:6pt 10pt;border-radius:6px;font-size:0.8rem;font-weight:600;color:#fff;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,0.15);transition:opacity 0.4s;background:' + color;
            t.textContent = msg;
            document.body.appendChild(t);
            setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 400); }, 4000);
        }

        document.querySelectorAll('.action-panel form').forEach(form => {
            form.addEventListener('submit', async function (e) {
                e.preventDefault();
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
            });
        });

        @if ($errors->any())
            const action = '{{ old("action", "receive") }}';
            togglePanel(action);
        @endif

        // ── Real-time posting status via Reverb ──────────────────
        const journalStatusStyle = {
            pending: { label: 'Journal pending', color: '#92400e' },
            posted:  { label: 'Journal posted',  color: '#065f46' },
            failed:  { label: 'Journal failed',  color: '#b91c1c' },
        };

        window.addEventListener('echo:ready', function () {
            window.Echo.private('company.{{ $company->id }}')
                .listen('.posting.status.updated', (e) => {
                    if (e.entity_type !== 'inventory_movement') return;
                    const row = document.querySelector('tr[data-movement-id="' + e.entity_id + '"]');
                    if (row) {
                        const cell = row.querySelector('[data-journal-status]');
                        if (cell) {
                            const js = journalStatusStyle[e.status] || journalStatusStyle.pending;
                            cell.textContent = js.label;
                            cell.style.color = js.color;
                        }
                    }
                    if (e.status === 'posted' || e.status === 'failed') {
                        const toast = document.createElement('div');
                        toast.style.cssText = 'position:fixed;bottom:1.5rem;right:1.5rem;padding:6pt 10pt;border-radius:6px;font-size:0.8rem;font-weight:600;color:#fff;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,0.15);transition:opacity 0.4s;background:' + (e.status==='posted'?'#065f46':'#b91c1c');
                        toast.textContent = e.label;
                        document.body.appendChild(toast);
                        setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 400); }, 4000);
                    }
                });
        });
    </script>
@endsection
