@extends('layouts.public')

@section('title', $relatedParty->name . ' — Related Party')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .inv-mgmt-bar { display:flex;align-items:center;justify-content:space-between;gap:8pt;flex-wrap:wrap;margin-bottom:12pt; }
        .inv-mgmt-bar a,.inv-mgmt-bar .mgmt-back { font-size:7pt;color:#6f869b;text-decoration:none;display:inline-flex;align-items:center;gap:3pt;transition:color 0.15s;background:none;border:none;cursor:pointer;font-family:inherit; }
        .inv-mgmt-bar a:hover,.inv-mgmt-bar .mgmt-back:hover { color:#1a345b; }
        .mgmt-btn { display:inline-flex;align-items:center;gap:3pt;background:#fff;border:1px solid #9ec1f5;color:#1a345b;font-size:6.5pt;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;padding:4pt 7pt;text-decoration:none;cursor:pointer;font-family:inherit;transition:background 0.15s,color 0.15s; }
        .mgmt-btn:hover { background:#f4fafc;color:#1a345b; }
        .mgmt-btn.primary { background:#005bf0;color:#fff; }
        .mgmt-btn.primary:hover { background:#005f9e; }
        .mgmt-btn.danger { border-color:#dc2626;color:#dc2626; }
        .mgmt-btn.danger:hover { background:#dc2626;color:#fff; }
        .cust-doc { background:#fff;border:1px solid #9ec1f5;font-family: "Century Gothic", "URW Gothic", "Avant Garde", Futura, "Avenir Next", Avenir, "Trebuchet MS", Helvetica, Arial, "DejaVu Sans", sans-serif;color:#1a345b;font-size:7pt;line-height:1.45; }
        .cust-doc-body { padding:16pt 18pt; }
        .cust-header-table { width:100%;border-collapse:collapse;margin-bottom:6pt; }
        .doc-title { font-size:11pt;font-weight:800;text-align:right;margin-bottom:2pt;letter-spacing:0.04em; }
        .doc-meta-line { text-align:right;font-size:7pt; }
        .status-box { display:inline-block;font-weight:700;text-transform:uppercase;border:1px solid #1a345b;padding:0.08rem 4pt;font-size:5pt;letter-spacing:0.08em;margin-top:3pt; }
        .status-box.inactive { color:#dc2626;border-color:#dc2626; }
        .divider { border:none;border-top:1.5pt solid #1a345b;margin:8pt 0 10pt; }
        .section-header { font-weight:700;font-size:7.5pt;text-transform:uppercase;letter-spacing:0.06em;border-bottom:1.5px solid #1a345b;padding-bottom:2pt;margin-bottom:4pt;color:#1a345b; }
        .af-row { display:flex;flex-wrap:wrap;gap:4pt 6pt;align-items:flex-end;margin-bottom:5pt; }
        .af-field { flex:1;min-width:100pt; }
        .af-field.wide { flex:2;min-width:150pt; }
        .af-field label { display:block;font-size:5pt;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;color:#6f869b;margin-bottom:0.2rem; }
        .af-field input,.af-field select,.af-field textarea { width:100%;border:1px solid #9ec1f5;padding:3pt 4pt;font-size:7pt;font-family:inherit;color:#1a345b;box-sizing:border-box;background:#fff; }
        .af-field input:focus,.af-field select:focus,.af-field textarea:focus { outline:none;border-color:#005bf0; }
        .af-hint { font-size:6pt;color:#6f869b;margin-bottom:4pt;line-height:1.4; }
        .as-field label { display:block;font-size:5pt;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;color:#6f869b;margin-bottom:0.2rem; }
        .as-field input,.as-field select { width:100%;border:1px solid #9ec1f5;padding:3pt 5pt;font-size:7pt;font-family:inherit;color:#1a345b;background:#fff;outline:none;box-sizing:border-box;height:16pt;border-radius:0; }
        .as-field input:focus,.as-field select:focus { border-color:#005bf0; }
        .arm-badge { display:inline-block;font-size:5pt;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;padding:1pt 4pt; }
        .arm-badge.yes { background:#dcfce7;color:#166534; }
        .arm-badge.no { background:#fee2e2;color:#991b1b; }
        @media (max-width:640px) {
            .cust-doc-body { padding:10pt 8pt; }
            .cust-header-table,.cust-header-table tr,.cust-header-table td { display:block;width:100%!important;text-align:left!important; }
            .doc-title,.doc-meta-line { text-align:left!important; }
        }
    </style>
@endpush

@section('content')
    <div class="co-wrap">

        @include('companies._topbar')

        <div class="co-body">
            @include('companies._sidebar')

            <main class="co-main">

                @if (session('success'))
                    <div style="background:#dcfce7;border:1px solid #bbf7d0;color:#15803d;padding:6pt 10pt;font-size:7.5pt;font-weight:600;margin-bottom:10pt;">
                        {{ session('success') }}
                    </div>
                @endif

                {{-- Management bar --}}
                <div class="inv-mgmt-bar">
                    <div style="display:flex;align-items:center;gap:4pt;flex-wrap:wrap;">
                        <button type="button" class="mgmt-btn primary" onclick="openEditModal()">Edit</button>
                        <button type="button" class="mgmt-btn" onclick="document.getElementById('add-txn-form').style.display = document.getElementById('add-txn-form').style.display === 'none' ? 'block' : 'none'">+ Transaction</button>
                    </div>
                </div>

                {{-- Document --}}
                <div class="cust-doc">
                    <div class="cust-doc-body">

                        {{-- Header --}}
                        <table class="cust-header-table">
                            <tr>
                                <td style="vertical-align:top;width:55%;">
                                    <div style="font-size:11pt;font-weight:800;letter-spacing:-0.01em;">{{ $relatedParty->name }}</div>
                                    @if ($relatedParty->description)
                                        <div style="font-size:7pt;color:#6f869b;margin-top:0.2rem;">{{ $relatedParty->description }}</div>
                                    @endif
                                </td>
                                <td style="vertical-align:top;width:45%;">
                                    <div class="doc-title">RELATED PARTY</div>
                                    <div class="doc-meta-line">Type: <strong>{{ \App\Models\RelatedParty::TYPES[$relatedParty->relationship_type] ?? $relatedParty->relationship_type }}</strong></div>
                                    @if ($relatedParty->contact_person)
                                        <div class="doc-meta-line">Contact: <strong>{{ $relatedParty->contact_person }}</strong></div>
                                    @endif
                                    <div style="text-align:right;">
                                        <span class="status-box {{ !$relatedParty->is_active ? 'inactive' : '' }}">
                                            {{ $relatedParty->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </table>

                        <hr class="divider">

                        {{-- Summary --}}
                        @php
                            $txns = $relatedParty->relatedPartyTransactions->sortByDesc('transaction_date');
                            $totalAmount = (float) $txns->sum('amount');
                            $totalOutstanding = (float) $txns->whereNotNull('outstanding_balance')->sum('outstanding_balance');
                        @endphp
                        <table style="width:100%;border-collapse:collapse;margin-bottom:8pt;">
                            <tr>
                                <td style="padding:0 10pt 0 0;font-size:7pt;vertical-align:top;">
                                    <span style="display:block;font-weight:700;font-size:5pt;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:1.5pt;">Total Transactions</span>
                                    <span style="font-size:8pt;font-weight:800;">{{ $txns->count() }}</span>
                                </td>
                                <td style="padding:0 10pt 0 0;font-size:7pt;vertical-align:top;">
                                    <span style="display:block;font-weight:700;font-size:5pt;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:1.5pt;">Total Amount</span>
                                    <span style="font-size:8pt;font-weight:800;">R {{ number_format($totalAmount, 2) }}</span>
                                </td>
                                <td style="padding:0 10pt 0 0;font-size:7pt;vertical-align:top;">
                                    <span style="display:block;font-weight:700;font-size:5pt;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:1.5pt;">Outstanding Balance</span>
                                    <span style="font-size:8pt;font-weight:800;color:#005bf0;">R {{ number_format($totalOutstanding, 2) }}</span>
                                </td>
                            </tr>
                        </table>

                        {{-- Add transaction form --}}
                        <div id="add-txn-form" style="display:none;margin-bottom:12pt;background:#f7fbfd;border:1px solid #9ec1f5;padding:8pt 10pt;">
                            <p style="font-size:6pt;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:#005bf0;margin:0 0 6pt;">Record Transaction</p>
                            <form method="POST" action="{{ route('companies.related-parties.transactions.store', [$company, $relatedParty]) }}">
                                @csrf
                                <div class="af-row">
                                    <div class="af-field">
                                        <label>Date</label>
                                        <input type="date" name="transaction_date" required value="{{ now()->format('Y-m-d') }}">
                                    </div>
                                    <div class="af-field">
                                        <label>Type</label>
                                        <select name="transaction_type" required>
                                            <option value="">-- Select --</option>
                                            @foreach (\App\Models\RelatedPartyTransaction::TRANSACTION_TYPES as $key => $label)
                                                <option value="{{ $key }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="af-field">
                                        <label>Amount (R)</label>
                                        <input type="number" name="amount" step="0.01" min="0" required placeholder="0.00">
                                    </div>
                                    <div class="af-field">
                                        <label>Outstanding balance (R)</label>
                                        <input type="number" name="outstanding_balance" step="0.01" placeholder="Optional">
                                    </div>
                                </div>
                                <div class="af-row">
                                    <div class="af-field wide">
                                        <label>Description</label>
                                        <input type="text" name="description" maxlength="500" placeholder="Brief description (optional)">
                                    </div>
                                    <div class="af-field wide">
                                        <label>Terms and conditions</label>
                                        <input type="text" name="terms_and_conditions" maxlength="500" placeholder="e.g. 30 days net, interest-free (optional)">
                                    </div>
                                </div>
                                <div class="af-row" style="align-items:center;">
                                    <div style="display:flex;align-items:center;gap:4pt;">
                                        <input type="checkbox" name="is_arm_length" value="1" id="is_arm_length" checked style="width:auto;">
                                        <label for="is_arm_length" style="font-size:6.5pt;color:#1a345b;cursor:pointer;margin:0;">Arm's length transaction</label>
                                    </div>
                                    <div style="flex:1;"></div>
                                    <button type="submit" class="mgmt-btn primary">Save Transaction</button>
                                </div>
                            </form>
                        </div>

                        {{-- Transactions table --}}
                        <div style="margin-top:8pt;">
                            <div class="section-header">Transactions</div>

                            @if ($txns->isEmpty())
                                <p style="color:#6f869b;font-style:italic;font-size:6.5pt;">No transactions recorded yet.</p>
                            @else
                                <div style="overflow-x:auto;">
                                    <table class="reg-table">
                                        <thead>
                                            <tr>
                                                <th style="width:12%;">Date</th>
                                                <th style="width:18%;">Type</th>
                                                <th class="amt" style="width:14%;">Amount</th>
                                                <th class="amt" style="width:14%;">Outstanding</th>
                                                <th style="width:10%;text-align:center;">Arm's Length</th>
                                                <th style="width:25%;">Description</th>
                                                <th style="width:7%;"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($txns as $txn)
                                                <tr>
                                                    <td>{{ $txn->transaction_date->format('d M Y') }}</td>
                                                    <td><span class="cat-badge">{{ \App\Models\RelatedPartyTransaction::TRANSACTION_TYPES[$txn->transaction_type] ?? $txn->transaction_type }}</span></td>
                                                    <td class="amt" style="font-weight:800;">{{ number_format((float) $txn->amount, 2) }}</td>
                                                    <td class="amt">{{ $txn->outstanding_balance !== null ? number_format((float) $txn->outstanding_balance, 2) : "\u{2014}" }}</td>
                                                    <td style="text-align:center;">
                                                        <span class="arm-badge {{ $txn->is_arm_length ? 'yes' : 'no' }}">
                                                            {{ $txn->is_arm_length ? 'Yes' : 'No' }}
                                                        </span>
                                                    </td>
                                                    <td class="dim" style="font-size:6.5pt;">{{ $txn->description ?? "\u{2014}" }}</td>
                                                    <td style="text-align:right;">
                                                        <div class="reg-row-actions">
                                                            <button type="button" class="reg-row-dots">&#x22EE;</button>
                                                            <div class="reg-row-menu">
                                                                <form method="POST" action="{{ route('companies.related-parties.transactions.destroy', [$company, $relatedParty, $txn]) }}"
                                                                    onsubmit="return false" data-confirm-label="Related Parties" data-confirm-title="Remove Transaction" data-confirm-body="Remove this transaction (R {{ number_format((float) $txn->amount, 2) }})?" data-confirm-text="Remove" data-confirm-danger="1">
                                                                    @csrf @method('DELETE')
                                                                    <button type="submit" class="menu-item danger">Remove</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="2">Totals</td>
                                                <td class="amt">{{ number_format($totalAmount, 2) }}</td>
                                                <td class="amt">{{ number_format($totalOutstanding, 2) }}</td>
                                                <td colspan="3"></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            @endif
                        </div>

                    </div>{{-- /.cust-doc-body --}}
                </div>{{-- /.cust-doc --}}

            </main>
        </div>
    </div>

    {{-- Edit modal --}}
    <div id="edit-modal"
        style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:9999;align-items:center;justify-content:center;padding:16pt 8pt;overflow-y:auto;">
        <div style="background:#fff;width:100%;max-width:520px;border:1px solid #1a345b;border-radius:0;box-shadow:0 20px 60px rgba(0,0,0,0.4);overflow:hidden;margin:0 auto;">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:9pt 12pt;border-bottom:1.5px solid #1a345b;">
                <h3 style="font-size:1.05rem;font-weight:800;letter-spacing:0.04em;margin:0;text-transform:uppercase;color:#1a345b;">Edit Related Party</h3>
                <button onclick="closeEditModal()"
                    style="background:none;border:none;font-size:1.4rem;line-height:1;color:#1a345b;cursor:pointer;">&times;</button>
            </div>

            <form method="POST" action="{{ route('companies.related-parties.update', [$company, $relatedParty]) }}">
                @csrf
                @method('PATCH')
                <div style="padding:10pt 12pt;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:5pt 7pt;margin-bottom:8pt;">
                    <div class="as-field" style="grid-column:1/-1;">
                        <label>Name</label>
                        <input type="text" name="name" required value="{{ old('name', $relatedParty->name) }}">
                    </div>
                    <div class="as-field">
                        <label>Relationship type</label>
                        <select name="relationship_type" required>
                            @foreach (\App\Models\RelatedParty::TYPES as $key => $label)
                                <option value="{{ $key }}" {{ $relatedParty->relationship_type === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="as-field">
                        <label>Contact person</label>
                        <input type="text" name="contact_person" value="{{ old('contact_person', $relatedParty->contact_person) }}">
                    </div>
                    <div class="as-field" style="grid-column:1/-1;">
                        <label>Description</label>
                        <input type="text" name="description" value="{{ old('description', $relatedParty->description) }}" maxlength="500">
                    </div>
                    <div style="display:flex;align-items:center;gap:4pt;grid-column:1/-1;">
                        <input type="checkbox" name="is_active" value="1" id="edit_is_active" {{ $relatedParty->is_active ? 'checked' : '' }} style="width:auto;">
                        <label for="edit_is_active" style="font-size:6.5pt;color:#1a345b;cursor:pointer;margin:0;">Active</label>
                    </div>
                </div>
                <div style="display:flex;gap:5pt;justify-content:flex-end;padding-top:4pt;border-top:1px solid #9ec1f5;">
                    <button type="button" onclick="closeEditModal()"
                        style="padding:4pt 9pt;border:1px solid #9ec1f5;font-size:6.5pt;color:#1a345b;background:#fff;cursor:pointer;font-weight:600;border-radius:0;">Cancel</button>
                    <button type="submit"
                        style="padding:4pt 10pt;background:#005bf0;color:#fff;border:1px solid #005bf0;font-size:6.5pt;font-weight:700;cursor:pointer;border-radius:0;">Save changes</button>
                </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal()  { document.getElementById('edit-modal').style.display = 'flex'; }
        function closeEditModal() { document.getElementById('edit-modal').style.display = 'none'; }
        document.getElementById('edit-modal').addEventListener('click', function(e) {
            if (e.target === this) closeEditModal();
        });
    </script>
    @include('companies._row-actions')
@endsection
