@extends('layouts.public')

@section('title', $company->registered_name . ' — Intangible Asset Register')
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
                @if (session('error'))
                    <div style="background:#fee2e2;border:1px solid #fca5a5;color:#b91c1c;padding:4pt 8pt;font-size:7pt;font-weight:600;margin-bottom:10pt;">
                        {{ session('error') }}
                    </div>
                @endif

                {{-- ── Intangible Classes ─────────────────────────────── --}}
                <div class="reg-mgmt-bar">
<a href="{{ route('companies.assets.index', $company) }}" style="margin-left:auto;">
                        Tangible Assets (PPE)
                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
                    </a>
                </div>

                <div class="reg-doc">
                    <div class="reg-doc-body">
                        <div class="reg-doc-title">Intangible Classes</div>
                        <p style="font-size:7pt;color:#4a5f78;margin:2pt 0 6pt;">Group intangibles (software, patents, trademarks, goodwill, licences) into classes. IAS 38.107 indefinite-life classes are not amortised — they are impairment-tested annually.</p>

                        <hr class="reg-divider">

                        @if ($intangibleClasses->isNotEmpty())
                            <table class="reg-table" style="margin-bottom:8pt;">
                                <thead>
                                    <tr>
                                        <th>Class</th>
                                        <th>Method</th>
                                        <th>Policy</th>
                                        <th style="text-align:right;">Useful life (yrs)</th>
                                        <th style="text-align:right;">Intangibles</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($intangibleClasses as $class)
                                        @php $assetCount = $assets->where('intangible_class_id', $class->id)->count(); @endphp
                                        <tr>
                                            <td style="font-weight:700;">
                                                {{ $class->name }}
                                                @if ($class->indefinite_life)
                                                    <span style="font-size:5.5pt;font-weight:700;color:#92400e;background:#fef3c7;padding:0.05rem 0.35rem;border:1px solid #fde68a;margin-left:0.35rem;">Indefinite life</span>
                                                @endif
                                            </td>
                                            <td>{{ \App\Models\IntangibleAsset::METHODS[$class->amortisation_method] ?? ($class->amortisation_method ?: '—') }}</td>
                                            <td>
                                                @if (($class->accounting_policy ?? 'cost') === 'revaluation')
                                                    <span style="font-size:6pt;font-weight:700;color:#7c3aed;background:#f5f3ff;padding:0.1rem 0.4rem;border:1px solid #ddd6fe;">Revaluation</span>
                                                @else
                                                    <span style="font-size:6pt;color:#4a5f78;">Cost</span>
                                                @endif
                                            </td>
                                            <td style="text-align:right;font-family:monospace;">
                                                {{ $class->useful_life_years !== null ? rtrim(rtrim(number_format((float)$class->useful_life_years, 2), '0'), '.') : '—' }}
                                            </td>
                                            <td style="text-align:right;color:#4a5f78;">{{ $assetCount }}</td>
                                            <td style="text-align:right;">
                                                <div class="reg-row-actions">
                                                    <button type="button" class="reg-row-dots" onclick="toggleMenu(this)">&#x22EE;</button>
                                                    <div class="reg-row-menu">
                                                        <form method="POST"
                                                            action="{{ route('companies.intangibles.classes.destroy', [$company, $class]) }}"
                                                            onsubmit="return false" data-confirm-label="Intangible Assets" data-confirm-title="Delete Asset Class" data-confirm-body="Intangibles in this class will keep their data but become unclassified." data-confirm-text="Delete" data-confirm-danger="1">
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
                            <p style="color:#7a90a5;font-size:7pt;font-style:italic;margin-bottom:8pt;">No intangible classes yet.</p>
                        @endif

                        <div class="add-panel" id="panel-add-class">
                            <button type="button" class="add-panel-head" onclick="this.closest('.add-panel').classList.toggle('open')">
                                + Add Class
                            </button>
                            <div class="add-panel-body">
                                <form method="POST" action="{{ route('companies.intangibles.classes.store', $company) }}">
                                    @csrf
                                    <div class="af-row">
                                        <div class="af-field wide">
                                            <label>Class name</label>
                                            <input type="text" name="name" placeholder="e.g. Software, Trademarks, Goodwill" required maxlength="120">
                                        </div>
                                        <div class="af-field">
                                            <label>Useful life (yrs)</label>
                                            <input type="number" name="useful_life_years" min="0" max="999.99" step="0.01" placeholder="5">
                                        </div>
                                        <div class="af-field">
                                            <label>Method</label>
                                            <select name="amortisation_method">
                                                @foreach (\App\Models\IntangibleAsset::METHODS as $key => $label)
                                                    <option value="{{ $key }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="af-row">
                                        <div class="af-field wide">
                                            <label>Accounting policy</label>
                                            <select name="accounting_policy">
                                                @foreach (\App\Models\IntangibleClass::POLICIES as $key => $label)
                                                    <option value="{{ $key }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="af-field">
                                            <label>Indefinite life (IAS 38.107)</label>
                                            <label class="toggle-wrap">
                                                <input type="checkbox" name="indefinite_life" value="1">
                                                <span class="toggle-track"></span>
                                                <span class="toggle-text">No amortisation — impairment-only</span>
                                            </label>
                                        </div>
                                        <div style="display:flex;align-items:flex-end;justify-content:flex-end;">
                                            <button type="submit" class="reg-btn primary">Add Class</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- ── Intangible Register ──────────────────────────── --}}
                <div class="reg-mgmt-bar">
                    <span style="font-size:7pt;color:#4a5f78;">
                        {{ $assets->count() }} intangible{{ $assets->count() !== 1 ? 's' : '' }}
                    </span>
                    <button type="button" class="reg-btn primary" onclick="document.getElementById('panel-add-asset').classList.toggle('open');document.getElementById('panel-add-asset').scrollIntoView({behavior:'smooth',block:'nearest'})">
                        + Add Intangible
                    </button>
                </div>

                @php $asOf = now()->format('Y-m-d'); @endphp

                <div class="reg-doc">
                    <div class="reg-doc-body">
                        <div class="reg-doc-title">Intangibles</div>

                        <hr class="reg-divider">

                        @if ($assets->isEmpty())
                            <p style="color:#7a90a5;font-style:italic;font-size:7pt;">No intangible assets yet. Add your first one below.</p>
                        @else
                            @php
                                $totalCost = 0.0; $totalAmort = 0.0; $totalImp = 0.0; $totalNbv = 0.0;
                                $anyImp = $assets->contains(fn($a) => (float)($a->accumulated_impairment ?? 0) != 0);
                            @endphp
                            <div style="overflow-x:auto;">
                                <table class="reg-table">
                                    <thead>
                                        <tr>
                                            <th style="width:22%;">Intangible</th>
                                            <th style="width:9%;">Ref</th>
                                            <th style="width:13%;">Class</th>
                                            <th style="width:10%;">Acquired</th>
                                            <th class="amt" style="width:12%;">Cost</th>
                                            <th class="amt" style="width:12%;">Acc. Amort.</th>
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
                                                $accAmort = $asset->accumulatedAmortisation($asOf);
                                                $accImp   = (float)($asset->accumulated_impairment ?? 0);
                                                $nbv      = $asset->netBookValue($asOf);
                                                if (!$asset->isDisposed()) {
                                                    $totalCost  += (float)$asset->cost;
                                                    $totalAmort += $accAmort;
                                                    $totalImp   += $accImp;
                                                    $totalNbv   += $nbv;
                                                }
                                            @endphp
                                            <tr>
                                                <td>
                                                    <a class="reg-link" href="{{ route('companies.intangibles.show', [$company, $asset]) }}">
                                                        {{ $asset->name }}
                                                    </a>
                                                    @if ($asset->isIndefiniteLife())
                                                        <span style="font-size:5.5pt;font-weight:700;color:#92400e;background:#fef3c7;padding:0.04rem 0.3rem;border:1px solid #fde68a;margin-left:0.3rem;">&#x221E;</span>
                                                    @endif
                                                </td>
                                                <td class="dim" style="font-size:6.5pt;">{{ $asset->reference ?? '—' }}</td>
                                                <td class="dim" style="font-size:6.5pt;">{{ $asset->intangibleClass?->name ?? '—' }}</td>
                                                <td class="dim" style="font-size:6.5pt;white-space:nowrap;">{{ $asset->acquisition_date->format('d M Y') }}</td>
                                                <td class="amt">{{ number_format((float)$asset->cost, 2) }}</td>
                                                <td class="amt" style="color:#92400e;">{{ $asset->isIndefiniteLife() ? '—' : number_format($accAmort, 2) }}</td>
                                                @if ($anyImp)
                                                    <td class="amt" style="color:#92400e;">{{ $accImp != 0 ? number_format($accImp, 2) : '—' }}</td>
                                                @endif
                                                <td class="amt" style="font-weight:800;">{{ number_format($nbv, 2) }}</td>
                                                <td style="text-align:center;">
                                                    <span class="reg-status {{ $asset->isDisposed() ? 'disposed' : '' }}">
                                                        {{ $asset->isDisposed() ? 'Disposed' : 'Active' }}
                                                    </span>
                                                </td>
                                                <td style="text-align:right;">
                                                    <div class="reg-row-actions">
                                                        <button type="button" class="reg-row-dots" onclick="toggleMenu(this)">&#x22EE;</button>
                                                        <div class="reg-row-menu">
                                                            <a href="{{ route('companies.intangibles.show', [$company, $asset]) }}">View</a>
                                                            <form method="POST"
                                                                action="{{ route('companies.intangibles.destroy', [$company, $asset]) }}"
                                                                onsubmit="return false" data-confirm-label="Intangible Assets" data-confirm-title="Remove Asset" data-confirm-body="Remove {{ addslashes($asset->name) }} from the register?" data-confirm-text="Remove" data-confirm-danger="1">
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
                                            <td colspan="4">Total (active intangibles)</td>
                                            <td class="amt">{{ number_format($totalCost, 2) }}</td>
                                            <td class="amt" style="color:#92400e;">{{ number_format($totalAmort, 2) }}</td>
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

                        {{-- Add intangible inline form --}}
                        <div class="add-panel" id="panel-add-asset">
                            <button type="button" class="add-panel-head" onclick="this.closest('.add-panel').classList.toggle('open')">
                                + Add Intangible
                            </button>
                            <div class="add-panel-body">
                                @if ($errors->any())
                                    <div style="background:#fee2e2;border:1px solid #fca5a5;color:#b91c1c;padding:4pt 8pt;font-size:6.5pt;margin-bottom:6pt;">
                                        <strong>Please fix the following:</strong>
                                        <ul style="margin:2pt 0 0 8pt;padding:0;">
                                            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                                        </ul>
                                    </div>
                                @endif
                                <form method="POST" action="{{ route('companies.intangibles.store', $company) }}">
                                    @csrf
                                    {{-- Row 1: Identity --}}
                                    <div class="af-row">
                                        <div class="af-field wide">
                                            <label>Intangible name</label>
                                            <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. SAP ERP licence, Coca-Cola trademark" required>
                                        </div>
                                        <div class="af-field">
                                            <label>Reference</label>
                                            <input type="text" name="reference" value="{{ old('reference') }}" placeholder="e.g. SW-001">
                                        </div>
                                        <div class="af-field">
                                            <label>Intangible class</label>
                                            <select name="intangible_class_id">
                                                <option value="">— Unclassified —</option>
                                                @foreach ($intangibleClasses as $class)
                                                    <option value="{{ $class->id }}" @selected(old('intangible_class_id') == $class->id)>{{ $class->name }}</option>
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
                                            <label>Category</label>
                                            <input type="text" name="category" value="{{ old('category') }}" placeholder="e.g. Software">
                                        </div>
                                    </div>
                                    {{-- Row 3: Amortisation --}}
                                    <div class="af-row">
                                        <div class="af-field">
                                            <label>Useful life (yrs)</label>
                                            <input type="number" name="useful_life_years" value="{{ old('useful_life_years') }}" min="0" max="999.99" step="0.01" placeholder="from class">
                                        </div>
                                        <div class="af-field">
                                            <label>Amortisation method</label>
                                            <select name="amortisation_method">
                                                @foreach (\App\Models\IntangibleAsset::METHODS as $key => $label)
                                                    <option value="{{ $key }}" @selected(old('amortisation_method', 'straight_line') === $key)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="af-field">
                                            <label>Indefinite life (IAS 38.107)</label>
                                            <label class="toggle-wrap">
                                                <input type="checkbox" name="useful_life_indefinite" value="1" @checked(old('useful_life_indefinite'))>
                                                <span class="toggle-track"></span>
                                                <span class="toggle-text">No amortisation — impairment-only</span>
                                            </label>
                                        </div>
                                        <div class="af-field">
                                            <label>Notes</label>
                                            <input type="text" name="notes" value="{{ old('notes') }}" placeholder="Any additional detail">
                                        </div>
                                    </div>
                                    <div class="af-submit">
                                        <button type="submit" class="reg-btn primary">Add Intangible</button>
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
            document.getElementById('panel-add-asset').classList.add('open');
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
