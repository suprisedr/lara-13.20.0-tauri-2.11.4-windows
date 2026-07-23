@extends('layouts.public')

@section('title', $company->registered_name . ' — New Customer')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .inv-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5pt 7pt;
        }

        @media (max-width: 640px) {
            .inv-form-grid { grid-template-columns: 1fr; }
        }

        .inv-field label {
            display: block;
            font-size: 5pt;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #8b7aad;
            margin-bottom: 1.5pt;
        }

        .inv-field input,
        .inv-field select,
        .inv-field textarea {
            width: 100%;
            border: 1px solid #c4b5fd;
            border-radius: 0;
            padding: 3pt 4pt;
            font-size: 7pt;
            color: #4c1d95;
            background: #fff;
            outline: none;
            transition: border-color 0.15s;
            box-sizing: border-box;
            font-family: Helvetica, Arial, "DejaVu Sans", sans-serif;
        }

        .inv-field textarea {
            min-height: 50pt;
            resize: vertical;
        }

        .inv-field input::placeholder,
        .inv-field textarea::placeholder { color: #c4b5fd; font-size: 6.5pt; }

        .inv-field input:focus,
        .inv-field select:focus,
        .inv-field textarea:focus {
            border-color: #7c3aed;
        }

        .inv-field .field-hint {
            font-size: 5.5pt;
            color: #8b7aad;
            margin-top: 1pt;
        }

        .inv-status-field {
            display: flex;
            align-items: center;
            gap: 5pt;
            padding: 3pt 5pt;
            border: 1px solid #c4b5fd;
            border-radius: 0;
            background: #f5f3ff;
        }

        .inv-status-field span {
            font-size: 7pt;
            font-weight: 600;
            color: #4c1d95;
        }

        .inv-field span.optional {
            font-weight: 400;
            text-transform: none;
            letter-spacing: 0;
            color: #8b7aad;
            font-size: 4.5pt;
        }

        .form-actions {
            display: flex;
            gap: 4pt;
            justify-content: flex-end;
            margin-top: 7pt;
            padding-top: 6pt;
            border-top: 0.4pt solid #ddd6fe;
        }

        .btn-cancel {
            padding: 2.5pt 6pt;
            border: 1px solid #c4b5fd;
            border-radius: 0;
            font-size: 6pt;
            color: #6b5b8a;
            text-decoration: none;
            font-weight: 600;
            font-family: Helvetica, Arial, "DejaVu Sans", sans-serif;
            background: #fff;
            cursor: pointer;
            transition: background 0.15s;
        }

        .btn-cancel:hover { background: #f5f3ff; }

        .btn-primary {
            padding: 2.5pt 7pt;
            background: #7c3aed;
            color: #fff;
            border: none;
            border-radius: 0;
            font-size: 6pt;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.15s;
            font-family: Helvetica, Arial, "DejaVu Sans", sans-serif;
        }

        .btn-primary:hover { background: #005f9e; }
    </style>
@endpush

@section('content')
    <div class="co-wrap">

        @include('companies._topbar')

        <div class="co-body">
            @include('companies._sidebar')

            <main class="co-main">
                <form method="POST" action="{{ route('companies.customers.store', $company) }}">
                    @csrf

                    <div class="co-card">
                        <div class="co-card-head">
                            <div>
                                <div class="co-section-heading"><p class="co-section-label">Customers</p><h2>Add New Customer</h2></div>
                            </div>
</div>

                        <div style="padding:8pt 12pt 10pt;">
                            @if ($errors->any())
                                <div style="background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;padding:4pt 7pt;font-size:6.5pt;margin-bottom:7pt;font-family:Helvetica,Arial,'DejaVu Sans',sans-serif;">
                                    <strong>Please fix the following:</strong>
                                    <ul style="margin:2pt 0 0 8pt;padding:0;">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="inv-form-grid">
                                <div class="inv-field">
                                    <label for="name">Customer Name</label>
                                    <input type="text" id="name" name="name" value="{{ old('name') }}"
                                        required>
                                </div>
                                <div class="inv-field">
                                    <label for="contact_name">Contact Person <span class="optional">(optional)</span></label>
                                    <input type="text" id="contact_name" name="contact_name"
                                        value="{{ old('contact_name') }}">
                                </div>
                                <div class="inv-field">
                                    <label for="email">Customer Email <span class="optional">(optional)</span></label>
                                    <input type="email" id="email" name="email" value="{{ old('email') }}">
                                </div>
                                <div class="inv-field">
                                    <label for="phone">Phone <span class="optional">(optional)</span></label>
                                    <input type="tel" id="phone" name="phone" value="{{ old('phone') }}">
                                </div>
                                <div class="inv-field" style="grid-column:span 2;">
                                    <label for="address">Customer Address <span class="optional">(optional)</span></label>
                                    <textarea id="address" name="address" rows="2">{{ old('address') }}</textarea>
                                </div>
                                <div class="inv-field" style="grid-column:span 2;">
                                    <label class="inv-status-field">
                                        <input type="hidden" name="is_active" value="0">
                                        <input type="checkbox" id="is_active" name="is_active" value="1"
                                            {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                                        <span>Active customer</span>
                                    </label>
                                </div>
                            </div>

                            <div class="form-actions">
                                <a href="{{ route('companies.customers.index', $company) }}" class="btn-cancel">Cancel</a>
                                <button type="submit" class="btn-primary">Create Customer</button>
                            </div>
                        </div>
                    </div>
                </form>
            </main>
        </div>
    </div>
@endsection
