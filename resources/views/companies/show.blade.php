@extends('layouts.public')

@section('title', $company->registered_name . ' — Profile')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        /* ── Document shell ───────────────── */
        .cust-doc { background: #fff; border: 1px solid #c9dff0; font-size: 7pt; font-family: Helvetica, Arial, "DejaVu Sans", sans-serif; line-height: 1.45; margin-bottom: 12pt; }
        .cust-doc-body { padding: 14pt 16pt; }

        /* ── Section dividers ─────────────── */
        .section-header { border-top: 1.5pt solid #16355c; margin: 12pt 0 6pt; padding-top: 3pt; display: flex; justify-content: space-between; align-items: baseline; }
        .section-header:first-child { margin-top: 0; }
        .section-header-title { font-size: 5.5pt; font-weight: 800; text-transform: uppercase; letter-spacing: 0.12em; color: #16355c; }
        .section-header-sub { font-size: 5.5pt; color: #7a90a5; }

        /* ── Field grid ───────────────────── */
        .af-row { display: grid; grid-template-columns: repeat(auto-fill, minmax(140pt, 1fr)); gap: 7pt 12pt; }
        .af-field-label { font-size: 5pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.09em; color: #7a90a5; margin: 0 0 1.5pt; }
        .af-field-value { font-size: 7pt; font-weight: 600; color: #16355c; margin: 0; line-height: 1.4; }
        .af-na { color: #a9bccd; font-style: italic; font-weight: 400; }

        /* ── Management bar ───────────────── */
        .inv-mgmt-bar { display: flex; align-items: center; justify-content: space-between; gap: 8pt; flex-wrap: wrap; margin-bottom: 10pt; }
        .mgmt-btn { display: inline-flex; align-items: center; gap: 3pt; background: #fff; border: 1px solid #c9dff0; color: #16355c; font-size: 6pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; padding: 3pt 7pt; text-decoration: none; cursor: pointer; font-family: Helvetica, Arial, "DejaVu Sans", sans-serif; transition: background 0.15s, color 0.15s; }
        .mgmt-btn:hover { background: #eef6fc; }
        .mgmt-btn.primary { background: #0079c8; color: #fff; border-color: #0079c8; }
        .mgmt-btn.primary:hover { background: #005f9e; }
        .mgmt-btn.warn { border-color: #b91c1c; color: #b91c1c; }
        .mgmt-btn.warn:hover { background: #b91c1c; color: #fff; }

        /* ── Quick-action cards ───────────── */
        .qa-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(130pt, 1fr)); gap: 6pt; }
        .qa-card {
            display: flex; align-items: center; gap: 6pt;
            padding: 7pt 8pt; border: 1px solid #c9dff0;
            background: #fff; text-decoration: none;
            transition: border-color 0.15s, background 0.15s;
        }
        .qa-card:hover { border-color: #0079c8; background: #eef6fc; }
        .qa-icon {
            width: 26pt; height: 26pt; flex-shrink: 0;
            background: #eef6fc; color: #0079c8;
            display: flex; align-items: center; justify-content: center;
        }
        .qa-label { font-size: 7pt; font-weight: 700; color: #16355c; margin: 0; line-height: 1.2; }
        .qa-sub { font-size: 5.5pt; color: #7a90a5; margin: 1pt 0 0; }

        /* ── Status pills ─────────────────── */
        .sp { display: inline-flex; align-items: center; gap: 2pt; font-size: 5pt; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; padding: 1pt 4pt; }
        .sp-ok { background: #ecfdf5; color: #047857; }
        .sp-no { background: #eef6fc; color: #7a90a5; }

        /* ── Edit form inputs ─────────────── */
        .ef-input, .ef-select {
            width: 100%; box-sizing: border-box;
            border: 1px solid #c9dff0; padding: 4pt 6pt;
            font-size: 7pt; font-family: Helvetica, Arial, "DejaVu Sans", sans-serif; color: #16355c;
            background: #fff; outline: none; transition: border-color 0.15s;
            appearance: none;
        }
        .ef-input:focus, .ef-select:focus { border-color: #0079c8; }
        .ef-label { display: block; font-size: 5pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.09em; color: #7a90a5; margin-bottom: 2pt; }
        .ef-field { margin-bottom: 0; }

        /* Logo upload */
        .logo-upload-area { display: flex; align-items: center; gap: 8pt; flex-wrap: wrap; }
        .logo-preview { width: 54pt; height: 54pt; object-fit: contain; border: 1px solid #c9dff0; background: #eef6fc; padding: 3pt; }
        .logo-placeholder { width: 54pt; height: 54pt; border: 1px dashed #c9dff0; background: #eef6fc; display: flex; align-items: center; justify-content: center; color: #a9bccd; font-size: 5pt; font-weight: 700; text-align: center; line-height: 1.3; }
    </style>
@endpush

@section('content')
<div class="co-wrap">

    @include('companies._topbar')

    <div class="co-body">
        @include('companies._sidebar')

        <main class="co-main">

            @if (session('success'))
                <div style="display:flex;align-items:center;gap:4pt;background:#dcfce7;border:1px solid #bbf7d0;color:#15803d;padding:5pt 8pt;font-size:7pt;font-weight:600;margin-bottom:10pt;font-family:Helvetica,Arial,'DejaVu Sans',sans-serif;">
                    <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div style="background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;padding:5pt 8pt;font-size:7pt;font-weight:600;margin-bottom:10pt;font-family:Helvetica,Arial,'DejaVu Sans',sans-serif;">
                    @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                </div>
            @endif

            {{-- Quick Actions --}}
            <div class="cust-doc">
                <div class="cust-doc-body">
                    <div class="section-header" style="margin-top:0;">
                        <span class="section-header-title">Quick Actions</span>
                    </div>
                    <div class="qa-grid">
                        <a href="{{ route('companies.transactions', $company) }}" class="qa-card">
                            <div class="qa-icon">
                                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
                            </div>
                            <div><p class="qa-label">Transactions</p><p class="qa-sub">Record &amp; review entries</p></div>
                        </a>
                        <a href="{{ route('companies.invoices.index', $company) }}" class="qa-card">
                            <div class="qa-icon">
                                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="14" x2="15" y2="14"/></svg>
                            </div>
                            <div><p class="qa-label">Invoices</p><p class="qa-sub">Bill your customers</p></div>
                        </a>
                        <a href="{{ route('companies.quotations.index', $company) }}" class="qa-card">
                            <div class="qa-icon">
                                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 15l2 2 4-4"/></svg>
                            </div>
                            <div><p class="qa-label">Quotations</p><p class="qa-sub">Create estimates</p></div>
                        </a>
                        <a href="{{ route('companies.reports.balance-sheet', $company) }}" class="qa-card">
                            <div class="qa-icon">
                                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                            </div>
                            <div><p class="qa-label">Reports</p><p class="qa-sub">Financial statements</p></div>
                        </a>
                        <a href="{{ route('companies.payroll.employees.index', $company) }}" class="qa-card">
                            <div class="qa-icon">
                                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            </div>
                            <div><p class="qa-label">Payroll</p><p class="qa-sub">Employees &amp; runs</p></div>
                        </a>
                        <a href="{{ route('companies.chart-of-accounts', $company) }}" class="qa-card">
                            <div class="qa-icon">
                                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                            </div>
                            <div><p class="qa-label">Chart of Accounts</p><p class="qa-sub">Ledger structure</p></div>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Company Profile --}}
            <div class="cust-doc">
                <div class="cust-doc-body">

                    {{-- ══ READ VIEW ══ --}}
                    <div id="view-mode">
                        <div class="inv-mgmt-bar" style="margin-bottom:8pt;">
                            <div>
                                <div style="font-size:5pt;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:#7a90a5;margin-bottom:1.5pt;">Business Profile</div>
                                <div style="font-size:9pt;font-weight:800;color:#16355c;">Company Details</div>
                            </div>
                            <button type="button" class="mgmt-btn" onclick="enterEditMode()">
                                <svg width="9" height="9" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                Edit Profile
                            </button>
                        </div>

                        @php
                            $na  = '<span class="af-na">—</span>';
                            $val = fn(?string $v) => $v ? e($v) : $na;
                        @endphp

                        <div class="section-header" style="margin-top:0;">
                            <span class="section-header-title">Identity &amp; Registration</span>
                        </div>
                        <div class="af-row" style="margin-bottom:10pt;">
                            <div>
                                <p class="af-field-label">Company Type</p>
                                <p class="af-field-value">{!! $val($company->company_type_label) !!}</p>
                            </div>
                            <div>
                                <p class="af-field-label">Registration No.</p>
                                <p class="af-field-value">{!! $val($company->registration_number) !!}</p>
                            </div>
                            <div>
                                <p class="af-field-label">Industry</p>
                                <p class="af-field-value">{!! $company->industry ? e(\App\Models\Company::industries()[$company->industry] ?? $company->industry) : $na !!}</p>
                            </div>
                            <div>
                                <p class="af-field-label">Financial Year End</p>
                                <p class="af-field-value">{!! $val($company->financial_year_end_label ?: null) !!}</p>
                            </div>
                        </div>

                        <div class="section-header">
                            <span class="section-header-title">Tax &amp; Compliance</span>
                        </div>
                        <div class="af-row" style="margin-bottom:10pt;">
                            <div>
                                <p class="af-field-label">Income Tax No.</p>
                                <p class="af-field-value">{!! $val($company->income_tax_number) !!}</p>
                            </div>
                            <div>
                                <p class="af-field-label">VAT No.</p>
                                <p class="af-field-value" style="display:flex;align-items:center;gap:4pt;flex-wrap:wrap;">
                                    {!! $val($company->vat_number) !!}
                                    @if ($company->vat_number)
                                        <span class="sp sp-ok">Registered</span>
                                    @else
                                        <span class="sp sp-no">Not Registered</span>
                                    @endif
                                </p>
                            </div>
                            <div>
                                <p class="af-field-label">PAYE No.</p>
                                <p class="af-field-value">{!! $val($company->paye_number) !!}</p>
                            </div>
                            <div>
                                <p class="af-field-label">UIF No.</p>
                                <p class="af-field-value">{!! $val($company->uif_number) !!}</p>
                            </div>
                            <div>
                                <p class="af-field-label">SDL No.</p>
                                <p class="af-field-value">{!! $val($company->sdl_number) !!}</p>
                            </div>
                            <div>
                                <p class="af-field-label">Corporate Tax Rate</p>
                                <p class="af-field-value">{{ $company->corporate_tax_rate ?? 27 }}%</p>
                            </div>
                        </div>

                        <div class="section-header">
                            <span class="section-header-title">Address</span>
                        </div>
                        <div class="af-row" style="margin-bottom:10pt;">
                            <div>
                                <p class="af-field-label">Street</p>
                                <p class="af-field-value">
                                    @if ($company->address_line_1)
                                        {{ $company->address_line_1 }}@if ($company->address_line_2)<br>{{ $company->address_line_2 }}@endif
                                    @else
                                        {!! $na !!}
                                    @endif
                                </p>
                            </div>
                            <div>
                                <p class="af-field-label">City</p>
                                <p class="af-field-value">{!! $val($company->city) !!}</p>
                            </div>
                            <div>
                                <p class="af-field-label">Province</p>
                                <p class="af-field-value">{!! $company->province ? e(\App\Models\Company::saProvinces()[$company->province] ?? $company->province) : $na !!}</p>
                            </div>
                            <div>
                                <p class="af-field-label">Postal Code</p>
                                <p class="af-field-value">{!! $val($company->postal_code) !!}</p>
                            </div>
                        </div>

                        <div class="section-header">
                            <span class="section-header-title">Banking</span>
                        </div>
                        <div class="af-row">
                            <div>
                                <p class="af-field-label">Bank</p>
                                <p class="af-field-value">{!! $val($company->bank_name) !!}</p>
                            </div>
                            <div>
                                <p class="af-field-label">Account No.</p>
                                <p class="af-field-value">{!! $val($company->bank_account_number) !!}</p>
                            </div>
                            <div>
                                <p class="af-field-label">Account Type</p>
                                <p class="af-field-value">{!! $company->bank_account_type ? e(ucfirst($company->bank_account_type)) : $na !!}</p>
                            </div>
                            <div>
                                <p class="af-field-label">Branch Code</p>
                                <p class="af-field-value">{!! $val($company->bank_branch_code) !!}</p>
                            </div>
                        </div>
                    </div>

                    {{-- ══ EDIT FORM ══ --}}
                    <div id="edit-mode" style="display:none;">
                        <div class="inv-mgmt-bar" style="margin-bottom:8pt;">
                            <div>
                                <div style="font-size:5pt;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:#7a90a5;margin-bottom:1.5pt;">Business Profile</div>
                                <div style="font-size:9pt;font-weight:800;color:#16355c;">Edit Company Details</div>
                            </div>
                            <button type="button" class="mgmt-btn" onclick="exitEditMode()">Cancel</button>
                        </div>

                        <form method="POST" action="{{ route('companies.profile.update', $company) }}" enctype="multipart/form-data">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" id="remove-logo-input" name="remove_logo" value="0">

                            <div class="section-header" style="margin-top:0;"><span class="section-header-title">Company Logo</span></div>
                            <div class="logo-upload-area" style="margin-bottom:10pt;">
                                <div id="logo-current">
                                    @if ($company->logo_path)
                                        <img id="logo-img" src="{{ asset('storage/' . $company->logo_path) }}" alt="Current logo" class="logo-preview">
                                    @else
                                        <div id="logo-img" class="logo-placeholder">No<br>Logo</div>
                                    @endif
                                </div>
                                <div>
                                    <label class="ef-label">Upload new logo</label>
                                    <input type="file" name="logo" id="logo-file-input" accept="image/jpeg,image/png,image/webp,image/svg+xml" onchange="previewLogo(this)" style="font-size:7pt;color:#4a5f78;">
                                    <p style="font-size:5.5pt;color:#7a90a5;margin:2pt 0 0;">JPG, PNG, WebP or SVG · max 2 MB</p>
                                    @if ($company->logo_path)
                                        <button type="button" id="remove-logo-btn" onclick="removeLogo()" style="margin-top:3pt;font-size:6pt;font-weight:700;color:#b91c1c;background:none;border:none;cursor:pointer;padding:0;">Remove current logo</button>
                                    @endif
                                </div>
                            </div>

                            <div class="section-header"><span class="section-header-title">Identity &amp; Registration</span></div>
                            <div class="af-row" style="margin-bottom:8pt;">
                                <div class="ef-field"><label class="ef-label" for="f-name">Registered Name *</label><input class="ef-input" id="f-name" name="registered_name" type="text" value="{{ old('registered_name', $company->registered_name) }}" required></div>
                                <div class="ef-field"><label class="ef-label" for="f-type">Company Type *</label>
                                    <select class="ef-select" id="f-type" name="company_type" required>
                                        @foreach (\App\Models\Company::companyTypes() as $key => $label)
                                            <option value="{{ $key }}" {{ old('company_type', $company->company_type) === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="ef-field"><label class="ef-label" for="f-reg">Registration No.</label><input class="ef-input" id="f-reg" name="registration_number" type="text" value="{{ old('registration_number', $company->registration_number) }}" placeholder="e.g. 2024/000000/07"></div>
                                <div class="ef-field"><label class="ef-label" for="f-fye">Financial Year End *</label>
                                    <select class="ef-select" id="f-fye" name="financial_year_end_month" required>
                                        @foreach (\App\Models\Company::months() as $num => $name)
                                            <option value="{{ $num }}" {{ (int) old('financial_year_end_month', $company->financial_year_end_month) === $num ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="ef-field"><label class="ef-label" for="f-industry">Industry</label>
                                    <select class="ef-select" id="f-industry" name="industry">
                                        <option value="">— None —</option>
                                        @foreach (\App\Models\Company::industries() as $key => $label)
                                            <option value="{{ $key }}" {{ old('industry', $company->industry) === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="section-header"><span class="section-header-title">Tax &amp; Compliance</span></div>
                            <div class="af-row" style="margin-bottom:8pt;">
                                <div class="ef-field"><label class="ef-label" for="f-tax">Income Tax No.</label><input class="ef-input" id="f-tax" name="income_tax_number" type="text" value="{{ old('income_tax_number', $company->income_tax_number) }}" placeholder="e.g. 9123456789"></div>
                                <div class="ef-field"><label class="ef-label" for="f-vat">VAT No.</label><input class="ef-input" id="f-vat" name="vat_number" type="text" value="{{ old('vat_number', $company->vat_number) }}" placeholder="10 digits"></div>
                                <div class="ef-field"><label class="ef-label" for="f-paye">PAYE No.</label><input class="ef-input" id="f-paye" name="paye_number" type="text" value="{{ old('paye_number', $company->paye_number) }}"></div>
                                <div class="ef-field"><label class="ef-label" for="f-uif">UIF No.</label><input class="ef-input" id="f-uif" name="uif_number" type="text" value="{{ old('uif_number', $company->uif_number) }}"></div>
                                <div class="ef-field"><label class="ef-label" for="f-sdl">SDL No.</label><input class="ef-input" id="f-sdl" name="sdl_number" type="text" value="{{ old('sdl_number', $company->sdl_number) }}"></div>
                                <div class="ef-field"><label class="ef-label" for="f-corp-tax">Corporate Tax Rate (%)</label><input class="ef-input" id="f-corp-tax" name="corporate_tax_rate" type="number" min="0" max="100" step="0.01" value="{{ old('corporate_tax_rate', $company->corporate_tax_rate ?? 27) }}" placeholder="27"></div>
                            </div>

                            <div class="section-header"><span class="section-header-title">Address</span></div>
                            <div class="af-row" style="margin-bottom:8pt;">
                                <div class="ef-field"><label class="ef-label" for="f-addr1">Street Line 1</label><input class="ef-input" id="f-addr1" name="address_line_1" type="text" value="{{ old('address_line_1', $company->address_line_1) }}"></div>
                                <div class="ef-field"><label class="ef-label" for="f-addr2">Street Line 2</label><input class="ef-input" id="f-addr2" name="address_line_2" type="text" value="{{ old('address_line_2', $company->address_line_2) }}"></div>
                                <div class="ef-field"><label class="ef-label" for="f-city">City</label><input class="ef-input" id="f-city" name="city" type="text" value="{{ old('city', $company->city) }}"></div>
                                <div class="ef-field"><label class="ef-label" for="f-province">Province</label>
                                    <select class="ef-select" id="f-province" name="province">
                                        <option value="">— Select —</option>
                                        @foreach (\App\Models\Company::saProvinces() as $key => $label)
                                            <option value="{{ $key }}" {{ old('province', $company->province) === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="ef-field"><label class="ef-label" for="f-postal">Postal Code</label><input class="ef-input" id="f-postal" name="postal_code" type="text" value="{{ old('postal_code', $company->postal_code) }}" placeholder="e.g. 1215"></div>
                            </div>

                            <div class="section-header"><span class="section-header-title">Banking</span></div>
                            <div class="af-row" style="margin-bottom:12pt;">
                                <div class="ef-field"><label class="ef-label" for="f-bank">Bank Name</label><input class="ef-input" id="f-bank" name="bank_name" type="text" value="{{ old('bank_name', $company->bank_name) }}" placeholder="e.g. Capitec Bank"></div>
                                <div class="ef-field"><label class="ef-label" for="f-accno">Account No.</label><input class="ef-input" id="f-accno" name="bank_account_number" type="text" value="{{ old('bank_account_number', $company->bank_account_number) }}"></div>
                                <div class="ef-field"><label class="ef-label" for="f-acctype">Account Type</label>
                                    <select class="ef-select" id="f-acctype" name="bank_account_type">
                                        <option value="">— Select —</option>
                                        <option value="current" {{ old('bank_account_type', $company->bank_account_type) === 'current' ? 'selected' : '' }}>Current</option>
                                        <option value="savings" {{ old('bank_account_type', $company->bank_account_type) === 'savings' ? 'selected' : '' }}>Savings</option>
                                        <option value="cheque"  {{ old('bank_account_type', $company->bank_account_type) === 'cheque'  ? 'selected' : '' }}>Cheque</option>
                                    </select>
                                </div>
                                <div class="ef-field"><label class="ef-label" for="f-branch">Branch Code</label><input class="ef-input" id="f-branch" name="bank_branch_code" type="text" value="{{ old('bank_branch_code', $company->bank_branch_code) }}" placeholder="e.g. 470010"></div>
                            </div>

                            <div style="border-top:1.5pt solid #16355c;padding-top:8pt;display:flex;align-items:center;justify-content:space-between;gap:8pt;flex-wrap:wrap;">
                                <button type="button" class="mgmt-btn" onclick="exitEditMode()">Cancel</button>
                                <button type="submit" class="mgmt-btn primary">Save Changes</button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>

        </main>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function enterEditMode() {
        document.getElementById('view-mode').style.display = 'none';
        document.getElementById('edit-mode').style.display = '';
    }
    function exitEditMode() {
        document.getElementById('view-mode').style.display = '';
        document.getElementById('edit-mode').style.display = 'none';
        document.getElementById('remove-logo-input').value = '0';
    }
    function previewLogo(input) {
        if (!input.files || !input.files[0]) return;
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('logo-current').innerHTML = '<img src="' + e.target.result + '" class="logo-preview" alt="Preview">';
        };
        reader.readAsDataURL(input.files[0]);
        document.getElementById('remove-logo-input').value = '0';
    }
    function removeLogo() {
        document.getElementById('remove-logo-input').value = '1';
        document.getElementById('logo-file-input').value = '';
        document.getElementById('logo-current').innerHTML = '<div class="logo-placeholder">No<br>Logo</div>';
        const btn = document.getElementById('remove-logo-btn');
        if (btn) btn.style.display = 'none';
    }
    @if ($errors->any())
        document.addEventListener('DOMContentLoaded', enterEditMode);
    @endif
</script>
@endpush
