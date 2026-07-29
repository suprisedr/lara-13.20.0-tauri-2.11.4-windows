@extends('layouts.public')

@section('title', $company->registered_name . ' — Invoice ' . $invoice->invoice_number)
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        /* ── Invoice status badges ─────────────────────────────── */
        .reg-status.draft     { color: #92400e; border-color: #92400e; background: #fef9c3; }
        .reg-status.pending   { color: #1d4ed8; border-color: #1d4ed8; background: #dbeafe; }
        .reg-status.partially_paid { color: #b45309; border-color: #b45309; background: #fef3c7; }
        .reg-status.paid      { color: #15803d; border-color: #15803d; background: #dcfce7; }
        .reg-status.overdue   { color: #b91c1c; border-color: #b91c1c; background: #fee2e2; }
        .reg-status.voided    { color: #6b5b8a; border-color: #6b5b8a; background: #f3f4f6; }
        .reg-status.write_off { color: #4c1d95; border-color: #4c1d95; background: #ede9fe; }

        /* ── Invoice parties grid ──────────────────────────────── */
        .inv-parties {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10pt;
            margin-bottom: 10pt;
        }


        /* ── Banking details grid ──────────────────────────────── */
        .inv-bank-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 6pt 14pt;
            background: #f5f3ff;
            border: 1px solid #c4b5fd;
            padding: 6pt 8pt;
        }

        /* ── Notes block ───────────────────────────────────────── */
        .inv-notes {
            background: #f5f3ff;
            border: 1px solid #c4b5fd;
            padding: 6pt 8pt;
            font-size: 7pt;
            color: #23282d;
            line-height: 1.55;
            white-space: pre-line;
        }

        /* ── Document footer ───────────────────────────────────── */
        .inv-doc-footer {
            border-top: 0.75pt solid #c4b5fd;
            padding-top: 6pt;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 6pt;
            flex-wrap: wrap;
            font-size: 6pt;
            color: #8b7aad;
        }

        /* ── Date rows ─────────────────────────────────────────── */
        .inv-date-line {
            font-size: 6.5pt;
            color: #6b5b8a;
            margin-bottom: 1pt;
            text-align: right;
        }
        .inv-date-line strong {
            color: #23282d;
            font-weight: 700;
            margin-left: 3pt;
        }

        /* ── Status form inline ────────────────────────────────── */
        .inv-status-form {
            display: flex;
            align-items: center;
            gap: 4pt;
        }
        .inv-status-select {
            border: 1px solid #c4b5fd;
            padding: 2pt 5pt;
            font-size: 6.5pt;
            font-family: Helvetica, Arial, "DejaVu Sans", sans-serif;
            color: #4c1d95;
            background: #fff;
            outline: none;
            cursor: pointer;
        }

        /* ── Credit note / delivery note accent buttons ────────── */
        .reg-btn.cn { border-color: #b91c1c; color: #b91c1c; }
        .reg-btn.cn:hover { background: #b91c1c; color: #fff; }
        .reg-btn.dn { border-color: #6b5b8a; color: #6b5b8a; }
        .reg-btn.dn:hover { background: #6b5b8a; color: #fff; }

        /* ── Journal posting status rows ───────────────────────── */
        .inv-journals {
            display: flex;
            flex-direction: column;
            gap: 6pt;
            margin-bottom: 8pt;
        }
        .inv-journal-row {
            display: flex;
            align-items: center;
            gap: 6pt;
            padding: 6pt 10pt;
            border-radius: 4px;
            font-size: 6.5pt;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .inv-journal-row.pending {
            background: #eff6ff;
            border: 1px solid #93c5fd;
            color: #1e40af;
        }
        .inv-journal-row.posted {
            background: #f0fdf4;
            border: 1px solid #86efac;
            color: #166534;
        }
        .inv-journal-row.failed {
            background: #fef2f2;
            border: 1px solid #fca5a5;
            color: #991b1b;
        }
        .inv-journal-label {
            flex: 1;
        }
        .inv-journal-status {
            font-size: 5.5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .inv-journal-spinner {
            width: 12px;
            height: 12px;
            border: 2px solid #93c5fd;
            border-top-color: #1d4ed8;
            border-radius: 50%;
            animation: ai-spin 0.8s linear infinite;
            flex-shrink: 0;
        }
        @keyframes ai-spin { to { transform: rotate(360deg); } }
        .inv-journal-icon {
            width: 12px;
            height: 12px;
            flex-shrink: 0;
        }

        /* ── Success / error flash ─────────────────────────────── */
        .inv-flash {
            padding: 4pt 8pt;
            font-size: 6.5pt;
            font-weight: 700;
            margin-bottom: 8pt;
        }
        .inv-flash.success { background: #dcfce7; border: 1px solid #bbf7d0; color: #15803d; }
        .inv-flash.error   { background: #fee2e2; border: 1px solid #fecaca; color: #b91c1c; }

        /* ── Field error ───────────────────────────────────────── */
        .field-error { font-size: 6pt; color: #b91c1c; margin: 2pt 0 0; }

        @media (max-width: 640px) {
            .inv-parties { grid-template-columns: 1fr; }
            .afs-letterhead { flex-direction: column; gap: 4pt; }
            .afs-letterhead-meta.afs-right { text-align: left; }
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
                    <div class="inv-flash success">{{ session('success') }}</div>
                @endif

                @if (session('error'))
                    <div class="inv-flash error">{{ session('error') }}</div>
                @endif

                {{-- Journal posting status rows --}}
                <div class="inv-journals" id="invoiceJournals">
                    @if ($invoice->status !== 'draft')
                    <div class="inv-journal-row {{ $invoice->posting_transaction_id ? 'posted' : 'pending' }}" id="journalPosting" data-type="invoice">
                        @if ($invoice->posting_transaction_id)
                            <svg class="inv-journal-icon" fill="none" stroke="#166534" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @else
                            <div class="inv-journal-spinner" data-spinner></div>
                        @endif
                        <span class="inv-journal-label">Invoice Journal</span>
                        <span class="inv-journal-status" data-journal-status>
                            @if ($invoice->posting_transaction_id)
                                <a href="{{ route('companies.transactions', [$company, 'amount' => number_format($invoice->total(), 2, '.', ''), 'start_date' => $invoice->invoice_date->format('Y-m-d'), 'end_date' => $invoice->invoice_date->format('Y-m-d')]) }}" style="color:inherit;text-decoration:underline;">Journal posted</a>
                            @else
                                Journal pending
                            @endif
                        </span>
                    </div>
                    @endif
                    @if ($invoice->payment_transaction_id || in_array($invoice->status, ['paid', 'partially_paid']))
                    <div class="inv-journal-row {{ $invoice->payment_transaction_id ? 'posted' : 'pending' }}" id="journalPayment" data-type="invoice_payment">
                        @if ($invoice->payment_transaction_id)
                            <svg class="inv-journal-icon" fill="none" stroke="#166534" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @else
                            <div class="inv-journal-spinner" data-spinner></div>
                        @endif
                        <span class="inv-journal-label">Payment Journal</span>
                        <span class="inv-journal-status" data-journal-status>
                            @if ($invoice->payment_transaction_id)
                                @php $paymentDate = $invoice->paymentTransaction->transaction_date; @endphp
                                <a href="{{ route('companies.transactions', [$company, 'amount' => number_format($invoice->total(), 2, '.', ''), 'start_date' => \Carbon\Carbon::parse($paymentDate)->format('Y-m-d'), 'end_date' => \Carbon\Carbon::parse($paymentDate)->format('Y-m-d')]) }}" style="color:inherit;text-decoration:underline;">Journal posted</a>
                            @else
                                Journal pending
                            @endif
                        </span>
                    </div>
                    @endif
                </div>

                {{-- Management bar --}}
                <div class="reg-mgmt-bar">
                    <div style="display:flex;align-items:center;gap:4pt;flex-wrap:wrap;">
                        @if ($invoice->status === \App\Enums\InvoiceStatus::Draft->value)
                            <a href="{{ route('companies.invoices.edit', [$company, $invoice]) }}" class="reg-btn">
                                <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                                Edit
                            </a>
                        @endif
                        <a href="{{ route('companies.invoices.pdf', [$company, $invoice]) }}" class="reg-btn">
                            <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="7 10 12 15 17 10"/>
                                <line x1="12" y1="15" x2="12" y2="3"/>
                            </svg>
                            Download PDF
                        </a>
                        {{-- Credit Note button --}}
                        @php $existingCn = $company->creditNotes()->where('invoice_id', $invoice->id)->first(); @endphp
                        @if ($existingCn)
                            <a href="{{ route('companies.credit-notes.show', [$company, $existingCn]) }}" class="reg-btn cn">
                                CN: {{ $existingCn->credit_note_number }}
                            </a>
                        @else
                            <a href="{{ route('companies.credit-notes.create', $company) }}?invoice_id={{ $invoice->id }}" class="reg-btn cn">
                                <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="11" y2="17"/></svg>
                                Credit Note
                            </a>
                        @endif

                        @php $existingDn = $company->deliveryNotes()->where('invoice_id', $invoice->id)->first(); @endphp
                        @if ($existingDn)
                            <a href="{{ route('companies.delivery-notes.show', [$company, $existingDn]) }}" class="reg-btn dn">
                                <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 4v4h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                                {{ $existingDn->delivery_note_number }}
                            </a>
                        @else
                            <a href="{{ route('companies.delivery-notes.create', $company) }}?invoice_id={{ $invoice->id }}" class="reg-btn dn">
                                <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 4v4h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                                Delivery Note
                            </a>
                        @endif
                    @php
                        $currentInvoiceStatus = \App\Enums\InvoiceStatus::from($invoice->status);
                        $invoiceStatusOptions = [$currentInvoiceStatus, ...$currentInvoiceStatus->allowedTransitions()];
                    @endphp
                    <form method="POST" action="{{ route('companies.invoices.update-status', [$company, $invoice]) }}" class="inv-status-form">
                        @csrf
                        @method('PATCH')
                        <select name="status" class="inv-status-select">
                            @foreach ($invoiceStatusOptions as $statusOption)
                                <option value="{{ $statusOption->value }}" {{ $invoice->status === $statusOption->value ? 'selected' : '' }}>{{ $statusOption->label() }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="reg-btn primary">Update Status</button>
                    </form>
                    </div>
                </div>

                {{-- Invoice document --}}
                <div class="reg-doc">
                    <div class="reg-doc-body">

                        {{-- Letterhead: company + invoice number --}}
                        <div class="afs-letterhead">
                            <div>
                                <div class="afs-letterhead-name">{{ $company->registered_name }}</div>
                                <div class="afs-letterhead-meta">
                                    @if ($company->address_line_1)
                                        {{ $company->address_line_1 }}<br>
                                    @endif
                                    @if ($company->address_line_2)
                                        {{ $company->address_line_2 }}<br>
                                    @endif
                                    @if ($company->city || $company->postal_code)
                                        {{ implode(', ', array_filter([$company->city, $company->province, $company->postal_code])) }}<br>
                                    @endif
                                    @if ($company->vat_number)
                                        VAT Reg. No: {{ $company->vat_number }}<br>
                                    @endif
                                    @if ($company->registration_number)
                                        Reg. No: {{ $company->registration_number }}
                                    @endif
                                </div>
                            </div>
                            <div class="afs-letterhead-meta afs-right">
                                <div style="font-size:9pt;font-weight:800;color:#4c1d95;margin-bottom:3pt;">{{ $invoice->invoice_number }}</div>
                                <div style="margin-bottom:4pt;">
                                    <span class="reg-status {{ $invoice->status }}">{{ $currentInvoiceStatus->label() }}</span>
                                </div>
                                <div class="inv-date-line">
                                    Invoice Date: <strong>{{ $invoice->invoice_date->format('d M Y') }}</strong>
                                </div>
                                @if ($invoice->due_date)
                                    <div class="inv-date-line">
                                        Due Date: <strong>{{ $invoice->due_date->format('d M Y') }}</strong>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Parties --}}
                        <div class="inv-parties" style="margin-top:8pt;">
                            <div>
                                <div class="reg-section-header">Bill To</div>
                                <p style="font-size:7pt;font-weight:700;color:#23282d;margin:2pt 0 1pt;">
                                    {{ optional($invoice->customer)->name ?? $invoice->customer_name }}
                                </p>
                                @if ($invoice->customer_email)
                                    <p style="font-size:7pt;color:#6b5b8a;margin:0 0 1pt;">{{ $invoice->customer_email }}</p>
                                @endif
                                @if ($invoice->customer_address)
                                    <p style="font-size:7pt;color:#6b5b8a;margin:0;white-space:pre-line;">{{ $invoice->customer_address }}</p>
                                @endif
                            </div>
                            <div>
                                <div class="reg-section-header">From</div>
                                <p style="font-size:7pt;font-weight:700;color:#23282d;margin:2pt 0 1pt;">{{ $company->registered_name }}</p>
                                @if ($company->address_line_1)
                                    <p style="font-size:7pt;color:#6b5b8a;margin:0 0 1pt;">{{ $company->address_line_1 }}</p>
                                @endif
                                @if ($company->address_line_2)
                                    <p style="font-size:7pt;color:#6b5b8a;margin:0 0 1pt;">{{ $company->address_line_2 }}</p>
                                @endif
                                @if ($company->city)
                                    <p style="font-size:7pt;color:#6b5b8a;margin:0 0 1pt;">
                                        {{ implode(', ', array_filter([$company->city, $company->province, $company->postal_code])) }}
                                    </p>
                                @endif
                                @if ($company->vat_number)
                                    <p style="font-size:7pt;color:#6b5b8a;margin:0;">VAT No: {{ $company->vat_number }}</p>
                                @endif
                            </div>
                        </div>

                        {{-- Line items --}}
                        <table class="reg-table" style="margin-bottom:8pt;">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th class="amt" style="width:50pt;">Qty</th>
                                    <th class="amt" style="width:70pt;">Unit Price</th>
                                    <th class="amt" style="width:45pt;">VAT %</th>
                                    <th class="amt" style="width:55pt;">Tax</th>
                                    <th class="amt" style="width:70pt;">Line Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($invoice->items as $item)
                                    @php
                                        $base    = (float) $item->quantity * (float) $item->unit_price;
                                        $itemTax = $item->tax_rate !== null ? $base * ((float) $item->tax_rate / 100) : 0;
                                    @endphp
                                    <tr>
                                        <td>
                                            <span style="font-weight:700;">{{ $item->description }}</span>
                                            @if ($item->inventoryItem && $item->inventoryItem->sku)
                                                <br>
                                                <span style="font-size:6pt;color:#8b7aad;">SKU: {{ $item->inventoryItem->sku }}</span>
                                            @endif
                                        </td>
                                        <td class="amt">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                                        <td class="amt">R&nbsp;{{ number_format((float) $item->unit_price, 2) }}</td>
                                        <td class="amt">{{ $item->tax_rate !== null ? number_format((float) $item->tax_rate, 2) . '%' : '—' }}</td>
                                        <td class="amt">R&nbsp;{{ number_format($itemTax, 2) }}</td>
                                        <td class="amt" style="font-weight:800;">R&nbsp;{{ number_format($base + $itemTax, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        {{-- Totals --}}
                        <div style="display:flex;justify-content:flex-end;margin-bottom:10pt;">
                            <table class="reg-table" style="width:180pt;">
                                <tbody>
                                    <tr>
                                        <td style="color:#6b5b8a;">Subtotal</td>
                                        <td class="amt">R&nbsp;{{ number_format($invoice->subtotal(), 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td style="color:#6b5b8a;">VAT</td>
                                        <td class="amt">R&nbsp;{{ number_format($invoice->taxTotal(), 2) }}</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td>{{ $invoice->status === \App\Enums\InvoiceStatus::Paid->value ? 'Total Paid' : 'Total Due' }}</td>
                                        <td class="amt">R&nbsp;{{ number_format($invoice->total(), 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        @if ($invoice->payments->isNotEmpty())
                            <div style="display:flex;justify-content:flex-end;margin-bottom:10pt;">
                                <table class="reg-table" style="width:180pt;">
                                    <tbody>
                                        <tr>
                                            <td style="color:#6b5b8a;">Amount Paid</td>
                                            <td class="amt">R&nbsp;{{ number_format($invoice->amountPaid(), 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight:700;">Balance Due</td>
                                            <td class="amt" style="font-weight:800;color:#4c1d95;">R&nbsp;{{ number_format($invoice->balanceDue(), 2) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        {{-- Payments --}}
                        @if ($invoice->payments->isNotEmpty())
                            <div style="margin-bottom:10pt;">
                                <div class="reg-section-header">Payments</div>

                                <table class="reg-table" style="margin-bottom:6pt;">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Method</th>
                                            <th>Recorded By</th>
                                            <th>Notes</th>
                                            <th class="amt">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($invoice->payments as $payment)
                                            <tr>
                                                <td>{{ $payment->payment_date->format('d M Y') }}</td>
                                                <td>{{ $payment->method ?: '—' }}</td>
                                                <td>{{ $payment->user->name ?? '—' }}</td>
                                                <td>{{ $payment->notes ?: '—' }}</td>
                                                <td class="amt">R&nbsp;{{ number_format($payment->amount, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        {{-- Payment / banking details --}}
                        @if ($company->bank_name || $company->bank_account_number)
                            <div style="margin-bottom:10pt;">
                                <div class="reg-section-header">Payment Details</div>
                                <div class="inv-bank-grid">
                                    @if ($company->bank_name)
                                        <div>
                                            <p style="font-size:5.5pt;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;color:#8b7aad;margin:0 0 1pt;">Bank</p>
                                            <p style="font-size:7pt;font-weight:700;color:#23282d;margin:0;">{{ $company->bank_name }}</p>
                                        </div>
                                    @endif
                                    @if ($company->bank_account_number)
                                        <div>
                                            <p style="font-size:5.5pt;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;color:#8b7aad;margin:0 0 1pt;">Account Number</p>
                                            <p style="font-size:7pt;font-weight:700;color:#23282d;margin:0;font-family:'DejaVu Sans Mono',monospace;">{{ $company->bank_account_number }}</p>
                                        </div>
                                    @endif
                                    @if ($company->bank_account_type)
                                        <div>
                                            <p style="font-size:5.5pt;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;color:#8b7aad;margin:0 0 1pt;">Account Type</p>
                                            <p style="font-size:7pt;font-weight:700;color:#23282d;margin:0;">{{ ucfirst($company->bank_account_type) }}</p>
                                        </div>
                                    @endif
                                    @if ($company->bank_branch_code)
                                        <div>
                                            <p style="font-size:5.5pt;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;color:#8b7aad;margin:0 0 1pt;">Branch Code</p>
                                            <p style="font-size:7pt;font-weight:700;color:#23282d;margin:0;font-family:'DejaVu Sans Mono',monospace;">{{ $company->bank_branch_code }}</p>
                                        </div>
                                    @endif
                                    <div>
                                        <p style="font-size:5.5pt;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;color:#8b7aad;margin:0 0 1pt;">Reference</p>
                                        <p style="font-size:7pt;font-weight:700;color:#4c1d95;margin:0;font-family:'DejaVu Sans Mono',monospace;">{{ $invoice->invoice_number }}</p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Notes --}}
                        @if ($invoice->notes)
                            <div style="margin-bottom:10pt;">
                                <div class="reg-section-header">Notes</div>
                                <div class="inv-notes">{{ $invoice->notes }}</div>
                            </div>
                        @endif

                        {{-- Document footer --}}
                        <div class="inv-doc-footer">
                            <span>Thank you for your business.</span>
                            @if ($company->vat_number)
                                <span>VAT Vendor &mdash; {{ $company->vat_number }}</span>
                            @endif
                            <span>{{ $invoice->invoice_number }} &middot; {{ now()->format('d M Y') }}</span>
                        </div>

                    </div>
                </div>

            </main>
        </div>
    </div>

@push('scripts')
<script>
(function () {
    const statusLabels = {
        draft: 'Draft', pending: 'Pending', paid: 'Paid',
        partially_paid: 'Partially Paid', overdue: 'Overdue',
        voided: 'Voided', write_off: 'Write Off',
    };

    function showToast(msg, color) {
        const t = document.createElement('div');
        t.style.cssText = 'position:fixed;bottom:1.5rem;right:1.5rem;padding:0.75rem 1.25rem;font-size:0.8rem;font-weight:600;color:#fff;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,0.15);transition:opacity 0.4s;background:' + color;
        t.textContent = msg;
        document.body.appendChild(t);
        setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 400); }, 4000);
    }

    // ── Journal posting status (asset-style) ──
    const invoiceId = {{ $invoice->id }};
    const companyId = {{ $company->id }};
    const journalsContainer = document.getElementById('invoiceJournals');

    const journalStyle = {
        pending: { label: 'Journal pending', color: '#1e40af', rowClass: 'pending' },
        posted:  { label: 'Journal posted',  color: '#166534', rowClass: 'posted' },
        failed:  { label: 'Journal failed',  color: '#991b1b', rowClass: 'failed' },
    };

    function updateJournalRow(type, status, label) {
        if (status === 'posted' || status === 'failed') {
            location.reload();
            return;
        }

        let row = document.querySelector('.inv-journal-row[data-type="' + type + '"]');
        if (!row) {
            row = document.createElement('div');
            row.className = 'inv-journal-row pending';
            row.dataset.type = type;
            row.innerHTML = '<div class="inv-journal-spinner" data-spinner></div>'
                + '<span class="inv-journal-label">' + (type === 'invoice' ? 'Invoice Journal' : 'Payment Journal') + '</span>'
                + '<span class="inv-journal-status" data-journal-status style="color:#1e40af;">Journal pending</span>';
            journalsContainer.appendChild(row);
        }
    }

    if (window.Echo) {
        window.Echo.private('company.' + companyId)
            .listen('.posting.status.updated', function (e) {
                if ((e.entity_type === 'invoice' || e.entity_type === 'invoice_payment') && e.entity_id === invoiceId) {
                    updateJournalRow(e.entity_type, e.status, e.label);
                }
            });
    }

    // Status update form — update badge in place
    const statusForm = document.querySelector('.inv-status-form');
    if (statusForm) {
        statusForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = statusForm.querySelector('[type=submit]');
            const newStatus = statusForm.querySelector('[name=status]').value;
            if (btn) { btn.disabled = true; btn.textContent = 'Updating…'; }
            try {
                const fd = new FormData(statusForm);
                const res = await fetch(statusForm.action, {
                    method: 'POST', body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    redirect: 'follow',
                });
                if (res.ok || res.redirected) {
                    const badge = document.querySelector('.reg-status');
                    if (badge) {
                        badge.className = 'reg-status ' + newStatus;
                        badge.textContent = statusLabels[newStatus] ?? newStatus;
                    }
                    showToast('Status updated', '#065f46');
                    if (newStatus === 'pending') {
                        updateJournalRow('invoice', 'pending', 'AI is posting this invoice…');
                    }
                } else {
                    showToast('Could not update status', '#b91c1c');
                }
            } catch { showToast('Network error', '#b91c1c'); }
            finally { if (btn) { btn.disabled = false; btn.textContent = 'Update Status'; } }
        });
    }

})();
</script>
@endpush
@endsection
