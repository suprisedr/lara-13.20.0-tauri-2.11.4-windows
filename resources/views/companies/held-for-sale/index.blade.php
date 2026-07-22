@extends('layouts.public')

@section('title', $company->registered_name . ' — Assets Held for Sale')
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

                <div class="reg-mgmt-bar">
                    <span style="font-size:6.5pt;color:#4a5f78;">
                        {{ $items->where('status', 'held_for_sale')->count() }} asset{{ $items->where('status', 'held_for_sale')->count() !== 1 ? 's' : '' }} held for sale
                    </span>
                </div>

                <div class="reg-doc">
                    <div class="reg-doc-body">
                        <div class="reg-doc-title">Assets Held for Sale</div>
                        <p class="reg-doc-subtitle">
                            Assets reclassified under IFRS 5 — available for immediate sale, sale highly probable within 12 months. Depreciation ceases on reclassification. Measured at lower of carrying amount and fair value less costs to sell.
                        </p>

                        <hr class="reg-divider">

                        @if ($items->isEmpty())
                            <p class="reg-empty">No assets held for sale. Use the "IFRS 5" action on the PPE register to reclassify an asset.</p>
                        @else
                            @php $totalCarrying = 0.0; @endphp
                            <div style="overflow-x:auto;">
                                <table class="reg-table">
                                    <thead>
                                        <tr>
                                            <th style="width:22%;">Asset</th>
                                            <th style="width:10%;">Tag</th>
                                            <th style="width:12%;">PPE Class</th>
                                            <th style="width:11%;">Reclassified</th>
                                            <th class="amt" style="width:12%;">Carrying Amt</th>
                                            <th class="amt" style="width:12%;">FVLCTS</th>
                                            <th class="amt" style="width:10%;">Write-down</th>
                                            <th style="width:7%;text-align:center;">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($items as $hfs)
                                            @php
                                                $ca = $hfs->carryingAmount();
                                                if ($hfs->status === 'held_for_sale') $totalCarrying += $ca;
                                            @endphp
                                            <tr>
                                                <td>
                                                    <a class="reg-link" href="{{ route('companies.held-for-sale.show', [$company, $hfs]) }}">
                                                        {{ $hfs->asset->name }}
                                                    </a>
                                                </td>
                                                <td class="dim">{{ $hfs->asset->asset_tag ?? "\u{2014}" }}</td>
                                                <td class="dim">{{ $hfs->asset->ppeClass?->name ?? "\u{2014}" }}</td>
                                                <td class="dim" style="white-space:nowrap;">{{ $hfs->reclassification_date->format('d M Y') }}</td>
                                                <td class="amt">{{ number_format((float)$hfs->carrying_amount_at_reclassification, 2) }}</td>
                                                <td class="amt" style="color:#0079c8;">{{ $hfs->fair_value_less_costs_to_sell !== null ? number_format((float)$hfs->fair_value_less_costs_to_sell, 2) : "\u{2014}" }}</td>
                                                <td class="amt" style="color:#92400e;">{{ (float)$hfs->impairment_on_reclassification > 0 ? number_format((float)$hfs->impairment_on_reclassification, 2) : "\u{2014}" }}</td>
                                                <td style="text-align:center;">
                                                    @if ($hfs->status === 'sold')
                                                        <span class="reg-status" style="color:#065f46;border-color:#065f46;">Sold</span>
                                                    @elseif ($hfs->status === 'reversed')
                                                        <span class="reg-status" style="color:#6a86a0;border-color:#6a86a0;">Reversed</span>
                                                    @else
                                                        <span class="reg-status hfs">HFS</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    @if ($items->where('status', 'held_for_sale')->count() > 0)
                                    <tfoot>
                                        <tr>
                                            <td colspan="4">Total (held for sale)</td>
                                            <td class="amt" colspan="3"></td>
                                            <td></td>
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
@endsection
