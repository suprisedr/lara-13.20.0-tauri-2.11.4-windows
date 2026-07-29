@extends('layouts.public')

@section('title', 'Pay Components — ' . $company->registered_name)
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
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

        .check-label {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.75rem;
            cursor: pointer;
            color: #333;
        }

        .status-box {
            display: inline-block;
            font-weight: 700;
            text-transform: uppercase;
            border: 1px solid #4c1d95;
            color: #4c1d95;
            padding: 0.08rem 0.5rem;
            font-size: 0.6rem;
            letter-spacing: 0.08em;
        }

        .status-box.inactive { color: #dc2626; border-color: #dc2626; }

        @media (max-width: 640px) {
            .reg-doc-body { padding: 1.25rem 1rem; }
        }
    </style>
@endpush

@section('content')
    <div class="co-wrap">
        @include('companies._topbar')

        <div class="co-body">
            @include('companies._sidebar')

            <main class="co-main">

                <div class="reg-mgmt-bar">
                    <a href="{{ route('companies.payroll.employees.index', $company) }}">
                        Employee Register
                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
                    </a>
                </div>

                @if (session('success'))
                    <div style="background:#dcfce7;border:1px solid #bbf7d0;color:#15803d;padding:4pt 8pt;font-size:7pt;font-weight:600;margin-bottom:10pt;">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="reg-doc">
                    <div class="reg-doc-body">

                        <div class="reg-doc-title">Pay Components</div>
                        <div class="reg-doc-subtitle">
                            Define earnings, deductions, and employer contributions that appear on employee payslips.
                            Each component is assigned per-employee with a monthly rand amount on the employee's profile.
                        </div>

                        <hr class="reg-divider">

                        @if ($components->isEmpty())
                            <div class="reg-empty-state">
                                <p class="reg-empty-title">No components yet</p>
                                <p>Add your first component below.</p>
                            </div>
                        @else
                            @foreach ([
                                ['earning',              'Earnings',               'Additional taxable or non-taxable income paid to the employee.'],
                                ['deduction',            'Deductions',             'Amounts withheld from gross pay before the net wage is paid.'],
                                ['employer_contribution','Employer Contributions',  'Costs borne by the employer in addition to the employee\'s gross pay.'],
                            ] as [$type, $typeLabel, $typeDesc])
                                @php $group = $components->where('type', $type); @endphp
                                @if ($group->isNotEmpty())
                                    <div class="reg-section-header">{{ $typeLabel }}</div>
                                    <p style="font-size:6.5pt;color:#6b5b8a;margin:-2pt 0 4pt;">{{ $typeDesc }}</p>
                                    <table class="reg-table">
                                        <thead>
                                            <tr>
                                                <th style="width:35%;">Name</th>
                                                <th style="width:22%;">Category</th>
                                                <th style="width:12%;text-align:center;">PAYE</th>
                                                <th style="width:12%;text-align:center;">Pensionable</th>
                                                <th style="width:10%;text-align:center;">Status</th>
                                                <th style="width:9%;"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($group as $component)
                                                <tr>
                                                    <td style="font-weight:700;">{{ $component->name }}</td>
                                                    <td class="dim">{{ \App\Models\PayrollComponent::categories()[$component->category] ?? $component->category }}</td>
                                                    <td style="text-align:center;">
                                                        @if ($component->is_taxable)
                                                            <span style="color:#15803d;font-weight:700;font-size:6.5pt;">&#10003; Yes</span>
                                                        @else
                                                            <span style="color:#8b7aad;font-size:6.5pt;">No</span>
                                                        @endif
                                                    </td>
                                                    <td style="text-align:center;">
                                                        @if ($component->is_pensionable)
                                                            <span style="color:#15803d;font-weight:700;font-size:6.5pt;">&#10003; Yes</span>
                                                        @else
                                                            <span style="color:#8b7aad;font-size:6.5pt;">No</span>
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
                                    <div style="background:#fee2e2;border:1px solid #fca5a5;color:#b91c1c;padding:3pt 5pt;font-size:6.5pt;margin-bottom:5pt;">
                                        <strong>Please fix the following:</strong>
                                        <ul style="margin:2pt 0 0 8pt;padding:0;">
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
                                    <div style="display:flex;gap:1.5rem;margin-bottom:5pt;">
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
                                        <button type="submit" class="reg-btn primary">Add Component</button>
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
