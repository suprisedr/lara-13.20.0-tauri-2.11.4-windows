@extends('layouts.public')

@section('title', 'Payroll Run — ' . $run->period_label)
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        /* ── Management bar ─────────────────────── */
        .inv-mgmt-bar { display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; margin-bottom:1.5rem; }
        .inv-mgmt-bar a { font-size:0.78rem; color:#6b7280; text-decoration:none; display:inline-flex; align-items:center; gap:0.3rem; transition:color 0.15s; }
        .inv-mgmt-bar a:hover { color:#000; }
        .mgmt-btn { display:inline-flex; align-items:center; gap:0.4rem; background:#fff; border:1px solid #000; color:#000; font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; padding:0.4rem 0.85rem; text-decoration:none; cursor:pointer; font-family:inherit; transition:background 0.15s,color 0.15s; }
        .mgmt-btn:hover { background:#000; color:#fff; }
        .mgmt-btn.primary { background:#000; color:#fff; }
        .mgmt-btn.primary:hover { background:#333; }
        .mgmt-btn.danger { border-color:#dc2626; color:#dc2626; }
        .mgmt-btn.danger:hover { background:#dc2626; color:#fff; }
        .mgmt-btn.ghost { border-color:#e5e7eb; color:#555; }
        .mgmt-btn.ghost:hover { background:#f3f4f6; }

        /* ── Document shell ─────────────────────── */
        .cust-doc { background:#fff; border:1px solid #ddd; font-family:'DejaVu Sans',Helvetica,Arial,sans-serif; color:#000; font-size:0.78rem; line-height:1.45; }
        .cust-doc-body { padding:2rem 2.25rem; }

        /* ── Section headers ────────────────────── */
        .section-header { border-top:2px solid #000; margin:1.75rem 0 0.85rem; padding-top:0.35rem; display:flex; justify-content:space-between; align-items:baseline; }
        .section-header-title { font-size:0.62rem; font-weight:800; text-transform:uppercase; letter-spacing:0.12em; color:#000; }
        .section-header-sub { font-size:0.6rem; color:#888; }

        /* ── Summary chips ──────────────────────── */
        .run-chips { display:grid; grid-template-columns:repeat(auto-fill,minmax(130px,1fr)); gap:0.6rem; margin-bottom:0.5rem; }
        .run-chip { border:1px solid #e5e7eb; padding:0.55rem 0.75rem; }
        .run-chip-label { font-size:0.58rem; font-weight:700; text-transform:uppercase; letter-spacing:0.09em; color:#888; margin-bottom:0.2rem; }
        .run-chip-value { font-size:0.85rem; font-weight:700; color:#1b1b18; }

        /* ── Journal table ──────────────────────── */
        .jnl-table { width:100%; border-collapse:collapse; font-size:0.76rem; }
        .jnl-table thead tr { background:#f9fafb; border-bottom:1px solid #e5e7eb; }
        .jnl-table thead th { padding:0.4rem 0.75rem; font-size:0.62rem; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:#888; text-align:left; }
        .jnl-table thead th:last-child { text-align:right; }
        .jnl-table tbody td { padding:0.42rem 0.75rem; border-bottom:1px solid #f3f4f6; }
        .jnl-table tfoot td { padding:0.42rem 0.75rem; font-weight:700; border-top:2px solid #e5e7eb; }
        .dr-badge { font-size:0.6rem; font-weight:700; background:#fef9c3; color:#854d0e; padding:0.1rem 0.35rem; }
        .cr-badge { font-size:0.6rem; font-weight:700; background:#dcfce7; color:#15803d; padding:0.1rem 0.35rem; }

        /* ── Payslips table ─────────────────────── */
        .cust-items-table { width:100%; border-collapse:collapse; font-size:0.77rem; }
        .cust-items-table thead tr { border-bottom:2px solid #000; }
        .cust-items-table thead th { padding:0.35rem 0.5rem; font-size:0.6rem; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:#888; text-align:left; }
        .cust-items-table thead th:not(:first-child) { text-align:right; }
        .cust-items-table tbody td { padding:0.45rem 0.5rem; border-bottom:1px solid #f3f4f6; }
        .cust-items-table tbody td:not(:first-child) { text-align:right; }
        .cust-items-table tfoot td { padding:0.45rem 0.5rem; font-weight:700; border-top:2px solid #000; }
        .cust-items-table tfoot td:not(:first-child) { text-align:right; }

        /* ── Payslip detail accordion ───────────── */
        .tax-row { font-size:0.7rem; color:#555; display:flex; justify-content:space-between; padding:0.22rem 0; border-bottom:1px solid #f3f4f6; }
        .tax-row:last-child { border-bottom:none; }
        .tax-section-title { font-size:0.58rem; font-weight:700; text-transform:uppercase; letter-spacing:0.1em; color:#5e17eb; margin:0 0 0.35rem; }

        /* ── Hours worksheet ────────────────────── */
        .ws-table { width:100%; border-collapse:collapse; font-size:0.8rem; }
        .ws-table th { padding:0.4rem 1rem; font-size:0.62rem; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:#888; border-bottom:1px solid #e5e7eb; text-align:left; background:#f9fafb; }
        .ws-table td { padding:0.55rem 1rem; border-bottom:1px solid #f9fafb; }
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

                {{-- Action bar --}}
                <div class="inv-mgmt-bar">
                    <div style="display:flex;align-items:center;gap:0.6rem;flex-wrap:wrap;">
                        @if ($run->employee_type === 'salary')
                            <span style="font-size:0.68rem;font-weight:700;background:#f0fdf4;color:#15803d;padding:0.18rem 0.55rem;border:1px solid #bbf7d0;">Salary Run</span>
                        @elseif ($run->employee_type === 'hourly')
                            <span style="font-size:0.68rem;font-weight:700;background:#fef9c3;color:#854d0e;padding:0.18rem 0.55rem;border:1px solid #fde68a;">Hourly Run</span>
                        @endif
                        @if ($run->isPosted())
                            <span style="font-size:0.68rem;font-weight:700;background:#dcfce7;color:#15803d;padding:0.18rem 0.55rem;border:1px solid #bbf7d0;">Finalised</span>
                        @else
                            <span style="font-size:0.68rem;font-weight:700;background:#f3f4f6;color:#555;padding:0.18rem 0.55rem;border:1px solid #e5e7eb;">Draft</span>
                        @endif
                    </div>
                    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;align-items:center;">
                        @if (! $run->isPosted())
                            <form method="POST" action="{{ route('companies.payroll.runs.recalculate', [$company, $run]) }}">
                                @csrf
                                <button class="mgmt-btn ghost" type="submit">Recalculate</button>
                            </form>
                            <form method="POST" action="{{ route('companies.payroll.runs.post', [$company, $run]) }}"
                                onsubmit="return false" data-confirm-label="Payroll" data-confirm-title="Finalise Payroll Run" data-confirm-body="The payroll run will be locked. Journal entries are shown for manual posting — nothing will be automatically posted to accounting." data-confirm-text="Finalise">
                                @csrf
                                <button class="mgmt-btn primary" type="submit">Finalise Run</button>
                            </form>
                            <form method="POST" action="{{ route('companies.payroll.runs.destroy', [$company, $run]) }}"
                                onsubmit="return false" data-confirm-label="Payroll" data-confirm-title="Delete Payroll Run" data-confirm-body="This payroll run and all its payslips will be permanently deleted." data-confirm-text="Delete" data-confirm-danger="1">
                                @csrf @method('DELETE')
                                <button class="mgmt-btn danger" type="submit">Delete</button>
                            </form>
                        @else
                            <a href="{{ route('companies.payroll.emp201', [$company, $run]) }}" class="mgmt-btn">EMP201</a>
                        @endif
                    </div>
                </div>

                {{-- Document shell --}}
                <div class="cust-doc">
                    <div class="cust-doc-body">

                        {{-- Run header --}}
                        <table style="width:100%;border-collapse:collapse;margin-bottom:0.5rem;">
                            <tr>
                                <td style="vertical-align:top;padding-right:1.5rem;">
                                    <div style="font-size:1.1rem;font-weight:800;letter-spacing:-0.01em;margin-bottom:0.15rem;">
                                        {{ $run->employee_type === 'hourly' ? 'Hourly' : 'Salary' }} Payroll Run
                                    </div>
                                    <div style="font-size:0.72rem;color:#555;">
                                        {{ $company->registered_name }}
                                    </div>
                                </td>
                                <td style="text-align:right;vertical-align:top;font-size:0.72rem;color:#555;">
                                    <div><strong>Period:</strong> {{ $run->period_label }}</div>
                                    @if ($run->notes)
                                        <div style="margin-top:0.3rem;font-style:italic;">{{ $run->notes }}</div>
                                    @endif
                                </td>
                            </tr>
                        </table>

                        {{-- Summary chips --}}
                        <div class="section-header">
                            <span class="section-header-title">Run Summary</span>
                            <span class="section-header-sub">{{ $payslips->count() }} employee{{ $payslips->count() !== 1 ? 's' : '' }}</span>
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
                            <div class="run-chip" style="border-color:#000;">
                                <div class="run-chip-label">Total Employer Cost</div>
                                <div class="run-chip-value">R {{ number_format($run->total_employer_cost, 2) }}</div>
                            </div>
                        </div>

                        {{-- Journal Entry Reference --}}
                        <div class="section-header">
                            <span class="section-header-title">Journal Entry Reference</span>
                            <span class="section-header-sub">Post manually to accounting — see each employee's pay date on their payslip</span>
                        </div>
                        <div style="background:#fffbeb;border:1px solid #fde68a;padding:0.6rem 0.85rem;font-size:0.72rem;color:#78350f;margin-bottom:0.75rem;">
                            These entries are for reference only. Post them to your accounting system manually using the accounts shown below.
                        </div>
                        @php $debitLines = collect($journalLines)->where('type', 'debit')->values(); @endphp
                        @if ($debitLines->isNotEmpty())
                            <table class="jnl-table">
                                <thead>
                                    <tr>
                                        <th>Description</th>
                                        <th style="text-align:right;width:130px;">Amount (R)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($debitLines as $line)
                                        <tr>
                                            <td>{{ $line['description'] }}</td>
                                            <td style="text-align:right;font-family:monospace;">{{ number_format($line['amount'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td style="font-size:0.7rem;color:#888;">Total payroll cost</td>
                                        <td style="text-align:right;font-family:monospace;">{{ number_format($debitLines->sum('amount'), 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        @else
                            <p style="font-size:0.78rem;color:#888;">No payslips yet — calculate the run first.</p>
                        @endif

                        {{-- Hours Worksheet (hourly runs, draft only) --}}
                        @if ($run->employee_type === 'hourly' && ! $run->isPosted())
                            <div class="section-header">
                                <span class="section-header-title">Hours Worksheet</span>
                                <span class="section-header-sub">Enter hours &rarr; save &rarr; recalculate</span>
                            </div>
                            <form method="POST" action="{{ route('companies.payroll.runs.hours-worksheet', [$company, $run]) }}">
                                @csrf @method('PATCH')
                                <table class="ws-table">
                                    <thead>
                                        <tr>
                                            <th>Employee</th>
                                            <th>Hourly Rate</th>
                                            <th style="text-align:right;">Current Gross</th>
                                            <th style="width:170px;">Hours Worked</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($payslips as $i => $payslip)
                                            <input type="hidden" name="hours[{{ $i }}][id]" value="{{ $payslip->id }}">
                                            <tr>
                                                <td>
                                                    <span style="font-weight:600;">{{ $payslip->employee->full_name }}</span><br>
                                                    <span style="font-size:0.68rem;color:#888;font-family:monospace;">{{ $payslip->employee->employee_number }}</span>
                                                </td>
                                                <td style="color:#555;">R {{ number_format($payslip->employee->hourly_rate, 2) }}/hr</td>
                                                <td style="text-align:right;font-weight:600;color:#15803d;">R {{ number_format($payslip->gross_earnings, 2) }}</td>
                                                <td>
                                                    <input type="number"
                                                        name="hours[{{ $i }}][worked]"
                                                        value="{{ $payslip->hours_worked ?? '' }}"
                                                        step="0.25" min="0" max="744" placeholder="0.00"
                                                        style="width:100%;border:1.5px solid #e5e7eb;padding:0.38rem 0.6rem;font-size:0.82rem;font-family:inherit;outline:none;box-sizing:border-box;"
                                                        onfocus="this.style.borderColor='#000'" onblur="this.style.borderColor='#e5e7eb'">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <div style="margin-top:0.75rem;display:flex;gap:0.75rem;align-items:center;">
                                    <button class="mgmt-btn primary" type="submit">Save Hours &amp; Recalculate</button>
                                    <span style="font-size:0.7rem;color:#888;">Supports quarter-hour increments (0.25).</span>
                                </div>
                            </form>
                        @endif

                        {{-- Payslips table --}}
                        <div class="section-header">
                            <span class="section-header-title">Individual Payslips</span>
                            <span class="section-header-sub">{{ $payslips->count() }} payslip{{ $payslips->count() !== 1 ? 's' : '' }}</span>
                        </div>
                        <table class="cust-items-table">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Pay Date</th>
                                    <th>Gross</th>
                                    <th>PAYE</th>
                                    <th>UIF</th>
                                    <th>Other Ded.</th>
                                    <th>Net Pay</th>
                                    <th>Employer Cost</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($payslips as $payslip)
                                    <tr>
                                        <td>
                                            <span style="font-weight:600;">{{ $payslip->employee->full_name }}</span>
                                            @if ($payslip->is_adjusted)
                                                <span style="background:#fef9c3;color:#854d0e;font-size:0.58rem;padding:0.08rem 0.35rem;font-weight:700;margin-left:0.3rem;">Adj</span>
                                            @endif
                                            <br>
                                            <span style="font-size:0.68rem;color:#888;font-family:monospace;">{{ $payslip->employee->employee_number }}</span>
                                        </td>
                                        <td style="font-size:0.75rem;color:#555;">
                                            {{ $payslip->pay_date ? $payslip->pay_date->format('d M Y') : '—' }}
                                        </td>
                                        <td>R {{ number_format($payslip->gross_earnings, 2) }}</td>
                                        <td style="color:#dc2626;">R {{ number_format($payslip->paye, 2) }}</td>
                                        <td style="color:#dc2626;">R {{ number_format($payslip->uif_employee, 2) }}</td>
                                        <td style="color:#dc2626;">R {{ number_format($payslip->other_deductions, 2) }}</td>
                                        <td style="font-weight:700;color:#15803d;">R {{ number_format($payslip->net_pay, 2) }}</td>
                                        <td>R {{ number_format($payslip->total_employer_cost, 2) }}</td>
                                        <td>
                                            <a href="{{ route('companies.payroll.payslip.pdf', [$company, $run, $payslip]) }}"
                                                style="font-size:0.7rem;color:#5e17eb;font-weight:600;text-decoration:none;" target="_blank">PDF</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="2">Total</td>
                                    <td>R {{ number_format($run->total_gross_earnings, 2) }}</td>
                                    <td style="color:#dc2626;">R {{ number_format($run->total_paye, 2) }}</td>
                                    <td style="color:#dc2626;">R {{ number_format($run->total_uif_employee, 2) }}</td>
                                    <td style="color:#dc2626;">R {{ number_format($run->total_other_deductions, 2) }}</td>
                                    <td style="color:#15803d;">R {{ number_format($run->total_net_pay, 2) }}</td>
                                    <td>R {{ number_format($run->total_employer_cost, 2) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>

                        {{-- Per-payslip detail accordion --}}
                        <div class="section-header" style="margin-top:2rem;">
                            <span class="section-header-title">Payslip Detail</span>
                            <span class="section-header-sub">Click a row to expand</span>
                        </div>

                        @foreach ($payslips as $payslip)
                            <div style="border:1px solid #e5e7eb;margin-bottom:0.5rem;" x-data="{ open: false }">
                                <div style="padding:0.75rem 1rem;display:flex;justify-content:space-between;align-items:center;cursor:pointer;user-select:none;background:#fafafa;"
                                    @click="open = !open">
                                    <div>
                                        <span style="font-weight:700;font-size:0.85rem;">{{ $payslip->employee->full_name }}</span>
                                        <span style="font-size:0.68rem;color:#888;font-family:monospace;margin-left:0.5rem;">{{ $payslip->employee->employee_number }}</span>
                                        @if ($payslip->is_adjusted)
                                            <span style="background:#fef9c3;color:#854d0e;font-size:0.6rem;padding:0.08rem 0.35rem;font-weight:700;margin-left:0.35rem;">Adjusted</span>
                                        @endif
                                    </div>
                                    <div style="display:flex;align-items:center;gap:1.25rem;">
                                        <span style="font-size:0.75rem;color:#888;">Net: <strong style="color:#15803d;">R {{ number_format($payslip->net_pay, 2) }}</strong></span>
                                        <svg width="13" height="13" fill="none" stroke="#888" stroke-width="2" viewBox="0 0 24 24"
                                            :style="open ? 'transform:rotate(180deg);transition:transform 0.2s' : 'transition:transform 0.2s'">
                                            <polyline points="6 9 12 15 18 9"/>
                                        </svg>
                                    </div>
                                </div>

                                <div x-show="open" x-cloak style="border-top:1px solid #e5e7eb;">
                                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.25rem;padding:1.25rem 1rem;">

                                        <div>
                                            <p class="tax-section-title">Earnings</p>
                                            @foreach ($payslip->earningLines as $line)
                                                <div class="tax-row"><span>{{ $line->description }}</span><span>R {{ number_format($line->amount, 2) }}</span></div>
                                            @endforeach
                                            <div class="tax-row" style="font-weight:700;border-top:2px solid #e5e7eb;margin-top:0.2rem;padding-top:0.3rem;">
                                                <span>Gross Earnings</span><span>R {{ number_format($payslip->gross_earnings, 2) }}</span>
                                            </div>
                                        </div>

                                        <div>
                                            <p class="tax-section-title">Deductions</p>
                                            @foreach ($payslip->deductionLines as $line)
                                                <div class="tax-row">
                                                    <span>{{ $line->description }}{{ $line->is_statutory ? ' *' : '' }}</span>
                                                    <span style="color:#dc2626;">(R {{ number_format($line->amount, 2) }})</span>
                                                </div>
                                            @endforeach
                                            <div class="tax-row" style="font-weight:700;border-top:2px solid #e5e7eb;margin-top:0.2rem;padding-top:0.3rem;">
                                                <span>Total Deductions</span>
                                                <span style="color:#dc2626;">(R {{ number_format($payslip->total_deductions, 2) }})</span>
                                            </div>
                                            <div class="tax-row" style="font-weight:800;font-size:0.82rem;padding-top:0.45rem;">
                                                <span>Net Pay</span><span style="color:#15803d;">R {{ number_format($payslip->net_pay, 2) }}</span>
                                            </div>
                                        </div>

                                        <div>
                                            <p class="tax-section-title">Employer Contributions</p>
                                            @foreach ($payslip->employerLines as $line)
                                                <div class="tax-row"><span>{{ $line->description }}</span><span>R {{ number_format($line->amount, 2) }}</span></div>
                                            @endforeach
                                            <div class="tax-row" style="font-weight:700;border-top:2px solid #e5e7eb;margin-top:0.2rem;padding-top:0.3rem;">
                                                <span>Total Employer Cost</span><span>R {{ number_format($payslip->total_employer_cost, 2) }}</span>
                                            </div>

                                            <p class="tax-section-title" style="margin-top:0.85rem;">PAYE Detail</p>
                                            <div class="tax-row"><span>Annual Equivalent Income</span><span>R {{ number_format($payslip->annual_equivalent_income, 2) }}</span></div>
                                            <div class="tax-row"><span>Taxable Income (after Sec 11F)</span><span>R {{ number_format($payslip->taxable_income, 2) }}</span></div>
                                            <div class="tax-row"><span>Annual Tax (before rebates)</span><span>R {{ number_format($payslip->annual_tax_before_rebates, 2) }}</span></div>
                                            <div class="tax-row"><span>Annual Tax (net of rebates)</span><span>R {{ number_format($payslip->annual_tax_after_rebates, 2) }}</span></div>
                                            <div class="tax-row"><span>Medical Aid Credit (monthly)</span><span style="color:#15803d;">R {{ number_format($payslip->medical_aid_credit_monthly, 2) }}</span></div>
                                            <div class="tax-row" style="font-weight:700;"><span>Monthly PAYE</span><span style="color:#dc2626;">R {{ number_format($payslip->paye, 2) }}</span></div>
                                        </div>
                                    </div>

                                    {{-- Adjust form (draft only) --}}
                                    @if (! $run->isPosted())
                                        <div style="border-top:1px solid #e5e7eb;padding:0.85rem 1rem;background:#f9fafb;">
                                            <p class="tax-section-title" style="margin-bottom:0.55rem;">Adjust This Payslip</p>
                                            <form method="POST" action="{{ route('companies.payroll.payslip.adjust', [$company, $run, $payslip]) }}">
                                                @csrf @method('PATCH')
                                                <div style="display:flex;gap:0.85rem;flex-wrap:wrap;align-items:flex-end;">
                                                    @if ($payslip->employee->isHourly())
                                                        <div>
                                                            <label style="display:block;font-size:0.62rem;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:#888;margin-bottom:0.25rem;">Hours Worked</label>
                                                            <input type="number" name="hours_worked"
                                                                value="{{ $payslip->hours_worked ?? '' }}"
                                                                step="0.25" min="0" placeholder="0.00"
                                                                style="border:1.5px solid #e5e7eb;padding:0.38rem 0.6rem;font-size:0.8rem;font-family:inherit;width:130px;outline:none;">
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <label style="display:block;font-size:0.62rem;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:#888;margin-bottom:0.25rem;">
                                                            Override Gross (R) <span style="font-weight:400;text-transform:none;letter-spacing:0;">(leave blank for standard calc)</span>
                                                        </label>
                                                        <input type="number" name="override_gross_earnings"
                                                            value="{{ $payslip->override_gross_earnings ?? '' }}"
                                                            step="0.01" min="0" placeholder="e.g. 18500.00"
                                                            style="border:1.5px solid #e5e7eb;padding:0.38rem 0.6rem;font-size:0.8rem;font-family:inherit;width:175px;outline:none;">
                                                    </div>
                                                    <button class="mgmt-btn primary" type="submit">Recalculate</button>
                                                </div>
                                                <p style="font-size:0.68rem;color:#999;margin:0.45rem 0 0;">PAYE, UIF and SDL recalculate automatically on save.</p>
                                            </form>
                                        </div>
                                    @endif

                                    <div style="padding:0.6rem 1rem;border-top:1px solid #e5e7eb;text-align:right;">
                                        <a href="{{ route('companies.payroll.payslip.pdf', [$company, $run, $payslip]) }}"
                                            class="mgmt-btn" target="_blank">Download Payslip PDF</a>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                    </div>{{-- /cust-doc-body --}}
                </div>{{-- /cust-doc --}}

            </main>
        </div>
    </div>
@endsection

@push('scripts')
<script src="//unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@endpush
