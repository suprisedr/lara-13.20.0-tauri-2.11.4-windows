@extends('layouts.public')

@section('title', $company->registered_name . ' — New Customer')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .cn-form { max-width:920px; }
        .cn-section { margin-bottom:1.75rem; }
        .cn-section-title { font-size:0.65rem; font-weight:800; letter-spacing:0.1em; text-transform:uppercase; color:#5e17eb; margin:0 0 0.75rem; }
        .cn-grid { display:grid; grid-template-columns:1fr 1fr; gap:0.75rem 1.25rem; }
        .cn-field label { display:block; font-size:0.6rem; font-weight:700; letter-spacing:0.07em; text-transform:uppercase; color:#555; margin-bottom:0.22rem; }
        .cn-field input, .cn-field select, .cn-field textarea {
            width:100%; border:1px solid #ccc; padding:0.35rem 0.55rem;
            font-size:0.8rem; font-family:inherit; color:#000; background:#fff; box-sizing:border-box;
        }
        .cn-field input:focus, .cn-field select:focus, .cn-field textarea:focus { outline:none; border-color:#5e17eb; }
        .cn-field textarea { resize:vertical; min-height:70px; }
        .btn-submit { background:#000; color:#fff; border:none; padding:0.55rem 1.5rem; font-size:0.82rem; font-weight:700; cursor:pointer; transition:background 0.15s; font-family:inherit; }
        .btn-submit:hover { background:#333; }
        .form-error { color:#dc2626; font-size:0.72rem; margin-top:0.2rem; }
    </style>
@endpush

@section('content')
    <div class="co-wrap">
        @include('companies._topbar')

        <div class="co-body">
            @include('companies._sidebar')
            <main class="co-main">

                <form method="POST" action="{{ route('companies.customers.store', $company) }}" class="cn-form">
                    @csrf

                    @if ($errors->any())
                        <div style="background:#fee2e2;border:1px solid #fecaca;color:#b91c1c;padding:0.75rem 1.25rem;font-size:0.82rem;margin-bottom:1.25rem;">
                            <strong>Please fix the following:</strong>
                            <ul style="margin:0.4rem 0 0 1.1rem;padding:0;">
                                @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="cn-section">
                        <p class="cn-section-title">Customer Details</p>
                        <div class="cn-grid">
                            <div class="cn-field">
                                <label>Customer Name <span style="color:#dc2626;">*</span></label>
                                <input type="text" name="name" value="{{ old('name') }}" required>
                                @error('name')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="cn-field">
                                <label>Contact Person <span style="font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span></label>
                                <input type="text" name="contact_name" value="{{ old('contact_name') }}">
                            </div>
                            <div class="cn-field">
                                <label>Email <span style="font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span></label>
                                <input type="email" name="email" value="{{ old('email') }}">
                                @error('email')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="cn-field">
                                <label>Phone <span style="font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span></label>
                                <input type="tel" name="phone" value="{{ old('phone') }}">
                            </div>
                        </div>
                    </div>

                    <div class="cn-section">
                        <p class="cn-section-title">Address</p>
                        <div class="cn-grid">
                            <div class="cn-field" style="grid-column:span 2;">
                                <label>Customer Address <span style="font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span></label>
                                <textarea name="address" rows="2">{{ old('address') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="cn-section">
                        <label style="display:flex;align-items:center;gap:0.5rem;font-size:0.8rem;color:#000;cursor:pointer;">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }} style="accent-color:#000;">
                            Active customer
                        </label>
                    </div>

                    <div style="display:flex;gap:1rem;align-items:center;">
                        <button type="submit" class="btn-submit">Create Customer</button>
                        <a href="{{ route('companies.customers.index', $company) }}" style="font-size:0.78rem;color:#6b7280;text-decoration:none;">Cancel</a>
                    </div>
                </form>

            </main>
        </div>
    </div>
@endsection
