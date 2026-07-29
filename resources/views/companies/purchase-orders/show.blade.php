@extends('layouts.public')

@section('title', $company->registered_name . ' — Purchase Order ' . $purchaseOrder->po_number)
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .reg-status.draft     { color: #92400e; border-color: #92400e; background: #fef9c3; }
        .reg-status.sent      { color: #1d4ed8; border-color: #1d4ed8; background: #dbeafe; }
        .reg-status.acknowledged { color: #4338ca; border-color: #4338ca; background: #e0e7ff; }
        .reg-status.partially_received { color: #b45309; border-color: #b45309; background: #fef3c7; }
        .reg-status.received  { color: #15803d; border-color: #15803d; background: #dcfce7; }
        .reg-status.cancelled { color: #b91c1c; border-color: #b91c1c; background: #fee2e2; }

        .inv-parties {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10pt;
            margin-bottom: 10pt;
        }

        .inv-notes {
            background: #f5f3ff;
            border: 1px solid #c4b5fd;
            padding: 6pt 8pt;
            font-size: 7pt;
            color: #23282d;
            line-height: 1.55;
            white-space: pre-line;
        }

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

        .inv-flash {
            padding: 4pt 8pt;
            font-size: 6.5pt;
            font-weight: 700;
            margin-bottom: 8pt;
        }
        .inv-flash.success { background: #dcfce7; border: 1px solid #bbf7d0; color: #15803d; }
        .inv-flash.error   { background: #fee2e2; border: 1px solid #fecaca; color: #b91c1c; }

        @media (max-width: 640px) {
            .inv-parties { grid-template-columns: 1fr; }
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

                {{-- Management bar --}}
                <div class="reg-mgmt-bar">
                    <div style="display:flex;align-items:center;gap:4pt;flex-wrap:wrap;">
                        @if ($purchaseOrder->status === \App\Enums\PurchaseOrderStatus::Draft)
                            <a href="{{ route('companies.purchase-orders.edit', [$company, $purchaseOrder]) }}" class="reg-btn">
                                <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                                Edit
                            </a>
                        @endif
                        <a href="{{ route('companies.purchase-orders.pdf', [$company, $purchaseOrder]) }}" class="reg-btn">
                            <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="7 10 12 15 17 10"/>
                                <line x1="12" y1="15" x2="12" y2="3"/>
                            </svg>
                            Download PDF
                        </a>

                        @if (!in_array($purchaseOrder->status, [\App\Enums\PurchaseOrderStatus::Cancelled]))
                            <form method="POST" action="{{ route('companies.purchase-orders.convert', [$company, $purchaseOrder]) }}" class="inv-status-form">
                                @csrf
                                <button type="submit" class="reg-btn primary">Convert to Supplier Invoice</button>
                            </form>
                        @endif

                        <form method="POST" action="{{ route('companies.purchase-orders.status', [$company, $purchaseOrder]) }}" class="inv-status-form">
                            @csrf
                            @method('PATCH')
                            <select name="status" class="inv-status-select">
                                @foreach (\App\Enums\PurchaseOrderStatus::cases() as $statusOption)
                                    <option value="{{ $statusOption->value }}" {{ $purchaseOrder->status === $statusOption ? 'selected' : '' }}>{{ $statusOption->label() }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="reg-btn primary">Update Status</button>
                        </form>

                        @if ($purchaseOrder->status === \App\Enums\PurchaseOrderStatus::Draft)
                            <form method="POST" action="{{ route('companies.purchase-orders.destroy', [$company, $purchaseOrder]) }}" onsubmit="return confirm('Delete this draft purchase order?');" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="reg-btn" style="color:#dc2626;border-color:#dc2626;">Delete</button>
                            </form>
                        @endif
                    </div>
                </div>

                {{-- PO document --}}
                <div class="reg-doc">
                    <div class="reg-doc-body">

                        {{-- Letterhead --}}
                        <div class="afs-letterhead">
                            <div>
                                <div class="afs-letterhead-name">{{ $company->registered_name }}</div>
                                <div class="afs-letterhead-meta">
                                    @if ($company->address_line_1){{ $company->address_line_1 }}<br>@endif
                                    @if ($company->address_line_2){{ $company->address_line_2 }}<br>@endif
                                    @if ($company->city || $company->postal_code)
                                        {{ implode(', ', array_filter([$company->city, $company->province, $company->postal_code])) }}<br>
                                    @endif
                                    @if ($company->vat_number)VAT Reg. No: {{ $company->vat_number }}<br>@endif
                                    @if ($company->registration_number)Reg. No: {{ $company->registration_number }}@endif
                                </div>
                            </div>
                            <div class="afs-letterhead-meta afs-right">
                                <div style="font-size:9pt;font-weight:800;color:#4c1d95;margin-bottom:3pt;">{{ $purchaseOrder->po_number }}</div>
                                <div style="margin-bottom:4pt;">
                                    <span class="reg-status {{ $purchaseOrder->status->value }}">{{ $purchaseOrder->status->label() }}</span>
                                </div>
                                <div class="inv-date-line">
                                    Order Date: <strong>{{ $purchaseOrder->order_date->format('d M Y') }}</strong>
                                </div>
                                @if ($purchaseOrder->expected_delivery_date)
                                    <div class="inv-date-line">
                                        Expected Delivery: <strong>{{ $purchaseOrder->expected_delivery_date->format('d M Y') }}</strong>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Parties --}}
                        <div class="inv-parties" style="margin-top:8pt;">
                            <div>
                                <div class="reg-section-header">Supplier</div>
                                @if ($purchaseOrder->supplier)
                                    <p style="font-size:7pt;font-weight:700;color:#23282d;margin:2pt 0 1pt;">
                                        {{ $purchaseOrder->supplier->name }}
                                    </p>
                                    @if ($purchaseOrder->supplier->email)
                                        <p style="font-size:7pt;color:#6b5b8a;margin:0 0 1pt;">{{ $purchaseOrder->supplier->email }}</p>
                                    @endif
                                    @if ($purchaseOrder->supplier->phone)
                                        <p style="font-size:7pt;color:#6b5b8a;margin:0 0 1pt;">{{ $purchaseOrder->supplier->phone }}</p>
                                    @endif
                                    @if ($purchaseOrder->supplier->address)
                                        <p style="font-size:7pt;color:#6b5b8a;margin:0;white-space:pre-line;">{{ $purchaseOrder->supplier->address }}</p>
                                    @endif
                                @endif
                            </div>
                            <div>
                                <div class="reg-section-header">Ship To</div>
                                <p style="font-size:7pt;font-weight:700;color:#23282d;margin:2pt 0 1pt;">{{ $company->registered_name }}</p>
                                @if ($company->address_line_1)
                                    <p style="font-size:7pt;color:#6b5b8a;margin:0 0 1pt;">{{ $company->address_line_1 }}</p>
                                @endif
                                @if ($company->city)
                                    <p style="font-size:7pt;color:#6b5b8a;margin:0 0 1pt;">
                                        {{ implode(', ', array_filter([$company->city, $company->province, $company->postal_code])) }}
                                    </p>
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
                                @foreach ($purchaseOrder->items as $item)
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
                                        <td class="amt">R&nbsp;{{ number_format((float) $purchaseOrder->subtotal, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td style="color:#6b5b8a;">VAT</td>
                                        <td class="amt">R&nbsp;{{ number_format((float) $purchaseOrder->tax_total, 2) }}</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td>Total</td>
                                        <td class="amt">R&nbsp;{{ number_format((float) $purchaseOrder->total, 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        {{-- Notes --}}
                        @if ($purchaseOrder->notes)
                            <div style="margin-bottom:10pt;">
                                <div class="reg-section-header">Notes</div>
                                <div class="inv-notes">{{ $purchaseOrder->notes }}</div>
                            </div>
                        @endif

                        {{-- Document footer --}}
                        <div class="inv-doc-footer">
                            <span>{{ $purchaseOrder->po_number }}</span>
                            <span>{{ now()->format('d M Y') }}</span>
                        </div>

                    </div>
                </div>

            </main>
        </div>
    </div>
@endsection
