@extends('layouts.public')

@section('title', 'Payroll Run — ' . $run->period_label)
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .run-chips {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100pt, 1fr));
            gap: 4pt;
            margin-bottom: 4pt;
        }

        .run-chip {
            border: 0.5pt solid #9ec1f5;
            padding: 5pt 7pt;
            background: #fff;
        }

        .run-chip-label {
            font-size: 5.5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #5a7186;
            margin-bottom: 1pt;
        }

        .run-chip-value {
            font-size: 8pt;
            font-weight: 700;
            color: #191919;
        }

        .run-chip.highlight { border-color: #1a345b; }

        .detail-row {
            font-size: 6.5pt;
            color: #191919;
            display: flex;
            justify-content: space-between;
            padding: 1.5pt 0;
            border-bottom: 0.4pt solid #d3e2f5;
        }

        .detail-row:last-child { border-bottom: none; }

        .detail-section-title {
            font-size: 5.5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #1a345b;
            margin: 0 0 3pt;
        }

        @media print {
            .co-topbar, .co-sidebar, nav, footer, .reg-mgmt-bar { display:none !important; }
            .co-main { padding:0 !important; }
            .co-body { display:block !important; }
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
                    <div style="background:#dcfce7;border:1px solid #bbf7d0;color:#15803d;padding:4pt 8pt;font-size:7pt;font-weight:600;margin-bottom:10pt;">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div style="background:#fee2e2;border:1px solid #fca5a5;color:#b91c1c;padding:4pt 8pt;font-size:7pt;font-weight:600;margin-bottom:10pt;">
                        {{ session('error') }}
                    </div>
                @endif

                {{-- Action bar --}}
                <div class="reg-mgmt-bar">
                    <div style="display:flex;align-items:center;gap:4pt;flex-wrap:wrap;">
                        @if ($run->employee_type === 'salary')
                            <span class="reg-status" style="color:#15803d;border-color:#15803d;background:#f0fdf4;">Salary Run</span>
                        @elseif ($run->employee_type === 'hourly')
                            <span class="reg-status" style="color:#854d0e;border-color:#854d0e;background:#fef9c3;">Hourly Run</span>
                        @endif
                        @if ($run->isPosted())
                            <span class="reg-status" style="color:#15803d;border-color:#15803d;background:#dcfce7;">Finalised</span>
                        @else
                            <span class="reg-status draft">Draft</span>
                        @endif
                    </div>
                    <div style="display:flex;gap:4pt;flex-wrap:wrap;align-items:center;">
                        @if (! $run->isPosted())
                            <form method="POST" action="{{ route('companies.payroll.runs.recalculate', [$company, $run]) }}">
                                @csrf
                                <button class="reg-btn ghost" type="submit">Recalculate</button>
                            </form>
                            <form method="POST" action="{{ route('companies.payroll.runs.post', [$company, $run]) }}"
                                onsubmit="return false" data-confirm-label="Payroll" data-confirm-title="Finalise Payroll Run" data-confirm-body="The payroll run will be locked. Journal entries are shown for manual posting — nothing will be automatically posted to accounting." data-confirm-text="Finalise">
                                @csrf
                                <button class="reg-btn primary" type="submit">Finalise Run</button>
                            </form>
                            <form method="POST" action="{{ route('companies.payroll.runs.destroy', [$company, $run]) }}"
                                onsubmit="return false" data-confirm-label="Payroll" data-confirm-title="Delete Payroll Run" data-confirm-body="This payroll run and all its payslips will be permanently deleted." data-confirm-text="Delete" data-confirm-danger="1">
                                @csrf @method('DELETE')
                                <button class="reg-btn danger" type="submit">Delete</button>
                            </form>
                        @else
                            <a href="{{ route('companies.payroll.emp201', [$company, $run]) }}" class="reg-btn">EMP201</a>
                        @endif
                    </div>
                </div>

                {{-- Document shell --}}
                <div class="reg-doc">
                    <div class="reg-doc-body">

                        {{-- Letterhead --}}
                        <div class="afs-letterhead">
                            <div>
                                <div class="afs-letterhead-name">{{ $run->employee_type === 'hourly' ? 'Hourly' : 'Salary' }} Payroll Run</div>
                                <div class="afs-letterhead-meta">{{ $company->registered_name }}</div>
                            </div>
                            <div class="afs-letterhead-meta afs-right">
                                <div style="font-size:9pt;font-weight:800;color:#1a345b;margin-bottom:3pt;">{{ $run->period_label }}</div>
                                @if ($run->notes)
                                    <div style="font-size:6.5pt;color:#5a7186;font-style:italic;">{{ $run->notes }}</div>
                                @endif
                            </div>
                        </div>

                        {{-- Summary chips --}}
                        <div class="reg-section-header" style="margin-top:8pt;">
                            Run Summary
                            <span style="float:right;font-size:5.5pt;font-weight:400;text-transform:none;letter-spacing:0;color:#6f869b;">{{ $payslips->count() }} employee{{ $payslips->count() !== 1 ? 's' : '' }}</span>
                        </div>
                        <div class="run-chips">
                            <div class="run-chip">
                                <div class="run-chip-label">Gross Earnings</div>
                                <div class="run-chip-value">R {{ number_format($run->total_gross_earnings, 2) }}</div>
                            </div>
                            <div class="run-chip">
                                <div class="run-chip-label">PAYE</div>
                                <div class="run-chip-value" style="color:#dc2626;">R {{ number_format($run->total_paye, 2) }}</div>
                            </div>
                            <div class="run-chip">
                                <div class="run-chip-label">UIF (employee)</div>
                                <div class="run-chip-value" style="color:#dc2626;">R {{ number_format($run->total_uif_employee, 2) }}</div>
                            </div>
                            <div class="run-chip">
                                <div class="run-chip-label">UIF (employer)</div>
                                <div class="run-chip-value">R {{ number_format($run->total_uif_employer, 2) }}</div>
                            </div>
                            <div class="run-chip">
                                <div class="run-chip-label">SDL</div>
                                <div class="run-chip-value">R {{ number_format($run->total_sdl, 2) }}</div>
                            </div>
                            <div class="run-chip">
                                <div class="run-chip-label">Net Pay</div>
                                <div class="run-chip-value" style="color:#15803d;">R {{ number_format($run->total_net_pay, 2) }}</div>
                            </div>
                            @if ((float) $run->total_employer_retirement > 0)
                                <div class="run-chip">
                                    <div class="run-chip-label">Retirement (IAS 19)</div>
                                    <div class="run-chip-value">R {{ number_format($run->total_employer_retirement, 2) }}</div>
                                </div>
                            @endif
                            @if ((float) $run->total_employer_medical_aid > 0)
                                <div class="run-chip">
                                    <div class="run-chip-label">Medical Aid (IAS 19)</div>
                                    <div class="run-chip-value">R {{ number_format($run->total_employer_medical_aid, 2) }}</div>
                                </div>
                            @endif
                            @if ((float) $run->total_leave_accrual > 0)
                                <div class="run-chip">
                                    <div class="run-chip-label">Leave Accrual (IAS 19)</div>
                                    <div class="run-chip-value">R {{ number_format($run->total_leave_accrual, 2) }}</div>
                                </div>
                            @endif
                            @if ((float) $run->total_bonus_accrual > 0)
                                <div class="run-chip">
                                    <div class="run-chip-label">Bonus Provision (IAS 19)</div>
                                    <div class="run-chip-value">R {{ number_format($run->total_bonus_accrual, 2) }}</div>
                                </div>
                            @endif
                            <div class="run-chip highlight">
                                <div class="run-chip-label">Total Employer Cost</div>
                                <div class="run-chip-value">R {{ number_format($run->total_employer_cost, 2) }}</div>
                            </div>
                        </div>

                        {{-- Journal Entry Reference --}}
                        <div class="reg-section-header" style="margin-top:8pt;">Journal Entry Reference</div>
                        <div class="afs-warning">
                            These entries are for reference only. Post them to your accounting system manually using the accounts shown below.
                        </div>
                        @php $debitLines = collect($journalLines)->where('type', 'debit')->values(); @endphp
                        @if ($debitLines->isNotEmpty())
                            <div style="overflow-x:auto;">
                                <table class="reg-table">
                                    <thead>
                                        <tr>
                                            <th>Description</th>
                                            <th class="amt" style="width:100pt;">Amount (R)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($debitLines as $line)
                                            <tr>
                                                <td>{{ $line['description'] }}</td>
                                                <td class="amt" style="font-family:'DejaVu Sans Mono',monospace;">{{ number_format($line['amount'], 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td style="color:#6f869b;">Total payroll cost</td>
                                            <td class="amt" style="font-family:'DejaVu Sans Mono',monospace;">{{ number_format($debitLines->sum('amount'), 2) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @else
                            <p style="font-size:7pt;color:#6f869b;">No payslips yet — calculate the run first.</p>
                        @endif

                        {{-- Hours Worksheet (hourly runs, draft only) --}}
                        @if ($run->employee_type === 'hourly' && ! $run->isPosted())
                            <div class="reg-section-header" style="margin-top:8pt;">
                                Hours Worksheet
                                <span style="float:right;font-size:5.5pt;font-weight:400;text-transform:none;letter-spacing:0;color:#6f869b;">Enter hours &rarr; save &rarr; recalculate</span>
                            </div>
                            <form method="POST" action="{{ route('companies.payroll.runs.hours-worksheet', [$company, $run]) }}">
                                @csrf @method('PATCH')
                                <div style="overflow-x:auto;">
                                    <table class="reg-table">
                                        <thead>
                                            <tr>
                                                <th>Employee</th>
                                                <th>Hourly Rate</th>
                                                <th class="amt">Current Gross</th>
                                                <th style="width:120pt;">Hours Worked</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($payslips as $i => $payslip)
                                                <input type="hidden" name="hours[{{ $i }}][id]" value="{{ $payslip->id }}">
                                                <tr>
                                                    <td>
                                                        <span style="font-weight:700;">{{ $payslip->employee->full_name }}</span><br>
                                                        <span style="font-size:6pt;color:#6f869b;font-family:'DejaVu Sans Mono',monospace;">{{ $payslip->employee->employee_number }}</span>
                                                    </td>
                                                    <td style="color:#5a7186;">R {{ number_format($payslip->employee->hourly_rate, 2) }}/hr</td>
                                                    <td class="amt" style="font-weight:700;color:#15803d;">R {{ number_format($payslip->gross_earnings, 2) }}</td>
                                                    <td>
                                                        <input type="number"
                                                            name="hours[{{ $i }}][worked]"
                                                            value="{{ $payslip->hours_worked ?? '' }}"
                                                            step="0.25" min="0" max="744" placeholder="0.00"
                                                            style="width:100%;border:0.5pt solid #9ec1f5;padding:3pt 5pt;font-size:7pt;font-family:inherit;outline:none;box-sizing:border-box;color:#191919;"
                                                            onfocus="this.style.borderColor='#1a345b'" onblur="this.style.borderColor='#9ec1f5'">
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div style="margin-top:6pt;display:flex;gap:6pt;align-items:center;">
                                    <button class="reg-btn primary" type="submit">Save Hours &amp; Recalculate</button>
                                    <span style="font-size:6pt;color:#6f869b;">Supports quarter-hour increments (0.25).</span>
                                </div>
                            </form>
                        @endif

                        {{-- Payslips table --}}
                        <div class="reg-section-header" style="margin-top:8pt;">
                            Individual Payslips
                            <span style="float:right;font-size:5.5pt;font-weight:400;text-transform:none;letter-spacing:0;color:#6f869b;">{{ $payslips->count() }} payslip{{ $payslips->count() !== 1 ? 's' : '' }}</span>
                        </div>
                        <div style="overflow-x:auto;">
                            <table class="reg-table">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Pay Date</th>
                                        <th class="amt">Gross</th>
                                        <th class="amt">PAYE</th>
                                        <th class="amt">UIF</th>
                                        <th class="amt">Other Ded.</th>
                                        <th class="amt">Net Pay</th>
                                        <th class="amt">Employer Cost</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($payslips as $payslip)
                                        <tr>
                                            <td>
                                                <span style="font-weight:700;">{{ $payslip->employee->full_name }}</span>
                                                @if ($payslip->is_adjusted)
                                                    <span class="reg-status" style="color:#854d0e;border-color:#854d0e;background:#fef9c3;font-size:5pt;margin-left:2pt;">Adj</span>
                                                @endif
                                                <br>
                                                <span style="font-size:6pt;color:#6f869b;font-family:'DejaVu Sans Mono',monospace;">{{ $payslip->employee->employee_number }}</span>
                                            </td>
                                            <td class="dim">
                                                {{ $payslip->pay_date ? $payslip->pay_date->format('d M Y') : '---' }}
                                            </td>
                                            <td class="amt">R {{ number_format($payslip->gross_earnings, 2) }}</td>
                                            <td class="amt" style="color:#dc2626;">R {{ number_format($payslip->paye, 2) }}</td>
                                            <td class="amt" style="color:#dc2626;">R {{ number_format($payslip->uif_employee, 2) }}</td>
                                            <td class="amt" style="color:#dc2626;">R {{ number_format($payslip->other_deductions, 2) }}</td>
                                            <td class="amt" style="font-weight:700;color:#15803d;">R {{ number_format($payslip->net_pay, 2) }}</td>
                                            <td class="amt">R {{ number_format($payslip->total_employer_cost, 2) }}</td>
                                            <td class="amt">
                                                <a href="{{ route('companies.payroll.payslip.pdf', [$company, $run, $payslip]) }}" class="reg-link" target="_blank">PDF</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="2">Total</td>
                                        <td class="amt">R {{ number_format($run->total_gross_earnings, 2) }}</td>
                                        <td class="amt" style="color:#dc2626;">R {{ number_format($run->total_paye, 2) }}</td>
                                        <td class="amt" style="color:#dc2626;">R {{ number_format($run->total_uif_employee, 2) }}</td>
                                        <td class="amt" style="color:#dc2626;">R {{ number_format($run->total_other_deductions, 2) }}</td>
                                        <td class="amt" style="color:#15803d;">R {{ number_format($run->total_net_pay, 2) }}</td>
                                        <td class="amt">R {{ number_format($run->total_employer_cost, 2) }}</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        {{-- Per-payslip detail accordion --}}
                        <div class="reg-section-header" style="margin-top:8pt;">
                            Payslip Detail
                            <span style="float:right;font-size:5.5pt;font-weight:400;text-transform:none;letter-spacing:0;color:#6f869b;">Click a row to expand</span>
                        </div>

                        @foreach ($payslips as $payslip)
                            <div style="border:0.5pt solid #9ec1f5;margin-bottom:4pt;" x-data="{ open: false }">
                                <div style="padding:5pt 7pt;display:flex;justify-content:space-between;align-items:center;cursor:pointer;user-select:none;background:#f4fafc;"
                                    @click="open = !open">
                                    <div>
                                        <span style="font-weight:700;font-size:7pt;">{{ $payslip->employee->full_name }}</span>
                                        <span style="font-size:6pt;color:#6f869b;font-family:'DejaVu Sans Mono',monospace;margin-left:4pt;">{{ $payslip->employee->employee_number }}</span>
                                        @if ($payslip->is_adjusted)
                                            <span class="reg-status" style="color:#854d0e;border-color:#854d0e;background:#fef9c3;font-size:5pt;margin-left:3pt;">Adjusted</span>
                                        @endif
                                    </div>
                                    <div style="display:flex;align-items:center;gap:8pt;">
                                        <span style="font-size:6.5pt;color:#6f869b;">Net: <strong style="color:#15803d;">R {{ number_format($payslip->net_pay, 2) }}</strong></span>
                                        <svg width="10" height="10" fill="none" stroke="#6f869b" stroke-width="2" viewBox="0 0 24 24"
                                            :style="open ? 'transform:rotate(180deg);transition:transform 0.2s' : 'transition:transform 0.2s'">
                                            <polyline points="6 9 12 15 18 9"/>
                                        </svg>
                                    </div>
                                </div>

                                <div x-show="open" x-cloak style="border-top:0.5pt solid #9ec1f5;">
                                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8pt;padding:8pt 7pt;">

                                        <div>
                                            <p class="detail-section-title">Earnings</p>
                                            @foreach ($payslip->earningLines as $line)
                                                <div class="detail-row"><span>{{ $line->description }}</span><span>R {{ number_format($line->amount, 2) }}</span></div>
                                            @endforeach
                                            <div class="detail-row" style="font-weight:700;border-top:0.5pt solid #1a345b;margin-top:2pt;padding-top:2pt;">
                                                <span>Gross Earnings</span><span>R {{ number_format($payslip->gross_earnings, 2) }}</span>
                                            </div>
                                        </div>

                                        <div>
                                            <p class="detail-section-title">Deductions</p>
                                            @foreach ($payslip->deductionLines as $line)
                                                <div class="detail-row">
                                                    <span>{{ $line->description }}</span>
                                                    <span style="color:#dc2626;">(R {{ number_format($line->amount, 2) }})</span>
                                                </div>
                                            @endforeach
                                            <div class="detail-row" style="font-weight:700;border-top:0.5pt solid #1a345b;margin-top:2pt;padding-top:2pt;">
                                                <span>Total Deductions</span>
                                                <span style="color:#dc2626;">(R {{ number_format($payslip->total_deductions, 2) }})</span>
                                            </div>
                                            <div class="detail-row" style="font-weight:800;font-size:7pt;padding-top:3pt;">
                                                <span>Net Pay</span><span style="color:#15803d;">R {{ number_format($payslip->net_pay, 2) }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Adjust form (draft only) --}}
                                    @if (! $run->isPosted())
                                        <div style="border-top:0.5pt solid #9ec1f5;padding:6pt 7pt;background:#f4fafc;">
                                            <p class="detail-section-title" style="margin-bottom:4pt;">Adjust This Payslip</p>
                                            <form method="POST" action="{{ route('companies.payroll.payslip.adjust', [$company, $run, $payslip]) }}">
                                                @csrf @method('PATCH')
                                                <div style="display:flex;gap:6pt;flex-wrap:wrap;align-items:flex-end;">
                                                    @if ($payslip->employee->isHourly())
                                                        <div class="af-field">
                                                            <label>Hours Worked</label>
                                                            <input type="number" name="hours_worked"
                                                                value="{{ $payslip->hours_worked ?? '' }}"
                                                                step="0.25" min="0" placeholder="0.00"
                                                                style="width:90pt;">
                                                        </div>
                                                    @endif
                                                    <div class="af-field">
                                                        <label>Override Gross (R) <span style="font-weight:400;text-transform:none;letter-spacing:0;">(leave blank for standard calc)</span></label>
                                                        <input type="number" name="override_gross_earnings"
                                                            value="{{ $payslip->override_gross_earnings ?? '' }}"
                                                            step="0.01" min="0" placeholder="e.g. 18500.00"
                                                            style="width:120pt;">
                                                    </div>
                                                    <button class="reg-btn primary" type="submit">Recalculate</button>
                                                </div>
                                                <p style="font-size:5.5pt;color:#6f869b;margin:3pt 0 0;">PAYE, UIF and SDL recalculate automatically on save.</p>
                                            </form>
                                        </div>
                                    @endif

                                    <div style="padding:4pt 7pt;border-top:0.5pt solid #9ec1f5;text-align:right;">
                                        <a href="{{ route('companies.payroll.payslip.pdf', [$company, $run, $payslip]) }}"
                                            class="reg-btn" target="_blank">Download Payslip PDF</a>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                    </div>
                </div>

            </main>
        </div>
    </div>
@endsection

@push('scripts')
<script src="//unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@endpush
