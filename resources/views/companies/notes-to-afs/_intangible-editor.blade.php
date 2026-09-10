{{--
    Intangible Assets editor (Note — kind: intangible).
    Expects: $company, $intangibleClasses, $intangibleMovements, $availableAccounts, $startDate, $endDate
--}}
@php
    $allKeys = [
        'cost_opening', 'additions', 'subsequent_costs', 'revaluations',
        'cost_disposals', 'cost_closing',
        'ad_opening', 'amortisation_charge', 'ad_disposals', 'ad_closing',
        'ai_opening', 'impairment_charge', 'impairment_reversal', 'ai_closing',
        'rev_surplus_opening', 'rev_surplus_gain', 'rev_surplus_loss', 'rev_surplus_closing',
        'carrying_opening', 'carrying_closing',
    ];

    $totals = array_fill_keys($allKeys, 0.0);
    foreach ($intangibleMovements as $m) {
        foreach ($allKeys as $k) {
            $totals[$k] += ($m[$k] ?? 0.0);
        }
    }

    $fmt  = fn ($v) => $v == 0.0 ? '—' : number_format(abs($v), 2);
    $sign = fn ($v) => $v < 0 ? '(' . number_format(abs($v), 2) . ')' : ($v == 0.0 ? '—' : number_format($v, 2));
    $any  = fn (string $key) => collect($intangibleMovements)->contains(fn($m) => ($m[$key] ?? 0.0) != 0.0);

    $showDisposals    = $any('cost_disposals') || $any('ad_disposals');
    $showSubsequent   = $any('subsequent_costs');
    $showRevaluations = $any('revaluations');
    $showImpairment   = $any('ai_opening') || $any('impairment_charge') || $any('impairment_reversal') || $any('ai_closing');
    $showRevSurplus   = $any('rev_surplus_opening') || $any('rev_surplus_gain') || $any('rev_surplus_closing');

    $sections = array_filter([
        'COST' => array_filter([
            'cost_opening'     => 'Opening balance',
            'additions'        => 'Additions',
            'subsequent_costs' => $showSubsequent   ? 'Subsequent expenditure (IAS 38.18)' : null,
            'revaluations'     => $showRevaluations ? 'Revaluations (IAS 38.75)'            : null,
            'cost_disposals'   => $showDisposals    ? 'Disposals'                           : null,
            'cost_closing'     => 'Closing balance',
        ]),
        'ACCUMULATED AMORTISATION' => array_filter([
            'ad_opening'          => 'Opening balance',
            'amortisation_charge' => 'Charge for period',
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

    $methodOpts = ['straight_line' => 'Straight-line', 'reducing_balance' => 'Reducing balance'];
@endphp

<div class="ppe-section">

    {{-- ── Movement schedule + period selector ───── --}}
    <div class="notes-card" style="padding:0.85rem 1.1rem;margin-bottom:0.75rem;">

        <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:0.7rem;">
            <h2 class="ppe-section-title" style="margin:0;font-size:0.8rem;">Movement Schedule</h2>
            <form method="GET" action="{{ route('companies.notes-to-afs.show', [$company, $note]) }}"
                style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;margin-left:auto;">
                <label style="font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:#5a7186;">From</label>
                <input type="date" name="start_date" value="{{ $startDate }}"
                    style="border:1px solid #d3e2f5;border-radius:0;padding:0.25rem 0.5rem;font-size:0.72rem;font-family:inherit;color:#000;" />
                <label style="font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:#5a7186;">To</label>
                <input type="date" name="end_date" value="{{ $endDate }}"
                    style="border:1px solid #d3e2f5;border-radius:0;padding:0.25rem 0.5rem;font-size:0.72rem;font-family:inherit;color:#000;" />
                <button type="submit" class="mgmt-btn primary sm">Apply</button>
            </form>
        </div>

        @if (empty($intangibleMovements))
            <p style="font-size:0.78rem;color:#888;padding:0.35rem 0;">
                No intangible classes defined yet — add a class below to start.
            </p>
        @else
            <div style="overflow-x:auto;">
                <table class="ppe-schedule" style="width:100%;border-collapse:collapse;font-size:0.72rem;">
                    <thead>
                        <tr>
                            <th style="text-align:left;padding:0.3rem 0.5rem;border-bottom:1.5px solid #000;background:#f7fbfd;min-width:200px;"></th>
                            @foreach ($intangibleMovements as $m)
                                <th style="text-align:right;padding:0.3rem 0.5rem;font-size:0.68rem;font-weight:700;color:#5a7186;text-transform:uppercase;letter-spacing:0.06em;border-bottom:1.5px solid #000;background:#f7fbfd;white-space:nowrap;">
                                    {{ $m['class']->name }}
                                </th>
                            @endforeach
                            <th style="text-align:right;padding:0.3rem 0.5rem;font-size:0.68rem;font-weight:700;color:#000;text-transform:uppercase;letter-spacing:0.06em;border-bottom:1.5px solid #000;background:#f4fafc;white-space:nowrap;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sections as $sectionLabel => $keys)
                            @php
                                $isCarrying = $sectionLabel === 'CARRYING AMOUNT';
                            @endphp

                            <tr>
                                <td colspan="{{ count($intangibleMovements) + 2 }}"
                                    style="padding:0.5rem 0.5rem 0.15rem;font-size:0.56rem;font-weight:800;text-transform:uppercase;letter-spacing:0.12em;color:#000;border-top:{{ $loop->first ? 'none' : '0.5pt solid #000000' }};">
                                    {{ $sectionLabel }}
                                </td>
                            </tr>

                            @foreach ($keys as $key => $rowLabel)
                                @php
                                    $isClosing = str_ends_with($key, '_closing');
                                    $isMinus   = in_array($key, ['cost_disposals','ad_disposals','impairment_reversal','rev_surplus_loss']);
                                    $rowBg     = $isCarrying ? '#f4fafc' : ($isClosing ? '#f7fbfd' : '');
                                    $rowWeight = ($isClosing || $isCarrying) ? '700' : '400';
                                    $valColor  = '#000';
                                    $rowBorder = ($isClosing && !$isCarrying) ? '0.5pt solid #000000' : '0.5pt solid #d3e2f5';
                                @endphp
                                <tr style="{{ $rowBg ? 'background:'.$rowBg.';' : '' }}">
                                    <td style="padding:0.28rem 0.5rem 0.28rem 1rem;color:{{ ($isClosing || $isCarrying) ? '#000' : '#5a7186' }};font-weight:{{ $rowWeight }};border-bottom:{{ $rowBorder }};white-space:nowrap;">
                                        {{ $isMinus ? '(' : '' }}{{ $rowLabel }}{{ $isMinus ? ')' : '' }}
                                    </td>
                                    @foreach ($intangibleMovements as $m)
                                        @php $v = $m[$key] ?? 0.0; @endphp
                                        <td style="text-align:right;padding:0.28rem 0.5rem;font-family:'Courier New',monospace;color:{{ $valColor }};font-weight:{{ $rowWeight }};border-bottom:{{ $rowBorder }};white-space:nowrap;">
                                            {{ $isMinus ? ($v != 0 ? '('.$fmt($v).')' : '—') : $sign($v) }}
                                        </td>
                                    @endforeach
                                    @php $tv = $totals[$key] ?? 0.0; @endphp
                                    <td style="text-align:right;padding:0.28rem 0.5rem;font-family:'Courier New',monospace;font-weight:700;color:{{ $valColor }};background:{{ $isCarrying ? '#f4fafc' : ($isClosing ? '#f4fafc' : 'transparent') }};border-bottom:{{ $rowBorder }};white-space:nowrap;">
                                        {{ $isMinus ? ($tv != 0 ? '('.$fmt($tv).')' : '—') : $sign($tv) }}
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p style="margin:0.6rem 0 0;font-size:0.7rem;color:#888;">
                Built from the intangible asset register. Brackets&nbsp;( ) denote deductions.
                Sections appear once activity exists for them on the register.
            </p>
        @endif
    </div>

    {{-- ── Intangible classes (compact) ──────────────────────────────────── --}}
    <div class="notes-card ppe-classes-card" style="padding:0.55rem 0.85rem;">
        <div style="display:flex;align-items:center;gap:0.6rem;margin-bottom:0.35rem;">
            <h2 class="ppe-section-title" style="margin:0;font-size:0.72rem;flex-shrink:0;">Intangible Classes</h2>
            <span style="font-size:0.68rem;color:#888;">IAS 38.107 — indefinite-life classes are not amortised.</span>
        </div>

        @foreach ($intangibleClasses as $class)
            <div style="border-bottom:1px solid #d3e2f5;padding-bottom:0.5rem;margin-bottom:0.5rem;">
                <form method="POST"
                    action="{{ route('companies.notes-to-afs.intangible.classes.update', [$company, $class]) }}">
                    @csrf
                    @method('PATCH')
                    <div class="af-row">
                        <div class="af-field wide">
                            <label>Class Name</label>
                            <input type="text" name="name" value="{{ $class->name }}" required maxlength="120" />
                        </div>
                        <div class="af-field">
                            <label>Useful Life (yrs)</label>
                            <input type="number" step="0.01" min="0" max="999.99" name="useful_life_years"
                                value="{{ $class->useful_life_years }}" placeholder="e.g. 5" />
                        </div>
                        <div class="af-field">
                            <label>Method</label>
                            <select name="amortisation_method">
                                @foreach ($methodOpts as $val => $label)
                                    <option value="{{ $val }}" @selected($class->amortisation_method === $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:0.75rem;">
                        <label style="display:inline-flex;align-items:center;gap:0.35rem;cursor:pointer;" title="Indefinite useful life (IAS 38.107)">
                            <input type="hidden" name="indefinite_life" value="0">
                            <input type="checkbox" name="indefinite_life" value="1" @checked($class->indefinite_life)
                                style="accent-color:#000;margin:0;">
                            <span style="font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:#5a7186;">Indefinite Life</span>
                        </label>
                        <button type="submit" class="mgmt-btn sm">Save</button>
                    </div>
                </form>
                <form method="POST"
                    action="{{ route('companies.notes-to-afs.intangible.classes.destroy', [$company, $class]) }}"
                    onsubmit="return false" data-confirm-label="Notes to AFS" data-confirm-title="Delete Class" data-confirm-body="Delete {{ addslashes($class->name) }}?" data-confirm-text="Delete" data-confirm-danger="1"
                    style="margin-top:0.3rem;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-danger-link">Delete</button>
                </form>
            </div>
        @endforeach

        @if ($intangibleClasses->isEmpty())
            <p style="color:#888;font-size:0.65rem;margin:0 0 0.35rem;">No classes yet.</p>
        @endif

        {{-- Add new class --}}
        <form method="POST" action="{{ route('companies.notes-to-afs.intangible.classes.store', $company) }}"
            style="margin-top:0.5rem;padding-top:0.65rem;border-top:1px dashed #d3e2f5;">
            @csrf
            <div class="af-row">
                <div class="af-field wide">
                    <label>New Class Name</label>
                    <input type="text" name="name" required maxlength="120" placeholder="e.g. Software, Patents, Licences" />
                </div>
                <div class="af-field">
                    <label>Useful Life (yrs)</label>
                    <input type="number" step="0.01" min="0" max="999.99" name="useful_life_years" placeholder="e.g. 5" />
                </div>
                <div class="af-field">
                    <label>Method</label>
                    <select name="amortisation_method">
                        @foreach ($methodOpts as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:0.75rem;">
                <label style="display:inline-flex;align-items:center;gap:0.35rem;cursor:pointer;" title="Indefinite useful life (IAS 38.107)">
                    <input type="hidden" name="indefinite_life" value="0">
                    <input type="checkbox" name="indefinite_life" value="1"
                        style="accent-color:#000;margin:0;">
                    <span style="font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:#5a7186;">Indefinite Life</span>
                </label>
                <button type="submit" class="mgmt-btn primary sm">Add Class</button>
            </div>
        </form>
    </div>

    {{-- ── Intangible asset register link ─────────────────────────────────── --}}
    <div class="notes-card" style="padding:0.75rem 1.1rem;margin-top:0.75rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
            <div>
                <h2 class="ppe-section-title" style="margin:0 0 0.15rem;font-size:0.8rem;">Intangible Asset Register</h2>
                <p style="margin:0;font-size:0.65rem;color:#888;">Manage intangible assets, post acquisitions, amortisation, revaluations and impairments from the register.</p>
            </div>
            <a href="{{ route('companies.intangibles.index', $company) }}" class="mgmt-btn">
                Open Intangible Register &rarr;
            </a>
        </div>
    </div>

</div>
