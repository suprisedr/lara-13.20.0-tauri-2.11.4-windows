@extends('layouts.public')

@section('title', $company->registered_name . ' — Government Grants')
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
                        {{ $grants->where('status', 'active')->count() }} active grant{{ $grants->where('status', 'active')->count() !== 1 ? 's' : '' }}
                    </span>
                    <div style="display:flex;gap:4pt;">
                        <button type="button" class="reg-btn primary" onclick="document.getElementById('add-form').style.display = document.getElementById('add-form').style.display === 'none' ? 'block' : 'none'">+ Grant</button>
                    </div>
                </div>

                <div id="add-form" style="display:none;margin-bottom:12pt;background:#f7fbfd;border:1px solid #9ec1f5;padding:8pt 10pt;">
                    <p style="font-size:6pt;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:#005bf0;margin:0 0 6pt;">New Government Grant</p>
                    <form method="POST" action="{{ route('companies.government-grants.store', $company) }}">
                        @csrf
                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:4pt 6pt;margin-bottom:6pt;">
                            <div class="reg-modal-field" style="grid-column:span 2;">
                                <label>Name</label>
                                <input type="text" name="name" required placeholder="e.g. SARS R&D Tax Incentive, DTI Manufacturing Grant">
                            </div>
                            <div class="reg-modal-field">
                                <label>Grant type</label>
                                <select name="grant_type" required>
                                    <option value="income">Income</option>
                                    <option value="asset">Asset</option>
                                </select>
                            </div>
                            <div class="reg-modal-field">
                                <label>Grant reference</label>
                                <input type="text" name="grant_reference" placeholder="Optional reference">
                            </div>
                            <div class="reg-modal-field">
                                <label>Granting authority</label>
                                <input type="text" name="granting_authority" placeholder="e.g. DTI, SARS, IDC">
                            </div>
                            <div class="reg-modal-field">
                                <label>Grant date</label>
                                <input type="date" name="grant_date" required value="{{ now()->format('Y-m-d') }}">
                            </div>
                            <div class="reg-modal-field">
                                <label>Total amount (R)</label>
                                <input type="number" name="total_amount" step="0.01" min="0" required placeholder="e.g. 500000.00">
                            </div>
                            <div class="reg-modal-field">
                                <label>Recognition method</label>
                                <select name="recognition_method">
                                    <option value="systematic">Systematic</option>
                                    <option value="immediate">Immediate</option>
                                </select>
                            </div>
                            <div class="reg-modal-field">
                                <label>Related asset type</label>
                                <input type="text" name="related_asset_type" placeholder="Optional">
                            </div>
                            <div class="reg-modal-field">
                                <label>Related asset ID</label>
                                <input type="number" name="related_asset_id" min="1" placeholder="Optional">
                            </div>
                        </div>
                        <div class="reg-modal-field" style="margin-bottom:6pt;">
                            <label>Conditions</label>
                            <textarea name="conditions_text" rows="2" style="width:100%;border:1px solid #9ec1f5;padding:3pt 5pt;font-size:7pt;font-family:inherit;color:#1a345b;background:#fff;box-sizing:border-box;" placeholder="Optional grant conditions"></textarea>
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
                        <div class="reg-doc-title">Government Grants</div>
                        <p style="font-size:7pt;color:#5a7186;margin:1pt 0 6pt;">
                            IAS 20 Government Grants — accounting for government grants and disclosure of government assistance.
                        </p>

                        <hr class="reg-divider">

                        @if ($grants->isEmpty())
                            <p class="reg-empty">No government grants registered yet.</p>
                        @else
                            @php $totalDeferred = 0.0; @endphp
                            <div style="overflow-x:auto;">
                                <table class="reg-table">
                                    <thead>
                                        <tr>
                                            <th style="width:20%;">Name</th>
                                            <th style="width:8%;">Type</th>
                                            <th style="width:14%;">Authority</th>
                                            <th style="width:10%;">Date</th>
                                            <th class="amt" style="width:12%;">Total</th>
                                            <th class="amt" style="width:12%;">Recognised</th>
                                            <th class="amt" style="width:12%;">Deferred</th>
                                            <th style="width:9%;text-align:center;">Status</th>
                                            <th style="width:3%;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($grants as $grant)
                                            @php
                                                if ($grant->status === 'active') $totalDeferred += (float) ($grant->deferred_amount ?? 0);
                                            @endphp
                                            <tr>
                                                <td>
                                                    <a class="reg-link" href="{{ route('companies.government-grants.show', [$company, $grant]) }}">
                                                        {{ $grant->name }}
                                                    </a>
                                                </td>
                                                <td class="dim" style="font-size:6.5pt;">{{ ucfirst($grant->grant_type) }}</td>
                                                <td class="dim" style="font-size:6.5pt;">{{ $grant->granting_authority }}</td>
                                                <td style="font-size:6.5pt;">{{ $grant->grant_date->format('d M Y') }}</td>
                                                <td class="amt" style="font-weight:800;">{{ number_format((float) ($grant->total_amount ?? 0), 2) }}</td>
                                                <td class="amt">{{ number_format((float) ($grant->recognised_amount ?? 0), 2) }}</td>
                                                <td class="amt">{{ number_format((float) ($grant->deferred_amount ?? 0), 2) }}</td>
                                                <td style="text-align:center;">
                                                    <span class="reg-status {{ $grant->status !== 'active' ? 'disposed' : '' }}">
                                                        {{ ucfirst($grant->status) }}
                                                    </span>
                                                </td>
                                                <td style="text-align:right;">
                                                    <div class="reg-row-actions">
                                                        <button type="button" class="reg-row-dots">&#x22EE;</button>
                                                        <div class="reg-row-menu">
                                                            <a href="{{ route('companies.government-grants.show', [$company, $grant]) }}">View</a>
                                                            <form method="POST" action="{{ route('companies.government-grants.destroy', [$company, $grant]) }}"
                                                                onsubmit="return false" data-confirm-label="Government Grants" data-confirm-title="Remove" data-confirm-body="Remove {{ addslashes($grant->name) }}?" data-confirm-text="Remove" data-confirm-danger="1">
                                                                @csrf @method('DELETE')
                                                                <button type="submit" class="menu-item danger">Remove</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    @if ($grants->where('status', 'active')->count() > 0)
                                    <tfoot>
                                        <tr>
                                            <td colspan="6">Total deferred (active)</td>
                                            <td class="amt">{{ number_format($totalDeferred, 2) }}</td>
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
