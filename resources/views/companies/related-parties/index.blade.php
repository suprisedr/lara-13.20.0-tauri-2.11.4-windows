@extends('layouts.public')

@section('title', $company->registered_name . ' — Related Parties')
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
                        {{ $parties->where('is_active', true)->count() }} active related part{{ $parties->where('is_active', true)->count() !== 1 ? 'ies' : 'y' }}
                    </span>
                    <div style="display:flex;gap:4pt;">
                        <button type="button" class="reg-btn primary" onclick="document.getElementById('add-party-form').style.display = document.getElementById('add-party-form').style.display === 'none' ? 'block' : 'none'">+ Related Party</button>
                    </div>
                </div>

                {{-- Add party form --}}
                <div id="add-party-form" style="display:none;margin-bottom:12pt;background:#f7fbfd;border:1px solid #9ec1f5;padding:8pt 10pt;">
                    <p style="font-size:6pt;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:#005bf0;margin:0 0 6pt;">Register New Related Party</p>
                    <form method="POST" action="{{ route('companies.related-parties.store', $company) }}">
                        @csrf
                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:4pt 6pt;margin-bottom:6pt;">
                            <div class="reg-modal-field" style="grid-column:span 2;">
                                <label>Name</label>
                                <input type="text" name="name" required placeholder="e.g. ABC Holdings Ltd, John Smith — Director">
                            </div>
                            <div class="reg-modal-field">
                                <label>Relationship type</label>
                                <select name="relationship_type" required>
                                    <option value="">— Select —</option>
                                    @foreach (\App\Models\RelatedParty::TYPES as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="reg-modal-field">
                                <label>Contact person</label>
                                <input type="text" name="contact_person" placeholder="Optional">
                            </div>
                        </div>
                        <div class="reg-modal-field" style="margin-bottom:6pt;">
                            <label>Description</label>
                            <input type="text" name="description" maxlength="500" placeholder="Brief description of the relationship (optional)">
                        </div>
                        <div style="display:flex;justify-content:flex-end;">
                            <button type="submit" class="reg-btn primary">Register Party</button>
                        </div>
                    </form>
                </div>

                {{-- Related parties register --}}
                <div class="reg-doc">
                    <div class="reg-doc-body">
                        <div class="reg-doc-title">Related Party Register</div>
                        <p style="font-size:7pt;color:#5a7186;margin:1pt 0 6pt;">
                            IAS 24 Related Party Disclosures — register of related parties and their transactions for financial statement disclosure.
                        </p>

                        <hr class="reg-divider">

                        @if ($parties->isEmpty())
                            <p class="reg-empty">No related parties registered yet.</p>
                        @else
                            @php $totalAmount = 0.0; @endphp
                            <div style="overflow-x:auto;">
                                <table class="reg-table">
                                    <thead>
                                        <tr>
                                            <th style="width:22%;">Name</th>
                                            <th style="width:18%;">Relationship Type</th>
                                            <th style="width:15%;">Contact</th>
                                            <th class="amt" style="width:12%;">Transactions</th>
                                            <th class="amt" style="width:15%;">Total Amount</th>
                                            <th style="width:10%;text-align:center;">Status</th>
                                            <th style="width:8%;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($parties as $party)
                                            @php
                                                $partyTotal = (float) $party->relatedPartyTransactions->sum('amount');
                                                if ($party->is_active) $totalAmount += $partyTotal;
                                            @endphp
                                            <tr>
                                                <td>
                                                    <a class="reg-link" href="{{ route('companies.related-parties.show', [$company, $party]) }}">
                                                        {{ $party->name }}
                                                    </a>
                                                </td>
                                                <td><span class="cat-badge">{{ \App\Models\RelatedParty::TYPES[$party->relationship_type] ?? $party->relationship_type }}</span></td>
                                                <td class="dim" style="font-size:6.5pt;">{{ $party->contact_person ?? "\u{2014}" }}</td>
                                                <td class="amt">{{ $party->related_party_transactions_count }}</td>
                                                <td class="amt" style="font-weight:800;">{{ number_format($partyTotal, 2) }}</td>
                                                <td style="text-align:center;">
                                                    <span class="reg-status {{ !$party->is_active ? 'disposed' : '' }}">
                                                        {{ $party->is_active ? 'Active' : 'Inactive' }}
                                                    </span>
                                                </td>
                                                <td style="text-align:right;">
                                                    <div class="reg-row-actions">
                                                        <button type="button" class="reg-row-dots">&#x22EE;</button>
                                                        <div class="reg-row-menu">
                                                            <a href="{{ route('companies.related-parties.show', [$company, $party]) }}">View</a>
                                                            <form method="POST" action="{{ route('companies.related-parties.destroy', [$company, $party]) }}"
                                                                onsubmit="return false" data-confirm-label="Related Parties" data-confirm-title="Remove Party" data-confirm-body="Remove {{ addslashes($party->name) }} from the register?" data-confirm-text="Remove" data-confirm-danger="1">
                                                                @csrf @method('DELETE')
                                                                <button type="submit" class="menu-item danger">Remove</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    @if ($parties->where('is_active', true)->count() > 0)
                                    <tfoot>
                                        <tr>
                                            <td colspan="4">Total transaction amount (active parties)</td>
                                            <td class="amt">{{ number_format($totalAmount, 2) }}</td>
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
