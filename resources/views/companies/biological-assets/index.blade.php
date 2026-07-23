@extends('layouts.public')

@section('title', $company->registered_name . ' — Biological Assets')
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
                    <span style="font-size:7pt;color:#6b5b8a;">
                        {{ $assets->where('status', 'active')->count() }} active biological asset{{ $assets->where('status', 'active')->count() !== 1 ? 's' : '' }}
                    </span>
                    <div style="display:flex;gap:4pt;">
                        <button type="button" class="reg-btn" onclick="document.getElementById('add-class-form').style.display = document.getElementById('add-class-form').style.display === 'none' ? 'block' : 'none'">+ Class</button>
                        <button type="button" class="reg-btn primary" onclick="document.getElementById('add-asset-form').style.display = document.getElementById('add-asset-form').style.display === 'none' ? 'block' : 'none'">+ Asset</button>
                    </div>
                </div>

                {{-- Add class form --}}
                <div id="add-class-form" style="display:none;margin-bottom:12pt;background:#f8fbfe;border:1px solid #c4b5fd;padding:8pt 10pt;">
                    <p style="font-size:6pt;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:#7c3aed;margin:0 0 6pt;">New Biological Asset Class</p>
                    <form method="POST" action="{{ route('companies.biological-assets.classes.store', $company) }}">
                        @csrf
                        <div style="display:flex;flex-wrap:wrap;gap:4pt 6pt;align-items:flex-end;">
                            <div class="reg-modal-field" style="flex:2;min-width:160px;">
                                <label>Class name</label>
                                <input type="text" name="name" required placeholder="e.g. Cattle, Timber Plantations, Vineyards">
                            </div>
                            <div class="reg-modal-field" style="flex:1;min-width:130px;">
                                <label>Category</label>
                                <select name="category">
                                    @foreach (\App\Models\BiologicalAssetClass::CATEGORIES as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="reg-btn primary" style="margin-bottom:0;">Save</button>
                        </div>
                    </form>
                </div>

                {{-- Add asset form --}}
                <div id="add-asset-form" style="display:none;margin-bottom:12pt;background:#f8fbfe;border:1px solid #c4b5fd;padding:8pt 10pt;">
                    <p style="font-size:6pt;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:#7c3aed;margin:0 0 6pt;">Register New Biological Asset</p>
                    <form method="POST" action="{{ route('companies.biological-assets.store', $company) }}">
                        @csrf
                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:4pt 6pt;margin-bottom:6pt;">
                            <div class="reg-modal-field" style="grid-column:span 2;">
                                <label>Name</label>
                                <input type="text" name="name" required placeholder="e.g. Nguni Herd, Pine Plantation Block A">
                            </div>
                            <div class="reg-modal-field">
                                <label>Class</label>
                                <select name="biological_asset_class_id">
                                    <option value="">— None —</option>
                                    @foreach ($classes as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="reg-modal-field">
                                <label>Reference</label>
                                <input type="text" name="reference" placeholder="e.g. LOT-001">
                            </div>
                            <div class="reg-modal-field">
                                <label>Location</label>
                                <input type="text" name="location" placeholder="e.g. Limpopo farm">
                            </div>
                            <div class="reg-modal-field">
                                <label>Acquisition date</label>
                                <input type="date" name="acquisition_date" required value="{{ now()->format('Y-m-d') }}">
                            </div>
                            <div class="reg-modal-field">
                                <label>Quantity</label>
                                <input type="number" name="quantity" step="0.01" min="0" required placeholder="e.g. 150">
                            </div>
                            <div class="reg-modal-field">
                                <label>Unit</label>
                                <input type="text" name="unit" required value="head" placeholder="head, hectares, trees">
                            </div>
                            <div class="reg-modal-field">
                                <label>Cost (R)</label>
                                <input type="number" name="cost" step="0.01" min="0" required placeholder="0.00">
                            </div>
                            <div class="reg-modal-field">
                                <label>Fair value (R) — optional</label>
                                <input type="number" name="fair_value" step="0.01" min="0" placeholder="FVLCTS">
                            </div>
                            <div class="reg-modal-field">
                                <label>FV date</label>
                                <input type="date" name="fair_value_date">
                            </div>
                        </div>
                        <div class="reg-modal-field" style="margin-bottom:6pt;">
                            <label>Notes</label>
                            <input type="text" name="notes" maxlength="500">
                        </div>
                        <div style="display:flex;justify-content:flex-end;">
                            <button type="submit" class="reg-btn primary">Register Asset</button>
                        </div>
                    </form>
                </div>

                {{-- Classes table --}}
                @if ($classes->isNotEmpty())
                <div class="reg-doc">
                    <div class="reg-doc-body">
                        <div class="reg-section-header">Asset Classes</div>
                        <div style="overflow-x:auto;">
                            <table class="reg-table">
                                <thead>
                                    <tr>
                                        <th style="width:40%;">Class</th>
                                        <th style="width:20%;">Category</th>
                                        <th class="amt" style="width:15%;">Count</th>
                                        <th style="width:15%;text-align:right;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($classes as $class)
                                        <tr>
                                            <td style="font-weight:700;">{{ $class->name }}</td>
                                            <td><span class="cat-badge">{{ $class->category }}</span></td>
                                            <td class="amt">{{ $class->biological_assets_count }}</td>
                                            <td style="text-align:right;">
                                                @if ($class->biological_assets_count === 0)
                                                <div class="reg-row-actions">
                                                    <button type="button" class="reg-row-dots" onclick="toggleMenu(this)">&#x22EE;</button>
                                                    <div class="reg-row-menu">
                                                        <form method="POST" action="{{ route('companies.biological-assets.classes.destroy', [$company, $class]) }}"
                                                            onsubmit="return false" data-confirm-label="Biological Assets" data-confirm-title="Remove Class" data-confirm-body="Remove class {{ addslashes($class->name) }}?" data-confirm-text="Remove" data-confirm-danger="1">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="menu-item danger">Remove</button>
                                                        </form>
                                                    </div>
                                                </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Assets register --}}
                <div class="reg-doc">
                    <div class="reg-doc-body">
                        <div class="reg-doc-title">Biological Asset Register</div>
                        <p style="font-size:7pt;color:#6b5b8a;margin:1pt 0 6pt;">
                            IAS 41 Agriculture — biological assets measured at fair value less costs to sell, with changes recognised in profit or loss.
                        </p>

                        <hr class="reg-divider">

                        @if ($assets->isEmpty())
                            <p class="reg-empty">No biological assets registered yet.</p>
                        @else
                            @php $totalCarrying = 0.0; @endphp
                            <div style="overflow-x:auto;">
                                <table class="reg-table">
                                    <thead>
                                        <tr>
                                            <th style="width:18%;">Name</th>
                                            <th style="width:8%;">Ref</th>
                                            <th style="width:10%;">Class</th>
                                            <th class="amt" style="width:8%;">Qty</th>
                                            <th style="width:5%;">Unit</th>
                                            <th class="amt" style="width:10%;">Cost</th>
                                            <th class="amt" style="width:10%;">Fair Value</th>
                                            <th class="amt" style="width:10%;">FV Gain/Loss</th>
                                            <th class="amt" style="width:10%;">Carrying Amt</th>
                                            <th style="width:8%;text-align:center;">Status</th>
                                            <th style="width:3%;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($assets as $asset)
                                            @php
                                                $ca = $asset->carryingAmount();
                                                if ($asset->status === 'active') $totalCarrying += $ca;
                                            @endphp
                                            <tr>
                                                <td>
                                                    <a class="reg-link" href="{{ route('companies.biological-assets.show', [$company, $asset]) }}">
                                                        {{ $asset->name }}
                                                    </a>
                                                </td>
                                                <td class="dim" style="font-size:6.5pt;">{{ $asset->reference ?? "\u{2014}" }}</td>
                                                <td class="dim" style="font-size:6.5pt;">{{ $asset->biologicalAssetClass?->name ?? "\u{2014}" }}</td>
                                                <td class="amt">{{ number_format((float) $asset->quantity, 2) }}</td>
                                                <td style="font-size:6pt;color:#8b7aad;">{{ $asset->unit }}</td>
                                                <td class="amt">{{ number_format((float) $asset->cost, 2) }}</td>
                                                <td class="amt" style="color:#7c3aed;">{{ $asset->fair_value !== null ? number_format((float) $asset->fair_value, 2) : "\u{2014}" }}</td>
                                                <td class="amt" style="color:{{ (float) $asset->fair_value_gain_loss >= 0 ? '#166534' : '#dc2626' }};">
                                                    {{ (float) $asset->fair_value_gain_loss != 0 ? number_format((float) $asset->fair_value_gain_loss, 2) : "\u{2014}" }}
                                                </td>
                                                <td class="amt" style="font-weight:800;">{{ number_format($ca, 2) }}</td>
                                                <td style="text-align:center;">
                                                    <span class="reg-status {{ $asset->isDisposed() ? 'disposed' : '' }}">
                                                        {{ $asset->isDisposed() ? 'Disposed' : 'Active' }}
                                                    </span>
                                                </td>
                                                <td style="text-align:right;">
                                                    <div class="reg-row-actions">
                                                        <button type="button" class="reg-row-dots" onclick="toggleMenu(this)">&#x22EE;</button>
                                                        <div class="reg-row-menu">
                                                            <a href="{{ route('companies.biological-assets.show', [$company, $asset]) }}">View</a>
                                                            <form method="POST" action="{{ route('companies.biological-assets.destroy', [$company, $asset]) }}"
                                                                onsubmit="return false" data-confirm-label="Biological Assets" data-confirm-title="Remove Asset" data-confirm-body="Remove {{ addslashes($asset->name) }} from the register?" data-confirm-text="Remove" data-confirm-danger="1">
                                                                @csrf @method('DELETE')
                                                                <button type="submit" class="menu-item danger">Remove</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    @if ($assets->where('status', 'active')->count() > 0)
                                    <tfoot>
                                        <tr>
                                            <td colspan="8">Total carrying amount (active)</td>
                                            <td class="amt">{{ number_format($totalCarrying, 2) }}</td>
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
    <script>
        function toggleMenu(btn) {
            var menu = btn.nextElementSibling;
            var wasOpen = menu.classList.contains('open');
            document.querySelectorAll('.reg-row-menu.open').forEach(function(m) { m.classList.remove('open'); });
            if (!wasOpen) menu.classList.add('open');
        }
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.reg-row-actions')) {
                document.querySelectorAll('.reg-row-menu.open').forEach(function(m) { m.classList.remove('open'); });
            }
        });
    </script>
@endsection
