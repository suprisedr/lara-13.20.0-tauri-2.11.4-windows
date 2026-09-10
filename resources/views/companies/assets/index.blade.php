@extends('layouts.public')

@section('title', $company->registered_name . ' — Asset Register')
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
                    <div style="background:#dcfce7;border:1px solid #bbf7d0;color:#15803d;padding:4pt 8pt;font-size:7pt;font-weight:600;margin-bottom:8pt;">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div style="background:#fee2e2;border:1px solid #fca5a5;color:#b91c1c;padding:4pt 8pt;font-size:7pt;font-weight:600;margin-bottom:8pt;">
                        {{ session('error') }}
                    </div>
                @endif

                {{-- ── PPE Classes ──────────────────────────────────── --}}
                <div class="reg-mgmt-bar">
                    <a href="{{ route('companies.intangibles.index', $company) }}" style="margin-left:auto;">
                        Intangible Assets (IAS 38)
                        <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
                    </a>
                </div>

                <div class="reg-doc">
                    <div class="reg-doc-body">
                        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:4pt;">
                            <div class="reg-doc-title">PPE Classes</div>
                            <button type="button" class="reg-btn primary" onclick="openModal('class-modal')">+ Add Class</button>
                        </div>
                        <p class="reg-doc-subtitle">Group assets into classes. Assets inherit the class useful life &amp; method unless overridden.</p>

                        <hr class="reg-divider">

                        @if ($ppeClasses->isNotEmpty())
                            <table class="reg-table">
                                <thead>
                                    <tr>
                                        <th>Class</th>
                                        <th>Method</th>
                                        <th>Policy</th>
                                        <th class="amt">Useful life (yrs)</th>
                                        <th class="amt">Assets</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($ppeClasses as $class)
                                        @php $assetCount = $assets->where('ppe_class_id', $class->id)->count(); @endphp
                                        <tr>
                                            <td style="font-weight:700;">{{ $class->name }}</td>
                                            <td>{{ \App\Models\Asset::METHODS[$class->depreciation_method] ?? ($class->depreciation_method ?: '—') }}</td>
                                            <td>
                                                @if (($class->accounting_policy ?? 'cost') === 'revaluation')
                                                    <span class="reg-status revaluation">Revaluation</span>
                                                @else
                                                    <span style="color:#6f869b;">Cost</span>
                                                @endif
                                            </td>
                                            <td class="amt">
                                                {{ $class->useful_life_years !== null ? rtrim(rtrim(number_format((float)$class->useful_life_years, 2), '0'), '.') : '—' }}
                                            </td>
                                            <td class="amt dim">{{ $assetCount }}</td>
                                            <td style="text-align:right;">
                                                <div class="reg-row-actions">
                                                    <button type="button" class="reg-row-dots">&#x22EE;</button>
                                                    <div class="reg-row-menu">
                                                        <form method="POST"
                                                            action="{{ route('companies.assets.classes.destroy', [$company, $class]) }}"
                                                            onsubmit="return false" data-confirm-label="Asset Register" data-confirm-title="Delete Asset Class" data-confirm-body="Assets in this class will keep their data but become unclassified." data-confirm-text="Delete" data-confirm-danger="1">
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
                            <p class="reg-empty">No PPE classes yet.</p>
                        @endif
                    </div>
                </div>

                {{-- ── Asset Register ───────────────────────────────── --}}
                <div class="reg-mgmt-bar">
                    <span style="font-size:6.5pt;color:#5a7186;">
                        {{ $assets->count() }} asset{{ $assets->count() !== 1 ? 's' : '' }}
                    </span>
                    <div style="display:flex;align-items:center;gap:6pt;flex-wrap:wrap;">
                        <form method="GET" action="{{ route('companies.assets.index', $company) }}" style="display:flex;align-items:center;gap:4pt;">
                            <label style="font-size:6pt;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#5a7186;">Dep. as at</label>
                            <input type="date" name="as_of" value="{{ $asOf }}" onchange="this.form.submit()"
                                style="border:1px solid #9ec1f5;padding:2pt 4pt;font-size:7pt;font-family: "Century Gothic", "URW Gothic", "Avant Garde", Futura, "Avenir Next", Avenir, "Trebuchet MS", Helvetica, Arial, "DejaVu Sans", sans-serif;color:#1a345b;">
                        </form>
                        <button type="button" class="reg-btn primary" onclick="openModal('asset-modal')">+ Add Asset</button>
                    </div>
                </div>

                <div class="reg-doc">
                    <div class="reg-doc-body">
                        <div class="reg-doc-title">Assets</div>

                        <hr class="reg-divider">

                        @if ($assets->isEmpty())
                            <p class="reg-empty">No assets yet. Add your first fixed asset above.</p>
                        @else
                            @php
                                $totalCost = 0.0; $totalDep = 0.0; $totalImp = 0.0; $totalNbv = 0.0;
                                $anyImp = $assets->contains(fn($a) => (float)($a->accumulated_impairment ?? 0) != 0);
                            @endphp
                            <div style="overflow-x:auto;">
                                <table class="reg-table">
                                    <thead>
                                        <tr>
                                            <th style="width:22%;">Asset</th>
                                            <th style="width:9%;">Tag</th>
                                            <th style="width:13%;">Class</th>
                                            <th style="width:10%;">Acquired</th>
                                            <th class="amt" style="width:12%;">Cost</th>
                                            <th class="amt" style="width:12%;">Acc. Dep.</th>
                                            @if ($anyImp)
                                                <th class="amt" style="width:10%;">Acc. Imp.</th>
                                            @endif
                                            <th class="amt" style="width:12%;">NBV</th>
                                            <th style="width:8%;text-align:center;">Status</th>
                                            <th style="width:3%;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($assets as $asset)
                                            @php
                                                $accDep = $asset->accumulatedDepreciation($asOf);
                                                $accImp = (float)($asset->accumulated_impairment ?? 0);
                                                $nbv    = $asset->netBookValue($asOf);
                                                if (!$asset->isDisposed()) {
                                                    $totalCost += (float)$asset->cost;
                                                    $totalDep  += $accDep;
                                                    $totalImp  += $accImp;
                                                    $totalNbv  += $nbv;
                                                }
                                            @endphp
                                            <tr>
                                                <td>
                                                    <a class="reg-link" href="{{ route('companies.assets.show', [$company, $asset]) }}">
                                                        {{ $asset->name }}
                                                    </a>
                                                </td>
                                                <td class="dim">{{ $asset->asset_tag ?? '—' }}</td>
                                                <td class="dim">{{ $asset->ppeClass?->name ?? '—' }}</td>
                                                <td class="dim" style="white-space:nowrap;">{{ $asset->acquisition_date->format('d M Y') }}</td>
                                                <td class="amt">{{ number_format((float)$asset->cost, 2) }}</td>
                                                <td class="amt" style="color:#92400e;">{{ number_format($accDep, 2) }}</td>
                                                @if ($anyImp)
                                                    <td class="amt" style="color:#92400e;">{{ $accImp != 0 ? number_format($accImp, 2) : '—' }}</td>
                                                @endif
                                                <td class="amt" style="font-weight:800;">{{ number_format($nbv, 2) }}</td>
                                                <td style="text-align:center;">
                                                    @if ($asset->status === 'held_for_sale')
                                                        <span class="reg-status hfs">HFS</span>
                                                    @elseif ($asset->isDisposed())
                                                        <span class="reg-status disposed">Disposed</span>
                                                    @else
                                                        <span class="reg-status">Active</span>
                                                    @endif
                                                </td>
                                                <td style="text-align:right;">
                                                    <div class="reg-row-actions">
                                                        <button type="button" class="reg-row-dots">&#x22EE;</button>
                                                        <div class="reg-row-menu">
                                                            <a href="{{ route('companies.assets.show', [$company, $asset]) }}">View</a>
                                                            <form method="POST"
                                                                action="{{ route('companies.assets.destroy', [$company, $asset]) }}"
                                                                onsubmit="return false" data-confirm-label="Asset Register" data-confirm-title="Remove Asset" data-confirm-body="Remove {{ addslashes($asset->name) }} from the register?" data-confirm-text="Remove" data-confirm-danger="1">
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
                                            <td colspan="4">Total (active assets)</td>
                                            <td class="amt">{{ number_format($totalCost, 2) }}</td>
                                            <td class="amt" style="color:#92400e;">{{ number_format($totalDep, 2) }}</td>
                                            @if ($anyImp)
                                                <td class="amt" style="color:#92400e;">{{ number_format($totalImp, 2) }}</td>
                                            @endif
                                            <td class="amt">{{ number_format($totalNbv, 2) }}</td>
                                            <td colspan="2"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

            </main>
        </div>
    </div>

    {{-- ── Add Class Modal ──────────────────────────────────────── --}}
    <div id="class-modal"
        style="display:none;position:fixed;inset:0;background:rgba(26, 52, 91,0.45);z-index:9999;align-items:center;justify-content:center;padding:2rem 1rem;overflow-y:auto;">
        <div style="background:#fff;width:100%;max-width:520px;border-top:2pt solid #1a345b;box-shadow:0 20px 60px rgba(26, 52, 91,0.25);overflow:hidden;margin:0 auto;">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:8pt 12pt;border-bottom:1pt solid #9ec1f5;">
                <h3 style="font-size:9pt;font-weight:800;letter-spacing:0.02em;margin:0;color:#1a345b;">Add PPE Class</h3>
                <button onclick="closeModal('class-modal')"
                    style="background:none;border:none;font-size:12pt;line-height:1;color:#6f869b;cursor:pointer;">&times;</button>
            </div>
            <form method="POST" action="{{ route('companies.assets.classes.store', $company) }}">
                @csrf
                <div style="padding:10pt 12pt;">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:5pt 8pt;margin-bottom:8pt;">
                        <div class="reg-modal-field" style="grid-column:1/-1;">
                            <label>Class name</label>
                            <input type="text" name="name" placeholder="e.g. Motor Vehicles" required maxlength="120">
                        </div>
                        <div class="reg-modal-field">
                            <label>Useful life (yrs)</label>
                            <input type="number" name="useful_life_years" min="0" max="999.99" step="0.01" placeholder="5">
                        </div>
                        <div class="reg-modal-field">
                            <label>Method</label>
                            <select name="depreciation_method">
                                @foreach (\App\Models\Asset::METHODS as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="reg-modal-field" style="grid-column:1/-1;">
                            <label>Accounting policy</label>
                            <select name="accounting_policy">
                                @foreach (\App\Models\PpeClass::POLICIES as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div style="display:flex;gap:5pt;justify-content:flex-end;padding-top:5pt;border-top:0.5pt solid #d3e2f5;">
                        <button type="button" onclick="closeModal('class-modal')"
                            style="padding:3pt 8pt;border:1px solid #9ec1f5;font-size:6.5pt;color:#1a345b;background:#fff;cursor:pointer;font-weight:600;border-radius:0;">Cancel</button>
                        <button type="submit"
                            style="padding:3pt 10pt;background:#1a345b;color:#fff;border:1px solid #1a345b;font-size:6.5pt;font-weight:700;cursor:pointer;border-radius:0;">Add Class</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Add Asset Modal ──────────────────────────────────────── --}}
    <div id="asset-modal"
        style="display:none;position:fixed;inset:0;background:rgba(26, 52, 91,0.45);z-index:9999;align-items:center;justify-content:center;padding:2rem 1rem;overflow-y:auto;">
        <div style="background:#fff;width:100%;max-width:680px;border-top:2pt solid #1a345b;box-shadow:0 20px 60px rgba(26, 52, 91,0.25);overflow:hidden;margin:0 auto;">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:8pt 12pt;border-bottom:1pt solid #9ec1f5;">
                <h3 style="font-size:9pt;font-weight:800;letter-spacing:0.02em;margin:0;color:#1a345b;">Add Asset</h3>
                <button onclick="closeModal('asset-modal')"
                    style="background:none;border:none;font-size:12pt;line-height:1;color:#6f869b;cursor:pointer;">&times;</button>
            </div>
            <form method="POST" action="{{ route('companies.assets.store', $company) }}">
                @csrf
                <div style="padding:10pt 12pt;">

                    @if ($errors->any())
                        <div style="background:#fee2e2;border:1px solid #fca5a5;color:#b91c1c;padding:4pt 6pt;font-size:6.5pt;margin-bottom:8pt;">
                            <strong>Please fix the following:</strong>
                            <ul style="margin:2pt 0 0 10pt;padding:0;">
                                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                            </ul>
                        </div>
                    @endif

                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:5pt 8pt;margin-bottom:8pt;">
                        <div class="reg-modal-field" style="grid-column:span 2;">
                            <label>Asset name</label>
                            <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. Toyota Hilux 2.4 GD-6" required>
                        </div>
                        <div class="reg-modal-field">
                            <label>Asset tag</label>
                            <input type="text" name="asset_tag" value="{{ old('asset_tag') }}" placeholder="e.g. VEH-001">
                        </div>
                        <div class="reg-modal-field">
                            <label>PPE class</label>
                            <select name="ppe_class_id">
                                <option value="">— Unclassified —</option>
                                @foreach ($ppeClasses as $class)
                                    <option value="{{ $class->id }}" @selected(old('ppe_class_id') == $class->id)>{{ $class->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="reg-modal-field">
                            <label>Acquisition date</label>
                            <input type="date" name="acquisition_date" value="{{ old('acquisition_date', now()->format('Y-m-d')) }}" required>
                        </div>
                        <div class="reg-modal-field">
                            <label>Cost (R)</label>
                            <input type="number" name="cost" value="{{ old('cost', '0.00') }}" min="0" step="0.01" required>
                        </div>
                        <div class="reg-modal-field">
                            <label>Residual value (R)</label>
                            <input type="number" name="residual_value" value="{{ old('residual_value', '0.00') }}" min="0" step="0.01">
                        </div>
                        <div class="reg-modal-field">
                            <label>Location</label>
                            <input type="text" name="location" value="{{ old('location') }}" placeholder="e.g. Head office">
                        </div>

                        <div class="reg-modal-field">
                            <label>Useful life (yrs)</label>
                            <input type="number" name="useful_life_years" value="{{ old('useful_life_years') }}" min="0" max="999.99" step="0.01" placeholder="from class">
                        </div>
                        <div class="reg-modal-field">
                            <label>SARS wear &amp; tear (yrs)</label>
                            <input type="number" name="sars_wear_tear_years" value="{{ old('sars_wear_tear_years') }}" min="0" max="999.99" step="0.01">
                        </div>
                        <div class="reg-modal-field">
                            <label>Depreciation method</label>
                            <select name="depreciation_method">
                                @foreach (\App\Models\Asset::METHODS as $key => $label)
                                    <option value="{{ $key }}" @selected(old('depreciation_method', 'straight_line') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="reg-modal-field">
                            <label>Depreciation start date</label>
                            <input type="date" name="depreciation_start_date" value="{{ old('depreciation_start_date') }}">
                            <span style="font-size:5pt;color:#6f869b;">Leave blank to use acquisition date.</span>
                        </div>

                        <div class="reg-modal-field" style="grid-column:1/-1;">
                            <label>Notes</label>
                            <input type="text" name="notes" value="{{ old('notes') }}" placeholder="Any additional detail">
                        </div>
                    </div>

                    <div style="display:flex;gap:5pt;justify-content:flex-end;padding-top:5pt;border-top:0.5pt solid #d3e2f5;">
                        <button type="button" onclick="closeModal('asset-modal')"
                            style="padding:3pt 8pt;border:1px solid #9ec1f5;font-size:6.5pt;color:#1a345b;background:#fff;cursor:pointer;font-weight:600;border-radius:0;">Cancel</button>
                        <button type="submit"
                            style="padding:3pt 10pt;background:#1a345b;color:#fff;border:1px solid #1a345b;font-size:6.5pt;font-weight:700;cursor:pointer;border-radius:0;">Add Asset</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @include('companies._row-actions')

    <script>
        function openModal(id)  { document.getElementById(id).style.display = 'flex'; }
        function closeModal(id) { document.getElementById(id).style.display = 'none'; }

        document.getElementById('class-modal').addEventListener('click', function(e) { if (e.target === this) closeModal('class-modal'); });
        document.getElementById('asset-modal').addEventListener('click', function(e) { if (e.target === this) closeModal('asset-modal'); });

        @if ($errors->any())
            openModal('asset-modal');
        @endif
    </script>
@endsection
