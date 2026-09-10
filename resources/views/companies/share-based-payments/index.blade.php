@extends('layouts.public')

@section('title', $company->registered_name . ' — Share-Based Payments')
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
                        {{ $arrangements->where('status', 'active')->count() }} active arrangement{{ $arrangements->where('status', 'active')->count() !== 1 ? 's' : '' }}
                    </span>
                    <div style="display:flex;gap:4pt;">
                        <button type="button" class="reg-btn primary" onclick="document.getElementById('add-form').style.display = document.getElementById('add-form').style.display === 'none' ? 'block' : 'none'">+ Arrangement</button>
                    </div>
                </div>

                <div id="add-form" style="display:none;margin-bottom:12pt;background:#f7fbfd;border:1px solid #9ec1f5;padding:8pt 10pt;">
                    <p style="font-size:6pt;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:#005bf0;margin:0 0 6pt;">New Share-Based Payment Arrangement</p>
                    <form method="POST" action="{{ route('companies.share-based-payments.store', $company) }}">
                        @csrf
                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:4pt 6pt;margin-bottom:6pt;">
                            <div class="reg-modal-field" style="grid-column:span 2;">
                                <label>Name</label>
                                <input type="text" name="name" required placeholder="e.g. Employee Share Option Plan 2026">
                            </div>
                            <div class="reg-modal-field">
                                <label>Arrangement type</label>
                                <select name="arrangement_type" required>
                                    <option value="equity_settled">Equity-settled</option>
                                    <option value="cash_settled">Cash-settled</option>
                                    <option value="choice">Choice</option>
                                </select>
                            </div>
                            <div class="reg-modal-field">
                                <label>Grant date</label>
                                <input type="date" name="grant_date" required value="{{ now()->format('Y-m-d') }}">
                            </div>
                            <div class="reg-modal-field">
                                <label>Vesting start date</label>
                                <input type="date" name="vesting_start_date">
                            </div>
                            <div class="reg-modal-field">
                                <label>Vesting end date</label>
                                <input type="date" name="vesting_end_date">
                            </div>
                            <div class="reg-modal-field">
                                <label>Number of instruments</label>
                                <input type="number" name="number_of_instruments" required min="1" placeholder="e.g. 10000">
                            </div>
                            <div class="reg-modal-field">
                                <label>Exercise price (R)</label>
                                <input type="number" name="exercise_price" step="0.01" min="0" placeholder="Optional">
                            </div>
                            <div class="reg-modal-field">
                                <label>Fair value at grant (R)</label>
                                <input type="number" name="fair_value_at_grant" step="0.01" min="0" required placeholder="e.g. 15.00">
                            </div>
                        </div>
                        <div class="reg-modal-field" style="margin-bottom:6pt;">
                            <label>Vesting conditions</label>
                            <input type="text" name="vesting_conditions" maxlength="500" placeholder="e.g. 3 years continuous service">
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
                        <div class="reg-doc-title">Share-Based Payment Arrangements</div>
                        <p style="font-size:7pt;color:#5a7186;margin:1pt 0 6pt;">
                            IFRS 2 Share-Based Payments — recognition of share-based payment transactions in the financial statements.
                        </p>

                        <hr class="reg-divider">

                        @if ($arrangements->isEmpty())
                            <p class="reg-empty">No share-based payment arrangements registered yet.</p>
                        @else
                            <div style="overflow-x:auto;">
                                <table class="reg-table">
                                    <thead>
                                        <tr>
                                            <th style="width:20%;">Name</th>
                                            <th style="width:10%;">Type</th>
                                            <th style="width:10%;">Grant Date</th>
                                            <th class="amt" style="width:10%;">Instruments</th>
                                            <th class="amt" style="width:12%;">FV at Grant</th>
                                            <th class="amt" style="width:12%;">Total Expense</th>
                                            <th class="amt" style="width:8%;">Vesting %</th>
                                            <th style="width:10%;text-align:center;">Status</th>
                                            <th style="width:3%;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($arrangements as $arrangement)
                                            <tr>
                                                <td>
                                                    <a class="reg-link" href="{{ route('companies.share-based-payments.show', [$company, $arrangement]) }}">
                                                        {{ $arrangement->name }}
                                                    </a>
                                                </td>
                                                <td class="dim" style="font-size:6.5pt;">{{ ucfirst(str_replace('_', ' ', $arrangement->arrangement_type)) }}</td>
                                                <td style="font-size:6.5pt;">{{ $arrangement->grant_date->format('d M Y') }}</td>
                                                <td class="amt">{{ number_format($arrangement->number_of_instruments) }}</td>
                                                <td class="amt">{{ number_format((float) $arrangement->fair_value_at_grant, 2) }}</td>
                                                <td class="amt" style="font-weight:800;">{{ number_format((float) ($arrangement->total_expense ?? 0), 2) }}</td>
                                                <td class="amt">{{ number_format($arrangement->vestingPercentage(), 1) }}%</td>
                                                <td style="text-align:center;">
                                                    <span class="reg-status {{ $arrangement->status !== 'active' ? 'disposed' : '' }}">
                                                        {{ ucfirst($arrangement->status) }}
                                                    </span>
                                                </td>
                                                <td style="text-align:right;">
                                                    <div class="reg-row-actions">
                                                        <button type="button" class="reg-row-dots">&#x22EE;</button>
                                                        <div class="reg-row-menu">
                                                            <a href="{{ route('companies.share-based-payments.show', [$company, $arrangement]) }}">View</a>
                                                            <form method="POST" action="{{ route('companies.share-based-payments.destroy', [$company, $arrangement]) }}"
                                                                onsubmit="return false" data-confirm-label="Share-Based Payments" data-confirm-title="Remove" data-confirm-body="Remove {{ addslashes($arrangement->name) }}?" data-confirm-text="Remove" data-confirm-danger="1">
                                                                @csrf @method('DELETE')
                                                                <button type="submit" class="menu-item danger">Remove</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
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
