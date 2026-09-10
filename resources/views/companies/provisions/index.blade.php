@extends('layouts.public')

@section('title', $company->registered_name . ' — Provisions')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
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

                <div class="reg-mgmt-bar">
                    <span style="font-size:7pt;color:#5a7186;">
                        {{ $provisions->where('status', 'active')->count() }} active provision{{ $provisions->where('status', 'active')->count() !== 1 ? 's' : '' }}
                    </span>
                    <div style="display:flex;gap:4pt;">
                        <button type="button" class="reg-btn" onclick="document.getElementById('add-class-form').style.display = document.getElementById('add-class-form').style.display === 'none' ? 'block' : 'none'">+ Class</button>
                        <button type="button" class="reg-btn primary" onclick="document.getElementById('add-provision-form').style.display = document.getElementById('add-provision-form').style.display === 'none' ? 'block' : 'none'">+ Provision</button>
                    </div>
                </div>

                {{-- Add class form --}}
                <div id="add-class-form" style="display:none;margin-bottom:12pt;background:#f7fbfd;border:1px solid #9ec1f5;padding:8pt 10pt;">
                    <p style="font-size:6pt;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:#005bf0;margin:0 0 6pt;">New Provision Class</p>
                    <form method="POST" action="{{ route('companies.provisions.classes.store', $company) }}">
                        @csrf
                        <div style="display:flex;flex-wrap:wrap;gap:4pt 6pt;align-items:flex-end;">
                            <div class="reg-modal-field" style="flex:2;min-width:160px;">
                                <label>Class name</label>
                                <input type="text" name="name" required placeholder="e.g. Legal, Warranties, Restructuring">
                            </div>
                            <button type="submit" class="reg-btn primary" style="margin-bottom:0;">Save</button>
                        </div>
                    </form>
                </div>

                {{-- Add provision form --}}
                <div id="add-provision-form" style="display:none;margin-bottom:12pt;background:#f7fbfd;border:1px solid #9ec1f5;padding:8pt 10pt;">
                    <p style="font-size:6pt;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:#005bf0;margin:0 0 6pt;">Register New Provision / Contingency</p>
                    <form method="POST" action="{{ route('companies.provisions.store', $company) }}">
                        @csrf
                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:4pt 6pt;margin-bottom:6pt;">
                            <div class="reg-modal-field" style="grid-column:span 2;">
                                <label>Name</label>
                                <input type="text" name="name" required placeholder="e.g. Environmental remediation, Product warranty">
                            </div>
                            <div class="reg-modal-field">
                                <label>Class</label>
                                <select name="provision_class_id">
                                    <option value="">— None —</option>
                                    @foreach ($classes as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="reg-modal-field">
                                <label>Type</label>
                                <select name="provision_type" required>
                                    <option value="provision">Provision</option>
                                    <option value="contingent_liability">Contingent Liability</option>
                                    <option value="contingent_asset">Contingent Asset</option>
                                </select>
                            </div>
                            <div class="reg-modal-field">
                                <label>Probability</label>
                                <select name="probability">
                                    <option value="probable">Probable (&gt;50%)</option>
                                    <option value="possible">Possible</option>
                                    <option value="remote">Remote</option>
                                </select>
                            </div>
                            <div class="reg-modal-field">
                                <label>Recognition date</label>
                                <input type="date" name="recognition_date" required value="{{ now()->format('Y-m-d') }}">
                            </div>
                            <div class="reg-modal-field">
                                <label>Expected settlement</label>
                                <input type="date" name="expected_settlement_date">
                            </div>
                            <div class="reg-modal-field">
                                <label>Initial estimate (R)</label>
                                <input type="number" name="initial_estimate" step="0.01" min="0" required placeholder="0.00">
                            </div>
                            <div class="reg-modal-field">
                                <label>Discount rate (%)</label>
                                <input type="number" name="discount_rate" step="0.01" min="0" max="100" placeholder="e.g. 8.5">
                            </div>
                        </div>
                        <div class="reg-modal-field" style="margin-bottom:6pt;">
                            <label>Notes</label>
                            <input type="text" name="notes" maxlength="500">
                        </div>
                        <div style="display:flex;justify-content:flex-end;">
                            <button type="submit" class="reg-btn primary">Register</button>
                        </div>
                    </form>
                </div>

                {{-- Classes table --}}
                @if ($classes->isNotEmpty())
                <div class="reg-doc">
                    <div class="reg-doc-body">
                        <div class="reg-section-header">Provision Classes</div>
                        <div style="overflow-x:auto;">
                            <table class="reg-table">
                                <thead>
                                    <tr>
                                        <th style="width:50%;">Class</th>
                                        <th class="amt" style="width:20%;">Count</th>
                                        <th style="width:20%;text-align:right;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($classes as $class)
                                        <tr>
                                            <td style="font-weight:700;">{{ $class->name }}</td>
                                            <td class="amt">{{ $class->provisions_count }}</td>
                                            <td style="text-align:right;">
                                                @if ($class->provisions_count === 0)
                                                <div class="reg-row-actions">
                                                    <button type="button" class="reg-row-dots">&#x22EE;</button>
                                                    <div class="reg-row-menu">
                                                        <form method="POST" action="{{ route('companies.provisions.classes.destroy', [$company, $class]) }}"
                                                            onsubmit="return false" data-confirm-label="Provisions" data-confirm-title="Remove Class" data-confirm-body="Remove class {{ addslashes($class->name) }}?" data-confirm-text="Remove" data-confirm-danger="1">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="menu-item danger">Remove</button>
                                                        </form>
                                                    </div>
                                                </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Provisions register --}}
                <div class="reg-doc">
                    <div class="reg-doc-body">
                        <div class="reg-doc-title">Provisions & Contingencies Register</div>
                        <p style="font-size:7pt;color:#5a7186;margin:1pt 0 6pt;">
                            IAS 37 Provisions, Contingent Liabilities and Contingent Assets — provisions recognised when a present obligation exists, outflow is probable, and a reliable estimate can be made.
                        </p>

                        <hr class="reg-divider">

                        @if ($provisions->isEmpty())
                            <p class="reg-empty">No provisions registered yet.</p>
                        @else
                            @php $totalCarrying = 0.0; @endphp
                            <div style="overflow-x:auto;">
                                <table class="reg-table">
                                    <thead>
                                        <tr>
                                            <th style="width:20%;">Name</th>
                                            <th style="width:10%;">Class</th>
                                            <th style="width:10%;">Type</th>
                                            <th style="width:8%;">Probability</th>
                                            <th class="amt" style="width:12%;">Initial Est.</th>
                                            <th class="amt" style="width:12%;">Current Est.</th>
                                            <th class="amt" style="width:10%;">Present Value</th>
                                            <th style="width:8%;text-align:center;">Status</th>
                                            <th style="width:3%;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($provisions as $provision)
                                            @php
                                                if ($provision->status === 'active') $totalCarrying += (float) ($provision->present_value ?? $provision->current_estimate);
                                            @endphp
                                            <tr>
                                                <td>
                                                    <a class="reg-link" href="{{ route('companies.provisions.show', [$company, $provision]) }}">
                                                        {{ $provision->name }}
                                                    </a>
                                                </td>
                                                <td class="dim" style="font-size:6.5pt;">{{ $provision->provisionClass?->name ?? "\u{2014}" }}</td>
                                                <td><span class="cat-badge">{{ ucfirst(str_replace('_', ' ', $provision->provision_type)) }}</span></td>
                                                <td style="font-size:6.5pt;color:#6f869b;">{{ ucfirst($provision->probability ?? 'N/A') }}</td>
                                                <td class="amt">{{ number_format((float) $provision->initial_estimate, 2) }}</td>
                                                <td class="amt" style="font-weight:800;">{{ number_format((float) $provision->current_estimate, 2) }}</td>
                                                <td class="amt" style="color:#005bf0;">{{ $provision->present_value !== null ? number_format((float) $provision->present_value, 2) : "\u{2014}" }}</td>
                                                <td style="text-align:center;">
                                                    <span class="reg-status {{ in_array($provision->status, ['settled', 'reversed', 'lapsed']) ? 'disposed' : '' }}">
                                                        {{ ucfirst($provision->status) }}
                                                    </span>
                                                </td>
                                                <td style="text-align:right;">
                                                    <div class="reg-row-actions">
                                                        <button type="button" class="reg-row-dots">&#x22EE;</button>
                                                        <div class="reg-row-menu">
                                                            <a href="{{ route('companies.provisions.show', [$company, $provision]) }}">View</a>
                                                            <form method="POST" action="{{ route('companies.provisions.destroy', [$company, $provision]) }}"
                                                                onsubmit="return false" data-confirm-label="Provisions" data-confirm-title="Remove Provision" data-confirm-body="Remove {{ addslashes($provision->name) }} from the register?" data-confirm-text="Remove" data-confirm-danger="1">
                                                                @csrf @method('DELETE')
                                                                <button type="submit" class="menu-item danger">Remove</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    @if ($provisions->where('status', 'active')->count() > 0)
                                    <tfoot>
                                        <tr>
                                            <td colspan="5">Total carrying amount (active)</td>
                                            <td class="amt">{{ number_format($totalCarrying, 2) }}</td>
                                            <td colspan="3"></td>
                                        </tr>
                                    </tfoot>
                                    @endif
                                </table>
                            </div>
                        @endif

                    </div>
                </div>

            </main>
        </div>
    </div>
    @include('companies._row-actions')
@endsection
