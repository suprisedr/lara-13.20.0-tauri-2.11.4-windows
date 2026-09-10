@extends('layouts.public')

@section('title', $company->registered_name . ' — Revenue Contracts')
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
                        {{ $contracts->where('status', 'active')->count() }} active contract{{ $contracts->where('status', 'active')->count() !== 1 ? 's' : '' }}
                    </span>
                    <div style="display:flex;gap:4pt;">
                        <button type="button" class="reg-btn primary" onclick="document.getElementById('add-form').style.display = document.getElementById('add-form').style.display === 'none' ? 'block' : 'none'">+ Contract</button>
                    </div>
                </div>

                <div id="add-form" style="display:none;margin-bottom:12pt;background:#f7fbfd;border:1px solid #9ec1f5;padding:8pt 10pt;">
                    <p style="font-size:6pt;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:#005bf0;margin:0 0 6pt;">New Revenue Contract</p>
                    <form method="POST" action="{{ route('companies.revenue-contracts.store', $company) }}">
                        @csrf
                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:4pt 6pt;margin-bottom:6pt;">
                            <div class="reg-modal-field" style="grid-column:span 2;">
                                <label>Contract name</label>
                                <input type="text" name="name" required placeholder="e.g. Software licence — ABC Corp">
                            </div>
                            <div class="reg-modal-field">
                                <label>Reference</label>
                                <input type="text" name="contract_reference" placeholder="e.g. CNT-2026-001">
                            </div>
                            <div class="reg-modal-field">
                                <label>Customer</label>
                                <input type="text" name="customer_name" required placeholder="Customer name">
                            </div>
                            <div class="reg-modal-field">
                                <label>Contract date</label>
                                <input type="date" name="contract_date" required value="{{ now()->format('Y-m-d') }}">
                            </div>
                            <div class="reg-modal-field">
                                <label>Transaction price (R)</label>
                                <input type="number" name="total_transaction_price" step="0.01" min="0" required placeholder="0.00">
                            </div>
                            <div class="reg-modal-field">
                                <label>Currency</label>
                                <input type="text" name="currency" value="ZAR" maxlength="3">
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
                        <div class="reg-doc-title">Revenue Contracts Register</div>
                        <p style="font-size:7pt;color:#5a7186;margin:1pt 0 6pt;">
                            IFRS 15 Revenue from Contracts with Customers — five-step model for revenue recognition.
                        </p>

                        <hr class="reg-divider">

                        @if ($contracts->isEmpty())
                            <p class="reg-empty">No revenue contracts registered yet.</p>
                        @else
                            @php $totalPrice = 0.0; $totalRecognised = 0.0; @endphp
                            <div style="overflow-x:auto;">
                                <table class="reg-table">
                                    <thead>
                                        <tr>
                                            <th style="width:20%;">Name</th>
                                            <th style="width:8%;">Ref</th>
                                            <th style="width:14%;">Customer</th>
                                            <th style="width:10%;">Date</th>
                                            <th class="amt" style="width:12%;">Txn Price</th>
                                            <th class="amt" style="width:12%;">Recognised</th>
                                            <th class="amt" style="width:6%;">POs</th>
                                            <th style="width:8%;text-align:center;">Status</th>
                                            <th style="width:3%;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($contracts as $contract)
                                            @php
                                                $recognised = $contract->totalRevenueRecognised();
                                                if ($contract->status === 'active') { $totalPrice += (float) $contract->total_transaction_price; $totalRecognised += $recognised; }
                                            @endphp
                                            <tr>
                                                <td>
                                                    <a class="reg-link" href="{{ route('companies.revenue-contracts.show', [$company, $contract]) }}">
                                                        {{ $contract->name }}
                                                    </a>
                                                </td>
                                                <td class="dim" style="font-size:6.5pt;">{{ $contract->contract_reference ?? "\u{2014}" }}</td>
                                                <td style="font-size:6.5pt;">{{ $contract->customer_name }}</td>
                                                <td style="font-size:6.5pt;">{{ $contract->contract_date->format('d M Y') }}</td>
                                                <td class="amt" style="font-weight:800;">{{ number_format((float) $contract->total_transaction_price, 2) }}</td>
                                                <td class="amt" style="color:#005bf0;">{{ number_format($recognised, 2) }}</td>
                                                <td class="amt">{{ $contract->performance_obligations_count }}</td>
                                                <td style="text-align:center;">
                                                    <span class="reg-status {{ $contract->status !== 'active' ? 'disposed' : '' }}">
                                                        {{ ucfirst($contract->status) }}
                                                    </span>
                                                </td>
                                                <td style="text-align:right;">
                                                    <div class="reg-row-actions">
                                                        <button type="button" class="reg-row-dots">&#x22EE;</button>
                                                        <div class="reg-row-menu">
                                                            <a href="{{ route('companies.revenue-contracts.show', [$company, $contract]) }}">View</a>
                                                            <form method="POST" action="{{ route('companies.revenue-contracts.destroy', [$company, $contract]) }}"
                                                                onsubmit="return false" data-confirm-label="Revenue" data-confirm-title="Remove Contract" data-confirm-body="Remove {{ addslashes($contract->name) }}?" data-confirm-text="Remove" data-confirm-danger="1">
                                                                @csrf @method('DELETE')
                                                                <button type="submit" class="menu-item danger">Remove</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    @if ($contracts->where('status', 'active')->count() > 0)
                                    <tfoot>
                                        <tr>
                                            <td colspan="4">Total (active)</td>
                                            <td class="amt">{{ number_format($totalPrice, 2) }}</td>
                                            <td class="amt">{{ number_format($totalRecognised, 2) }}</td>
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
