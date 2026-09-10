@extends('layouts.public')

@section('title', $company->registered_name . ' — Inventory Register')
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
                    <div style="background:#dcfce7;border:1px solid #bbf7d0;color:#15803d;padding:4pt 8pt;font-size:7pt;font-weight:600;margin-bottom:1.25rem;">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div style="background:#fee2e2;border:1px solid #fca5a5;color:#b91c1c;padding:4pt 8pt;font-size:7pt;font-weight:600;margin-bottom:1.25rem;">
                        {{ session('error') }}
                    </div>
                @endif

                {{-- ── Navigation bar ──────────────────────────────── --}}
                <div class="reg-mgmt-bar">
<a href="{{ route('companies.assets.index', $company) }}" style="margin-left:auto;">
                        Asset Register (IAS 16)
                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
                    </a>
                </div>

                {{-- ── Inventory Summary by Type ───────────────────── --}}
                @php
                    $typeLabels = [
                        'raw_material'   => 'Raw Materials',
                        'wip'            => 'Work in Progress',
                        'finished_goods' => 'Finished Goods',
                        'merchandise'    => 'Merchandise',
                        'consumable'     => 'Consumables',
                    ];
                    $stockItems   = $inventoryItems->where('is_service', false);
                    $serviceItems = $inventoryItems->where('is_service', true);
                    $grouped = $stockItems->groupBy(fn($i) => $i->inventory_type?->value ?? 'unclassified');
                @endphp

                <div class="reg-doc">
                    <div class="reg-doc-body">
                        <div class="reg-doc-title">Inventory Categories</div>
                        <p style="font-size:7pt;color:#5a7186;margin:0.2rem 0 0.75rem;">IAS 2 inventory classification. Items are grouped by type for disclosure and valuation.</p>

                        <hr class="reg-divider">

                        @if ($stockItems->isNotEmpty())
                            <table class="reg-table" style="margin-bottom:1rem;">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th class="amt">Items</th>
                                        <th class="amt">Total Qty</th>
                                        <th class="amt">Stock Value (R)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($typeLabels as $typeKey => $typeLabel)
                                        @php
                                            $items = $grouped->get($typeKey, collect());
                                            $totalQty = $items->sum('quantity_on_hand');
                                            $totalVal = $items->sum(fn($i) => $i->stockValue());
                                        @endphp
                                        <tr>
                                            <td style="font-weight:700;">{{ $typeLabel }}</td>
                                            <td class="amt">{{ $items->count() }}</td>
                                            <td class="amt">{{ number_format($totalQty, 2) }}</td>
                                            <td class="amt">{{ number_format($totalVal, 2) }}</td>
                                        </tr>
                                    @endforeach
                                    @if ($grouped->has('unclassified'))
                                        @php
                                            $uncl = $grouped->get('unclassified');
                                        @endphp
                                        <tr>
                                            <td style="font-weight:700;color:#6f869b;">Unclassified</td>
                                            <td class="amt">{{ $uncl->count() }}</td>
                                            <td class="amt">{{ number_format($uncl->sum('quantity_on_hand'), 2) }}</td>
                                            <td class="amt">{{ number_format($uncl->sum(fn($i) => $i->stockValue()), 2) }}</td>
                                        </tr>
                                    @endif
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td>Total</td>
                                        <td class="amt">{{ $stockItems->count() }}</td>
                                        <td class="amt">{{ number_format($stockItems->sum('quantity_on_hand'), 2) }}</td>
                                        <td class="amt">{{ number_format($stockItems->sum(fn($i) => $i->stockValue()), 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        @else
                            <p style="color:#6f869b;font-size:7pt;font-style:italic;margin-bottom:1rem;">No inventory items yet.</p>
                        @endif

                        @if ($serviceItems->isNotEmpty())
                            <div style="margin-top:0.5rem;font-size:7pt;color:#5a7186;">
                                + <strong>{{ $serviceItems->count() }}</strong> service item{{ $serviceItems->count() !== 1 ? 's' : '' }} (no stock tracked)
                            </div>
                        @endif
                    </div>
                </div>

                {{-- ── Inventory Register ──────────────────────────── --}}
                <div class="reg-mgmt-bar">
                    <span style="font-size:7pt;color:#5a7186;">
                        {{ $stockItems->count() }} inventory item{{ $stockItems->count() !== 1 ? 's' : '' }}, {{ $serviceItems->count() }} service{{ $serviceItems->count() !== 1 ? 's' : '' }}
                    </span>
                    <div style="display:flex;align-items:center;gap:0.65rem;flex-wrap:wrap;">
                        <button type="button" class="reg-btn" onclick="document.getElementById('panel-add-service').classList.toggle('open');document.getElementById('panel-add-service').scrollIntoView({behavior:'smooth',block:'nearest'})">
                            + Add Service
                        </button>
                        <button type="button" class="reg-btn primary" onclick="document.getElementById('panel-add-item').classList.toggle('open');document.getElementById('panel-add-item').scrollIntoView({behavior:'smooth',block:'nearest'})">
                            + Add Item
                        </button>
                    </div>
                </div>

                <div class="reg-doc">
                    <div class="reg-doc-body">
                        <div class="reg-doc-title">Inventory Items</div>

                        <hr class="reg-divider">

                        @if ($stockItems->isEmpty())
                            <p style="color:#6f869b;font-style:italic;font-size:7pt;">No inventory items yet. Add your first item below.</p>
                        @else
                            @php
                                $totalStockValue = 0.0;
                                $totalQtyOnHand  = 0.0;
                            @endphp
                            <div style="overflow-x:auto;">
                                <table class="reg-table">
                                    <thead>
                                        <tr>
                                            <th style="width:20%;">Item</th>
                                            <th style="width:8%;">SKU</th>
                                            <th style="width:12%;">Type</th>
                                            <th class="amt" style="width:10%;">Qty on Hand</th>
                                            <th class="amt" style="width:12%;">Unit Cost</th>
                                            <th class="amt" style="width:12%;">Landed Cost</th>
                                            <th class="amt" style="width:11%;">Stock Value</th>
                                            <th class="amt" style="width:11%;">Carrying Amt</th>
                                            <th style="width:6%;text-align:center;">Status</th>
                                            <th style="width:6%;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($stockItems as $item)
                                            @php
                                                $landed      = $item->landedCost();
                                                $stockVal    = $item->stockValue();
                                                $carryAmt    = $item->carryingAmount();
                                                $hasWriteDown = (float)$item->accumulated_write_down > 0;
                                                $totalStockValue += $stockVal;
                                                $totalQtyOnHand  += (float)$item->quantity_on_hand;
                                                $totalCarrying   = ($totalCarrying ?? 0) + $carryAmt;
                                            @endphp
                                            <tr>
                                                <td>
                                                    <a class="reg-link" href="{{ route('companies.inventory.show', [$company, $item]) }}">
                                                        {{ $item->name }}
                                                    </a>
                                                </td>
                                                <td class="dim" style="font-size:6.5pt;">{{ $item->sku ?? '—' }}</td>
                                                <td>
                                                    @if ($item->inventory_type)
                                                        <span class="type-badge" style="font-size:6pt;font-weight:700;padding:0.1rem 0.4rem;border:1px solid #9ec1f5;background:#f4fafc;color:#5a7186;text-transform:uppercase;letter-spacing:0.04em;display:inline-block;">{{ $typeLabels[$item->inventory_type->value] ?? $item->inventory_type->value }}</span>
                                                    @else
                                                        <span style="color:#6f869b;font-size:6.5pt;">—</span>
                                                    @endif
                                                </td>
                                                <td class="amt">{{ number_format((float)$item->quantity_on_hand, 2) }}</td>
                                                <td class="amt">{{ number_format((float)$item->unit_price, 2) }}</td>
                                                <td class="amt" style="font-weight:700;">{{ number_format($landed, 2) }}</td>
                                                <td class="amt" style="font-weight:800;">{{ number_format($stockVal, 2) }}</td>
                                                <td class="amt" style="font-weight:800;{{ $hasWriteDown ? 'color:#9d174d;' : '' }}">{{ number_format($carryAmt, 2) }}</td>
                                                <td style="text-align:center;">
                                                    <span class="reg-status {{ !$item->is_active ? 'disposed' : '' }}">
                                                        {{ $item->is_active ? 'Active' : 'Inactive' }}
                                                    </span>
                                                </td>
                                                <td style="text-align:right;">
                                                    <div class="reg-row-actions" style="display:flex;align-items:center;gap:0.5rem;justify-content:flex-end;">
                                                        <button type="button" style="background:none;border:none;font-size:6.5pt;color:#1a345b;text-decoration:underline;cursor:pointer;font-family:inherit;padding:0;"
                                                            onclick="openEditPanel({{ $item->id }}, {{ json_encode([
                                                                'name' => $item->name,
                                                                'sku' => $item->sku ?? '',
                                                                'description' => $item->description ?? '',
                                                                'inventory_type' => $item->inventory_type?->value ?? '',
                                                                'unit_price' => (float)$item->unit_price,
                                                                'purchase_cost' => (float)($item->purchase_cost ?? 0),
                                                                'freight_in' => (float)($item->freight_in ?? 0),
                                                                'import_duties' => (float)($item->import_duties ?? 0),
                                                                'handling_costs' => (float)($item->handling_costs ?? 0),
                                                                'trade_discount' => (float)($item->trade_discount ?? 0),
                                                                'tax_rate' => $item->tax_rate !== null ? (float)$item->tax_rate : null,
                                                                'initial_quantity' => (float)($item->initial_quantity ?? 0),
                                                                'is_active' => $item->is_active,
                                                            ]) }})">Edit</button>
                                                        <form method="POST"
                                                            action="{{ route('companies.inventory.destroy', [$company, $item]) }}"
                                                            onsubmit="return false" data-confirm-label="Inventory" data-confirm-title="Remove Item" data-confirm-body="Remove {{ addslashes($item->name) }} from the inventory register?" data-confirm-text="Remove" data-confirm-danger="1">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" style="background:none;border:none;font-size:6.5pt;color:#dc2626;text-decoration:underline;cursor:pointer;font-family:inherit;padding:0;">Remove</button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3">Total</td>
                                            <td class="amt">{{ number_format($totalQtyOnHand, 2) }}</td>
                                            <td colspan="2"></td>
                                            <td class="amt">{{ number_format($totalStockValue, 2) }}</td>
                                            <td class="amt" style="{{ ($totalCarrying ?? 0) < $totalStockValue ? 'color:#9d174d;' : '' }}">{{ number_format($totalCarrying ?? 0, 2) }}</td>
                                            <td colspan="2"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @endif

                        {{-- Add item inline form --}}
                        <div class="add-panel" id="panel-add-item">
                            <button type="button" class="add-panel-head" onclick="this.closest('.add-panel').classList.toggle('open')">
                                + Add Item
                            </button>
                            <div class="add-panel-body">
                                @if ($errors->any() && !old('_edit'))
                                    <div style="background:#fee2e2;border:1px solid #fca5a5;color:#b91c1c;padding:4pt 8pt;font-size:6.5pt;margin-bottom:0.85rem;">
                                        <strong>Please fix the following:</strong>
                                        <ul style="margin:0.3rem 0 0 1rem;padding:0;">
                                            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                                        </ul>
                                    </div>
                                @endif
                                <form method="POST" action="{{ route('companies.inventory.store', $company) }}">
                                    @csrf
                                    {{-- Row 1: Identity --}}
                                    <div class="af-row">
                                        <div class="af-field wide">
                                            <label>Item name</label>
                                            <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. Widget A" required>
                                        </div>
                                        <div class="af-field">
                                            <label>SKU / Code</label>
                                            <input type="text" name="sku" value="{{ old('sku') }}" placeholder="e.g. WGT-001">
                                        </div>
                                        <div class="af-field">
                                            <label>Category</label>
                                            <select name="inventory_type">
                                                <option value="">— Select —</option>
                                                @foreach ($typeLabels as $typeKey => $typeLabel)
                                                    <option value="{{ $typeKey }}" @selected(old('inventory_type') === $typeKey)>{{ $typeLabel }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    {{-- Row 2: Pricing --}}
                                    <div class="af-row">
                                        <div class="af-field">
                                            <label>Selling price (R)</label>
                                            <input type="number" name="unit_price" value="{{ old('unit_price', '0.00') }}" min="0" step="0.01" required>
                                        </div>
                                        <div class="af-field">
                                            <label>Purchase cost (R)</label>
                                            <input type="number" name="purchase_cost" value="{{ old('purchase_cost', '0.00') }}" min="0" step="0.01">
                                        </div>
                                        <div class="af-field">
                                            <label>Initial quantity</label>
                                            <input type="number" name="initial_quantity" value="{{ old('initial_quantity', '0') }}" min="0" step="0.0001">
                                        </div>
                                        <div class="af-field">
                                            <label>VAT rate %</label>
                                            <input type="number" name="tax_rate" value="{{ old('tax_rate') }}" min="0" max="100" step="0.01" placeholder="e.g. 15">
                                        </div>
                                    </div>
                                    {{-- Row 3: IAS 2 Landed Cost --}}
                                    <div class="af-row">
                                        <div class="af-field">
                                            <label>Freight in (R)</label>
                                            <input type="number" name="freight_in" value="{{ old('freight_in', '0.00') }}" min="0" step="0.01">
                                        </div>
                                        <div class="af-field">
                                            <label>Import duties (R)</label>
                                            <input type="number" name="import_duties" value="{{ old('import_duties', '0.00') }}" min="0" step="0.01">
                                        </div>
                                        <div class="af-field">
                                            <label>Handling costs (R)</label>
                                            <input type="number" name="handling_costs" value="{{ old('handling_costs', '0.00') }}" min="0" step="0.01">
                                        </div>
                                        <div class="af-field">
                                            <label>Trade discount (R)</label>
                                            <input type="number" name="trade_discount" value="{{ old('trade_discount', '0.00') }}" min="0" step="0.01">
                                        </div>
                                    </div>
                                    <div class="af-row">
                                        <div class="af-field full">
                                            <label>Description</label>
                                            <input type="text" name="description" value="{{ old('description') }}" placeholder="Short description of the item">
                                        </div>
                                    </div>
                                    <div class="af-submit">
                                        <button type="submit" class="reg-btn primary">Add Item</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        {{-- Edit item inline panel (hidden, populated via JS) --}}
                        <div class="add-panel open" id="panel-edit-item" style="display:none;">
                            <div class="add-panel-body" style="display:block;">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:5pt;">
                                    <span style="font-size:6.5pt;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#1a345b;">Edit Item</span>
                                    <button type="button" onclick="closeEditPanel()" style="background:none;border:none;font-size:9pt;color:#6f869b;cursor:pointer;">&times;</button>
                                </div>
                                <form id="edit-form" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="_edit" value="1">
                                    <div class="af-row">
                                        <div class="af-field wide">
                                            <label>Item name</label>
                                            <input type="text" id="ef-name" name="name" required>
                                        </div>
                                        <div class="af-field">
                                            <label>SKU / Code</label>
                                            <input type="text" id="ef-sku" name="sku">
                                        </div>
                                        <div class="af-field">
                                            <label>Category</label>
                                            <select id="ef-inventory_type" name="inventory_type">
                                                <option value="">— Select —</option>
                                                @foreach ($typeLabels as $typeKey => $typeLabel)
                                                    <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="af-row">
                                        <div class="af-field">
                                            <label>Selling price (R)</label>
                                            <input type="number" id="ef-unit_price" name="unit_price" min="0" step="0.01" required>
                                        </div>
                                        <div class="af-field">
                                            <label>Purchase cost (R)</label>
                                            <input type="number" id="ef-purchase_cost" name="purchase_cost" min="0" step="0.01">
                                        </div>
                                        <div class="af-field">
                                            <label>Initial quantity</label>
                                            <input type="number" id="ef-initial_quantity" name="initial_quantity" min="0" step="0.0001">
                                        </div>
                                        <div class="af-field">
                                            <label>VAT rate %</label>
                                            <input type="number" id="ef-tax_rate" name="tax_rate" min="0" max="100" step="0.01">
                                        </div>
                                    </div>
                                    <div class="af-row">
                                        <div class="af-field">
                                            <label>Freight in (R)</label>
                                            <input type="number" id="ef-freight_in" name="freight_in" min="0" step="0.01">
                                        </div>
                                        <div class="af-field">
                                            <label>Import duties (R)</label>
                                            <input type="number" id="ef-import_duties" name="import_duties" min="0" step="0.01">
                                        </div>
                                        <div class="af-field">
                                            <label>Handling costs (R)</label>
                                            <input type="number" id="ef-handling_costs" name="handling_costs" min="0" step="0.01">
                                        </div>
                                        <div class="af-field">
                                            <label>Trade discount (R)</label>
                                            <input type="number" id="ef-trade_discount" name="trade_discount" min="0" step="0.01">
                                        </div>
                                    </div>
                                    <div class="af-row">
                                        <div class="af-field wide">
                                            <label>Description</label>
                                            <input type="text" id="ef-description" name="description">
                                        </div>
                                        <div style="display:flex;align-items:center;gap:4pt;padding-bottom:3pt;">
                                            <input type="checkbox" id="ef-is_active" name="is_active" value="1" style="width:auto;margin:0;">
                                            <label for="ef-is_active" style="font-size:6.5pt;color:#1a345b;text-transform:none;letter-spacing:0;margin:0;cursor:pointer;">Active</label>
                                        </div>
                                    </div>
                                    <div class="af-submit">
                                        <button type="submit" class="reg-btn primary">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- ── Service Items ──────────────────────────────── --}}
                <div class="reg-doc">
                    <div class="reg-doc-body">
                        <div class="reg-doc-title">Service Items</div>
                        <p style="font-size:7pt;color:#5a7186;margin:0.2rem 0 0.75rem;">Services, labour, and non-stock items used for invoicing. No stock or IAS 2 tracking.</p>

                        <hr class="reg-divider">

                        @if ($serviceItems->isEmpty())
                            <p style="color:#6f869b;font-style:italic;font-size:7pt;">No service items yet.</p>
                        @else
                            <div style="overflow-x:auto;">
                                <table class="reg-table">
                                    <thead>
                                        <tr>
                                            <th style="width:30%;">Service</th>
                                            <th style="width:30%;">Description</th>
                                            <th class="amt" style="width:15%;">Selling Price (R)</th>
                                            <th class="amt" style="width:10%;">VAT %</th>
                                            <th style="width:8%;text-align:center;">Status</th>
                                            <th style="width:7%;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($serviceItems as $svc)
                                            <tr>
                                                <td>
                                                    <a class="reg-link" href="{{ route('companies.inventory.show', [$company, $svc]) }}">
                                                        {{ $svc->name }}
                                                    </a>
                                                </td>
                                                <td class="dim" style="font-size:6.5pt;">{{ Str::limit($svc->description, 60) ?? '—' }}</td>
                                                <td class="amt" style="font-weight:700;">{{ number_format((float)$svc->unit_price, 2) }}</td>
                                                <td class="amt">{{ $svc->tax_rate !== null ? number_format((float)$svc->tax_rate, 0) . '%' : '—' }}</td>
                                                <td style="text-align:center;">
                                                    <span class="reg-status {{ !$svc->is_active ? 'disposed' : '' }}">
                                                        {{ $svc->is_active ? 'Active' : 'Inactive' }}
                                                    </span>
                                                </td>
                                                <td style="text-align:right;">
                                                    <div class="reg-row-actions" style="display:flex;align-items:center;gap:0.5rem;justify-content:flex-end;">
                                                        <button type="button" style="background:none;border:none;font-size:6.5pt;color:#1a345b;text-decoration:underline;cursor:pointer;font-family:inherit;padding:0;"
                                                            onclick="openEditPanel({{ $svc->id }}, {{ json_encode([
                                                                'name' => $svc->name,
                                                                'sku' => $svc->sku ?? '',
                                                                'description' => $svc->description ?? '',
                                                                'inventory_type' => '',
                                                                'unit_price' => (float)$svc->unit_price,
                                                                'purchase_cost' => 0,
                                                                'freight_in' => 0,
                                                                'import_duties' => 0,
                                                                'handling_costs' => 0,
                                                                'trade_discount' => 0,
                                                                'tax_rate' => $svc->tax_rate !== null ? (float)$svc->tax_rate : null,
                                                                'initial_quantity' => 0,
                                                                'is_active' => $svc->is_active,
                                                            ]) }})">Edit</button>
                                                        <form method="POST"
                                                            action="{{ route('companies.inventory.destroy', [$company, $svc]) }}"
                                                            onsubmit="return false" data-confirm-label="Inventory" data-confirm-title="Remove Service" data-confirm-body="Remove {{ addslashes($svc->name) }} from the register?" data-confirm-text="Remove" data-confirm-danger="1">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" style="background:none;border:none;font-size:6.5pt;color:#dc2626;text-decoration:underline;cursor:pointer;font-family:inherit;padding:0;">Remove</button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        {{-- Add service inline form --}}
                        <div class="add-panel" id="panel-add-service">
                            <button type="button" class="add-panel-head" onclick="this.closest('.add-panel').classList.toggle('open')">
                                + Add Service Item
                            </button>
                            <div class="add-panel-body">
                                <form method="POST" action="{{ route('companies.inventory.store', $company) }}">
                                    @csrf
                                    <input type="hidden" name="is_service" value="1">
                                    <div class="af-row">
                                        <div class="af-field wide">
                                            <label>Service name</label>
                                            <input type="text" name="name" placeholder="e.g. Consulting — hourly rate" required>
                                        </div>
                                        <div class="af-field">
                                            <label>Selling price (R)</label>
                                            <input type="number" name="unit_price" value="0.00" min="0" step="0.01" required>
                                        </div>
                                        <div class="af-field">
                                            <label>VAT rate %</label>
                                            <input type="number" name="tax_rate" min="0" max="100" step="0.01" placeholder="e.g. 15">
                                        </div>
                                    </div>
                                    <div class="af-row">
                                        <div class="af-field full">
                                            <label>Description</label>
                                            <input type="text" name="description" placeholder="Short description of the service">
                                        </div>
                                    </div>
                                    <div class="af-submit">
                                        <button type="submit" class="reg-btn primary">Add Service</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script>
        const baseUpdateUrl = '{{ route('companies.inventory.update', [$company, '__ID__']) }}';

        function openEditPanel(id, data) {
            const panel = document.getElementById('panel-edit-item');
            document.getElementById('edit-form').action = baseUpdateUrl.replace('__ID__', id);
            document.getElementById('ef-name').value = data.name;
            document.getElementById('ef-sku').value = data.sku;
            document.getElementById('ef-description').value = data.description;
            document.getElementById('ef-inventory_type').value = data.inventory_type;
            document.getElementById('ef-unit_price').value = data.unit_price;
            document.getElementById('ef-purchase_cost').value = data.purchase_cost;
            document.getElementById('ef-freight_in').value = data.freight_in;
            document.getElementById('ef-import_duties').value = data.import_duties;
            document.getElementById('ef-handling_costs').value = data.handling_costs;
            document.getElementById('ef-trade_discount').value = data.trade_discount;
            document.getElementById('ef-tax_rate').value = data.tax_rate !== null ? data.tax_rate : '';
            document.getElementById('ef-initial_quantity').value = data.initial_quantity;
            document.getElementById('ef-is_active').checked = data.is_active;
            panel.style.display = 'block';
            panel.scrollIntoView({behavior: 'smooth', block: 'nearest'});
        }

        function closeEditPanel() {
            document.getElementById('panel-edit-item').style.display = 'none';
        }

        @if ($errors->any() && !old('_edit'))
            document.getElementById('panel-add-item').classList.add('open');
        @endif
    </script>
@endsection
