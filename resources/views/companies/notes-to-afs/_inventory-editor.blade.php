{{--
    Inventory movement schedule (Note — kind: inventory).
    IAS 2.36(d) disclosure: reconciliation of carrying amount.
    Expects: $company, $note, $inventoryMovements, $startDate, $endDate
--}}
@php
    $allKeys = [
        'opening_qty', 'opening_val',
        'qty_received', 'cost_received',
        'qty_issued', 'cost_issued',
        'qty_adjusted', 'cost_adjusted',
        'write_downs', 'reversals',
        'closing_qty', 'closing_val', 'carrying_val',
    ];

    $totals = array_fill_keys($allKeys, 0.0);
    foreach ($inventoryMovements as $m) {
        foreach ($allKeys as $k) {
            $totals[$k] += ($m[$k] ?? 0.0);
        }
    }

    $fmt  = fn ($v) => $v == 0.0 ? '—' : number_format(abs($v), 2);
    $fmtS = fn ($v) => $v < 0 ? '(' . number_format(abs($v), 2) . ')' : ($v == 0.0 ? '—' : number_format($v, 2));

    $hasWriteDowns = collect($inventoryMovements)->contains(fn($m) => ($m['write_downs'] ?? 0) != 0);
    $hasReversals  = collect($inventoryMovements)->contains(fn($m) => ($m['reversals'] ?? 0) != 0);
    $hasAdjusted   = collect($inventoryMovements)->contains(fn($m) => ($m['qty_adjusted'] ?? 0) != 0);
    $hasNrv        = $hasWriteDowns || $hasReversals;
@endphp

