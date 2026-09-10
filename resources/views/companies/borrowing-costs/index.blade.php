@extends('layouts.public')

@section('title', $company->registered_name . ' — Borrowing Costs')
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
                        {{ $capitalisations->where('status', 'active')->count() }} active capitalisation{{ $capitalisations->where('status', 'active')->count() !== 1 ? 's' : '' }}
                    </span>
                    <div style="display:flex;gap:4pt;">
                        <button type="button" class="reg-btn primary" onclick="document.getElementById('add-form').style.display = document.getElementById('add-form').style.display === 'none' ? 'block' : 'none'">+ Capitalisation</button>
                    </div>
                </div>

                <div id="add-form" style="display:none;margin-bottom:12pt;background:#f7fbfd;border:1px solid #9ec1f5;padding:8pt 10pt;">
                    <p style="font-size:6pt;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:#005bf0;margin:0 0 6pt;">New Borrowing Cost Capitalisation</p>
                    <form method="POST" action="{{ route('companies.borrowing-costs.store', $company) }}">
                        @csrf
                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:4pt 6pt;margin-bottom:6pt;">
                            <div class="reg-modal-field" style="grid-column:span 2;">
                                <label>Borrowing source</label>
                                <input type="text" name="borrowing_source" required placeholder="e.g. Standard Bank term loan, Bond issue 2026">
                            </div>
                            <div class="reg-modal-field">
                                <label>Qualifying asset type</label>
                                <select name="qualifying_asset_type" required>
                                    <option value="asset">PPE (IAS 16)</option>
                                    <option value="intangible_asset">Intangible (IAS 38)</option>
                                    <option value="investment_property">Inv. Property (IAS 40)</option>
                                    <option value="inventory_item">Inventory (IAS 2)</option>
                                </select>
                            </div>
                            <div class="reg-modal-field">
                                <label>Qualifying asset ID</label>
                                <input type="number" name="qualifying_asset_id" required min="1" placeholder="Asset ID">
                            </div>
                            <div class="reg-modal-field">
                                <label>Start date</label>
                                <input type="date" name="capitalisation_start_date" required value="{{ now()->format('Y-m-d') }}">
                            </div>
                            <div class="reg-modal-field">
                                <label>Borrowing rate (%)</label>
                                <input type="number" name="borrowing_rate" step="0.01" min="0" required placeholder="e.g. 11.5">
                            </div>
                            <div class="reg-modal-field">
                                <label>Weighted avg rate (%)</label>
                                <input type="number" name="weighted_average_rate" step="0.01" min="0" placeholder="Optional">
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

                <div class="reg-doc">
                    <div class="reg-doc-body">
                        <div class="reg-doc-title">Borrowing Cost Capitalisations</div>
                        <p style="font-size:7pt;color:#5a7186;margin:1pt 0 6pt;">
                            IAS 23 Borrowing Costs — borrowing costs directly attributable to qualifying assets are capitalised as part of the cost of that asset.
                        </p>

                        <hr class="reg-divider">

                        @if ($capitalisations->isEmpty())
                            <p class="reg-empty">No borrowing cost capitalisations registered yet.</p>
                        @else
                            @php $totalCapitalised = 0.0; @endphp
                            <div style="overflow-x:auto;">
                                <table class="reg-table">
                                    <thead>
                                        <tr>
                                            <th style="width:22%;">Borrowing Source</th>
                                            <th style="width:12%;">Asset Type</th>
                                            <th style="width:8%;">Asset ID</th>
                                            <th style="width:10%;">Start</th>
                                            <th class="amt" style="width:8%;">Rate</th>
                                            <th class="amt" style="width:12%;">Total Capitalised</th>
                                            <th style="width:10%;text-align:center;">Status</th>
                                            <th style="width:3%;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($capitalisations as $cap)
                                            @php
                                                if ($cap->status === 'active') $totalCapitalised += (float) ($cap->total_capitalised ?? 0);
                                            @endphp
                                            <tr>
                                                <td>
                                                    <a class="reg-link" href="{{ route('companies.borrowing-costs.show', [$company, $cap]) }}">
                                                        {{ $cap->borrowing_source }}
                                                    </a>
                                                </td>
                                                <td class="dim" style="font-size:6.5pt;">{{ ucfirst(str_replace('_', ' ', $cap->qualifying_asset_type)) }}</td>
                                                <td class="amt">{{ $cap->qualifying_asset_id }}</td>
                                                <td style="font-size:6.5pt;">{{ $cap->capitalisation_start_date->format('d M Y') }}</td>
                                                <td class="amt">{{ number_format((float) $cap->borrowing_rate, 2) }}%</td>
                                                <td class="amt" style="font-weight:800;">{{ number_format((float) ($cap->total_capitalised ?? 0), 2) }}</td>
                                                <td style="text-align:center;">
                                                    <span class="reg-status {{ $cap->status !== 'active' ? 'disposed' : '' }}">
                                                        {{ ucfirst($cap->status) }}
                                                    </span>
                                                </td>
                                                <td style="text-align:right;">
                                                    <div class="reg-row-actions">
                                                        <button type="button" class="reg-row-dots">&#x22EE;</button>
                                                        <div class="reg-row-menu">
                                                            <a href="{{ route('companies.borrowing-costs.show', [$company, $cap]) }}">View</a>
                                                            <form method="POST" action="{{ route('companies.borrowing-costs.destroy', [$company, $cap]) }}"
                                                                onsubmit="return false" data-confirm-label="Borrowing Costs" data-confirm-title="Remove" data-confirm-body="Remove {{ addslashes($cap->borrowing_source) }}?" data-confirm-text="Remove" data-confirm-danger="1">
                                                                @csrf @method('DELETE')
                                                                <button type="submit" class="menu-item danger">Remove</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    @if ($capitalisations->where('status', 'active')->count() > 0)
                                    <tfoot>
                                        <tr>
                                            <td colspan="5">Total capitalised (active)</td>
                                            <td class="amt">{{ number_format($totalCapitalised, 2) }}</td>
                                            <td colspan="2"></td>
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
