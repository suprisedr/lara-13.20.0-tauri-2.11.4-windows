@extends('layouts.public')

@section('title', $company->registered_name . ' — Deferred Tax')
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
                        {{ $items->where('status', 'active')->count() }} active item{{ $items->where('status', 'active')->count() !== 1 ? 's' : '' }}
                    </span>
                    <div style="display:flex;gap:4pt;">
                        <button type="button" class="reg-btn primary" onclick="document.getElementById('add-form').style.display = document.getElementById('add-form').style.display === 'none' ? 'block' : 'none'">+ Deferred Tax Item</button>
                    </div>
                </div>

                <div id="add-form" style="display:none;margin-bottom:12pt;background:#f7fbfd;border:1px solid #9ec1f5;padding:8pt 10pt;">
                    <p style="font-size:6pt;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:#005bf0;margin:0 0 6pt;">New Deferred Tax Item</p>
                    <form method="POST" action="{{ route('companies.deferred-tax.store', $company) }}">
                        @csrf
                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:4pt 6pt;margin-bottom:6pt;">
                            <div class="reg-modal-field" style="grid-column:span 2;">
                                <label>Name</label>
                                <input type="text" name="name" required placeholder="e.g. Building depreciation difference">
                            </div>
                            <div class="reg-modal-field">
                                <label>Source type</label>
                                <select name="source_type" required>
                                    <option value="ppe">PPE</option>
                                    <option value="intangible">Intangible</option>
                                    <option value="lease">Lease</option>
                                    <option value="provision">Provision</option>
                                    <option value="revenue_contract">Revenue Contract</option>
                                    <option value="inventory">Inventory</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="reg-modal-field">
                                <label>Source ID (optional)</label>
                                <input type="number" name="source_id" min="1" placeholder="Asset/item ID">
                            </div>
                            <div class="reg-modal-field">
                                <label>Tax base (R)</label>
                                <input type="number" name="tax_base" step="0.01" min="0" required placeholder="0.00">
                            </div>
                            <div class="reg-modal-field">
                                <label>Carrying amount (R)</label>
                                <input type="number" name="carrying_amount" step="0.01" min="0" required placeholder="0.00">
                            </div>
                            <div class="reg-modal-field">
                                <label>Tax rate (%)</label>
                                <input type="number" name="tax_rate" step="0.01" min="0" max="100" required placeholder="e.g. 27">
                            </div>
                            <div class="reg-modal-field">
                                <label>Measurement date</label>
                                <input type="date" name="measurement_date" required value="{{ now()->format('Y-m-d') }}">
                            </div>
                            <div class="reg-modal-field" style="display:flex;align-items:center;gap:4pt;padding-top:10pt;">
                                <input type="hidden" name="is_taxable" value="0">
                                <input type="checkbox" name="is_taxable" value="1" checked id="is_taxable_check">
                                <label for="is_taxable_check" style="font-size:6.5pt;">Taxable</label>
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
                        <div class="reg-doc-title">Deferred Tax Items</div>
                        <p style="font-size:7pt;color:#5a7186;margin:1pt 0 6pt;">
                            IAS 12 Income Taxes — deferred tax assets and liabilities arising from temporary differences between carrying amounts and tax bases.
                        </p>

                        <hr class="reg-divider">

                        @if ($items->isEmpty())
                            <p class="reg-empty">No deferred tax items registered yet.</p>
                        @else
                            @php $totalDTA = 0.0; $totalDTL = 0.0; @endphp
                            <div style="overflow-x:auto;">
                                <table class="reg-table">
                                    <thead>
                                        <tr>
                                            <th style="width:18%;">Name</th>
                                            <th style="width:9%;">Source</th>
                                            <th class="amt" style="width:10%;">Tax Base</th>
                                            <th class="amt" style="width:10%;">Carrying Amt</th>
                                            <th class="amt" style="width:10%;">Temp Diff</th>
                                            <th class="amt" style="width:10%;">DTA</th>
                                            <th class="amt" style="width:10%;">DTL</th>
                                            <th class="amt" style="width:7%;">Rate</th>
                                            <th style="width:8%;text-align:center;">Status</th>
                                            <th style="width:3%;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($items as $item)
                                            @php
                                                if ($item->status === 'active') {
                                                    $totalDTA += (float) ($item->deferred_tax_asset ?? 0);
                                                    $totalDTL += (float) ($item->deferred_tax_liability ?? 0);
                                                }
                                            @endphp
                                            <tr>
                                                <td>
                                                    <a class="reg-link" href="{{ route('companies.deferred-tax.show', [$company, $item]) }}">
                                                        {{ $item->name }}
                                                    </a>
                                                </td>
                                                <td class="dim" style="font-size:6.5pt;">{{ ucfirst(str_replace('_', ' ', $item->source_type)) }}</td>
                                                <td class="amt">{{ number_format((float) $item->tax_base, 2) }}</td>
                                                <td class="amt">{{ number_format((float) $item->carrying_amount, 2) }}</td>
                                                <td class="amt">{{ number_format((float) $item->temporary_difference, 2) }}</td>
                                                <td class="amt" style="font-weight:800;">{{ number_format((float) ($item->deferred_tax_asset ?? 0), 2) }}</td>
                                                <td class="amt" style="font-weight:800;">{{ number_format((float) ($item->deferred_tax_liability ?? 0), 2) }}</td>
                                                <td class="amt">{{ number_format((float) $item->tax_rate, 2) }}%</td>
                                                <td style="text-align:center;">
                                                    <span class="reg-status {{ $item->status !== 'active' ? 'disposed' : '' }}">
                                                        {{ ucfirst($item->status) }}
                                                    </span>
                                                </td>
                                                <td style="text-align:right;">
                                                    <div class="reg-row-actions">
                                                        <button type="button" class="reg-row-dots">&#x22EE;</button>
                                                        <div class="reg-row-menu">
                                                            <a href="{{ route('companies.deferred-tax.show', [$company, $item]) }}">View</a>
                                                            <form method="POST" action="{{ route('companies.deferred-tax.destroy', [$company, $item]) }}"
                                                                onsubmit="return false" data-confirm-label="Deferred Tax" data-confirm-title="Remove" data-confirm-body="Remove {{ addslashes($item->name) }}?" data-confirm-text="Remove" data-confirm-danger="1">
                                                                @csrf @method('DELETE')
                                                                <button type="submit" class="menu-item danger">Remove</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    @if ($items->where('status', 'active')->count() > 0)
                                    <tfoot>
                                        <tr>
                                            <td colspan="5">Totals (active)</td>
                                            <td class="amt">{{ number_format($totalDTA, 2) }}</td>
                                            <td class="amt">{{ number_format($totalDTL, 2) }}</td>
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
