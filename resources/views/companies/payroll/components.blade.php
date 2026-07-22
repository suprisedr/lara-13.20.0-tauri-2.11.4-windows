@extends('layouts.public')

@section('title', 'Pay Components — ' . $company->registered_name)
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .inv-mgmt-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }

        .inv-mgmt-bar a {
            font-size: 0.78rem;
            color: #6b7280;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            transition: color 0.15s;
        }

        .inv-mgmt-bar a:hover { color: #000; }

        .mgmt-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: #fff;
            border: 1px solid #000;
            color: #000;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 0.4rem 0.85rem;
            text-decoration: none;
            cursor: pointer;
            font-family: inherit;
            transition: background 0.15s, color 0.15s;
        }

        .mgmt-btn:hover { background: #000; color: #fff; }
        .mgmt-btn.primary { background: #000; color: #fff; }
        .mgmt-btn.primary:hover { background: #333; }

        .cust-doc {
            background: #fff;
            border: 1px solid #ddd;
            font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
            color: #000;
            font-size: 0.78rem;
            line-height: 1.45;
            margin-bottom: 1.5rem;
        }

        .cust-doc-body { padding: 2rem 2.25rem; }

        .doc-title {
            font-size: 1.3rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            margin-bottom: 0.25rem;
        }

        .divider {
            border: none;
            border-top: 2px solid #000;
            margin: 1rem 0 1.25rem;
        }

        .divider.light {
            border-top: 1px solid #ddd;
            margin: 1.25rem 0;
        }

        .section-header {
            font-weight: 700;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            border-bottom: 1.5px solid #000;
            padding-bottom: 0.25rem;
            margin: 1.25rem 0 0.75rem;
        }

        .section-header:first-of-type { margin-top: 0; }

        table.cust-items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0.5rem;
        }

        table.cust-items-table thead td {
            font-weight: 700;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: 1.5px solid #000;
            padding-bottom: 0.45rem;
        }

        table.cust-items-table tbody td {
            padding: 0.5rem 0;
            font-size: 0.78rem;
            border-bottom: 1px solid #ddd;
            vertical-align: middle;
        }

        table.cust-items-table tbody tr:last-child td { border-bottom: none; }
        table.cust-items-table tbody tr:hover td { background: #fafafa; }

        .status-box {
            display: inline-block;
            font-weight: 700;
            text-transform: uppercase;
            border: 1px solid #000;
            padding: 0.08rem 0.5rem;
            font-size: 0.6rem;
            letter-spacing: 0.08em;
        }

        .status-box.inactive { color: #aaa; border-color: #aaa; }

        .del-btn {
            background: none;
            border: none;
            font-size: 0.72rem;
            color: #dc2626;
            text-decoration: underline;
            cursor: pointer;
            font-family: inherit;
            padding: 0;
        }

        .del-btn:hover { color: #991b1c; }

        /* ── Add form ────────────────────────────────────────────── */
        .add-panel { margin-top: 1.25rem; }

        .add-panel-head {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            cursor: pointer;
            background: none;
            border: 1px solid #000;
            padding: 0.35rem 0.75rem;
            font-family: inherit;
            transition: background 0.15s, color 0.15s;
            color: #000;
        }

        .add-panel-head:hover { background: #000; color: #fff; }

        .add-panel-body {
            display: none;
            margin-top: 0.75rem;
            padding: 1rem 1.25rem;
            border: 1px solid #ddd;
            background: #fafafa;
        }

        .add-panel.open .add-panel-body { display: block; }

        .af-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem 0.75rem;
            align-items: flex-end;
            margin-bottom: 0.65rem;
        }

        .af-field { flex: 1; min-width: 150px; }
        .af-field.wide { flex: 2; min-width: 220px; }

        .af-field label {
            display: block;
            font-size: 0.58rem;
            font-weight: 700;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: #555;
            margin-bottom: 0.2rem;
        }

        .af-field input,
        .af-field select {
            width: 100%;
            border: 1px solid #ccc;
            padding: 0.32rem 0.5rem;
            font-size: 0.82rem;
            font-family: inherit;
            color: #000;
            box-sizing: border-box;
            background: #fff;
        }

        .af-field input:focus,
        .af-field select:focus { outline: none; border-color: #000; }

        .af-submit { display: flex; justify-content: flex-end; margin-top: 0.5rem; }

        .check-label {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.75rem;
            cursor: pointer;
            color: #333;
        }

        @media (max-width: 640px) {
            .cust-doc-body { padding: 1.25rem 1rem; }
            .af-field, .af-field.wide { flex: 1 1 100%; }
        }
    </style>
@endpush

@section('content')
    <div class="co-wrap">
        @include('companies._topbar')

        <div class="co-body">
            @include('companies._sidebar')

            <main class="co-main">

                <div class="inv-mgmt-bar">
<a href="{{ route('companies.payroll.employees.index', $company) }}">
                        Employee Register
                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
                    </a>
                </div>

                @if (session('success'))
                    <div style="background:#dcfce7;border:1px solid #bbf7d0;color:#15803d;padding:0.75rem 1.25rem;font-size:0.855rem;font-weight:600;margin-bottom:1.25rem;">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="cust-doc">
                    <div class="cust-doc-body">

                        <div class="doc-title">Pay Components</div>
                        <p style="font-size:0.78rem;color:#555;margin:0.2rem 0 0.75rem;">
                            Define earnings, deductions, and employer contributions that appear on employee payslips.
                            Each component is assigned per-employee with a monthly rand amount on the employee's profile.
                        </p>

                        <hr class="divider">

                        @if ($components->isEmpty())
                            <p style="color:#999;font-style:italic;font-size:0.78rem;margin-bottom:1rem;">No components yet. Add your first component below.</p>
                        @else
                            @foreach ([
                                ['earning',              'Earnings',               'Additional taxable or non-taxable income paid to the employee.'],
                                ['deduction',            'Deductions',             'Amounts withheld from gross pay before the net wage is paid.'],
                                ['employer_contribution','Employer Contributions',  'Costs borne by the employer in addition to the employee\'s gross pay.'],
                            ] as [$type, $typeLabel, $typeDesc])
                                @php $group = $components->where('type', $type); @endphp
                                @if ($group->isNotEmpty())
                                    <div class="section-header">{{ $typeLabel }}</div>
                                    <p style="font-size:0.73rem;color:#555;margin:-0.5rem 0 0.75rem;">{{ $typeDesc }}</p>
                                    <table class="cust-items-table">
                                        <thead>
                                            <tr>
                                                <td style="width:35%;">Name</td>
                                                <td style="width:22%;">Category</td>
                                                <td style="width:12%;text-align:center;">PAYE</td>
                                                <td style="width:12%;text-align:center;">Pensionable</td>
                                                <td style="width:10%;text-align:center;">Status</td>
                                                <td style="width:9%;"></td>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($group as $component)
                                                <tr>
                                                    <td style="font-weight:700;">{{ $component->name }}</td>
                                                    <td style="color:#555;">{{ \App\Models\PayrollComponent::categories()[$component->category] ?? $component->category }}</td>
                                                    <td style="text-align:center;">
                                                        @if ($component->is_taxable)
                                                            <span style="color:#15803d;font-weight:700;font-size:0.72rem;">&#10003; Yes</span>
                                                        @else
                                                            <span style="color:#aaa;font-size:0.72rem;">No</span>
                                                        @endif
                                                    </td>
                                                    <td style="text-align:center;">
                                                        @if ($component->is_pensionable)
                                                            <span style="color:#15803d;font-weight:700;font-size:0.72rem;">&#10003; Yes</span>
                                                        @else
                                                            <span style="color:#aaa;font-size:0.72rem;">No</span>
                                                        @endif
                                                    </td>
                                                    <td style="text-align:center;">
                                                        <span class="status-box {{ $component->is_active ? '' : 'inactive' }}">
                                                            {{ $component->is_active ? 'Active' : 'Inactive' }}
                                                        </span>
                                                    </td>
                                                    <td style="text-align:right;">
                                                        <form method="POST"
                                                            action="{{ route('companies.payroll.components.destroy', [$company, $component]) }}"
                                                            style="display:inline;"
                                                            onsubmit="return false" data-confirm-label="Payroll" data-confirm-title="Delete Component" data-confirm-body="Delete {{ addslashes($component->name) }}?" data-confirm-text="Delete" data-confirm-danger="1">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="del-btn">Remove</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @endif
                            @endforeach
                        @endif

                        {{-- Add component --}}
                        <div class="add-panel" id="panel-add-component">
                            <button type="button" class="add-panel-head"
                                onclick="this.closest('.add-panel').classList.toggle('open')">
                                + Add Component
                            </button>
                            <div class="add-panel-body">

                                @if ($errors->any())
                                    <div style="background:#fee2e2;border:1px solid #fca5a5;color:#b91c1c;padding:0.6rem 0.85rem;font-size:0.72rem;margin-bottom:0.85rem;">
                                        <strong>Please fix the following:</strong>
                                        <ul style="margin:0.3rem 0 0 1rem;padding:0;">
                                            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                                        </ul>
                                    </div>
                                @endif

                                <form method="POST" action="{{ route('companies.payroll.components.store', $company) }}">
                                    @csrf
                                    <div class="af-row">
                                        <div class="af-field wide">
                                            <label>Component Name *</label>
                                            <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. Travel Allowance" required maxlength="100">
                                        </div>
                                        <div class="af-field">
                                            <label>Type *</label>
                                            <select name="type" required>
                                                @foreach (\App\Models\PayrollComponent::types() as $val => $label)
                                                    <option value="{{ $val }}" @selected(old('type') === $val)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="af-field">
                                            <label>Category *</label>
                                            <select name="category" required>
                                                @foreach (\App\Models\PayrollComponent::categories() as $val => $label)
                                                    <option value="{{ $val }}" @selected(old('category') === $val)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div style="display:flex;gap:1.5rem;margin-bottom:0.65rem;">
                                        <label class="check-label">
                                            <input type="checkbox" name="is_taxable" value="1" @checked((bool) old('is_taxable', false))>
                                            Subject to PAYE
                                        </label>
                                        <label class="check-label">
                                            <input type="checkbox" name="is_pensionable" value="1" @checked((bool) old('is_pensionable', false))>
                                            Pensionable
                                        </label>
                                    </div>
                                    <div class="af-submit">
                                        <button type="submit" class="mgmt-btn primary">Add Component</button>
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
            document.getElementById('panel-add-component').classList.add('open');
        @endif
    </script>
@endsection
