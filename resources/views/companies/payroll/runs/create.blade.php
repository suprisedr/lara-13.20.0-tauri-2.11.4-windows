@extends('layouts.public')

@section('title', 'New Payroll Run — ' . $company->registered_name)
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .run-type-card { flex:1; min-width:140pt; cursor:pointer; }
        .run-type-inner {
            padding:7pt 8pt;
            border:0.5pt solid #c4b5fd;
            background:#fff;
            transition:all 0.15s;
        }
        .run-type-inner.selected { border-color:#4c1d95; background:#f5f3ff; }
        .run-type-label {
            font-size:5.5pt;
            font-weight:700;
            text-transform:uppercase;
            letter-spacing:0.07em;
            color:#6b5b8a;
            margin-bottom:2pt;
        }
        .run-type-label.selected { color:#4c1d95; }
        .run-type-count { font-size:8pt; font-weight:800; color:#23282d; }
        .run-type-desc { font-size:6.5pt; color:#8b7aad; margin-top:1pt; }
    </style>
@endpush

@section('content')
    <div class="co-wrap">
        @include('companies._topbar')

        <div class="co-body">
            @include('companies._sidebar')

            <main class="co-main">

                {{-- Management bar --}}
                <div class="reg-mgmt-bar">
                    <a href="{{ route('companies.payroll.runs.index', $company) }}" class="reg-link">
                        &larr; Back to Payroll Runs
                    </a>
                </div>

                @if ($errors->any())
                    <div style="background:#fee2e2;border:1px solid #fca5a5;color:#b91c1c;padding:4pt 8pt;font-size:7pt;margin-bottom:8pt;">
                        <strong>Please fix the following errors:</strong>
                        <ul style="margin:3pt 0 0 8pt;padding:0;">
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

                    <div class="reg-doc">
                        <div class="reg-doc-body">

                            {{-- Letterhead --}}
                            <div class="afs-letterhead">
                                <div>
                                    <div class="afs-letterhead-name">New Payroll Run</div>
                                    <div class="afs-letterhead-meta">{{ $company->registered_name }}</div>
                                </div>
                            </div>

                            {{-- Run Type --}}
                            <div class="reg-section-header" style="margin-top:8pt;">Run Type</div>
                            <p style="font-size:6.5pt;color:#8b7aad;margin:0 0 6pt;">
                                Salary and hourly runs are processed separately. Hourly runs require hours to be entered on the worksheet before calculation.
                            </p>
                            <div style="display:flex;gap:6pt;flex-wrap:wrap;margin-bottom:2pt;">
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
                            <div class="reg-section-header" style="margin-top:8pt;">Pay Frequency</div>
                            <p style="font-size:6.5pt;color:#8b7aad;margin:0 0 6pt;">
                                Only employees matching this frequency will be included in the run.
                                <span style="font-weight:700;" x-text="getCount() + ' employee' + (getCount() !== 1 ? 's' : '') + ' match'"></span>
                            </p>
                            <div style="display:flex;gap:6pt;flex-wrap:wrap;margin-bottom:2pt;">
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
                            <div class="reg-section-header" style="margin-top:8pt;">Pay Period</div>
                            <div class="af-row" style="grid-template-columns:1fr 1fr;">
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
                            <div style="border-top:0.5pt solid #4c1d95;margin-top:10pt;padding-top:6pt;display:flex;gap:4pt;align-items:center;">
                                <button class="reg-btn primary" type="submit">
                                    <span x-text="runType === 'hourly' ? 'Create Run → Enter Hours' : 'Calculate &amp; Create Run'">Calculate &amp; Create Run</span>
                                </button>
                                <a href="{{ route('companies.payroll.runs.index', $company) }}" class="reg-btn ghost">Cancel</a>
                            </div>

                        </div>
                    </div>

                </form>

            </main>
        </div>
    </div>
@endsection

@push('scripts')
<script src="//unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@endpush