<div class="ppe-section">

    <div class="notes-card" style="padding:0.85rem 1.1rem;margin-bottom:0.75rem;">

        <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:0.7rem;">
            <h2 class="ppe-section-title" style="margin:0;font-size:0.8rem;">IAS 2 Inventory Movement Schedule</h2>
            <form method="GET" action="{{ route('companies.notes-to-afs.show', [$company, $note]) }}"
                style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;margin-left:auto;">
                <label style="font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:#5a7186;">From</label>
                <input type="date" name="start_date" value="{{ $startDate }}"
                    style="border:1px solid #d3e2f5;border-radius:0;padding:0.25rem 0.5rem;font-size:0.72rem;font-family:inherit;color:#000;" />
                <label style="font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:#5a7186;">To</label>
                <input type="date" name="end_date" value="{{ $endDate }}"
                    style="border:1px solid #d3e2f5;border-radius:0;padding:0.25rem 0.5rem;font-size:0.72rem;font-family:inherit;color:#000;" />
                <button type="submit"
                    style="background:#000;color:#fff;border:none;padding:0.3rem 0.75rem;font-size:0.68rem;font-weight:700;cursor:pointer;">
                    Update
                </button>
            </form>
        </div>

        @if (empty($inventoryMovements))
            <p style="color:#6f869b;font-style:italic;font-size:0.78rem;">No inventory items in the register.</p>
        @else
            <div style="overflow-x:auto;">
                <table class="cust-items-table" style="table-layout:fixed;font-size:0.72rem;">
                    <colgroup>
                        <col style="width:30%;">
                        @foreach ($inventoryMovements as $m)
                            <col style="width:{{ 55 / count($inventoryMovements) }}%;">
                        @endforeach
                        <col style="width:15%;">
                    </colgroup>
                    <thead>
                        <tr>
                            <td></td>
                            @foreach ($inventoryMovements as $m)
                                <td class="amt" style="font-weight:800;font-size:0.65rem;text-transform:uppercase;letter-spacing:0.04em;">
                                    {{ $m['type_label'] }}
                                </td>
                            @endforeach
                            <td class="amt" style="font-weight:800;font-size:0.65rem;text-transform:uppercase;letter-spacing:0.04em;">Total</td>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- COST AT COST --}}
                        <tr>
                            <td colspan="{{ count($inventoryMovements) + 2 }}" style="font-weight:800;font-size:0.65rem;text-transform:uppercase;letter-spacing:0.07em;padding-top:0.6rem;border-bottom:none;">
                                COST
                            </td>
                        </tr>
                        <tr>
                            <td style="padding-left:0.75rem;">Opening stock</td>
                            @foreach ($inventoryMovements as $m)
                                <td class="amt">{{ $fmt($m['opening_val']) }}</td>
                            @endforeach
                            <td class="amt" style="font-weight:700;">{{ $fmt($totals['opening_val']) }}</td>
                        </tr>
                        <tr>
                            <td style="padding-left:0.75rem;">Purchases / goods received</td>
                            @foreach ($inventoryMovements as $m)
                                <td class="amt" style="color:#166534;">{{ $fmt($m['cost_received']) }}</td>
                            @endforeach
                            <td class="amt" style="font-weight:700;color:#166534;">{{ $fmt($totals['cost_received']) }}</td>
                        </tr>
                        <tr>
                            <td style="padding-left:0.75rem;">Cost of sales / goods issued</td>
                            @foreach ($inventoryMovements as $m)
                                <td class="amt" style="color:#dc2626;">{{ $m['cost_issued'] != 0 ? '(' . number_format($m['cost_issued'], 2) . ')' : '—' }}</td>
                            @endforeach
                            <td class="amt" style="font-weight:700;color:#dc2626;">{{ $totals['cost_issued'] != 0 ? '(' . number_format($totals['cost_issued'], 2) . ')' : '—' }}</td>
                        </tr>
                        @if ($hasAdjusted)
                        <tr>
                            <td style="padding-left:0.75rem;">Adjustments</td>
                            @foreach ($inventoryMovements as $m)
                                <td class="amt">{{ $fmtS($m['cost_adjusted']) }}</td>
                            @endforeach
                            <td class="amt" style="font-weight:700;">{{ $fmtS($totals['cost_adjusted']) }}</td>
                        </tr>
                        @endif
                        <tr style="border-top:1px solid #000;">
                            <td style="padding-left:0.75rem;font-weight:700;">Closing stock (at cost)</td>
                            @foreach ($inventoryMovements as $m)
                                <td class="amt" style="font-weight:700;">{{ $fmt($m['closing_val']) }}</td>
                            @endforeach
                            <td class="amt" style="font-weight:800;">{{ $fmt($totals['closing_val']) }}</td>
                        </tr>

                        @if ($hasNrv)
                        {{-- NRV WRITE-DOWN --}}
                        <tr>
                            <td colspan="{{ count($inventoryMovements) + 2 }}" style="font-weight:800;font-size:0.65rem;text-transform:uppercase;letter-spacing:0.07em;padding-top:0.6rem;border-bottom:none;">
                                NRV WRITE-DOWN (IAS 2.34)
                            </td>
                        </tr>
                        @if ($hasWriteDowns)
                        <tr>
                            <td style="padding-left:0.75rem;">Write-down to NRV</td>
                            @foreach ($inventoryMovements as $m)
                                <td class="amt" style="color:#9d174d;">{{ $m['write_downs'] != 0 ? '(' . number_format($m['write_downs'], 2) . ')' : '—' }}</td>
                            @endforeach
                            <td class="amt" style="font-weight:700;color:#9d174d;">{{ $totals['write_downs'] != 0 ? '(' . number_format($totals['write_downs'], 2) . ')' : '—' }}</td>
                        </tr>
                        @endif
                        @if ($hasReversals)
                        <tr>
                            <td style="padding-left:0.75rem;">Reversal of write-down (IAS 2.33)</td>
                            @foreach ($inventoryMovements as $m)
                                <td class="amt" style="color:#065f46;">{{ $fmt($m['reversals']) }}</td>
                            @endforeach
                            <td class="amt" style="font-weight:700;color:#065f46;">{{ $fmt($totals['reversals']) }}</td>
                        </tr>
                        @endif
                        @endif

                        {{-- CARRYING AMOUNT --}}
                        <tr>
                            <td colspan="{{ count($inventoryMovements) + 2 }}" style="font-weight:800;font-size:0.65rem;text-transform:uppercase;letter-spacing:0.07em;padding-top:0.6rem;border-bottom:none;">
                                CARRYING AMOUNT
                            </td>
                        </tr>
                        <tr style="border-top:2px solid #000;border-bottom:2px solid #000;">
                            <td style="font-weight:800;">Carrying amount (lower of cost and NRV)</td>
                            @foreach ($inventoryMovements as $m)
                                <td class="amt" style="font-weight:800;{{ ($m['write_downs'] ?? 0) > 0 ? 'color:#9d174d;' : '' }}">{{ number_format($m['carrying_val'], 2) }}</td>
                            @endforeach
                            <td class="amt" style="font-weight:800;{{ ($totals['write_downs'] ?? 0) > 0 ? 'color:#9d174d;' : '' }}">{{ number_format($totals['carrying_val'], 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Quantities summary --}}
            <div style="margin-top:1rem;">
                <h3 style="font-size:0.68rem;font-weight:800;text-transform:uppercase;letter-spacing:0.07em;margin:0 0 0.5rem;">Quantities (units)</h3>
                <div style="overflow-x:auto;">
                    <table class="cust-items-table" style="table-layout:fixed;font-size:0.72rem;">
                        <colgroup>
                            <col style="width:30%;">
                            @foreach ($inventoryMovements as $m)
                                <col style="width:{{ 55 / count($inventoryMovements) }}%;">
                            @endforeach
                            <col style="width:15%;">
                        </colgroup>
                        <thead>
                            <tr>
                                <td></td>
                                @foreach ($inventoryMovements as $m)
                                    <td class="amt" style="font-weight:800;font-size:0.65rem;text-transform:uppercase;letter-spacing:0.04em;">{{ $m['type_label'] }}</td>
                                @endforeach
                                <td class="amt" style="font-weight:800;font-size:0.65rem;text-transform:uppercase;letter-spacing:0.04em;">Total</td>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Opening</td>
                                @foreach ($inventoryMovements as $m)
                                    <td class="amt">{{ number_format($m['opening_qty'], 2) }}</td>
                                @endforeach
                                <td class="amt" style="font-weight:700;">{{ number_format($totals['opening_qty'], 2) }}</td>
                            </tr>
                            <tr>
                                <td>Received</td>
                                @foreach ($inventoryMovements as $m)
                                    <td class="amt" style="color:#166534;">{{ $fmt($m['qty_received']) }}</td>
                                @endforeach
                                <td class="amt" style="font-weight:700;color:#166534;">{{ $fmt($totals['qty_received']) }}</td>
                            </tr>
                            <tr>
                                <td>Issued</td>
                                @foreach ($inventoryMovements as $m)
                                    <td class="amt" style="color:#dc2626;">{{ $m['qty_issued'] != 0 ? '(' . number_format($m['qty_issued'], 2) . ')' : '—' }}</td>
                                @endforeach
                                <td class="amt" style="font-weight:700;color:#dc2626;">{{ $totals['qty_issued'] != 0 ? '(' . number_format($totals['qty_issued'], 2) . ')' : '—' }}</td>
                            </tr>
                            @if ($hasAdjusted)
                            <tr>
                                <td>Adjustments</td>
                                @foreach ($inventoryMovements as $m)
                                    <td class="amt">{{ $fmtS($m['qty_adjusted']) }}</td>
                                @endforeach
                                <td class="amt" style="font-weight:700;">{{ $fmtS($totals['qty_adjusted']) }}</td>
                            </tr>
                            @endif
                            <tr style="border-top:2px solid #000;">
                                <td style="font-weight:800;">Closing</td>
                                @foreach ($inventoryMovements as $m)
                                    <td class="amt" style="font-weight:800;">{{ number_format($m['closing_qty'], 2) }}</td>
                                @endforeach
                                <td class="amt" style="font-weight:800;">{{ number_format($totals['closing_qty'], 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    <div class="notes-card" style="padding:0.65rem 1.1rem;font-size:0.72rem;color:#5a7186;">
        <p style="margin:0;">
            <strong>IAS 2.36(d)</strong> — The financial statements shall disclose the carrying amount of inventories
            and the amount recognised as an expense during the period, including any write-down and any reversal of write-down.
        </p>
        <p style="margin:0.4rem 0 0;">
            Figures sourced from the <a href="{{ route('companies.inventory.index', $company) }}" style="color:#005bf0;font-weight:700;">inventory register</a>.
        </p>
    </div>
</div>
