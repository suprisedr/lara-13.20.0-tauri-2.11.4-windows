@extends('layouts.public')

@section('title', $company->registered_name . ' — AFS Details')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .co-topbar { padding: 1rem 2rem; }
        .co-main   { padding: 1.25rem 1.5rem; }

        .afsd-wrap {
            font-family:'Century Gothic','URW Gothic','Avant Garde',Futura,'Avenir Next',Avenir,'Trebuchet MS',Helvetica,Arial,'DejaVu Sans',sans-serif;
        }

        .afsd-card {
            background: #fff;
            border: 1px solid #d3e2f5;
            border-radius: 0;
            margin-bottom: 1.25rem;
        }

        .afsd-card-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #d3e2f5;
        }

        .afsd-section-title {
            font-size: 7pt;
            font-weight: 800;
            color: #000;
            text-transform: none;
            letter-spacing: 0;
            margin: 1.5rem 0 0.75rem;
            padding-bottom: 0.3rem;
            border-bottom: 1.5px solid #000;
        }

        .afsd-section-title:first-child { margin-top: 0; }

        .af-row  { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.5rem 0.75rem; align-items: start; margin-bottom: 0.65rem; }
        .af-field label { display: block; font-size: 10pt; font-weight: 700; letter-spacing: 0; text-transform: none; color: #5a7186; margin-bottom: 0.2rem; }
        .af-field input, .af-field select, .af-field textarea {
            width: 100%; border: 1px solid #d3e2f5; padding: 0.35rem 0.5rem; font-size: 10.5pt;
            font-family: inherit; color: #000; box-sizing: border-box; background: #fff; border-radius: 0;
            resize: vertical; outline: none; transition: border-color 0.15s;
        }
        .af-field input:focus, .af-field select:focus, .af-field textarea:focus { border-color: #000; }
        .af-field.wide  { grid-column: span 2; }
        .af-field.full  { grid-column: span 4; }

        .af-hint {
            font-size: 8pt;
            color: #888;
            margin-top: 0.2rem;
        }

        .mgmt-btn {
            display: inline-flex; align-items: center; gap: 0.4rem; background: #fff;
            border: 1px solid #000; color: #000; font-size: 8pt; font-weight: 700;
            text-transform: none; letter-spacing: 0; padding: 0.4rem 0.95rem;
            text-decoration: none; cursor: pointer; font-family: inherit;
            transition: background 0.15s, color 0.15s; height: 2rem;
            box-sizing: border-box; border-radius: 0; white-space: nowrap;
        }
        .mgmt-btn:hover { background: #000; color: #fff; }
        .mgmt-btn.primary { background: #000; color: #fff; }
        .mgmt-btn.primary:hover { background: #1a345b; }

        .flash-success {
            margin: 0 1.25rem;
            padding: 0.55rem 0.85rem;
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
            border-radius: 0;
            font-size: 10.5pt;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .af-row { grid-template-columns: 1fr 1fr; }
            .af-field.full { grid-column: span 2; }
        }
        @media (max-width: 480px) {
            .af-row { grid-template-columns: 1fr; }
            .af-field.wide, .af-field.full { grid-column: span 1; }
            .afsd-card-head { flex-direction: column; }
        }
    </style>
@endpush

@section('content')
    <div class="co-wrap">
        @include('companies._topbar')

        <div class="co-body">
            @include('companies._sidebar')
            <main class="co-main">
                <div class="afsd-wrap">
                    <div class="afsd-card">
                        <div class="afsd-card-head">
                            <div>
                                <p style="font-size:0.68rem;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:#5a7186;margin:0 0 0.25rem;">Annual Financial Statements</p>
                                <h2 style="font-size:1.05rem;font-weight:800;color:#000;margin:0 0 0.15rem;">AFS Details</h2>
                                <p style="font-size:0.72rem;color:#888;margin:0;">
                                    These details populate the front matter of the downloadable financial statements.
                                </p>
                            </div>
                            <form method="GET" action="{{ route('companies.reports.afs-bundle.pdf', $company) }}"
                                target="_blank"
                                style="display:flex;align-items:flex-end;gap:0.5rem;flex-wrap:wrap;">
                                <div>
                                    <label style="font-size:0.68rem;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;color:#5a7186;display:block;margin-bottom:0.2rem;">From</label>
                                    <input type="date" name="start_date"
                                        style="font-size:0.75rem;padding:0.35rem 0.5rem;border:1px solid #d3e2f5;border-radius:0;font-family:inherit;color:#000;width:130px;"
                                        value="{{ request('start_date', $startDate) }}">
                                </div>
                                <div>
                                    <label style="font-size:0.68rem;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;color:#5a7186;display:block;margin-bottom:0.2rem;">To</label>
                                    <input type="date" name="end_date"
                                        style="font-size:0.75rem;padding:0.35rem 0.5rem;border:1px solid #d3e2f5;border-radius:0;font-family:inherit;color:#000;width:130px;"
                                        value="{{ request('end_date', $endDate) }}">
                                </div>
                                <button type="submit" class="mgmt-btn">
                                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                        <polyline points="7 10 12 15 17 10" />
                                        <line x1="12" y1="15" x2="12" y2="3" />
                                    </svg>
                                    Download PDF
                                </button>
                            </form>
                        </div>

                        @if (session('success'))
                            <div class="flash-success" style="margin-top:1rem;">
                                {{ session('success') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('companies.afs-details.update', $company) }}"
                            style="padding:1.25rem;">
                            @csrf
                            @method('PATCH')

                            <div class="afsd-section-title" style="margin-top:0;">General Information</div>
                            <div class="af-row">
                                <div class="af-field wide">
                                    <label for="country">Country of Incorporation</label>
                                    <input type="text" id="country" name="country_of_incorporation"
                                        value="{{ old('country_of_incorporation', $afs->country_of_incorporation ?? 'South Africa') }}">
                                </div>
                                <div class="af-field wide">
                                    <label for="nature">Nature of Business</label>
                                    <input type="text" id="nature" name="nature_of_business"
                                        value="{{ old('nature_of_business', $afs->nature_of_business) }}"
                                        placeholder="e.g. Construction">
                                </div>
                            </div>
                            <div class="af-row">
                                <div class="af-field full">
                                    <label for="directors">Director(s)</label>
                                    <textarea id="directors" name="directors" rows="3" placeholder="One director per line, e.g. Nkalanga LJ">{{ old('directors', implode("\n", $afs->directors ?? [])) }}</textarea>
                                    <div class="af-hint">Enter one director per line.</div>
                                </div>
                            </div>
                            <div class="af-row">
                                <div class="af-field wide">
                                    <label for="registered_office">Registered Office</label>
                                    <textarea id="registered_office" name="registered_office" rows="3" placeholder="Street address…">{{ old('registered_office', $afs->registered_office) }}</textarea>
                                </div>
                                <div class="af-field wide">
                                    <label for="business_address">Business Address</label>
                                    <textarea id="business_address" name="business_address" rows="3">{{ old('business_address', $afs->business_address) }}</textarea>
                                </div>
                            </div>
                            <div class="af-row">
                                <div class="af-field full">
                                    <label for="postal_address">Postal Address</label>
                                    <textarea id="postal_address" name="postal_address" rows="2">{{ old('postal_address', $afs->postal_address) }}</textarea>
                                </div>
                            </div>

                            <div class="afsd-section-title">Practitioner / Preparer</div>
                            <div class="af-row">
                                <div class="af-field wide">
                                    <label for="practitioner_name">Practitioner / Firm Name</label>
                                    <input type="text" id="practitioner_name" name="practitioner_name"
                                        value="{{ old('practitioner_name', $afs->practitioner_name) }}"
                                        placeholder="e.g. MNF Professional Accountants and Consulting">
                                </div>
                                <div class="af-field wide">
                                    <label for="practitioner_qualification">Qualification</label>
                                    <input type="text" id="practitioner_qualification" name="practitioner_qualification"
                                        value="{{ old('practitioner_qualification', $afs->practitioner_qualification) }}"
                                        placeholder="e.g. Professional Accountants (SA)">
                                </div>
                            </div>
                            <div class="af-row">
                                <div class="af-field wide">
                                    <label for="practitioner_membership">Professional Body / Membership</label>
                                    <input type="text" id="practitioner_membership" name="practitioner_membership"
                                        value="{{ old('practitioner_membership', $afs->practitioner_membership) }}"
                                        placeholder="e.g. SAIPA">
                                </div>
                                <div class="af-field wide">
                                    <label for="compilation_directors">Compilation Report Signatories</label>
                                    <input type="text" id="compilation_directors" name="compilation_directors"
                                        value="{{ old('compilation_directors', $afs->compilation_directors) }}"
                                        placeholder="e.g. Mbatsane ME, Fakude SS">
                                </div>
                            </div>
                            <div class="af-row">
                                <div class="af-field full">
                                    <label for="practitioner_contact">Practitioner Contact / Letterhead</label>
                                    <textarea id="practitioner_contact" name="practitioner_contact" rows="3" placeholder="Address, tel, email…">{{ old('practitioner_contact', $afs->practitioner_contact) }}</textarea>
                                </div>
                            </div>

                            <div class="afsd-section-title">Approval &amp; Assurance</div>
                            <div class="af-row">
                                <div class="af-field wide">
                                    <label for="approval_date">Date of Approval</label>
                                    <input type="date" id="approval_date" name="approval_date"
                                        value="{{ old('approval_date', optional($afs->approval_date)->format('Y-m-d')) }}">
                                </div>
                            </div>
                            <div class="af-row">
                                <div class="af-field full">
                                    <label for="level_of_assurance">Level of Assurance</label>
                                    <textarea id="level_of_assurance" name="level_of_assurance" rows="2">{{ old('level_of_assurance', $afs->level_of_assurance ?? 'These financial statements have not been audited or independently reviewed.') }}</textarea>
                                </div>
                            </div>

                            <div style="margin-top:1.25rem;">
                                <button type="submit" class="mgmt-btn primary">Save AFS Details</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
@endsection
