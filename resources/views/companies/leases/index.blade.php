@extends('layouts.public')

@section('title', $company->registered_name . ' — Lease Register')
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

                <div class="reg-mgmt-bar">
                    <span style="font-size:7pt;color:#4a5f78;">
                        {{ $leases->count() }} lease{{ $leases->count() !== 1 ? 's' : '' }}
                    </span>
                    <div style="display:flex;align-items:center;gap:0.65rem;flex-wrap:wrap;">
                        <form method="GET" action="{{ route('companies.leases.index', $company) }}" style="display:flex;align-items:center;gap:0.5rem;">
                            <label style="font-size:6pt;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#4a5f78;">As at</label>
                            <input type="date" name="as_of" value="{{ $asOf }}" onchange="this.form.submit()"
                                style="border:1px solid #c9dff0;padding:0.32rem 0.5rem;font-size:7pt;font-family:inherit;">
                        </form>
                        <button type="button" class="reg-btn primary" onclick="document.getElementById('panel-add-lease').classList.toggle('open');document.getElementById('panel-add-lease').scrollIntoView({behavior:'smooth',block:'nearest'})">
                            + Add Lease
                        </button>
                    </div>
                </div>

                <div class="reg-doc">
                    <div class="reg-doc-body">
                        <div class="reg-doc-title">Leases</div>
                        <p style="font-size:7pt;color:#4a5f78;margin:0.2rem 0 0.75rem;">
                            IFRS 16 lease register — lessee (ROU asset + liability) and lessor (finance or operating) leases.
                        </p>

                        <hr class="reg-divider">

                        @if ($leases->isEmpty())
                            <p style="color:#7a90a5;font-style:italic;font-size:7pt;">No leases yet. Add your first lease below.</p>
                        @else
                            @php
                                $totalRou = 0.0; $totalDep = 0.0; $totalNbv = 0.0; $totalLiab = 0.0; $totalNetInv = 0.0;
                            @endphp
                            <div style="overflow-x:auto;">
                                <table class="reg-table">
                                    <thead>
                                        <tr>
                                            <th style="width:18%;">Lease</th>
                                            <th style="width:9%;">Role</th>
                                            <th style="width:8%;">Start</th>
                                            <th style="width:8%;">End</th>
                                            <th class="amt" style="width:11%;">Asset / Inv.</th>
                                            <th class="amt" style="width:11%;">Acc. Dep.</th>
                                            <th class="amt" style="width:11%;">NBV / Bal.</th>
                                            <th class="amt" style="width:11%;">Liability</th>
                                            <th style="width:8%;text-align:center;">Status</th>
                                            <th style="width:4%;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($leases as $lease)
                                            @php
                                                $isLessee = $lease->isLessee();
                                                $isFinance = $lease->isFinanceLease();
                                                $isOperating = $lease->isOperatingLease();
                                                $terminated = $lease->isTerminated();
                                                $expired = $lease->isExpired($asOf);
                                                $exempt = $lease->isExempt();
                                                $active = !$terminated && !$expired;

                                                if ($isLessee) {
                                                    $colAsset = (float)$lease->rou_asset_cost;
                                                    $colDep = $lease->accumulatedDepreciation($asOf);
                                                    $colNbv = $lease->rouNetBookValue($asOf);
                                                    $colLiab = $lease->leaseLiabilityBalance($asOf);
                                                    $roleChip = 'lessee'; $roleLabel = 'Lessee';
                                                    if ($active) { $totalRou += $colAsset; $totalDep += $colDep; $totalNbv += $colNbv; $totalLiab += $colLiab; }
                                                } elseif ($isFinance) {
                                                    $colAsset = (float)$lease->net_investment;
                                                    $colDep = 0.0;
                                                    $colNbv = $lease->netInvestmentBalance($asOf);
                                                    $colLiab = 0.0;
                                                    $roleChip = 'lessor-finance'; $roleLabel = 'Finance';
                                                    if ($active) { $totalNetInv += $colNbv; }
                                                } else {
                                                    $colAsset = 0.0;
                                                    $colDep = 0.0;
                                                    $colNbv = 0.0;
                                                    $colLiab = 0.0;
                                                    $roleChip = 'lessor-operating'; $roleLabel = 'Operating';
                                                }

                                                $statusClass = $terminated ? 'terminated' : ($expired ? 'expired' : ($exempt ? 'exempt' : ''));
                                                $statusLabel = $terminated ? 'Terminated' : ($expired ? 'Expired' : ($exempt ? 'Exempt' : 'Active'));
                                            @endphp
                                            <tr>
                                                <td>
                                                    <a class="reg-link" href="{{ route('companies.leases.show', [$company, $lease]) }}">
                                                        {{ $lease->name }}
                                                    </a>
                                                    @if ($lease->counterparty)
                                                        <div style="font-size:6.5pt;color:#7a90a5;">{{ $lease->counterparty }}</div>
                                                    @endif
                                                </td>
                                                <td><span class="role-chip {{ $roleChip }}">{{ $roleLabel }}</span></td>
                                                <td style="font-size:6.5pt;white-space:nowrap;">{{ $lease->commencement_date->format('d M Y') }}</td>
                                                <td style="font-size:6.5pt;white-space:nowrap;">{{ $lease->end_date->format('d M Y') }}</td>
                                                <td class="amt">{{ $colAsset > 0 ? number_format($colAsset, 2) : '—' }}</td>
                                                <td class="amt" style="color:#92400e;">{{ $colDep > 0 ? number_format($colDep, 2) : '—' }}</td>
                                                <td class="amt" style="font-weight:800;">{{ ($colNbv > 0 || $isLessee) ? number_format($colNbv, 2) : '—' }}</td>
                                                <td class="amt" style="color:#1d4ed8;">{{ $isLessee ? number_format($colLiab, 2) : '—' }}</td>
                                                <td style="text-align:center;">
                                                    <span class="reg-status {{ $statusClass }}">{{ $statusLabel }}</span>
                                                </td>
                                                <td style="text-align:right;">
                                                    <div class="reg-row-actions">
                                                        <button type="button" class="reg-row-dots" onclick="toggleMenu(this)">&#x22EE;</button>
                                                        <div class="reg-row-menu">
                                                            <a href="{{ route('companies.leases.show', [$company, $lease]) }}">View</a>
                                                            <form method="POST"
                                                                action="{{ route('companies.leases.destroy', [$company, $lease]) }}"
                                                                onsubmit="return false" data-confirm-label="Leases" data-confirm-title="Remove Lease" data-confirm-body="Remove {{ addslashes($lease->name) }} from the register?" data-confirm-text="Remove" data-confirm-danger="1">
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
                                            <td colspan="4">Total (active lessee leases)</td>
                                            <td class="amt">{{ number_format($totalRou, 2) }}</td>
                                            <td class="amt" style="color:#92400e;">{{ number_format($totalDep, 2) }}</td>
                                            <td class="amt">{{ number_format($totalNbv, 2) }}</td>
                                            <td class="amt" style="color:#1d4ed8;">{{ number_format($totalLiab, 2) }}</td>
                                            <td colspan="2"></td>
                                        </tr>
                                        @if ($totalNetInv > 0)
                                        <tr>
                                            <td colspan="6">Total net investment (active finance leases — lessor)</td>
                                            <td class="amt" style="color:#15803d;">{{ number_format($totalNetInv, 2) }}</td>
                                            <td colspan="3"></td>
                                        </tr>
                                        @endif
                                    </tfoot>
                                </table>
                            </div>
                        @endif

                        <div class="add-panel" id="panel-add-lease">
                            <button type="button" class="add-panel-head" onclick="this.closest('.add-panel').classList.toggle('open')">
                                + Add Lease
                            </button>
                            <div class="add-panel-body">
                                @if ($errors->any())
                                    <div style="background:#fee2e2;border:1px solid #fca5a5;color:#b91c1c;padding:4pt 8pt;font-size:7pt;margin-bottom:0.85rem;">
                                        <strong>Please fix the following:</strong>
                                        <ul style="margin:0.3rem 0 0 1rem;padding:0;">
                                            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                                        </ul>
                                    </div>
                                @endif
                                <p class="af-hint">
                                    Add a lease. <strong>Lessee</strong> leases recognise an ROU asset and lease liability.
                                    <strong>Lessor</strong> leases are classified as finance (receivable) or operating (straight-line income).
                                </p>
                                <form method="POST" action="{{ route('companies.leases.store', $company) }}" x-data="leaseForm()" x-init="init()">
                                    @csrf
                                    <div class="af-row">
                                        <div class="af-field">
                                            <label>Role</label>
                                            <select name="role" x-model="role" @change="roleChanged()">
                                                <option value="lessee">Lessee (we rent)</option>
                                                <option value="lessor">Lessor (we lease out)</option>
                                            </select>
                                        </div>
                                        <div class="af-field" x-show="role === 'lessor'" x-cloak>
                                            <label>Classification</label>
                                            <select name="classification" x-model="classification">
                                                <option value="operating">Operating Lease</option>
                                                <option value="finance">Finance Lease</option>
                                            </select>
                                        </div>
                                        <div class="af-field wide">
                                            <label>Lease description</label>
                                            <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. Office lease — 123 Main St" required>
                                        </div>
                                    </div>
                                    <div class="af-row">
                                        <div class="af-field">
                                            <label>Asset tag</label>
                                            <input type="text" name="asset_tag" value="{{ old('asset_tag') }}" placeholder="e.g. LEASE-001">
                                        </div>
                                        <div class="af-field">
                                            <label>Category</label>
                                            <select name="category">
                                                @foreach (\App\Models\Lease::CATEGORIES as $key => $label)
                                                    <option value="{{ $key }}" @selected(old('category', 'property') === $key)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="af-field">
                                            <label x-text="role === 'lessee' ? 'Lessor / Counterparty' : 'Lessee / Counterparty'">Lessor / Counterparty</label>
                                            <input type="text" name="counterparty" value="{{ old('counterparty') }}" placeholder="e.g. ABC Properties (Pty) Ltd">
                                        </div>
                                        <div class="af-field">
                                            <label>Commencement date</label>
                                            <input type="date" name="commencement_date" value="{{ old('commencement_date', now()->format('Y-m-d')) }}" required>
                                        </div>
                                        <div class="af-field">
                                            <label>End date</label>
                                            <input type="date" name="end_date" value="{{ old('end_date') }}" required>
                                        </div>
                                        <div class="af-field">
                                            <label>Location</label>
                                            <input type="text" name="location" value="{{ old('location') }}" placeholder="e.g. Head office">
                                        </div>
                                    </div>
                                    <div class="af-row">
                                        <div class="af-field">
                                            <label>Monthly payment (R)</label>
                                            <input type="number" name="monthly_payment" value="{{ old('monthly_payment') }}" step="0.01" min="0.01" required>
                                        </div>
                                        <div class="af-field">
                                            <label>Payment frequency</label>
                                            <select name="payment_frequency">
                                                @foreach (\App\Models\Lease::PAYMENT_FREQUENCIES as $key => $label)
                                                    <option value="{{ $key }}" @selected(old('payment_frequency', 'monthly') === $key)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="af-field">
                                            <label>IBR (annual, decimal)</label>
                                            <input type="number" name="incremental_borrowing_rate" value="{{ old('incremental_borrowing_rate', '0.10') }}" step="0.0001" min="0" max="1" required>
                                        </div>
                                        <div class="af-field">
                                            <label>Initial direct costs (R)</label>
                                            <input type="number" name="initial_direct_costs" value="{{ old('initial_direct_costs', '0.00') }}" step="0.01" min="0">
                                        </div>
                                    </div>
                                    <div class="af-row">
                                        <div class="af-field">
                                            <label>Residual value guarantee (R)</label>
                                            <input type="number" name="residual_value_guarantee" value="{{ old('residual_value_guarantee', '0.00') }}" step="0.01" min="0">
                                        </div>
                                        <div class="af-field" x-show="role === 'lessor' && classification === 'finance'" x-cloak>
                                            <label>Asset fair value (R)</label>
                                            <input type="number" name="asset_fair_value" value="{{ old('asset_fair_value', '0.00') }}" step="0.01" min="0">
                                        </div>
                                        <div class="af-field" x-show="role === 'lessor' && classification === 'finance'" x-cloak>
                                            <label>Unguaranteed residual (R)</label>
                                            <input type="number" name="unguaranteed_residual" value="{{ old('unguaranteed_residual', '0.00') }}" step="0.01" min="0">
                                        </div>
                                        <div class="af-field" x-show="role === 'lessee'" style="display:flex;align-items:center;gap:0.5rem;padding-top:1.1rem;" x-cloak>
                                            <input type="checkbox" name="is_short_term" value="1" id="st-check" style="width:auto;margin:0;">
                                            <label for="st-check" style="margin:0;font-size:6.5pt;font-weight:600;text-transform:none;letter-spacing:0;color:#16355c;cursor:pointer;">Short-term (&le; 12 months)</label>
                                        </div>
                                        <div class="af-field" x-show="role === 'lessee'" style="display:flex;align-items:center;gap:0.5rem;padding-top:1.1rem;" x-cloak>
                                            <input type="checkbox" name="is_low_value" value="1" id="lv-check" style="width:auto;margin:0;">
                                            <label for="lv-check" style="margin:0;font-size:6.5pt;font-weight:600;text-transform:none;letter-spacing:0;color:#16355c;cursor:pointer;">Low-value asset</label>
                                        </div>
                                    </div>
                                    <div class="af-row">
                                        <div class="af-field full">
                                            <label>Notes</label>
                                            <input type="text" name="notes" value="{{ old('notes') }}" placeholder="Any additional detail">
                                        </div>
                                    </div>
                                    <div class="af-submit">
                                        <button type="submit" class="reg-btn primary">Add Lease</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        @if ($errors->any())
            document.getElementById('panel-add-lease').classList.add('open');
        @endif

        function leaseForm() {
            return {
                role: '{{ old('role', 'lessee') }}',
                classification: '{{ old('classification', 'operating') }}',
                init() {},
                roleChanged() {
                    if (this.role === 'lessee') this.classification = '';
                    else if (!this.classification) this.classification = 'operating';
                },
            };
        }

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
