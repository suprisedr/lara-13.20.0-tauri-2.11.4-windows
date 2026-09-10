@extends('layouts.public')

@section('title', $company->registered_name . ' — Notes to AFS')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .notes-layout {
            display: flex;
            gap: 1.25rem;
            align-items: flex-start;
            font-family:'Century Gothic','URW Gothic','Avant Garde',Futura,'Avenir Next',Avenir,'Trebuchet MS',Helvetica,Arial,'DejaVu Sans',sans-serif;
        }

        .notes-aside {
            width: 260px;
            flex-shrink: 0;
            background: #fff;
            border: 1px solid #d3e2f5;
            border-radius: 0;
            padding: 0.5rem 0;
            position: sticky;
            top: 1rem;
            max-height: calc(100vh - 2rem);
            overflow-y: auto;
        }

        .notes-aside-label {
            font-size: 10pt;
            font-weight: 700;
            letter-spacing: 0;
            text-transform: none;
            color: #5a7186;
            padding: 0.55rem 1rem 0.35rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            user-select: none;
        }

        .notes-aside-label:hover { color: #000; }

        .notes-aside-chevron {
            font-size: 7pt;
            color: #6f869b;
            transition: transform 0.2s;
        }

        .notes-aside.collapsed {
            width: 36px;
            overflow: hidden;
            padding: 0;
        }

        .notes-aside.collapsed .notes-aside-label {
            writing-mode: vertical-rl;
            text-orientation: mixed;
            transform: rotate(180deg);
            padding: 0.75rem 0.6rem;
            justify-content: flex-start;
            letter-spacing: 0.12em;
        }

        .notes-aside.collapsed .notes-aside-chevron {
            display: none;
        }

        .notes-aside.collapsed .notes-aside-row { display: none; }

        .notes-aside-item {
            display: flex;
            align-items: baseline;
            gap: 0.55rem;
            padding: 0.5rem 1rem;
            font-size: 10pt;
            color: #5a7186;
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: background 0.12s, color 0.12s;
        }

        .notes-aside-item:hover {
            background: #f7fbfd;
            color: #000;
        }

        .notes-aside-item.active {
            background: #f4fafc;
            color: #000;
            font-weight: 700;
            border-left-color: #000;
        }

        .notes-aside-num {
            font-family: 'Courier New', monospace;
            font-size: 7pt;
            color: #888;
            min-width: 1.5rem;
        }

        .notes-aside-item.active .notes-aside-num { color: #000; }

        .notes-aside-kind-inventory::after,
        .notes-aside-kind-ppe::after,
        .notes-aside-kind-intangible::after {
            margin-left: auto;
            font-size: 7pt;
            font-weight: 800;
            letter-spacing: 0.08em;
            background: transparent;
            color: #000;
            border: 1px solid #000;
            padding: 0.08rem 0.4rem;
            border-radius: 0;
        }
        .notes-aside-kind-ppe::after { content: 'PPE'; }
        .notes-aside-kind-inventory::after { content: 'INV'; }
        .notes-aside-kind-intangible::after { content: 'IAS 38'; }

        .notes-aside-row {
            display: flex;
            align-items: center;
        }

        .notes-aside-row .notes-aside-item {
            flex: 1;
            min-width: 0;
            border-radius: 0;
        }

        .notes-include-toggle {
            flex-shrink: 0;
            font-size: 7pt;
            font-weight: 800;
            letter-spacing: 0;
            text-transform: none;
            padding: 0.15rem 0.45rem;
            border-radius: 0;
            border: 1px solid #d3e2f5;
            cursor: pointer;
            font-family: inherit;
            margin-right: 0.5rem;
            line-height: 1.6;
            background: #fff;
            color: #6f869b;
            transition: background 0.15s, color 0.15s, border-color 0.15s;
        }

        .notes-include-toggle.is-on {
            background: #000;
            color: #fff;
            border-color: #000;
        }

        .notes-pane {
            flex: 1;
            min-width: 0;
        }

        .notes-card {
            background: #fff;
            border: 1px solid #d3e2f5;
            border-left: 3px solid #000;
            border-radius: 0;
            padding: 1rem 1.25rem;
            margin-bottom: 0.75rem;
        }

        .notes-title-input {
            width: 100%;
            font-size: 13pt;
            font-weight: 800;
            color: #000;
            border: none;
            border-bottom: 2px solid transparent;
            padding: 0.2rem 0;
            font-family:'Century Gothic','URW Gothic','Avant Garde',Futura,'Avenir Next',Avenir,'Trebuchet MS',Helvetica,Arial,'DejaVu Sans',sans-serif;
            outline: none;
            background: transparent;
            letter-spacing: -0.01em;
            box-sizing: border-box;
        }

        .notes-title-input:focus { border-bottom-color: #000; }

        @media (max-width: 480px) {
            .notes-title-input { font-size: 11.5pt; }
        }

        .notes-body-textarea {
            width: 100%;
            min-height: 3.5rem;
            font-size: 10.5pt;
            font-family:'Century Gothic','URW Gothic','Avant Garde',Futura,'Avenir Next',Avenir,'Trebuchet MS',Helvetica,Arial,'DejaVu Sans',sans-serif;
            border: 1px solid #d3e2f5;
            border-radius: 0;
            padding: 0.65rem 0.85rem;
            color: #000;
            resize: vertical;
            outline: none;
            line-height: 1.55;
            transition: border-color 0.15s;
            overflow: hidden;
            box-sizing: border-box;
            field-sizing: content;
        }

        .notes-body-textarea:focus { border-color: #000; }

        /* Intangibles-standard button */
        .mgmt-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: #fff;
            border: 1px solid #000;
            color: #000;
            font-size: 8pt;
            font-weight: 700;
            text-transform: none;
            letter-spacing: 0;
            padding: 0.4rem 0.95rem;
            text-decoration: none;
            cursor: pointer;
            font-family: inherit;
            transition: background 0.15s, color 0.15s;
            height: 2rem;
            box-sizing: border-box;
            border-radius: 0;
            white-space: nowrap;
        }
        .mgmt-btn:hover { background: #000; color: #fff; }
        .mgmt-btn.primary { background: #000; color: #fff; }
        .mgmt-btn.primary:hover { background: #1a345b; }
        .mgmt-btn.sm { font-size: 8pt; padding: 0.3rem 0.7rem; height: 1.65rem; }
        .mgmt-btn.danger { border-color: #dc2626; color: #dc2626; background: #fff; }
        .mgmt-btn.danger:hover { background: #dc2626; color: #fff; }

        .btn-danger-link {
            background: none; border: none; color: #dc2626; cursor: pointer;
            font-size: 7pt; font-weight: 700; font-family: inherit;
            text-transform: none; letter-spacing: 0;
            text-decoration: none; border-bottom: 1px solid #dc2626;
            padding: 0;
        }
        .btn-danger-link:hover { color: #991b1b; border-bottom-color: #991b1b; }

        .field-label {
            display: block;
            font-size: 10pt;
            font-weight: 700;
            color: #5a7186;
            text-transform: none;
            letter-spacing: 0;
            margin-bottom: 0.2rem;
        }

        .field-input, .field-select {
            border: 1px solid #d3e2f5;
            border-radius: 0;
            padding: 0.35rem 0.6rem;
            font-size: 10.5pt;
            font-family: inherit;
            color: #000;
            background: #fff;
        }
        .field-input:focus, .field-select:focus {
            outline: none;
            border-color: #000;
        }

        /* Intangibles-standard form grid */
        .af-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.5rem 0.75rem; align-items: end; margin-bottom: 0.65rem; }
        .af-field label { display: block; font-size: 10pt; font-weight: 700; letter-spacing: 0; text-transform: none; color: #5a7186; margin-bottom: 0.2rem; }
        .af-field input, .af-field select { width: 100%; border: 1px solid #d3e2f5; padding: 0.32rem 0.5rem; font-size: 10.5pt; font-family: inherit; color: #000; box-sizing: border-box; background: #fff; border-radius: 0; }
        .af-field input:focus, .af-field select:focus { outline: none; border-color: #000; }
        .af-field.wide { grid-column: span 2; }

        /* Responsive: stack sidebar below on narrow screens */
        @media (max-width: 768px) {
            .notes-layout { flex-direction: column; }
            .notes-aside { width: 100%; position: static; max-height: none; }
            .notes-aside.collapsed { width: 100%; }
            .notes-aside.collapsed .notes-aside-label {
                writing-mode: horizontal-tb;
                text-orientation: initial;
                transform: none;
                padding: 0.55rem 1rem 0.35rem;
            }
            .af-row { grid-template-columns: 1fr 1fr; }
            .af-field.wide { grid-column: span 2; }
        }

        @media (max-width: 480px) {
            .af-row { grid-template-columns: 1fr; }
            .af-field.wide { grid-column: span 1; }
        }

        /* PPE-specific styles */
        .ppe-section { margin-top: 0.5rem; }

        .ppe-section-title {
            font-size: 10.5pt;
            font-weight: 800;
            color: #000;
            margin: 0 0 0.6rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-transform: none;
            letter-spacing: 0;
        }

        .ppe-section-help {
            font-size: 8pt;
            color: #5a7186;
            margin: 0 0 0.85rem;
            line-height: 1.45;
        }

        .ppe-classes-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5pt;
        }

        .ppe-classes-table th {
            text-align: left;
            font-size: 10.5pt;
            font-weight: 700;
            color: #5a7186;
            text-transform: none;
            letter-spacing: 0;
            padding: 0.4rem 0.6rem;
            background: #f7fbfd;
            border-bottom: 1.5px solid #000;
        }

        .ppe-classes-table td {
            padding: 0.5rem 0.6rem;
            vertical-align: top;
            border-bottom: 1px solid #d3e2f5;
        }

        .links-list {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .links-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 10pt;
            background: #f7fbfd;
            border: 1px solid #d3e2f5;
            border-radius: 0;
            padding: 0.3rem 0.55rem;
        }

        .role-badge {
            font-size: 7pt;
            font-weight: 800;
            text-transform: capitalize;
            letter-spacing: 0;
            padding: 0.1rem 0.45rem;
            border-radius: 0;
            white-space: nowrap;
            border: 1px solid;
            background: transparent;
        }

        .role-badge.add  { color: #047857; border-color: #047857; }
        .role-badge.less { color: #b91c1c; border-color: #b91c1c; }

        .movement-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5pt;
        }

        .movement-table th, .movement-table td {
            padding: 0.45rem 0.6rem;
            border-bottom: 1px solid #d3e2f5;
        }

        .movement-table thead th {
            background: #f7fbfd;
            font-size: 10.5pt;
            font-weight: 700;
            color: #5a7186;
            text-transform: none;
            letter-spacing: 0;
            border-bottom: 1.5px solid #000;
        }

        .movement-table th.num, .movement-table td.num {
            text-align: right;
            font-family: 'Courier New', monospace;
            white-space: nowrap;
        }

        .movement-table tr.subtotal td {
            font-weight: 700;
            background: #f7fbfd;
            border-top: 1.5px solid #000;
        }

        .movement-table tr.carrying td {
            font-weight: 800;
            background: #f4fafc;
            color: #000;
        }

        .flash {
            padding: 0.5rem 0.85rem;
            border-radius: 0;
            font-size: 10.5pt;
            margin-bottom: 1rem;
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }
    </style>
@endpush

@section('content')
    <div class="co-wrap">
        @include('companies._topbar', ['topbarMeta' => 'Notes to the Annual Financial Statements'])

        <div class="co-body">
            @include('companies._sidebar')

            <main class="co-main">

                @if (session('success'))
                    <div class="flash">{{ session('success') }}</div>
                @endif

                <div class="notes-layout">

                    {{-- Drop-aside: list of all notes --}}
                    <aside class="notes-aside" id="notes-aside">
                        <div class="notes-aside-label" onclick="toggleNotesSidebar()">
                            <span>Notes</span>
                            <span class="notes-aside-chevron" id="notes-chevron">&#x25C2;</span>
                        </div>
                        @foreach ($notes as $n)
                            <div class="notes-aside-row">
                                <a href="{{ route('companies.notes-to-afs.show', [$company, $n]) }}"
                                    class="notes-aside-item {{ $n->id === $note->id ? 'active' : '' }} {{ $n->kind === 'ppe' ? 'notes-aside-kind-ppe' : ($n->kind === 'intangible' ? 'notes-aside-kind-intangible' : ($n->kind === 'inventory' ? 'notes-aside-kind-inventory' : '')) }}">
                                    <span class="notes-aside-num">{{ $n->note_number }}.</span>
                                    <span>{{ $n->title }}</span>
                                </a>
                                <form method="POST"
                                      action="{{ route('companies.notes-to-afs.include.toggle', [$company, $n]) }}"
                                      style="display:contents;">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="notes-include-toggle {{ $n->include_in_afs ? 'is-on' : '' }}"
                                            title="{{ $n->include_in_afs ? 'Included in AFS — click to exclude' : 'Excluded from AFS — click to include' }}">
                                        {{ $n->include_in_afs ? 'ON' : 'OFF' }}
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </aside>

                    {{-- Main pane: editor for selected note --}}
                    <section class="notes-pane">

                        {{-- Editable title + body --}}
                        <form method="POST" action="{{ route('companies.notes-to-afs.update', [$company, $note]) }}"
                            class="notes-card">
                            @csrf
                            @method('PATCH')

                            <div style="display:flex;align-items:baseline;gap:0.6rem;margin-bottom:0.75rem;">
                                <span style="font-family:'Courier New',monospace;color:#888;font-size:1rem;font-weight:700;">{{ $note->note_number }}.</span>
                                <input type="text" name="title" value="{{ $note->title }}" required maxlength="200"
                                    class="notes-title-input" />
                            </div>

                            <textarea name="body" class="notes-body-textarea"
                                placeholder="Enter the note text here…">{{ $note->body }}</textarea>

                            <div style="margin-top:0.75rem;display:flex;gap:0.5rem;align-items:center;">
                                <button type="submit" class="mgmt-btn primary sm">Save Note</button>
                                <span style="font-size:0.72rem;color:#888;letter-spacing:0.02em;">Edit the title and narrative text. Changes apply immediately.</span>
                            </div>
                        </form>

                        {{-- ── Figures table (accounts linked to this note) ── --}}
                        @unless ($note->isPpe() || $note->isIntangible() || $note->isInventory())
                        @include('companies._account-search')
                        @php
                            $curYearLabel = \Carbon\Carbon::parse($endDate)->format('Y');
                            $priorYearLabel = \Carbon\Carbon::parse($endDate)->subYear()->format('Y');
                            $fig = fn($v) => $v != 0 ? number_format($v, 2) : '—';
                        @endphp
                        <div class="notes-card">
                            <div class="ppe-section-title">Figures</div>
                            <p class="ppe-section-help">
                                Link chart-of-accounts to this note to show a figures table. Choose whether each
                                account is <strong>added</strong> or <strong>subtracted</strong>. Income/expense
                                accounts use their movement for the year; balance-sheet accounts use their balance
                                at year-end. Prior-year comparatives are shown automatically.
                            </p>

                            @if ($noteFigures['has'])
                                <table class="movement-table" style="margin-bottom:0.85rem;">
                                    <thead>
                                        <tr>
                                            <th>Description</th>
                                            <th class="num">{{ $curYearLabel }} (R)</th>
                                            <th class="num">{{ $priorYearLabel }} (R)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($noteFigures['rows'] as $row)
                                            <tr>
                                                <td>{{ $row['label'] }}</td>
                                                <td class="num">{{ $fig($row['current']) }}</td>
                                                <td class="num">{{ $fig($row['prior']) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="subtotal">
                                            <td>Total</td>
                                            <td class="num">{{ number_format($noteFigures['total_current'], 2) }}</td>
                                            <td class="num">{{ number_format($noteFigures['total_prior'], 2) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            @endif

                            @if ($noteLinks->isNotEmpty())
                                <ul class="links-list" style="margin-bottom:0.6rem;">
                                    @foreach ($noteLinks as $link)
                                        <li class="links-item">
                                            <span class="role-badge {{ $link->sign < 0 ? 'less' : 'add' }}">
                                                {{ $link->sign < 0 ? '− LESS' : '+ ADD' }}
                                            </span>
                                            @if (($link->balance_point ?? 'closing') === 'opening')
                                                <span style="font-size:0.7rem;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;padding:0.1rem 0.45rem;flex-shrink:0;">Opening</span>
                                            @else
                                                <span style="font-size:0.7rem;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;padding:0.1rem 0.45rem;flex-shrink:0;">Closing</span>
                                            @endif
                                            <span style="flex:1;min-width:0;">
                                                {{ $link->account?->account_code }} ·
                                                {{ $link->label ?: $link->account?->account_name }}
                                            </span>
                                            <form method="POST"
                                                action="{{ route('companies.notes-to-afs.lines.destroy', [$company, $note, $link]) }}"
                                                onsubmit="return false" data-confirm-label="Notes to AFS" data-confirm-title="Remove Account" data-confirm-body="This account will be removed from the note." data-confirm-text="Remove">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-danger-link">Remove</button>
                                            </form>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            <form method="POST" action="{{ route('companies.notes-to-afs.lines.store', [$company, $note]) }}">
                                @csrf
                                <div class="af-row">
                                    <div class="af-field wide acct-search">
                                        <label>Account</label>
                                        <input type="text" class="acct-search-input" placeholder="Search account…" autocomplete="off">
                                        <input type="hidden" name="chart_of_account_id" class="acct-search-value" required>
                                        <div class="acct-search-dropdown" style="display:none;">
                                            @foreach ($availableAccounts as $account)
                                                <div class="acct-search-option" data-id="{{ $account->id }}"
                                                    data-label="{{ $account->account_code }} · {{ $account->account_name }}">
                                                    {{ $account->account_code }} · {{ $account->account_name }}
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="af-field">
                                        <label>Balance Point</label>
                                        <select name="balance_point">
                                            <option value="closing">Closing (year end)</option>
                                            <option value="opening">Opening (year start)</option>
                                        </select>
                                    </div>
                                    <div class="af-field">
                                        <label>Effect</label>
                                        <select name="sign">
                                            <option value="1">Add (+)</option>
                                            <option value="-1">Less (−)</option>
                                        </select>
                                    </div>
                                    <div class="af-field" style="display:flex;align-items:flex-end;">
                                        <button type="submit" class="mgmt-btn primary sm" style="width:100%;">Add</button>
                                    </div>
                                </div>
                                <div class="af-row" style="margin-top:-0.35rem;">
                                    <div class="af-field wide">
                                        <label>Label <span style="font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span></label>
                                        <input type="text" name="label" maxlength="255" placeholder="Defaults to account name">
                                    </div>
                                </div>
                            </form>
                        </div>
                        @endunless

                        @if ($note->isPpe())
                            @include('companies.notes-to-afs._ppe-editor', [
                                'company'           => $company,
                                'ppeClasses'        => $ppeClasses,
                                'movements'         => $movements,
                                'availableAccounts' => $availableAccounts,
                                'startDate'         => $startDate,
                                'endDate'           => $endDate,
                            ])
                        @elseif ($note->isIntangible())
                            @include('companies.notes-to-afs._intangible-editor', [
                                'company'              => $company,
                                'note'                 => $note,
                                'intangibleClasses'    => $intangibleClasses,
                                'intangibleMovements'  => $intangibleMovements,
                                'availableAccounts'    => $availableAccounts,
                                'startDate'            => $startDate,
                                'endDate'              => $endDate,
                            ])
                        @elseif ($note->isInventory())
                            @include('companies.notes-to-afs._inventory-editor', [
                                'company'             => $company,
                                'note'                => $note,
                                'inventoryMovements'  => $inventoryMovements,
                                'startDate'           => $startDate,
                                'endDate'             => $endDate,
                            ])
                        @endif
                    </section>
                </div>
            </main>
        </div>
    </div>
@push('scripts')
<script>
    (function () {
        const aside = document.getElementById('notes-aside');
        const chevron = document.getElementById('notes-chevron');
        const KEY = 'notes-aside-collapsed';

        function apply(collapsed) {
            aside.classList.toggle('collapsed', collapsed);
            chevron.style.transform = collapsed ? 'rotate(90deg)' : '';
        }

        apply(localStorage.getItem(KEY) === '1');

        window.toggleNotesSidebar = function () {
            const next = !aside.classList.contains('collapsed');
            localStorage.setItem(KEY, next ? '1' : '0');
            apply(next);
        };

        // Auto-resize textareas (fallback for browsers without field-sizing: content)
        document.querySelectorAll('.notes-body-textarea').forEach(function (ta) {
            function resize() {
                ta.style.height = 'auto';
                ta.style.height = ta.scrollHeight + 'px';
            }
            ta.addEventListener('input', resize);
            resize();
        });
    })();
</script>
@endpush
@endsection
