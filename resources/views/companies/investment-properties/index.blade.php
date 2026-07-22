@extends('layouts.public')

@section('title', $company->registered_name . ' — Investment Property Register')
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

                {{-- ── Property Classes ────────────────────────────────── --}}
                <div class="reg-mgmt-bar">
<a href="{{ route('companies.assets.index', $company) }}" style="margin-left:auto;">
                        PPE (IAS 16)
                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
                    </a>
                </div>

                <div class="reg-doc">
                    <div class="reg-doc-body">
                        <div class="reg-doc-title">Investment Property Classes</div>
                        <p style="font-size:7pt;color:#4a5f78;margin:0.2rem 0 0.75rem;">Group properties by measurement model. Cost model properties are depreciated; fair value model properties are remeasured to fair value each period.</p>

                        <hr class="reg-divider">

                        @if ($classes->isNotEmpty())
                            <table class="reg-table" style="margin-bottom:1rem;">
                                <thead>
                                    <tr>
                                        <th>Class</th>
                                        <th>Method</th>
                                        <th>Measurement</th>
                                        <th style="text-align:right;">Useful life (yrs)</th>
                                        <th style="text-align:right;">Properties</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($classes as $class)
                                        @php $propCount = $properties->where('investment_property_class_id', $class->id)->count(); @endphp
                                        <tr>
                                            <td style="font-weight:700;">{{ $class->name }}</td>
                                            <td>{{ \App\Models\InvestmentProperty::METHODS[$class->depreciation_method] ?? ($class->depreciation_method ?: "\u{2014}") }}</td>
                                            <td>
                                                @if ($class->measurement_model === 'fair_value')
                                                    <span style="font-size:6pt;font-weight:700;color:#7c3aed;background:#f5f3ff;padding:0.1rem 0.4rem;border:1px solid #ddd6fe;">Fair Value</span>
                                                @else
                                                    <span style="font-size:6pt;color:#4a5f78;">Cost</span>
                                                @endif
                                            </td>
                                            <td style="text-align:right;font-family:monospace;">
                                                {{ $class->useful_life_years !== null ? rtrim(rtrim(number_format((float)$class->useful_life_years, 2), '0'), '.') : "\u{2014}" }}
                                            </td>
                                            <td style="text-align:right;color:#4a5f78;">{{ $propCount }}</td>
                                            <td style="text-align:right;">
                                                <div class="reg-row-actions">
                                                    <button type="button" class="reg-row-dots" onclick="toggleMenu(this)">&#x22EE;</button>
                                                    <div class="reg-row-menu">
                                                        <form method="POST"
                                                            action="{{ route('companies.investment-properties.classes.destroy', [$company, $class]) }}"
                                                            onsubmit="return false" data-confirm-label="Investment Property Register" data-confirm-title="Delete Class" data-confirm-body="Properties in this class will keep their data but become unclassified." data-confirm-text="Delete" data-confirm-danger="1">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="menu-item danger">Remove</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p style="color:#7a90a5;font-size:7pt;font-style:italic;margin-bottom:1rem;">No property classes yet.</p>
                        @endif

                        <div class="add-panel" id="panel-add-class">
                            <button type="button" class="add-panel-head" onclick="this.closest('.add-panel').classList.toggle('open')">
                                + Add Class
                            </button>
                            <div class="add-panel-body">
                                <form method="POST" action="{{ route('companies.investment-properties.classes.store', $company) }}">
                                    @csrf
                                    <div class="af-row">
                                        <div class="af-field wide">
                                            <label>Class name</label>
                                            <input type="text" name="name" placeholder="e.g. Commercial Buildings" required maxlength="120">
                                        </div>
                                        <div class="af-field">
                                            <label>Useful life (yrs)</label>
                                            <input type="number" name="useful_life_years" min="0" max="999.99" step="0.01" placeholder="20">
                                        </div>
                                        <div class="af-field">
                                            <label>Method</label>
                                            <select name="depreciation_method">
                                                @foreach (\App\Models\InvestmentProperty::METHODS as $key => $label)
                                                    <option value="{{ $key }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="af-row">
                                        <div class="af-field wide">
                                            <label>Measurement model</label>
                                            <select name="measurement_model">
                                                @foreach (\App\Models\InvestmentPropertyClass::MODELS as $key => $label)
                                                    <option value="{{ $key }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div style="grid-column:span 2;display:flex;align-items:flex-end;justify-content:flex-end;">
                                            <button type="submit" class="reg-btn primary">Add Class</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- ── Property Register ───────────────────────────────── --}}
                <div class="reg-mgmt-bar">
                    <span style="font-size:7pt;color:#4a5f78;">
                        {{ $properties->count() }} propert{{ $properties->count() !== 1 ? 'ies' : 'y' }}
                    </span>
                    <div style="display:flex;align-items:center;gap:0.65rem;flex-wrap:wrap;">
                        <form method="GET" action="{{ route('companies.investment-properties.index', $company) }}" style="display:flex;align-items:center;gap:0.5rem;">
                            <label style="font-size:6pt;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#4a5f78;">As at</label>
                            <input type="date" name="as_of" value="{{ $asOf }}" onchange="this.form.submit()"
                                style="border:1px solid #c9dff0;padding:0.32rem 0.5rem;font-size:7pt;font-family:inherit;">
                        </form>
                        <button type="button" class="reg-btn primary" onclick="document.getElementById('panel-add-property').classList.toggle('open');document.getElementById('panel-add-property').scrollIntoView({behavior:'smooth',block:'nearest'})">
                            + Add Property
                        </button>
                    </div>
                </div>

                <div class="reg-doc">
                    <div class="reg-doc-body">
                        <div class="reg-doc-title">Investment Properties</div>

                        <hr class="reg-divider">

                        @if ($properties->isEmpty())
                            <p style="color:#7a90a5;font-style:italic;font-size:7pt;">No investment properties yet. Add your first property below.</p>
                        @else
                            @php
                                $totalCost = 0.0; $totalDep = 0.0; $totalImp = 0.0; $totalCarrying = 0.0;
                                $anyImp = $properties->contains(fn($p) => (float)($p->accumulated_impairment ?? 0) != 0);
                                $anyFV  = $properties->contains(fn($p) => $p->isFairValueModel());
                            @endphp
                            <div style="overflow-x:auto;">
                                <table class="reg-table">
                                    <thead>
                                        <tr>
                                            <th style="width:20%;">Property</th>
                                            <th style="width:8%;">Ref</th>
                                            <th style="width:12%;">Class</th>
                                            <th style="width:8%;">Model</th>
                                            <th style="width:9%;">Acquired</th>
                                            <th class="amt" style="width:11%;">Cost</th>
                                            <th class="amt" style="width:10%;">Acc. Dep.</th>
                                            @if ($anyImp)
                                                <th class="amt" style="width:9%;">Acc. Imp.</th>
                                            @endif
                                            @if ($anyFV)
                                                <th class="amt" style="width:10%;">Fair Value</th>
                                            @endif
                                            <th class="amt" style="width:11%;">Carrying Amt</th>
                                            <th style="width:8%;text-align:center;">Status</th>
                                            <th style="width:4%;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($properties as $prop)
                                            @php
                                                $accDep   = $prop->accumulatedDepreciation($asOf);
                                                $accImp   = (float)($prop->accumulated_impairment ?? 0);
                                                $carrying = $prop->carryingAmount($asOf);
                                                if (!$prop->isDisposed()) {
                                                    $totalCost     += (float)$prop->cost;
                                                    $totalDep      += $accDep;
                                                    $totalImp      += $accImp;
                                                    $totalCarrying += $carrying;
                                                }
                                            @endphp
                                            <tr>
                                                <td>
                                                    <a class="reg-link" href="{{ route('companies.investment-properties.show', [$company, $prop]) }}">
                                                        {{ $prop->name }}
                                                    </a>
                                                </td>
                                                <td style="color:#4a5f78;font-size:6.5pt;">{{ $prop->property_reference ?? "\u{2014}" }}</td>
                                                <td style="color:#4a5f78;font-size:6.5pt;">{{ $prop->investmentPropertyClass?->name ?? "\u{2014}" }}</td>
                                                <td>
                                                    @if ($prop->isFairValueModel())
                                                        <span style="font-size:5.5pt;font-weight:700;color:#7c3aed;background:#f5f3ff;padding:0.08rem 0.35rem;border:1px solid #ddd6fe;">FV</span>
                                                    @else
                                                        <span style="font-size:5.5pt;color:#4a5f78;">Cost</span>
                                                    @endif
                                                </td>
                                                <td style="color:#4a5f78;font-size:6.5pt;white-space:nowrap;">{{ $prop->acquisition_date->format('d M Y') }}</td>
                                                <td class="amt">{{ number_format((float)$prop->cost, 2) }}</td>
                                                <td class="amt" style="color:#92400e;">{{ $prop->isFairValueModel() ? "\u{2014}" : number_format($accDep, 2) }}</td>
                                                @if ($anyImp)
                                                    <td class="amt" style="color:#92400e;">{{ $accImp != 0 ? number_format($accImp, 2) : "\u{2014}" }}</td>
                                                @endif
                                                @if ($anyFV)
                                                    <td class="amt" style="color:#7c3aed;">{{ $prop->fair_value !== null ? number_format((float)$prop->fair_value, 2) : "\u{2014}" }}</td>
                                                @endif
                                                <td class="amt" style="font-weight:800;">{{ number_format($carrying, 2) }}</td>
                                                <td style="text-align:center;">
                                                    <span class="reg-status {{ $prop->isDisposed() ? 'disposed' : '' }}">
                                                        {{ $prop->isDisposed() ? 'Disposed' : 'Active' }}
                                                    </span>
                                                </td>
                                                <td style="text-align:right;">
                                                    <div class="reg-row-actions">
                                                        <button type="button" class="reg-row-dots" onclick="toggleMenu(this)">&#x22EE;</button>
                                                        <div class="reg-row-menu">
                                                            <a href="{{ route('companies.investment-properties.show', [$company, $prop]) }}">View</a>
                                                            <form method="POST"
                                                                action="{{ route('companies.investment-properties.destroy', [$company, $prop]) }}"
                                                                onsubmit="return false" data-confirm-label="Investment Property Register" data-confirm-title="Remove Property" data-confirm-body="Remove {{ addslashes($prop->name) }} from the register?" data-confirm-text="Remove" data-confirm-danger="1">
                                                                @csrf
                                                                @method('DELETE')
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
                                            <td colspan="{{ 5 + ($anyFV ? 0 : 0) }}">Total (active properties)</td>
                                            <td class="amt">{{ number_format($totalCost, 2) }}</td>
                                            <td class="amt" style="color:#92400e;">{{ number_format($totalDep, 2) }}</td>
                                            @if ($anyImp)
                                                <td class="amt" style="color:#92400e;">{{ number_format($totalImp, 2) }}</td>
                                            @endif
                                            @if ($anyFV)
                                                <td class="amt"></td>
                                            @endif
                                            <td class="amt">{{ number_format($totalCarrying, 2) }}</td>
                                            <td colspan="2"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @endif

                        {{-- Add property inline form --}}
                        <div class="add-panel" id="panel-add-property" style="margin-top:1.25rem;">
                            <button type="button" class="add-panel-head" onclick="this.closest('.add-panel').classList.toggle('open')">
                                + Add Property
                            </button>
                            <div class="add-panel-body">
                                @if ($errors->any())
                                    <div style="background:#fee2e2;border:1px solid #fca5a5;color:#b91c1c;padding:4pt 8pt;font-size:6.5pt;margin-bottom:0.85rem;">
                                        <strong>Please fix the following:</strong>
                                        <ul style="margin:0.3rem 0 0 1rem;padding:0;">
                                            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                                        </ul>
                                    </div>
                                @endif
                                <form method="POST" action="{{ route('companies.investment-properties.store', $company) }}">
                                    @csrf
                                    {{-- Row 1: Identity --}}
                                    <div class="af-row">
                                        <div class="af-field wide">
                                            <label>Property name</label>
                                            <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. 42 Main Road, Cape Town" required>
                                        </div>
                                        <div class="af-field">
                                            <label>Reference</label>
                                            <input type="text" name="property_reference" value="{{ old('property_reference') }}" placeholder="e.g. IP-001">
                                        </div>
                                        <div class="af-field">
                                            <label>Property class</label>
                                            <select name="investment_property_class_id">
                                                <option value="">— Unclassified —</option>
                                                @foreach ($classes as $class)
                                                    <option value="{{ $class->id }}" @selected(old('investment_property_class_id') == $class->id)>{{ $class->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    {{-- Row 2: Financials --}}
                                    <div class="af-row">
                                        <div class="af-field">
                                            <label>Acquisition date</label>
                                            <input type="date" name="acquisition_date" value="{{ old('acquisition_date', now()->format('Y-m-d')) }}" required>
                                        </div>
                                        <div class="af-field">
                                            <label>Cost (R)</label>
                                            <input type="number" name="cost" value="{{ old('cost', '0.00') }}" min="0" step="0.01" required>
                                        </div>
                                        <div class="af-field">
                                            <label>Residual value (R)</label>
                                            <input type="number" name="residual_value" value="{{ old('residual_value', '0.00') }}" min="0" step="0.01">
                                        </div>
                                        <div class="af-field">
                                            <label>Location</label>
                                            <input type="text" name="location" value="{{ old('location') }}" placeholder="e.g. Sandton, Johannesburg">
                                        </div>
                                    </div>
                                    {{-- Row 3: Depreciation / Fair Value --}}
                                    <div class="af-row">
                                        <div class="af-field">
                                            <label>Useful life (yrs)</label>
                                            <input type="number" name="useful_life_years" value="{{ old('useful_life_years') }}" min="0" max="999.99" step="0.01" placeholder="from class">
                                        </div>
                                        <div class="af-field">
                                            <label>Depreciation method</label>
                                            <select name="depreciation_method">
                                                @foreach (\App\Models\InvestmentProperty::METHODS as $key => $label)
                                                    <option value="{{ $key }}" @selected(old('depreciation_method', 'straight_line') === $key)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="af-field">
                                            <label>Fair value (R)</label>
                                            <input type="number" name="fair_value" value="{{ old('fair_value') }}" min="0" step="0.01" placeholder="Optional">
                                        </div>
                                        <div class="af-field">
                                            <label>Fair value date</label>
                                            <input type="date" name="fair_value_date" value="{{ old('fair_value_date') }}">
                                        </div>
                                    </div>
                                    <div class="af-row">
                                        <div class="af-field full">
                                            <label>Notes</label>
                                            <input type="text" name="notes" value="{{ old('notes') }}" placeholder="Any additional detail">
                                        </div>
                                    </div>
                                    <div class="af-submit">
                                        <button type="submit" class="reg-btn primary">Add Property</button>
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
        @if ($errors->any())
            document.getElementById('panel-add-property').classList.add('open');
        @endif

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
