@extends('layouts.public')

@section('title', $company->registered_name . ' — Edit Supplier')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .inv-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.6rem 0.85rem;
        }

        @media (max-width: 640px) {
            .inv-form-grid { grid-template-columns: 1fr; }
        }

        .inv-field label {
            display: block;
            font-size: 0.58rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #5e17eb;
            margin-bottom: 0.15rem;
        }

        .inv-field input,
        .inv-field select,
        .inv-field textarea {
            width: 100%;
            border: 1px solid rgba(94, 23, 235, 0.2);
            border-radius: 0;
            padding: 0.3rem 0.5rem;
            font-size: 0.74rem;
            color: #1b1b18;
            background: #fff;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
            box-sizing: border-box;
            font-family: inherit;
        }

        .inv-field textarea {
            min-height: 68px;
            resize: vertical;
        }

        .inv-field input::placeholder,
        .inv-field textarea::placeholder { color: #c4b5fd; font-size: 0.7rem; }

        .inv-field input:focus,
        .inv-field select:focus,
        .inv-field textarea:focus {
            border-color: #5e17eb;
            box-shadow: 0 0 0 2px rgba(94, 23, 235, 0.08);
        }

        .inv-field .field-hint {
            font-size: 0.6rem;
            color: #9ca3af;
            margin-top: 0.12rem;
        }

        .inv-status-field {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.35rem 0.55rem;
            border: 1px solid rgba(94, 23, 235, 0.12);
            border-radius: 0;
            background: #faf5ff;
        }

        .inv-status-field span {
            font-size: 0.74rem;
            font-weight: 600;
            color: #1b1b18;
        }

        .inv-field span.optional {
            font-weight: 400;
            text-transform: none;
            letter-spacing: 0;
            color: #9ca3af;
            font-size: 0.55rem;
        }

        .form-actions {
            display: flex;
            gap: 0.45rem;
            justify-content: flex-end;
            margin-top: 0.85rem;
            padding-top: 0.7rem;
            border-top: 1px solid #f3f0ff;
        }

        .btn-cancel {
            padding: 0.28rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0;
            font-size: 0.7rem;
            color: #555;
            text-decoration: none;
            font-weight: 600;
            font-family: inherit;
            background: #fff;
            cursor: pointer;
            transition: background 0.15s;
        }

        .btn-cancel:hover { background: #f9fafb; }

        .btn-primary {
            padding: 0.28rem 0.85rem;
            background: #5e17eb;
            color: #fff;
            border: none;
            border-radius: 0;
            font-size: 0.7rem;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.15s;
            font-family: inherit;
        }

        .btn-primary:hover { background: #4a10c4; }
    </style>
@endpush

@section('content')
    <div class="co-wrap">

        @include('companies._topbar')

        <div class="co-body">
            @include('companies._sidebar')

            <main class="co-main">
                <form method="POST" action="{{ route('companies.suppliers.update', [$company, $supplier]) }}">
                    @csrf
                    @method('PATCH')

                    <div class="co-card">
                        <div class="co-card-head">
                            <div>
                                <div class="co-section-heading"><p class="co-section-label">Suppliers</p><h2>Edit Supplier</h2></div>
                            </div>
</div>

                        <div style="padding:1rem 1.5rem 1.25rem;">
                            @if ($errors->any())
                                <div style="background:#fee2e2;border:1px solid #fca5a5;color:#b91c1c;padding:0.5rem 0.85rem;border-radius:0;font-size:0.72rem;margin-bottom:0.85rem;">
                                    <strong>Please fix the following:</strong>
                                    <ul style="margin:0.25rem 0 0 1rem;padding:0;">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="inv-form-grid">
                                <div class="inv-field">
                                    <label for="name">Supplier Name</label>
                                    <input type="text" id="name" name="name"
                                        value="{{ old('name', $supplier->name) }}" required>
                                </div>
                                <div class="inv-field">
                                    <label for="contact_name">Contact Person <span class="optional">(optional)</span></label>
                                    <input type="text" id="contact_name" name="contact_name"
                                        value="{{ old('contact_name', $supplier->contact_name) }}">
                                </div>
                                <div class="inv-field">
                                    <label for="email">Supplier Email</label>
                                    <input type="email" id="email" name="email"
                                        value="{{ old('email', $supplier->email) }}" required>
                                </div>
                                <div class="inv-field">
                                    <label for="phone">Phone <span class="optional">(optional)</span></label>
                                    <input type="tel" id="phone" name="phone"
                                        value="{{ old('phone', $supplier->phone) }}">
                                </div>
                                <div class="inv-field" style="grid-column:span 2;">
                                    <label for="address">Supplier Address <span class="optional">(optional)</span></label>
                                    <textarea id="address" name="address" rows="2">{{ old('address', $supplier->address) }}</textarea>
                                </div>
                                <div class="inv-field" style="grid-column:span 2;">
                                    <label class="inv-status-field">
                                        <input type="hidden" name="is_active" value="0">
                                        <input type="checkbox" id="is_active" name="is_active" value="1"
                                            {{ old('is_active', $supplier->is_active ? '1' : '0') == '1' ? 'checked' : '' }}>
                                        <span>Active supplier</span>
                                    </label>
                                </div>
                            </div>

                            <div class="form-actions">
                                <a href="{{ route('companies.suppliers.index', $company) }}" class="btn-cancel">Cancel</a>
                                <button type="submit" class="btn-primary">Save Changes</button>
                            </div>
                        </div>
                    </div>
                </form>
            </main>
        </div>
    </div>
@endsection
