{{--
    Property, Plant & Equipment editor (Note — kind: ppe).
    Expects: $company, $ppeClasses, $movements, $availableAccounts, $startDate, $endDate, $assets
--}}
@php
    $allKeys = [
        'cost_opening', 'additions', 'subsequent_costs', 'revaluations',
        'cost_disposals', 'cost_other', 'cost_closing',
        'ad_opening', 'ad_reval_eliminated', 'depreciation_charge', 'ad_disposals', 'ad_closing',
        'ai_opening', 'impairment_charge', 'impairment_reversal', 'ai_disposals', 'ai_closing',
        'rev_surplus_opening', 'rev_surplus_gain', 'rev_surplus_loss', 'rev_surplus_closing',
        'carrying_opening', 'carrying_closing',
    ];

    $totals = array_fill_keys($allKeys, 0.0);
    foreach ($movements as $m) {
        foreach ($allKeys as $k) {
            $totals[$k] += ($m[$k] ?? 0.0);
        }
    }

    $fmt  = fn ($v) => $v == 0.0 ? '—' : number_format(abs($v), 2);
    $sign = fn ($v) => $v < 0 ? '(' . number_format(abs($v), 2) . ')' : ($v == 0.0 ? '—' : number_format($v, 2));
    $any  = fn (string $key) => collect($movements)->contains(fn($m) => ($m[$key] ?? 0.0) != 0.0);

    $showDisposals      = $any('cost_disposals') || $any('ad_disposals');
    $showSubsequent     = $any('subsequent_costs');
    $showRevaluations   = $any('revaluations');
    $showOtherCost      = $any('cost_other');
    $showImpairment     = $any('ai_opening') || $any('impairment_charge') || $any('impairment_reversal') || $any('ai_closing');
    $showRevSurplus     = $any('rev_surplus_opening') || $any('rev_surplus_gain') || $any('rev_surplus_closing');

    $sections = array_filter([
        'COST' => array_filter([
            'cost_opening'     => 'Opening balance',
            'additions'        => 'Additions',
            'subsequent_costs' => $showSubsequent   ? 'Subsequent expenditure (IAS 16.7)' : null,
            'revaluations'     => $showRevaluations ? 'Revaluations'                       : null,
            'cost_disposals'   => $showDisposals    ? 'Disposals'                          : null,
            'cost_other'       => $showOtherCost    ? 'Other adjustments'                  : null,
            'cost_closing'     => 'Closing balance',
        ]),
        'ACCUMULATED DEPRECIATION' => array_filter([
            'ad_opening'          => 'Opening balance',
            'depreciation_charge' => 'Charge for period',
            'ad_disposals'        => $showDisposals ? 'Disposals' : null,
            'ad_closing'          => 'Closing balance',
        ]),
        'ACCUMULATED IMPAIRMENT (IAS 36)' => $showImpairment ? array_filter([
            'ai_opening'          => 'Opening balance',
            'impairment_charge'   => 'Impairment losses recognised',
            'impairment_reversal' => 'Reversals (IAS 36.114)',
            'ai_closing'          => 'Closing balance',
        ]) : null,
        'REVALUATION SURPLUS — OCI' => $showRevSurplus ? [
            'rev_surplus_opening' => 'Opening balance',
            'rev_surplus_gain'    => 'Gains recognised in OCI',
            'rev_surplus_loss'    => 'Losses / transfers',
            'rev_surplus_closing' => 'Closing balance',
        ] : null,
        'CARRYING AMOUNT' => [
            'carrying_opening' => 'Opening',
            'carrying_closing' => 'Closing',
        ],
    ]);
@endphp

