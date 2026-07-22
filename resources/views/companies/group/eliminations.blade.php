@extends('layouts.public')

@section('title', $company->registered_name . ' — Intragroup Eliminations')
@section('meta-robots', 'noindex, nofollow')

@php
    use App\Models\GroupElimination;
    use App\Models\GroupEliminationLine;
    $buckets = GroupEliminationLine::BUCKETS;
@endphp

@push('styles')
    @include('companies._styles')
    <style>
        .co-topbar { padding: 1rem 2rem; }
        .co-main { padding: 1.25rem 1.5rem; }
        .grp-card { background:#fff; border:1px solid rgba(94,23,235,0.1); border-radius:0; padding:1.1rem 1.25rem; margin-bottom:1.25rem; }
        .grp-card h3 { font-size:0.95rem; font-weight:800; margin:0 0 0.2rem; color:#1b1b18; }
        .grp-card p.sub { font-size:0.78rem; color:#888; margin:0 0 1rem; }
        .el-input { box-sizing:border-box; border:1.5px solid #e5e7eb; border-radius:0; padding:0.4rem 0.55rem; font-size:0.8rem; font-family:inherit; width:100%; }
        .el-input:focus { border-color:#7c3aed; outline:none; }
        .el-btn { background:#5e17eb; color:#fff; border:none; border-radius:0; padding:0.45rem 0.9rem; font-size:0.78rem; font-weight:700; cursor:pointer; font-family:inherit; }
        .el-btn:hover { background:#4a10c4; }
        .el-btn-light { background:#fff; border:1.5px solid #e5e7eb; color:#374151; }
        .el-line-row { display:grid; grid-template-columns:1.6fr 1.6fr 1fr 1fr 32px; gap:0.5rem; margin-bottom:0.5rem; align-items:center; }
        .el-table { width:100%; border-collapse:collapse; }
        .el-table th { text-align:left; font-size:0.6rem; font-weight:800; letter-spacing:0.08em; text-transform:uppercase; color:#9ca3af; padding:0.4rem 0.6rem; border-bottom:1px solid #eee; }
        .el-table td { padding:0.5rem 0.6rem; font-size:0.8rem; border-bottom:1px solid #f3f4f6; vertical-align:top; }
        .el-table td.amt { text-align:right; font-family:monospace; white-space:nowrap; }
        .el-balance { font-size:0.78rem; font-weight:700; }
        .el-balance.ok { color:#166534; }
        .el-balance.off { color:#b91c1c; }
        .el-remove { background:none; border:none; color:#b91c1c; font-size:1rem; cursor:pointer; line-height:1; }
    </style>
@endpush

@section('content')
    <div class="co-wrap">
        @include('companies._topbar')

        <div class="co-body">
            @include('companies._sidebar')
            <main class="co-main">
                @if (session('success'))
                    <div style="background:#dcfce7;border:1px solid #86efac;color:#166534;padding:0.6rem 0.9rem;border-radius:0;font-size:0.82rem;margin-bottom:1rem;">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div style="background:#fee2e2;border:1px solid #fca5a5;color:#b91c1c;padding:0.6rem 0.9rem;border-radius:0;font-size:0.82rem;margin-bottom:1rem;">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="grp-card">
                    <h3>Record an elimination journal</h3>
                    <p class="sub">
                        Remove the effect of intragroup transactions and balances in full (IFRS 10):
                        intragroup receivables/payables and loans, intragroup sales and purchases,
                        unrealised profit in inventory or PPE, and intragroup dividends. Each journal must
                        balance (total debits = total credits).
                    </p>

                    <form method="POST" action="{{ route('companies.group.eliminations.store', $company) }}" id="elim-form">
                        @csrf
                        <div style="display:grid;grid-template-columns:1fr 1.4fr 2fr;gap:0.75rem;margin-bottom:1rem;">
                            <div>
                                <label style="display:block;font-size:0.66rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#374151;margin-bottom:0.25rem;">Date</label>
                                <input class="el-input" type="date" name="elimination_date" value="{{ now()->toDateString() }}" required>
                            </div>
                            <div>
                                <label style="display:block;font-size:0.66rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#374151;margin-bottom:0.25rem;">Type</label>
                                <select class="el-input" name="type" required>
                                    @foreach (GroupElimination::TYPES as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label style="display:block;font-size:0.66rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#374151;margin-bottom:0.25rem;">Description</label>
                                <input class="el-input" type="text" name="description" placeholder="e.g. Eliminate intragroup loan: Parent ↔ Sub" required>
                            </div>
                        </div>

                        <div style="display:grid;grid-template-columns:1.6fr 1.6fr 1fr 1fr 32px;gap:0.5rem;margin-bottom:0.4rem;">
                            <span style="font-size:0.6rem;font-weight:800;letter-spacing:0.08em;text-transform:uppercase;color:#9ca3af;">Consolidated line</span>
                            <span style="font-size:0.6rem;font-weight:800;letter-spacing:0.08em;text-transform:uppercase;color:#9ca3af;">Label (optional)</span>
                            <span style="font-size:0.6rem;font-weight:800;letter-spacing:0.08em;text-transform:uppercase;color:#9ca3af;text-align:right;">Debit</span>
                            <span style="font-size:0.6rem;font-weight:800;letter-spacing:0.08em;text-transform:uppercase;color:#9ca3af;text-align:right;">Credit</span>
                            <span></span>
                        </div>

                        <div id="elim-lines"></div>

                        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:0.6rem;">
                            <button type="button" class="el-btn el-btn-light" onclick="addElimLine()">+ Add line</button>
                            <span class="el-balance" id="elim-balance">Dr 0.00 = Cr 0.00</span>
                        </div>

                        <div style="margin-top:1rem;">
                            <button type="submit" class="el-btn" id="elim-submit">Save elimination</button>
                        </div>
                    </form>
                </div>

                <div class="grp-card">
                    <h3>Recorded eliminations</h3>
                    @if ($eliminations->isEmpty())
                        <p style="font-size:0.82rem;color:#888;margin:0;">No elimination journals recorded yet.</p>
                    @else
                        <table class="el-table">
                            <thead>
                                <tr>
                                    <th style="width:110px;">Date</th>
                                    <th>Description</th>
                                    <th>Lines</th>
                                    <th style="width:60px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($eliminations as $elim)
                                    <tr>
                                        <td>{{ $elim->elimination_date->format('d M Y') }}</td>
                                        <td>
                                            <strong>{{ $elim->description }}</strong>
                                            <div style="font-size:0.7rem;color:#9ca3af;">{{ $elim->type_label }}</div>
                                        </td>
                                        <td>
                                            @foreach ($elim->lines as $line)
                                                <div style="font-size:0.74rem;">
                                                    {{ $line->bucket_label }}@if($line->label) — {{ $line->label }}@endif:
                                                    @if($line->debit > 0)<span style="color:#1b1b18;">Dr {{ number_format($line->debit, 2) }}</span>@endif
                                                    @if($line->credit > 0)<span style="color:#1b1b18;">Cr {{ number_format($line->credit, 2) }}</span>@endif
                                                </div>
                                            @endforeach
                                        </td>
                                        <td>
                                            <form method="POST" action="{{ route('companies.group.eliminations.destroy', [$company, $elim]) }}" onsubmit="return false" data-confirm-label="Group Accounting" data-confirm-title="Delete Elimination Journal" data-confirm-body="This elimination journal will be permanently deleted." data-confirm-text="Delete" data-confirm-danger="1">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="el-remove" title="Delete">&times;</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </main>
        </div>
    </div>

    <template id="elim-line-template">
        <div class="el-line-row">
            <select class="el-input" name="lines[__IDX__][bucket]">
                @foreach ($buckets as $key => $meta)
                    <option value="{{ $key }}">{{ $meta['label'] }} ({{ $meta['statement'] === 'sfp' ? 'SFP' : 'P&L' }})</option>
                @endforeach
            </select>
            <input class="el-input" type="text" name="lines[__IDX__][label]" placeholder="optional">
            <input class="el-input el-debit" type="number" step="0.01" min="0" name="lines[__IDX__][debit]" placeholder="0.00" oninput="recalcElim()">
            <input class="el-input el-credit" type="number" step="0.01" min="0" name="lines[__IDX__][credit]" placeholder="0.00" oninput="recalcElim()">
            <button type="button" class="el-remove" onclick="this.closest('.el-line-row').remove(); recalcElim();">&times;</button>
        </div>
    </template>
@endsection

@push('scripts')
    <script>
        let elimIdx = 0;
        const elimContainer = document.getElementById('elim-lines');
        const elimTemplate = document.getElementById('elim-line-template').innerHTML;

        function addElimLine() {
            elimContainer.insertAdjacentHTML('beforeend', elimTemplate.replace(/__IDX__/g, elimIdx++));
            recalcElim();
        }

        function recalcElim() {
            let dr = 0, cr = 0;
            document.querySelectorAll('#elim-lines .el-debit').forEach(i => dr += parseFloat(i.value) || 0);
            document.querySelectorAll('#elim-lines .el-credit').forEach(i => cr += parseFloat(i.value) || 0);
            const balanced = Math.abs(dr - cr) < 0.01 && dr > 0;
            const el = document.getElementById('elim-balance');
            el.textContent = 'Dr ' + dr.toFixed(2) + ' = Cr ' + cr.toFixed(2);
            el.className = 'el-balance ' + (balanced ? 'ok' : 'off');
            document.getElementById('elim-submit').disabled = !balanced;
        }

        // Start with two lines (a typical Dr/Cr pair).
        addElimLine();
        addElimLine();
    </script>
@endpush
