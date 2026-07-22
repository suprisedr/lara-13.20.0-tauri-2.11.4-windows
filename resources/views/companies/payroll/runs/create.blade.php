@extends('layouts.public')

@section('title', 'New Payroll Run — ' . $company->registered_name)
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .inv-mgmt-bar { display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; margin-bottom:1.5rem; }
        .inv-mgmt-bar a { font-size:0.78rem; color:#6b7280; text-decoration:none; display:inline-flex; align-items:center; gap:0.3rem; transition:color 0.15s; }
        .inv-mgmt-bar a:hover { color:#000; }
        .mgmt-btn { display:inline-flex; align-items:center; gap:0.4rem; background:#fff; border:1px solid #000; color:#000; font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; padding:0.4rem 0.85rem; text-decoration:none; cursor:pointer; font-family:inherit; transition:background 0.15s,color 0.15s; }
        .mgmt-btn:hover { background:#000; color:#fff; }
        .mgmt-btn.primary { background:#000; color:#fff; }
        .mgmt-btn.primary:hover { background:#333; }
        .mgmt-btn.ghost { border-color:#e5e7eb; color:#555; }
        .mgmt-btn.ghost:hover { background:#f3f4f6; color:#000; }

        .cust-doc { background:#fff; border:1px solid #ddd; font-family:'DejaVu Sans',Helvetica,Arial,sans-serif; color:#000; font-size:0.78rem; line-height:1.45; }
        .cust-doc-body { padding:2rem 2.25rem; }

        .section-header { border-top:2px solid #000; margin:1.75rem 0 0.85rem; padding-top:0.35rem; }
        .section-header-title { font-size:0.62rem; font-weight:800; text-transform:uppercase; letter-spacing:0.12em; color:#000; }

        .af-row { display:grid; grid-template-columns:repeat(auto-fill,minmax(210px,1fr)); gap:0.75rem 1.5rem; margin-bottom:0.85rem; }
        .af-field label { display:block; font-size:0.62rem; font-weight:700; text-transform:uppercase; letter-spacing:0.09em; color:#888; margin-bottom:0.3rem; }
        .af-field input, .af-field select, .af-field textarea { width:100%; box-sizing:border-box; border:1.5px solid #e5e7eb; padding:0.45rem 0.65rem; font-size:0.82rem; font-family:inherit; color:#1b1b18; background:#fff; outline:none; transition:border-color 0.15s; }
        .af-field input:focus, .af-field select:focus, .af-field textarea:focus { border-color:#000; }

        /* Run type selector cards */
        .run-type-card { flex:1; min-width:185px; cursor:pointer; }
        .run-type-inner { padding:1rem 1.1rem; border:2px solid #e5e7eb; background:#fff; transition:all 0.15s; }
        .run-type-inner.selected { border-color:#000; background:#f9fafb; }
        .run-type-label { font-size:0.62rem; font-weight:800; text-transform:uppercase; letter-spacing:0.1em; margin-bottom:0.3rem; color:#888; }
        .run-type-label.selected { color:#000; }
        .run-type-count { font-size:0.9rem; font-weight:800; color:#1b1b18; }
        .run-type-desc { font-size:0.7rem; color:#888; margin-top:0.2rem; }
    </style>
@endpush

@section('content')
    <div class="co-wrap">
        @include('companies._topbar')

        <div class="co-body">
            @include('companies._sidebar')

            <main class="co-main">

                {{-- Management bar --}}
                <div class="inv-mgmt-bar">
                    <a href="{{ route('companies.payroll.runs.index', $company) }}">
                        &larr; Back to Payroll Runs
                    </a>
                </div>

                @if ($errors->any())
                    <div style="background:#fee2e2;border:1px solid #fca5a5;color:#b91c1c;padding:0.75rem 1rem;font-size:0.8rem;margin-bottom:1.25rem;">
                        <strong>Please fix the following errors:</strong>
                        <ul style="margin:0.4rem 0 0 1.1rem;padding:0;">
                            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('companies.payroll.runs.store', $company) }}"
                    x-data="{
                        runType: '{{ old('employee_type', 'salary') }}',
                        payFreq: '{{ old('pay_frequency', 'monthly') }}',
                        counts: {{ Js::from($frequencyCounts) }},
                        getCount() {
                            return this.counts?.[this.runType]?.[this.payFreq] ?? 0;
                        }
                    }">
                    @csrf

                    <div class="cust-doc">
                        <div class="cust-doc-body">

                            {{-- Document heading --}}
                            <div style="font-size:1.1rem;font-weight:800;letter-spacing:-0.01em;margin-bottom:0.2rem;">New Payroll Run</div>
                            <div style="font-size:0.72rem;color:#555;">{{ $company->registered_name }}</div>

                            {{-- Run Type --}}
                            <div class="section-header"><span class="section-header-title">Run Type</span></div>
                            <p style="font-size:0.72rem;color:#666;margin:0 0 0.85rem;">
                                Salary and hourly runs are processed separately. Hourly runs require hours to be entered on the worksheet before calculation.
                            </p>
                            <div style="display:flex;gap:0.75rem;flex-wrap:wrap;margin-bottom:0.25rem;">
                                <label class="run-type-card">
                                    <input type="radio" name="employee_type" value="salary" x-model="runType" style="display:none;">
                                    <div class="run-type-inner" :class="runType === 'salary' ? 'selected' : ''">
                                        <div class="run-type-label" :class="runType === 'salary' ? 'selected' : ''">Salary Run</div>
                                        <div class="run-type-count">{{ $salaryCount }} employee{{ $salaryCount !== 1 ? 's' : '' }}</div>
                                        <div class="run-type-desc">Fixed pay — calculated automatically on create.</div>
                                    </div>
                                </label>
                                <label class="run-type-card">
                                    <input type="radio" name="employee_type" value="hourly" x-model="runType" style="display:none;">
                                    <div class="run-type-inner" :class="runType === 'hourly' ? 'selected' : ''">
                                        <div class="run-type-label" :class="runType === 'hourly' ? 'selected' : ''">Hourly Run</div>
                                        <div class="run-type-count">{{ $hourlyCount }} employee{{ $hourlyCount !== 1 ? 's' : '' }}</div>
                                        <div class="run-type-desc">Hours entered via worksheet after the run is created.</div>
                                    </div>
                                </label>
                            </div>

                            {{-- Pay Frequency --}}
                            <div class="section-header"><span class="section-header-title">Pay Frequency</span></div>
                            <p style="font-size:0.72rem;color:#666;margin:0 0 0.85rem;">
                                Only employees matching this frequency will be included in the run.
                                <span style="font-weight:700;" x-text="getCount() + ' employee' + (getCount() !== 1 ? 's' : '') + ' match'"></span>
                            </p>
                            <div style="display:flex;gap:0.75rem;flex-wrap:wrap;margin-bottom:0.25rem;">
                                <label class="run-type-card">
                                    <input type="radio" name="pay_frequency" value="monthly" x-model="payFreq" style="display:none;">
                                    <div class="run-type-inner" :class="payFreq === 'monthly' ? 'selected' : ''">
                                        <div class="run-type-label" :class="payFreq === 'monthly' ? 'selected' : ''">Monthly</div>
                                        <div class="run-type-desc">Paid once per month on a set day.</div>
                                    </div>
                                </label>
                                <label class="run-type-card">
                                    <input type="radio" name="pay_frequency" value="fortnightly" x-model="payFreq" style="display:none;">
                                    <div class="run-type-inner" :class="payFreq === 'fortnightly' ? 'selected' : ''">
                                        <div class="run-type-label" :class="payFreq === 'fortnightly' ? 'selected' : ''">Fortnightly</div>
                                        <div class="run-type-desc">Paid every 14 days from anchor date.</div>
                                    </div>
                                </label>
                                <label class="run-type-card">
                                    <input type="radio" name="pay_frequency" value="weekly" x-model="payFreq" style="display:none;">
                                    <div class="run-type-inner" :class="payFreq === 'weekly' ? 'selected' : ''">
                                        <div class="run-type-label" :class="payFreq === 'weekly' ? 'selected' : ''">Weekly</div>
                                        <div class="run-type-desc">Paid every 7 days from anchor date.</div>
                                    </div>
                                </label>
                            </div>

                            {{-- Pay Period --}}
                            <div class="section-header"><span class="section-header-title">Pay Period</span></div>
                            <div class="af-row">
                                <div class="af-field">
                                    <label>Period Start</label>
                                    <input type="date" name="period_start" value="{{ old('period_start') }}" required>
                                </div>
                                <div class="af-field">
                                    <label>Period End</label>
                                    <input type="date" name="period_end" value="{{ old('period_end') }}" required>
                                </div>
                            </div>
                            <div class="af-row" style="grid-template-columns:1fr;">
                                <div class="af-field">
                                    <label>Notes <span style="font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span></label>
                                    <input type="text" name="notes" value="{{ old('notes') }}" placeholder="e.g. June 2026 salary run">
                                </div>
                            </div>

                            {{-- Submit --}}
                            <div style="border-top:2px solid #000;margin-top:1.75rem;padding-top:1rem;display:flex;gap:0.6rem;align-items:center;">
                                <button class="mgmt-btn primary" type="submit">
                                    <span x-text="runType === 'hourly' ? 'Create Run → Enter Hours' : 'Calculate &amp; Create Run'">Calculate &amp; Create Run</span>
                                </button>
                                <a href="{{ route('companies.payroll.runs.index', $company) }}" class="mgmt-btn ghost">Cancel</a>
                            </div>

                        </div>{{-- /cust-doc-body --}}
                    </div>{{-- /cust-doc --}}

                </form>

            </main>
        </div>
    </div>
@endsection

@push('scripts')
<script src="//unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@endpush