<div class="ppe-section">

    {{-- ── Movement schedule + period selector + INLINE COA LINKING ───── --}}
    <div class="notes-card" style="padding:0.85rem 1.1rem;margin-bottom:0.75rem;">

        <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:0.7rem;">
            <h2 class="ppe-section-title" style="margin:0;font-size:0.8rem;">Movement schedule</h2>
            <form method="GET" action="{{ route('companies.notes-to-afs.show', [$company, $note]) }}"
                style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;margin-left:auto;">
                <label style="font-size:0.58rem;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:#555;">From</label>
                <input type="date" name="start_date" value="{{ $startDate }}"
                    style="border:1px solid #ccc;border-radius:0;padding:0.25rem 0.5rem;font-size:0.72rem;font-family:inherit;color:#000;" />
                <label style="font-size:0.58rem;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:#555;">To</label>
                <input type="date" name="end_date" value="{{ $endDate }}"
                    style="border:1px solid #ccc;border-radius:0;padding:0.25rem 0.5rem;font-size:0.72rem;font-family:inherit;color:#000;" />
                <button type="submit" class="mgmt-btn primary sm">Apply</button>
            </form>
        </div>

        @if (empty($movements))
            <p style="font-size:0.78rem;color:#9ca3af;padding:0.35rem 0;">
                No PPE classes defined yet — add a class below to start.
            </p>
        @else
            <div style="overflow-x:auto;">
                <table class="ppe-schedule" style="width:100%;border-collapse:collapse;font-size:0.72rem;">
                    <thead>
                        <tr>
                            <th style="text-align:left;padding:0.3rem 0.5rem;border-bottom:1.5px solid #000;background:#fafafa;min-width:200px;"></th>
                            @foreach ($movements as $m)
                                <th style="text-align:right;padding:0.3rem 0.5rem;font-size:0.58rem;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:0.06em;border-bottom:1.5px solid #000;background:#fafafa;white-space:nowrap;">
                                    {{ $m['class']->name }}
                                </th>
                            @endforeach
                            <th style="text-align:right;padding:0.3rem 0.5rem;font-size:0.58rem;font-weight:700;color:#000;text-transform:uppercase;letter-spacing:0.06em;border-bottom:1.5px solid #000;background:#f5f5f5;white-space:nowrap;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sections as $sectionLabel => $keys)
                            @php
                                $isCarrying   = $sectionLabel === 'CARRYING AMOUNT';
                                $isImpairment = str_starts_with($sectionLabel, 'ACCUMULATED IMPAIRMENT');
                                $isRevSurplus = str_starts_with($sectionLabel, 'REVALUATION SURPLUS');
                            @endphp

                            <tr>
                                <td colspan="{{ count($movements) + 2 }}"
                                    style="padding:0.5rem 0.5rem 0.15rem;font-size:0.56rem;font-weight:800;text-transform:uppercase;letter-spacing:0.12em;color:#000;border-top:{{ $loop->first ? 'none' : '1.5px solid #000' }};">
                                    {{ $sectionLabel }}
                                </td>
                            </tr>

                            @foreach ($keys as $key => $rowLabel)
                                @php
                                    $isClosing = str_ends_with($key, '_closing');
                                    $isMinus   = in_array($key, ['cost_disposals','ad_disposals','impairment_reversal','rev_surplus_loss']);
                                    $rowBg     = $isCarrying ? '#f5f5f5' : ($isClosing ? '#fafafa' : '');
                                    $rowWeight = ($isClosing || $isCarrying) ? '700' : '400';
                                    $valColor  = '#000';
                                    $rowBorder = ($isClosing && !$isCarrying) ? '1.5px solid #000' : '1px solid #ddd';
                                @endphp
                                <tr style="{{ $rowBg ? 'background:'.$rowBg.';' : '' }}">
                                    <td style="padding:0.28rem 0.5rem 0.28rem 1rem;color:{{ ($isClosing || $isCarrying) ? '#000' : '#555' }};font-weight:{{ $rowWeight }};border-bottom:{{ $rowBorder }};white-space:nowrap;">
                                        {{ $isMinus ? '(' : '' }}{{ $rowLabel }}{{ $isMinus ? ')' : '' }}
                                    </td>
                                    @foreach ($movements as $m)
                                        @php $v = $m[$key] ?? 0.0; @endphp
                                        <td style="text-align:right;padding:0.28rem 0.5rem;font-family:'Courier New',monospace;color:{{ $valColor }};font-weight:{{ $rowWeight }};border-bottom:{{ $rowBorder }};white-space:nowrap;">
                                            {{ $isMinus ? ($v != 0 ? '('.$fmt($v).')' : '—') : $sign($v) }}
                                        </td>
                                    @endforeach
                                    @php $tv = $totals[$key] ?? 0.0; @endphp
                                    <td style="text-align:right;padding:0.28rem 0.5rem;font-family:'Courier New',monospace;font-weight:700;color:{{ $valColor }};background:{{ $isCarrying ? '#f0f0f0' : ($isClosing ? '#f5f5f5' : 'transparent') }};border-bottom:{{ $rowBorder }};white-space:nowrap;">
                                        {{ $isMinus ? ($tv != 0 ? '('.$fmt($tv).')' : '—') : $sign($tv) }}
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p style="margin:0.6rem 0 0;font-size:0.6rem;color:#888;">
                Built from the asset register. Brackets&nbsp;( ) denote deductions.
                Sections appear once activity exists for them on the register.
            </p>
        @endif
    </div>

    {{-- ── PPE classes (compact) ──────────────────────────────────────── --}}
    @php
        $methodOpts = ['straight_line' => 'Straight-line', 'reducing_balance' => 'Reducing balance'];
    @endphp
    <div class="notes-card ppe-classes-card" style="padding:0.55rem 0.85rem;">
        <div style="display:flex;align-items:center;gap:0.6rem;margin-bottom:0.35rem;">
            <h2 class="ppe-section-title" style="margin:0;font-size:0.72rem;flex-shrink:0;">PPE classes</h2>
            <span style="font-size:0.58rem;color:#9ca3af;">Link accounts on the schedule above.</span>
        </div>

        @foreach ($ppeClasses as $class)
            <div class="ppe-class-row">
                <form method="POST"
                    action="{{ route('companies.notes-to-afs.ppe.classes.update', [$company, $class]) }}"
                    class="ppe-class-form">
                    @csrf
                    @method('PATCH')
                    <input type="text" name="name" value="{{ $class->name }}" required maxlength="120"
                        class="ppe-cls-input" style="flex:1;min-width:80px;" />
                    <input type="number" step="0.01" min="0" max="999.99" name="useful_life_years"
                        value="{{ $class->useful_life_years }}" placeholder="yrs"
                        title="Useful life (years)"
                        class="ppe-cls-input" style="width:44px;text-align:right;" />
                    <select name="depreciation_method" class="ppe-cls-input" style="width:90px;" title="Depreciation method">
                        @foreach ($methodOpts as $val => $label)
                            <option value="{{ $val }}" @selected($class->depreciation_method === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="ppe-cls-btn" title="Save changes">✓</button>
                </form>
                <form method="POST"
                    action="{{ route('companies.notes-to-afs.ppe.classes.destroy', [$company, $class]) }}"
                    onsubmit="return false" data-confirm-label="Notes to AFS" data-confirm-title="Delete Class" data-confirm-body="Delete {{ addslashes($class->name) }}?" data-confirm-text="Delete" data-confirm-danger="1"
                    style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="ppe-cls-btn ppe-cls-del" title="Delete class">×</button>
                </form>
            </div>
        @endforeach

        @if ($ppeClasses->isEmpty())
            <p style="color:#9ca3af;font-size:0.65rem;margin:0 0 0.35rem;">No classes yet.</p>
        @endif

        {{-- Add new class — same compact row layout --}}
        <form method="POST" action="{{ route('companies.notes-to-afs.ppe.classes.store', $company) }}"
            class="ppe-class-form ppe-class-row" style="margin-top:0.35rem;padding-top:0.35rem;border-top:1px dashed #ccc;">
            @csrf
            <input type="text" name="name" required maxlength="120" placeholder="New class (e.g. Motor vehicles)"
                class="ppe-cls-input" style="flex:1;min-width:80px;" />
            <input type="number" step="0.01" min="0" max="999.99" name="useful_life_years" placeholder="yrs"
                title="Useful life (years)" class="ppe-cls-input" style="width:44px;text-align:right;" />
            <select name="depreciation_method" class="ppe-cls-input" style="width:90px;" title="Depreciation method">
                @foreach ($methodOpts as $val => $label)
                    <option value="{{ $val }}">{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="ppe-cls-btn ppe-cls-add" title="Add class">+</button>
        </form>
    </div>

    <style>
        .ppe-classes-card .ppe-class-row {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            margin-bottom: 0.25rem;
        }
        .ppe-classes-card .ppe-class-form {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            flex: 1;
        }
        .ppe-classes-card .ppe-cls-input {
            border: 1px solid #ccc;
            border-radius: 0;
            padding: 0.18rem 0.35rem;
            font-size: 0.66rem;
            font-family: inherit;
            color: #000;
            background: #fff;
            box-sizing: border-box;
            line-height: 1.2;
        }
        .ppe-classes-card .ppe-cls-input:focus {
            outline: none;
            border-color: #000;
        }
        .ppe-classes-card .ppe-cls-btn {
            background: #fff;
            border: 1px solid #000;
            border-radius: 0;
            padding: 0.12rem 0.4rem;
            font-size: 0.7rem;
            font-weight: 700;
            line-height: 1.2;
            cursor: pointer;
            color: #000;
            font-family: inherit;
            flex-shrink: 0;
        }
        .ppe-classes-card .ppe-cls-btn:hover {
            background: #000;
            color: #fff;
        }
        .ppe-classes-card .ppe-cls-add {
            color: #fff;
            background: #000;
            border-color: #000;
        }
        .ppe-classes-card .ppe-cls-add:hover {
            background: #333;
        }
        .ppe-classes-card .ppe-cls-del {
            color: #dc2626;
            border-color: #dc2626;
        }
        .ppe-classes-card .ppe-cls-del:hover {
            background: #dc2626;
            color: #fff;
        }
    </style>

    {{-- ── Asset register link ─────────────────────────────────────────── --}}
    <div class="notes-card" style="padding:0.75rem 1.1rem;margin-top:0.75rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
            <div>
                <h2 class="ppe-section-title" style="margin:0 0 0.15rem;font-size:0.8rem;">Asset register</h2>
                <p style="margin:0;font-size:0.65rem;color:#9ca3af;">Manage assets, post acquisitions, depreciation, revaluations and impairments from the asset register.</p>
            </div>
            <a href="{{ route('companies.assets.index', $company) }}" class="mgmt-btn">
                Open Asset Register &rarr;
            </a>
        </div>
    </div>

</div>

<style>
