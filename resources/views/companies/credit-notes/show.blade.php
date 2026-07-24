@extends('layouts.public')

@section('title', $company->registered_name . ' — ' . $creditNote->credit_note_number)
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .cn-alert {
            padding: 5pt 10pt;
            font-size: 7pt;
            font-weight: 600;
            margin-bottom: 10pt;
            font-family: Helvetica, Arial, "DejaVu Sans", sans-serif;
        }
        .cn-alert.success { background: #dcfce7; border: 1px solid #bbf7d0; color: #15803d; }

        .cn-journal-tag {
            font-size: 5.5pt;
            font-weight: 700;
        }
        .cn-journal-tag.posted { color: #16a34a; }
        .cn-journal-tag.pending { color: #d97706; }

        .reg-btn.danger { border-color: #dc2626; color: #dc2626; }
        .reg-btn.danger:hover { background: #dc2626; color: #fff; }

        .reg-status.draft   { color: #92400e; border-color: #92400e; }
        .reg-status.issued  { color: #1d4ed8; border-color: #1d4ed8; }
        .reg-status.applied { color: #15803d; border-color: #15803d; }
        .reg-status.voided  { color: #b91c1c; border-color: #b91c1c; }

        .cn-letterhead {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 1.5pt solid #4c1d95;
            padding: 0 0 6pt;
            margin: 0 0 8pt;
        }
        .cn-letterhead-left {}
        .cn-letterhead-logo {
            max-height: 36pt;
            max-width: 120pt;
            object-fit: contain;
        }
        .cn-letterhead-company {
            font-size: 9pt;
            font-weight: 800;
            color: #4c1d95;
            letter-spacing: 0.01em;
        }
        .cn-letterhead-meta {
            font-size: 6.5pt;
            color: #6b5b8a;
            margin-top: 2pt;
            line-height: 1.6;
        }
        .cn-letterhead-right {
            text-align: right;
        }
        .cn-doc-title {
            font-size: 9pt;
            font-weight: 800;
            letter-spacing: 0.02em;
            color: #4c1d95;
            margin-bottom: 2pt;
        }
        .cn-doc-meta {
            font-size: 6.5pt;
            color: #6b5b8a;
            line-height: 1.7;
        }
        .cn-doc-meta strong { color: #4c1d95; }

        .cn-customer-block {
            font-size: 7pt;
            line-height: 1.7;
        }
        .cn-customer-block strong { color: #23282d; }
        .cn-customer-block .dim { color: #8b7aad; }

        .cn-notes-box {
            font-size: 7pt;
            color: #23282d;
            white-space: pre-line;
            border: 1px solid #c4b5fd;
            padding: 5pt 6pt;
            background: #f5f3ff;
        }

        .cn-sku { font-size: 5.5pt; color: #8b7aad; }
    </style>
@endpush

@section('content')
    <div class="co-wrap">
        @include('companies._topbar')

        <div class="co-body">
            @include('companies._sidebar')
            <main class="co-main">

                @if (session('success'))
                    <div class="cn-alert success">{{ session('success') }}</div>
                @endif

                {{-- Management bar --}}
                <div class="reg-mgmt-bar">
                    <div style="display:flex;align-items:center;gap:6pt;">
                        <span class="reg-status {{ $creditNote->status }}">{{ $creditNote->statusLabel() }}</span>
                        @if ($creditNote->posting_transaction_id)
                            <span class="cn-journal-tag posted">&#10003; Journal Posted</span>
                        @elseif ($creditNote->status === 'issued')
                            <span class="cn-journal-tag pending">&#9203; Posting in progress&hellip;</span>
                        @endif
                    </div>
                    <div style="display:flex;align-items:center;gap:4pt;flex-wrap:wrap;">
                        @if ($creditNote->status === 'draft')
                            <form method="POST" action="{{ route('companies.credit-notes.update-status', [$company, $creditNote]) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="issued">
                                <button type="submit" class="reg-btn primary">Issue &amp; Post Journal</button>
                            </form>
                        @endif
                        @if ($creditNote->status === 'issued')
                            <form method="POST" action="{{ route('companies.credit-notes.update-status', [$company, $creditNote]) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="applied">
                                <button type="submit" class="reg-btn primary">Mark Applied</button>
                            </form>
                        @endif
                        @if (!in_array($creditNote->status, ['voided', 'applied']))
                            <form method="POST" action="{{ route('companies.credit-notes.update-status', [$company, $creditNote]) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="voided">
                                <button type="submit" class="reg-btn danger" onclick="return confirm('Void this credit note?')">Void</button>
                            </form>
                        @endif
                        <a href="{{ route('companies.credit-notes.pdf', [$company, $creditNote]) }}" target="_blank" class="reg-btn">
                            <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            Download PDF
                        </a>
                        @if ($creditNote->invoice)
                            <a href="{{ route('companies.invoices.show', [$company, $creditNote->invoice]) }}" class="reg-btn">
                                Invoice {{ $creditNote->invoice->invoice_number }}
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Document --}}
                <div class="reg-doc">
                    <div class="reg-doc-body">

                        {{-- Letterhead --}}
                        <div class="cn-letterhead">
                            <div class="cn-letterhead-left">
                                @if ($company->logo_path && Storage::disk('public')->exists($company->logo_path))
                                    <img src="{{ Storage::url($company->logo_path) }}" class="cn-letterhead-logo" alt="">
                                @else
                                    <div class="cn-letterhead-company">{{ $company->registered_name }}</div>
                                @endif
                                <div class="cn-letterhead-meta">
                                    @if ($company->address_line_1)<div>{{ $company->address_line_1 }}</div>@endif
                                    @if ($company->city)<div>{{ $company->city }}@if ($company->postal_code), {{ $company->postal_code }}@endif</div>@endif
                                    @if ($company->vat_number)<div>VAT No: {{ $company->vat_number }}</div>@endif
                                </div>
                            </div>
                            <div class="cn-letterhead-right">
                                <div class="cn-doc-title">CREDIT NOTE</div>
                                <div class="cn-doc-meta">
                                    <div><strong>CN No:</strong> {{ $creditNote->credit_note_number }}</div>
                                    <div><strong>Date:</strong> {{ $creditNote->credit_note_date->format('d M Y') }}</div>
                                    @if ($creditNote->invoice)
                                        <div><strong>Re Invoice:</strong> {{ $creditNote->invoice->invoice_number }}</div>
                                    @endif
                                    @if ($creditNote->reason)
                                        <div><strong>Reason:</strong> {{ $creditNote->reason }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Customer --}}
                        <div class="reg-section-header">Credit To</div>
                        <div class="cn-customer-block" style="margin-bottom:8pt;">
                            <strong>{{ $creditNote->customer_name }}</strong>
                            @if ($creditNote->customer_email)<div class="dim">{{ $creditNote->customer_email }}</div>@endif
                            @if ($creditNote->customer_address)<div class="dim" style="white-space:pre-line;">{{ $creditNote->customer_address }}</div>@endif
                        </div>

                        <hr class="reg-divider">

                        {{-- Items --}}
                        <table class="reg-table">
                            <thead>
                                <tr>
                                    <th style="width:40%;">Description</th>
                                    <th class="amt" style="width:8%;">Qty</th>
                                    <th class="amt" style="width:14%;">Unit Price</th>
                                    <th class="amt" style="width:8%;">VAT %</th>
                                    <th class="amt" style="width:12%;">Tax</th>
                                    <th class="amt" style="width:14%;">Line Total</th>
                                    <th style="width:4%;text-align:center;" title="Return to stock">&#8617;</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($creditNote->items as $item)
                                    @php
                                        $base    = (float)$item->quantity * (float)$item->unit_price;
                                        $itemTax = $item->tax_rate !== null ? $base * ((float)$item->tax_rate / 100) : 0;
                                    @endphp
                                    <tr>
                                        <td>
                                            {{ $item->description }}
                                            @if ($item->inventoryItem?->sku)
                                                <div class="cn-sku">SKU: {{ $item->inventoryItem->sku }}</div>
                                            @endif
                                        </td>
                                        <td class="amt">{{ rtrim(rtrim(number_format((float)$item->quantity, 2), '0'), '.') }}</td>
                                        <td class="amt">{{ number_format((float)$item->unit_price, 2) }}</td>
                                        <td class="amt">{{ $item->tax_rate !== null ? number_format((float)$item->tax_rate, 2) . '%' : '—' }}</td>
                                        <td class="amt">{{ number_format($itemTax, 2) }}</td>
                                        <td class="amt">{{ number_format($base + $itemTax, 2) }}</td>
                                        <td style="text-align:center;font-size:6.5pt;">
                                            {{ $item->return_to_stock ? '&#10003;' : '' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" style="text-align:right;">Subtotal</td>
                                    <td class="amt">R {{ number_format($creditNote->subtotal(), 2) }}</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="5" style="text-align:right;">VAT</td>
                                    <td class="amt">R {{ number_format($creditNote->taxTotal(), 2) }}</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="5" style="text-align:right;">Total Credit</td>
                                    <td class="amt">R {{ number_format($creditNote->total(), 2) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>

                        @if ($creditNote->notes)
                            <div class="reg-section-header" style="margin-top:8pt;">Notes</div>
                            <div class="cn-notes-box">{{ $creditNote->notes }}</div>
                        @endif

                    </div>
                </div>

            </main>
        </div>
    </div>
@endsection
