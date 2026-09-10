@extends('layouts.public')

@section('title', $company->registered_name . ' — Dashboard')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        /* ── Dashboard layout ──────────────── */
        .dash-grid { display:grid; gap:10pt; }
        .dash-row-4 { grid-template-columns:repeat(4,1fr); }
        .dash-row-2 { grid-template-columns:repeat(2,1fr); }

        @media (max-width:900px) {
            .dash-row-4 { grid-template-columns:repeat(2,1fr); }
        }
        @media (max-width:600px) {
            .dash-row-4, .dash-row-2 { grid-template-columns:1fr; }
        }

        /* ── Cards (shared) ────────────────── */
        .kpi-card, .chart-panel {
            background:#fff;
            border:1px solid #9ec1f5;
            transition:border-color 0.2s;
        }
        .kpi-card:hover, .chart-panel:hover {
            border-color:#005bf0;
        }

        /* ── KPI stat cards ────────────────── */
        .kpi-card {
            padding:10pt 11pt 7pt;
            display:flex; flex-direction:column; gap:3pt;
            overflow:hidden; position:relative;
        }
        .kpi-head { display:flex; align-items:center; justify-content:space-between; gap:4pt; }
        .kpi-label { font-size: 10pt; font-weight:700; text-transform: none; letter-spacing: 0; color:#6f869b; margin:0; font-family: "Century Gothic", "URW Gothic", "Avant Garde", Futura, "Avenir Next", Avenir, "Trebuchet MS", Helvetica, Arial, "DejaVu Sans", sans-serif; }
        .kpi-icon {
            width:22pt; height:22pt; flex-shrink:0;
            display:inline-flex; align-items:center; justify-content:center;
        }
        .kpi-icon svg { width:12pt; height:12pt; }
        .kpi-value {
            font-size:14pt; font-weight:800; color:#1a345b; margin:0;
            line-height:1.1; letter-spacing:-0.02em; font-variant-numeric:tabular-nums;
            font-family: inherit; font-variant-numeric: tabular-nums;
        }
        .kpi-delta {
            font-size: 10pt; font-weight:700; margin:0;
            display:inline-flex; align-items:center; gap:2pt;
            padding:1pt 4pt; width:fit-content;
            font-family: "Century Gothic", "URW Gothic", "Avant Garde", Futura, "Avenir Next", Avenir, "Trebuchet MS", Helvetica, Arial, "DejaVu Sans", sans-serif;
        }
        .kpi-delta.up { color:#027a48; background:#ecfdf3; }
        .kpi-delta.down { color:#b42318; background:#fef3f2; }
        .kpi-delta.neutral { color:#6f869b; background:#f4fafc; }
        .kpi-spark { margin:3pt -11pt -7pt; height:32pt; }
        .kpi-spark svg { display:block; width:100%; height:32pt; }

        /* ── Chart panels ──────────────────── */
        .chart-panel { padding:11pt 12pt; display:flex; flex-direction:column; }
        .chart-panel-title {
            font-size:7pt; font-weight:700; letter-spacing:-0.01em;
            color:#1a345b; margin:0 0 1pt;
            font-family: "Century Gothic", "URW Gothic", "Avant Garde", Futura, "Avenir Next", Avenir, "Trebuchet MS", Helvetica, Arial, "DejaVu Sans", sans-serif;
        }
        .chart-panel-sub { font-size: 7pt; color:#6f869b; margin:0 0 7pt; font-family: "Century Gothic", "URW Gothic", "Avant Garde", Futura, "Avenir Next", Avenir, "Trebuchet MS", Helvetica, Arial, "DejaVu Sans", sans-serif; }
        .chart-panel svg { width:100%; height:auto; display:block; }

        /* ── D3 tooltip ────────────────────── */
        .d3-tooltip {
            position:absolute; pointer-events:none; z-index:999;
            background:rgba(26, 52, 91,0.94); color:#fff;
            font-size: 10pt; font-weight:500;
            padding:5pt 6pt; line-height:1.5;
            white-space:nowrap; opacity:0; transition:opacity 0.15s;
            font-family: "Century Gothic", "URW Gothic", "Avant Garde", Futura, "Avenir Next", Avenir, "Trebuchet MS", Helvetica, Arial, "DejaVu Sans", sans-serif;
        }
        .d3-tooltip .tt-label { color:#2674f2; font-size: 10pt; font-weight:700; text-transform: none; letter-spacing: 0; margin-bottom:1.5pt; }
        .d3-tooltip .tt-row { display:flex; align-items:center; gap:4pt; }
        .d3-tooltip .tt-swatch { width:6pt; height:6pt; flex-shrink:0; }
        .d3-tooltip .tt-val { font-weight:700; font-variant-numeric:tabular-nums; margin-left:auto; padding-left:6pt; font-family: inherit; font-variant-numeric: tabular-nums; }

        /* ── Legend (pill chips) ───────────── */
        .chart-legend { display:flex; flex-wrap:wrap; gap:3pt; margin-bottom:8pt; }
        .chart-legend-item {
            display:inline-flex; align-items:center; gap:3pt;
            font-size: 10.5pt; color:#5a7186; font-weight:600;
            background:#f4fafc; border:1px solid #9ec1f5;
            padding:1.5pt 5pt;
            font-family: "Century Gothic", "URW Gothic", "Avant Garde", Futura, "Avenir Next", Avenir, "Trebuchet MS", Helvetica, Arial, "DejaVu Sans", sans-serif;
        }
        .chart-legend-swatch { width:6pt; height:6pt; flex-shrink:0; }

        /* ── Recent transactions table ─────── */
        .dash-table { width:100%; border-collapse:collapse; font-size: 10pt; font-family: "Century Gothic", "URW Gothic", "Avant Garde", Futura, "Avenir Next", Avenir, "Trebuchet MS", Helvetica, Arial, "DejaVu Sans", sans-serif; }
        .dash-table th { font-size: 10.5pt; font-weight:700; text-transform: none; letter-spacing: 0; color:#6f869b; text-align:left; padding:5pt 5pt; border-bottom:1.5pt solid #1a345b; }
        .dash-table td { padding:5pt 5pt; border-bottom:0.4pt solid #d3e2f5; color:#1a345b; }
        .dash-table tbody tr { transition:background 0.12s; }
        .dash-table tbody tr:hover { background:#f4fafc; }
        .dash-table tr:last-child td { border-bottom:none; }
        .dash-table .mono { font-variant-numeric:tabular-nums; font-family: inherit; font-variant-numeric: tabular-nums; }
        .dash-table a {
            color:#005bf0; text-decoration:none; font-weight:600; font-size: 10pt;
            background:#eaf8fb; padding:1pt 4pt;
            display:inline-block; transition:background 0.12s;
        }
        .dash-table a:hover { background:#9ec1f5; }

        /* ── Empty state ───────────────────── */
        .dash-empty { text-align:center; padding:20pt 8pt; color:#6f869b; font-size:7pt; font-family: "Century Gothic", "URW Gothic", "Avant Garde", Futura, "Avenir Next", Avenir, "Trebuchet MS", Helvetica, Arial, "DejaVu Sans", sans-serif; }
    </style>
@endpush

@section('content')
<div class="co-wrap">

    @include('companies._topbar', ['backLabel' => 'Companies', 'topbarMeta' => 'Financial Dashboard &middot; ' . \Carbon\Carbon::parse($startDate)->format('d M Y') . ' &ndash; ' . \Carbon\Carbon::parse($endDate)->format('d M Y')])

    <div class="co-body">
    @include('companies._sidebar', ['company' => $company])

    <main class="co-main">

        {{-- Date Filter --}}
        <form method="GET" action="{{ route('companies.dashboard', $company) }}" class="is-filter-bar" style="margin-bottom:10pt;">
            <div>
                <label for="start_date">From</label>
                <input type="date" id="start_date" name="start_date" value="{{ $startDate }}">
            </div>
            <div>
                <label for="end_date">To</label>
                <input type="date" id="end_date" name="end_date" value="{{ $endDate }}">
            </div>
            <button type="submit" class="is-filter-btn">Apply</button>
        </form>

        {{-- Row 1: KPI Cards --}}
        <div class="dash-grid dash-row-4" style="margin-bottom:10pt;">
            @php
                $pctChange = function ($cur, $prior) {
                    if (abs($prior) < 0.01) return null;
                    return round(($cur - $prior) / abs($prior) * 100, 1);
                };
                $fmt = function ($v) {
                    $abs = abs($v);
                    if ($abs >= 1_000_000) return ($v < 0 ? '-' : '') . number_format($abs / 1_000_000, 1) . 'M';
                    if ($abs >= 1_000) return ($v < 0 ? '-' : '') . number_format($abs / 1_000, 1) . 'K';
                    return number_format($v, 0);
                };
                $cards = [
                    ['key' => 'revenue',  'label' => 'Revenue',       'value' => $revenue,      'prior' => $priorRevenue,   'upGood' => true,  'color' => '#005bf0', 'tint' => '#eaf8fb'],
                    ['key' => 'expenses', 'label' => 'Expenses',      'value' => $expenses,     'prior' => $priorExpenses,  'upGood' => false, 'color' => '#e34948', 'tint' => '#fef3f2'],
                    ['key' => 'profit',   'label' => 'Net Profit',    'value' => $netProfit,    'prior' => $priorNetProfit, 'upGood' => true,  'color' => '#1a345b', 'tint' => '#f4fafc'],
                    ['key' => 'cash',     'label' => 'Cash Position', 'value' => $cashPosition, 'prior' => null,            'upGood' => true,  'color' => '#1baf7a', 'tint' => '#ecfdf3'],
                ];
                $icons = [
                    'revenue'  => '<path d="M3 17l4-6 4 3 5-8 3 4" stroke-linecap="round" stroke-linejoin="round"/>',
                    'expenses' => '<path d="M3 7l4 6 4-3 5 8 3-4" stroke-linecap="round" stroke-linejoin="round"/>',
                    'profit'   => '<path d="M12 3v18M7 8l5-5 5 5" stroke-linecap="round" stroke-linejoin="round"/>',
                    'cash'     => '<rect x="3" y="7" width="18" height="12" rx="2"/><circle cx="12" cy="13" r="2.5"/>',
                ];
            @endphp
            @foreach ($cards as $card)
                <div class="kpi-card">
                    <div class="kpi-head">
                        <p class="kpi-label">{{ $card['label'] }}</p>
                        <span class="kpi-icon" style="background:{{ $card['tint'] }};">
                            <svg viewBox="0 0 24 24" fill="none" stroke="{{ $card['color'] }}" stroke-width="2">{!! $icons[$card['key']] !!}</svg>
                        </span>
                    </div>
                    <p class="kpi-value">R {{ $fmt($card['value']) }}</p>
                    @if ($card['prior'] !== null)
                        @php
                            $pct = $pctChange($card['value'], $card['prior']);
                            $isUp = $pct !== null && $pct > 0;
                            $isDown = $pct !== null && $pct < 0;
                            $dirClass = $pct === null ? 'neutral' : (($isUp && $card['upGood']) || ($isDown && !$card['upGood']) ? 'up' : (($isDown && $card['upGood']) || ($isUp && !$card['upGood']) ? 'down' : 'neutral'));
                        @endphp
                        <p class="kpi-delta {{ $dirClass }}">
                            @if ($pct !== null)
                                {{ $isUp ? '↑' : ($isDown ? '↓' : '') }} {{ $isUp ? '+' : '' }}{{ $pct }}% vs prior
                            @else
                                — no prior data
                            @endif
                        </p>
                    @else
                        <p class="kpi-delta neutral">as at period end</p>
                    @endif
                    <div class="kpi-spark" id="spark-{{ $card['key'] }}" data-color="{{ $card['color'] }}"></div>
                </div>
            @endforeach
        </div>

        {{-- Revenue vs Expenses (full width) --}}
        <div class="chart-panel" style="margin-bottom:10pt;">
            <p class="chart-panel-title">Revenue vs Expenses</p>
            <p class="chart-panel-sub">Monthly totals for the selected period</p>
            <div class="chart-legend">
                <span class="chart-legend-item"><span class="chart-legend-swatch" style="background:#005bf0;"></span> Revenue</span>
                <span class="chart-legend-item"><span class="chart-legend-swatch" style="background:#e34948;"></span> Expenses</span>
            </div>
            <div id="chart-rev-exp" style="width:100%;"></div>
        </div>

        {{-- Cash Flow Trend (full width) --}}
        <div class="chart-panel" style="margin-bottom:10pt;">
            <p class="chart-panel-title">Cash Flow Trend</p>
            <p class="chart-panel-sub">Operating, investing &amp; financing activity per month</p>
            <div class="chart-legend">
                <span class="chart-legend-item"><span class="chart-legend-swatch" style="background:#005bf0;"></span> Operating</span>
                <span class="chart-legend-item"><span class="chart-legend-swatch" style="background:#1baf7a;"></span> Investing</span>
                <span class="chart-legend-item"><span class="chart-legend-swatch" style="background:#eda100;"></span> Financing</span>
            </div>
            <div id="chart-cashflow" style="width:100%;"></div>
        </div>

        {{-- Row 3: Balance Sheet Composition + AR Aging --}}
        <div class="dash-grid dash-row-2" style="margin-bottom:10pt;">
            <div class="chart-panel">
                <p class="chart-panel-title">Balance Sheet Composition</p>
                <p class="chart-panel-sub">Financial position as at {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</p>
                <div class="chart-legend">
                    <span class="chart-legend-item"><span class="chart-legend-swatch" style="background:#005bf0;"></span> Current Assets</span>
                    <span class="chart-legend-item"><span class="chart-legend-swatch" style="background:#1baf7a;"></span> Non-Current Assets</span>
                    <span class="chart-legend-item"><span class="chart-legend-swatch" style="background:#eda100;"></span> Current Liabilities</span>
                    <span class="chart-legend-item"><span class="chart-legend-swatch" style="background:#e34948;"></span> Non-Current Liabilities</span>
                    <span class="chart-legend-item"><span class="chart-legend-swatch" style="background:#1a345b;"></span> Equity</span>
                </div>
                <div id="chart-bs" style="width:100%;"></div>
            </div>
            <div class="chart-panel">
                <p class="chart-panel-title">Accounts Receivable Aging</p>
                <p class="chart-panel-sub">Outstanding debtors by age bucket</p>
                <div class="chart-legend">
                    <span class="chart-legend-item"><span class="chart-legend-swatch" style="background:#005bf0;"></span> Current</span>
                    <span class="chart-legend-item"><span class="chart-legend-swatch" style="background:#1baf7a;"></span> 31-60 days</span>
                    <span class="chart-legend-item"><span class="chart-legend-swatch" style="background:#eda100;"></span> 61-90 days</span>
                    <span class="chart-legend-item"><span class="chart-legend-swatch" style="background:#e34948;"></span> 91+ days</span>
                </div>
                <div id="chart-ar" style="width:100%;"></div>
            </div>
        </div>

        {{-- Recent Transactions (full width) --}}
        <div class="chart-panel">
            <p class="chart-panel-title">Recent Transactions</p>
                <p class="chart-panel-sub">Latest activity in the selected period</p>
                @if ($recentTransactions->isEmpty())
                    <div class="dash-empty">No posted transactions yet.</div>
                @else
                    <div style="overflow-x:auto;">
                        <table class="dash-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th>Ref</th>
                                    <th style="text-align:right;">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentTransactions as $t)
                                    @php
                                        $flow = $t['cash_flow'] ?? 'none';
                                        $flowColor = $flow === 'inflow' ? '#027a48' : ($flow === 'outflow' ? '#b42318' : '#5a7186');
                                    @endphp
                                    <tr>
                                        <td class="mono" style="white-space:nowrap;">{{ $t['date'] }}</td>
                                        <td>{{ \Illuminate\Support\Str::limit($t['description'], 40) }}</td>
                                        <td><a href="{{ route('companies.transactions', [$company, 'highlight' => $t['reference']]) }}">{{ $t['reference'] }}</a></td>
                                        <td class="mono" style="text-align:right;font-weight:600;color:{{ $flowColor }};">R {{ number_format($t['amount'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
        </div>

    </main>
    </div>{{-- co-body --}}
</div>

<div class="d3-tooltip" id="d3-tooltip"></div>
@endsection

@push('scripts')
<script>
window.addEventListener('load', function () {
requestAnimationFrame(function () {
const D3 = window.d3;

const monthlyData = @json($monthlyData);
const cashFlowTrend = @json($cashFlowTrend);
const bsComposition = @json($bsComposition);
const arAging = @json($arAging);

const tooltip = document.getElementById('d3-tooltip');
const fmtCurrency = v => {
    const abs = Math.abs(v);
    const sign = v < 0 ? '-' : '';
    if (abs >= 1e6) return sign + 'R ' + (abs / 1e6).toFixed(1) + 'M';
    if (abs >= 1e3) return sign + 'R ' + (abs / 1e3).toFixed(1) + 'K';
    return sign + 'R ' + abs.toLocaleString('en-ZA', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
};
const fmtFull = v => 'R ' + v.toLocaleString('en-ZA', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

function showTooltip(evt, html) {
    tooltip.innerHTML = html;
    tooltip.style.opacity = '1';
    const rect = tooltip.getBoundingClientRect();
    let x = evt.pageX + 14, y = evt.pageY - 12;
    if (x + rect.width > window.innerWidth - 8) x = evt.pageX - rect.width - 14;
    if (y + rect.height > window.innerHeight + window.scrollY - 8) y = evt.pageY - rect.height - 12;
    tooltip.style.left = x + 'px';
    tooltip.style.top = y + 'px';
}
function hideTooltip() { tooltip.style.opacity = '0'; }

function topRoundedRect(x, y, w, h, r) {
    r = Math.max(0, Math.min(r, w / 2, h));
    return `M${x},${y + h} L${x},${y + r} Q${x},${y} ${x + r},${y} L${x + w - r},${y} Q${x + w},${y} ${x + w},${y + r} L${x + w},${y + h} Z`;
}

let gradSeq = 0;
function thinTicks(values, innerW) {
    const maxTicks = Math.max(2, Math.floor(innerW / 80));
    const step = Math.ceil(values.length / maxTicks);
    return values.filter((_, i) => i % step === 0);
}

function verticalGradient(svg, color, topOpacity, bottomOpacity) {
    const id = 'dashgrad-' + (++gradSeq);
    const grad = svg.append('defs').append('linearGradient')
        .attr('id', id).attr('x1', 0).attr('y1', 0).attr('x2', 0).attr('y2', 1);
    grad.append('stop').attr('offset', '0%').attr('stop-color', color).attr('stop-opacity', topOpacity);
    grad.append('stop').attr('offset', '100%').attr('stop-color', color).attr('stop-opacity', bottomOpacity);
    return `url(#${id})`;
}

// ─── 0. KPI Sparklines ────────────────────────────────────
(function () {
    const sparkSeries = {
        revenue:  monthlyData.map(d => d.revenue),
        expenses: monthlyData.map(d => d.expenses),
        profit:   monthlyData.map(d => d.revenue - d.expenses),
        cash:     cashFlowTrend.map(d => d.operating + d.investing + d.financing),
    };

    Object.keys(sparkSeries).forEach(key => {
        const el = document.getElementById('spark-' + key);
        if (!el) return;
        const values = sparkSeries[key];
        if (!values || values.length < 2) { el.style.display = 'none'; return; }

        const color = el.dataset.color;
        const w = el.clientWidth || 260, h = 32;
        const pad = 3;

        const svg = D3.select(el).append('svg')
            .attr('viewBox', `0 0 ${w} ${h}`).attr('preserveAspectRatio', 'none');

        const x = D3.scaleLinear().domain([0, values.length - 1]).range([0, w]);
        const ext = D3.extent(values);
        const y = D3.scaleLinear()
            .domain([Math.min(ext[0], 0), Math.max(ext[1], 1)])
            .range([h - pad, pad]);

        const areaFill = verticalGradient(svg, color, 0.22, 0);
        const area = D3.area().x((d, i) => x(i)).y0(h).y1(d => y(d)).curve(D3.curveMonotoneX);
        const line = D3.line().x((d, i) => x(i)).y(d => y(d)).curve(D3.curveMonotoneX);

        svg.append('path').datum(values).attr('d', area).attr('fill', areaFill);
        const path = svg.append('path').datum(values).attr('d', line)
            .attr('fill', 'none').attr('stroke', color).attr('stroke-width', 2)
            .attr('stroke-linecap', 'round').attr('stroke-linejoin', 'round');

        const len = path.node().getTotalLength();
        path.attr('stroke-dasharray', `${len} ${len}`).attr('stroke-dashoffset', len)
            .transition().duration(900).ease(D3.easeCubicOut).attr('stroke-dashoffset', 0);
    });
})();

// ─── 1. Revenue vs Expenses (Grouped Bar) ─────────────────
(function () {
    const container = document.getElementById('chart-rev-exp');
    if (!monthlyData.length) { container.innerHTML = '<div class="dash-empty">No data for current period.</div>'; return; }

    const margin = { top: 12, right: 16, bottom: 32, left: 56 };
    const width = container.clientWidth;
    const height = 270;
    const innerW = width - margin.left - margin.right;
    const innerH = height - margin.top - margin.bottom;

    const svg = D3.select(container).append('svg')
        .attr('viewBox', `0 0 ${width} ${height}`)
        .attr('preserveAspectRatio', 'xMidYMid meet');

    const g = svg.append('g').attr('transform', `translate(${margin.left},${margin.top})`);

    const x0 = D3.scaleBand().domain(monthlyData.map(d => d.month)).range([0, innerW]).padding(0.32);
    const x1 = D3.scaleBand().domain(['revenue', 'expenses']).range([0, x0.bandwidth()]).padding(0.12);
    const maxVal = D3.max(monthlyData, d => Math.max(d.revenue, d.expenses)) || 1;
    const y = D3.scaleLinear().domain([0, maxVal * 1.1]).nice().range([innerH, 0]);

    g.append('g').selectAll('line').data(y.ticks(5)).join('line')
        .attr('x1', 0).attr('x2', innerW)
        .attr('y1', d => y(d)).attr('y2', d => y(d))
        .attr('stroke', '#d3e2f5').attr('stroke-width', 1).attr('stroke-dasharray', '3,3');

    g.append('g').attr('transform', `translate(0,${innerH})`)
        .call(D3.axisBottom(x0).tickValues(thinTicks(x0.domain(), innerW)).tickSize(0).tickPadding(10))
        .call(g => g.select('.domain').remove())
        .selectAll('text').style('font-size', '5pt').style('fill', '#6f869b').style('font-weight', '500');

    g.append('g').call(D3.axisLeft(y).ticks(5).tickFormat(d => fmtCurrency(d)).tickSize(0).tickPadding(8))
        .call(g => g.select('.domain').remove())
        .selectAll('text').style('font-size', '5pt').style('fill', '#6f869b').style('font-weight', '500');

    const colors = { revenue: '#005bf0', expenses: '#e34948' };
    const gradients = {
        revenue: verticalGradient(svg, colors.revenue, 1, 0.72),
        expenses: verticalGradient(svg, colors.expenses, 1, 0.72),
    };
    const barW = Math.min(x1.bandwidth(), 22);
    const barOffset = (x1.bandwidth() - barW) / 2;

    ['revenue', 'expenses'].forEach(key => {
        const bars = g.selectAll(`.bar-${key}`).data(monthlyData).join('path')
            .attr('class', `bar-${key}`)
            .attr('fill', gradients[key])
            .style('cursor', 'pointer')
            .attr('d', d => topRoundedRect(x0(d.month) + x1(key) + barOffset, innerH, barW, 0, 5))
            .on('pointerenter', function (evt, d) {
                D3.select(this).attr('opacity', 0.82);
                showTooltip(evt, `
                    <div class="tt-label">${d.month}</div>
                    <div class="tt-row"><span class="tt-swatch" style="background:#005bf0;"></span> Revenue <span class="tt-val">${fmtFull(d.revenue)}</span></div>
                    <div class="tt-row"><span class="tt-swatch" style="background:#e34948;"></span> Expenses <span class="tt-val">${fmtFull(d.expenses)}</span></div>
                `);
            })
            .on('pointermove', (evt) => showTooltip(evt, tooltip.innerHTML))
            .on('pointerleave', function () { D3.select(this).attr('opacity', 1); hideTooltip(); });

        bars.transition().duration(700).ease(D3.easeCubicOut)
            .delay((d, i) => i * 40)
            .attr('d', d => topRoundedRect(x0(d.month) + x1(key) + barOffset, y(d[key]), barW, innerH - y(d[key]), 5));
    });
})();

// ─── 2. Cash Flow Trend (Line Chart) ──────────────────────
(function () {
    const container = document.getElementById('chart-cashflow');
    if (!cashFlowTrend.length) { container.innerHTML = '<div class="dash-empty">No data for current period.</div>'; return; }

    const margin = { top: 12, right: 52, bottom: 32, left: 56 };
    const width = container.clientWidth;
    const height = 270;
    const innerW = width - margin.left - margin.right;
    const innerH = height - margin.top - margin.bottom;

    const svg = D3.select(container).append('svg')
        .attr('viewBox', `0 0 ${width} ${height}`)
        .attr('preserveAspectRatio', 'xMidYMid meet');

    const g = svg.append('g').attr('transform', `translate(${margin.left},${margin.top})`);

    const x = D3.scalePoint().domain(cashFlowTrend.map(d => d.month)).range([0, innerW]).padding(0.5);
    const allVals = cashFlowTrend.flatMap(d => [d.operating, d.investing, d.financing]);
    const ext = D3.extent(allVals);
    const y = D3.scaleLinear().domain([Math.min(ext[0], 0) * 1.15, Math.max(ext[1], 0) * 1.15]).nice().range([innerH, 0]);

    g.append('g').selectAll('line').data(y.ticks(5)).join('line')
        .attr('x1', 0).attr('x2', innerW)
        .attr('y1', d => y(d)).attr('y2', d => y(d))
        .attr('stroke', '#d3e2f5').attr('stroke-width', 1).attr('stroke-dasharray', '3,3');

    g.append('line').attr('x1', 0).attr('x2', innerW).attr('y1', y(0)).attr('y2', y(0))
        .attr('stroke', '#9ec1f5').attr('stroke-width', 1).attr('stroke-dasharray', '4,3');

    g.append('g').attr('transform', `translate(0,${innerH})`)
        .call(D3.axisBottom(x).tickValues(thinTicks(x.domain(), innerW)).tickSize(0).tickPadding(10))
        .call(g => g.select('.domain').remove())
        .selectAll('text').style('font-size', '5pt').style('fill', '#6f869b').style('font-weight', '500');

    g.append('g').call(D3.axisLeft(y).ticks(5).tickFormat(d => fmtCurrency(d)).tickSize(0).tickPadding(8))
        .call(g => g.select('.domain').remove())
        .selectAll('text').style('font-size', '5pt').style('fill', '#6f869b').style('font-weight', '500');

    const series = [
        { key: 'operating', color: '#005bf0', label: 'Operating' },
        { key: 'investing', color: '#1baf7a', label: 'Investing' },
        { key: 'financing', color: '#eda100', label: 'Financing' },
    ];

    const line = D3.line().x(d => x(d.month)).y(d => y(d.value)).curve(D3.curveMonotoneX);

    series.forEach(s => {
        const data = cashFlowTrend.map(d => ({ month: d.month, value: d[s.key] }));

        const areaFill = verticalGradient(svg, s.color, 0.16, 0.02);
        const area = D3.area().x(d => x(d.month)).y0(y(0)).y1(d => y(d.value)).curve(D3.curveMonotoneX);
        g.append('path').datum(data).attr('d', area).attr('fill', areaFill);

        const path = g.append('path').datum(data).attr('d', line)
            .attr('fill', 'none').attr('stroke', s.color).attr('stroke-width', 2.5)
            .attr('stroke-linecap', 'round').attr('stroke-linejoin', 'round');
        const len = path.node().getTotalLength();
        path.attr('stroke-dasharray', `${len} ${len}`).attr('stroke-dashoffset', len)
            .transition().duration(1000).ease(D3.easeCubicOut).attr('stroke-dashoffset', 0);

        g.selectAll(`.dot-${s.key}`).data(data).join('circle')
            .attr('class', `dot-${s.key}`)
            .attr('cx', d => x(d.month)).attr('cy', d => y(d.value))
            .attr('r', 0).attr('fill', s.color)
            .attr('stroke', '#fff').attr('stroke-width', 2)
            .transition().delay(500).duration(400).attr('r', 3.5);

        if (data.length) {
            const last = data[data.length - 1];
            g.append('text')
                .attr('x', x(last.month) + 8).attr('y', y(last.value) + 3.5)
                .text(fmtCurrency(last.value))
                .style('font-size', '5pt').style('fill', '#5a7186').style('font-weight', '700')
                .style('font-variant-numeric', 'tabular-nums')
                .style('opacity', 0)
                .transition().delay(800).duration(300).style('opacity', 1);
        }
    });

    const crosshair = g.append('line').attr('y1', 0).attr('y2', innerH)
        .attr('stroke', '#9ec1f5').attr('stroke-width', 1).style('opacity', 0);

    const hitArea = g.append('rect').attr('width', innerW).attr('height', innerH).attr('fill', 'none').attr('pointer-events', 'all');
    const months = cashFlowTrend.map(d => d.month);

    hitArea.on('pointerenter', () => crosshair.style('opacity', 1))
        .on('pointermove', function (evt) {
            const [mx] = D3.pointer(evt, this);
            let closest = months[0], minDist = Infinity;
            months.forEach(m => { const dist = Math.abs(x(m) - mx); if (dist < minDist) { minDist = dist; closest = m; } });
            crosshair.attr('x1', x(closest)).attr('x2', x(closest)).style('opacity', 1);
            const d = cashFlowTrend.find(d => d.month === closest);
            showTooltip(evt, `
                <div class="tt-label">${closest}</div>
                ${series.map(s => `<div class="tt-row"><span class="tt-swatch" style="background:${s.color};"></span> ${s.label} <span class="tt-val">${fmtFull(d[s.key])}</span></div>`).join('')}
            `);
        })
        .on('pointerleave', () => { crosshair.style('opacity', 0); hideTooltip(); });
})();

// ─── Shared: modern segmented horizontal bar ──────────────
function segmentedBar(g, segments, innerW, yTop, barH, opts) {
    const total = segments.reduce((s, d) => s + d.value, 0);
    if (total <= 0) return;
    const gap = 3, radius = barH / 2;
    const xScale = D3.scaleLinear().domain([0, total]).range([0, innerW]);
    const visible = segments.filter(d => d.value > 0);

    let offset = 0;
    visible.forEach((d, i) => {
        const wFull = xScale(d.value);
        const isFirst = i === 0, isLast = i === visible.length - 1;
        const segX = offset + (isFirst ? 0 : gap / 2);
        const segW = Math.max(wFull - (isFirst || isLast ? gap / 2 : gap), 1);

        const rl = isFirst ? radius : 3, rr = isLast ? radius : 3;
        const path = `M${segX + rl},${yTop} L${segX + segW - rr},${yTop} Q${segX + segW},${yTop} ${segX + segW},${yTop + rr}
            L${segX + segW},${yTop + barH - rr} Q${segX + segW},${yTop + barH} ${segX + segW - rr},${yTop + barH}
            L${segX + rl},${yTop + barH} Q${segX},${yTop + barH} ${segX},${yTop + barH - rl}
            L${segX},${yTop + rl} Q${segX},${yTop} ${segX + rl},${yTop} Z`;

        g.append('path').attr('d', path).attr('fill', d.color)
            .style('cursor', 'pointer')
            .attr('opacity', 0)
            .on('pointerenter', (evt) => showTooltip(evt, `<div class="tt-label">${d.label}</div><div class="tt-row"><span class="tt-swatch" style="background:${d.color};"></span> <span class="tt-val">${fmtFull(d.value)}</span></div><div style="color:#2674f2;font-size:7pt;">${(d.value / total * 100).toFixed(1)}%${opts.pctSuffix || ''}</div>`))
            .on('pointermove', (evt) => showTooltip(evt, tooltip.innerHTML))
            .on('pointerleave', hideTooltip)
            .transition().duration(500).delay(i * 90).attr('opacity', 1);

        if (segW > 62) {
            g.append('text').attr('x', segX + segW / 2).attr('y', yTop + barH / 2 + 4)
                .text(fmtCurrency(d.value))
                .style('font-size', '5pt').style('fill', '#fff').style('font-weight', '700')
                .style('text-anchor', 'middle').style('font-variant-numeric', 'tabular-nums')
                .style('opacity', 0)
                .transition().duration(400).delay(300 + i * 90).style('opacity', 1);
        }
        if (opts.labelsBelow && segW > 46) {
            g.append('text').attr('x', segX + segW / 2).attr('y', yTop + barH + 16)
                .text(d.label)
                .style('font-size', '4.5pt').style('fill', '#6f869b').style('text-anchor', 'middle').style('font-weight', '600');
        }
        offset += wFull;
    });
}

// ─── 3. Balance Sheet Composition ─────────────────────────
(function () {
    const container = document.getElementById('chart-bs');
    const totalAll = bsComposition.reduce((s, d) => s + d.value, 0);
    if (totalAll < 0.01) { container.innerHTML = '<div class="dash-empty">No balance sheet data.</div>'; return; }

    const colors = ['#005bf0', '#1baf7a', '#eda100', '#e34948', '#1a345b'];

    const margin = { top: 6, right: 16, bottom: 8, left: 16 };
    const width = container.clientWidth || 400;
    const height = 168;
    const innerW = width - margin.left - margin.right;
    const barH = 34;

    const svg = D3.select(container).append('svg')
        .attr('viewBox', `0 0 ${width} ${height}`)
        .attr('preserveAspectRatio', 'xMidYMid meet');

    const g = svg.append('g').attr('transform', `translate(${margin.left},${margin.top})`);

    const withColor = bsComposition.map((d, i) => ({ ...d, color: colors[i] }));

    const assets = withColor.filter(d => d.label.includes('Assets'));
    const assetsTotal = assets.reduce((s, d) => s + d.value, 0);
    g.append('text').attr('x', 0).attr('y', 13)
        .text('Assets')
        .style('font-size', '5.5pt').style('font-weight', '700').style('fill', '#5a7186');
    g.append('text').attr('x', innerW).attr('y', 13)
        .text(fmtCurrency(assetsTotal))
        .style('font-size', '5.5pt').style('font-weight', '700').style('fill', '#1a345b')
        .style('text-anchor', 'end').style('font-variant-numeric', 'tabular-nums');
    segmentedBar(g, assets, innerW, 20, barH, { pctSuffix: ' of assets' });

    const le = withColor.filter(d => !d.label.includes('Assets'));
    const leTotal = le.reduce((s, d) => s + d.value, 0);
    const y2 = 94;
    g.append('text').attr('x', 0).attr('y', y2 - 7)
        .text('Liabilities + Equity')
        .style('font-size', '5.5pt').style('font-weight', '700').style('fill', '#5a7186');
    g.append('text').attr('x', innerW).attr('y', y2 - 7)
        .text(fmtCurrency(leTotal))
        .style('font-size', '5.5pt').style('font-weight', '700').style('fill', '#1a345b')
        .style('text-anchor', 'end').style('font-variant-numeric', 'tabular-nums');
    segmentedBar(g, le, innerW, y2, barH, { pctSuffix: '' });
})();

// ─── 4. AR Aging ──────────────────────────────────────────
(function () {
    const container = document.getElementById('chart-ar');
    const buckets = [
        { key: 'current', label: 'Current', color: '#005bf0' },
        { key: 'days_31_60', label: '31-60 days', color: '#1baf7a' },
        { key: 'days_61_90', label: '61-90 days', color: '#eda100' },
        { key: 'days_91_plus', label: '91+ days', color: '#e34948' },
    ];
    const total = buckets.reduce((s, b) => s + arAging[b.key], 0);
    if (total < 0.01) { container.innerHTML = '<div class="dash-empty">No outstanding receivables.</div>'; return; }

    const margin = { top: 6, right: 16, bottom: 8, left: 16 };
    const width = container.clientWidth || 400;
    const height = 108;
    const innerW = width - margin.left - margin.right;
    const barH = 34;

    const svg = D3.select(container).append('svg')
        .attr('viewBox', `0 0 ${width} ${height}`)
        .attr('preserveAspectRatio', 'xMidYMid meet');

    const g = svg.append('g').attr('transform', `translate(${margin.left},${margin.top})`);

    g.append('text').attr('x', 0).attr('y', 13)
        .text('Total Outstanding')
        .style('font-size', '5.5pt').style('font-weight', '700').style('fill', '#5a7186');
    g.append('text').attr('x', innerW).attr('y', 13)
        .text(fmtCurrency(total))
        .style('font-size', '5.5pt').style('font-weight', '700').style('fill', '#1a345b')
        .style('text-anchor', 'end').style('font-variant-numeric', 'tabular-nums');

    const segments = buckets.map(b => ({ label: b.label, value: arAging[b.key], color: b.color }));
    segmentedBar(g, segments, innerW, 20, barH, { pctSuffix: ' of total', labelsBelow: true });
})();

}); // requestAnimationFrame
}); // load
</script>
@endpush
