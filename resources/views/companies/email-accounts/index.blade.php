@extends('layouts.public')

@section('title', $company->registered_name . ' — Email Accounts')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .email-account-card {
            border: 1px solid rgba(94, 23, 235, 0.12);
            padding: 0.65rem 1rem;
            margin-bottom: 0.4rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: border-color 0.15s, background 0.15s;
        }

        .email-account-card:hover {
            border-color: rgba(94, 23, 235, 0.3);
            background: #faf8ff;
        }

        .email-provider-icon {
            width: 28px;
            height: 28px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 0.55rem;
            color: #fff;
            flex-shrink: 0;
        }

        .email-provider-icon.gmail { background: #ea4335; }
        .email-provider-icon.outlook { background: #0078d4; }
        .email-provider-icon.imap { background: #6b7280; }
        .email-provider-icon.pop { background: #8b5cf6; }

        .email-account-info { flex: 1; min-width: 0; }

        .email-account-info h3 {
            font-size: 0.82rem;
            font-weight: 700;
            color: #1b1b18;
            margin: 0 0 0.05rem;
        }

        .email-account-info p {
            font-size: 0.7rem;
            color: #6b7280;
            margin: 0;
            line-height: 1.3;
        }

        .email-account-actions {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            flex-shrink: 0;
        }

        .email-btn {
            padding: 0.25rem 0.6rem;
            font-size: 0.7rem;
            font-weight: 600;
            border: 1px solid #d1d5db;
            border-radius: 0;
            background: #fff;
            color: #374151;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            transition: background 0.15s;
            font-family: inherit;
        }

        .email-btn:hover { background: #f9fafb; }
        .email-btn.primary { background: #5e17eb; color: #fff; border-color: #5e17eb; }
        .email-btn.primary:hover { background: #4a10c4; }
        .email-btn.danger { color: #dc2626; border-color: #fca5a5; }
        .email-btn.danger:hover { background: #fef2f2; }

        .email-status-badge {
            font-size: 0.6rem;
            font-weight: 700;
            padding: 0.12rem 0.4rem;
            border-radius: 999px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .email-status-badge.active { background: #dcfce7; color: #166534; }
        .email-status-badge.inactive { background: #fee2e2; color: #991b1b; }
        .email-status-badge.error { background: #fef3c7; color: #92400e; }
    </style>
@endpush

@section('content')
    <div class="co-wrap">

        @include('companies._topbar')

        <div class="co-body">
            @include('companies._sidebar')

            <main class="co-main">
                <div class="co-card">
                    <div class="co-card-head">
                        <div>
                            <div class="co-section-heading"><p class="co-section-label">Integrations</p><h2>Email Accounts</h2></div>
                        </div>
                        @if ($accounts->isEmpty())
                            <a href="{{ route('companies.email-accounts.create', $company) }}" class="email-btn primary">
                                + Link Email Account
                            </a>
                        @endif
                    </div>

                    <div style="padding:1rem 1.5rem;">
                        @if (session('success'))
                            <div style="background:#dcfce7;border:1px solid #86efac;color:#166534;padding:0.5rem 0.85rem;font-size:0.75rem;margin-bottom:0.85rem;">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if (session('error'))
                            <div style="background:#fee2e2;border:1px solid #fca5a5;color:#b91c1c;padding:0.5rem 0.85rem;font-size:0.75rem;margin-bottom:0.85rem;">
                                {{ session('error') }}
                            </div>
                        @endif

                        @forelse ($accounts as $account)
                            <div class="email-account-card">
                                <div class="email-provider-icon {{ $account->provider->value }}">
                                    {{ strtoupper(substr($account->provider->value, 0, 2)) }}
                                </div>

                                <div class="email-account-info">
                                    <h3>{{ $account->email_address }}</h3>
                                    <p>
                                        {{ $account->provider->label() }}
                                        @if ($account->last_synced_at)
                                            &middot; Last activity {{ $account->last_synced_at->diffForHumans() }}
                                        @else
                                            &middot; Never synced
                                        @endif
                                    </p>
                                    @if ($account->last_error)
                                        <p style="color:#dc2626;font-size:0.65rem;margin-top:0.1rem;">
                                            Error: {{ \Illuminate\Support\Str::limit($account->last_error, 80) }}
                                        </p>
                                    @endif
                                </div>

                                <span class="email-status-badge {{ $account->is_active ? ($account->last_error ? 'error' : 'active') : 'inactive' }}">
                                    {{ $account->is_active ? ($account->last_error ? 'Error' : 'Active') : 'Inactive' }}
                                </span>

                                <div class="email-account-actions">
                                    <a href="{{ route('companies.email-accounts.emails', [$company, $account]) }}" class="email-btn">
                                        Inbox
                                    </a>

                                    <form method="POST" action="{{ route('companies.email-accounts.destroy', [$company, $account]) }}"
                                        style="margin:0;" onsubmit="return confirm('Disconnect this email account?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="email-btn danger">Disconnect</button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div style="text-align:center;padding:2rem 1rem;color:#9ca3af;">
                                <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 0.65rem;">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                    <polyline points="22,6 12,13 2,6"/>
                                </svg>
                                <p style="font-size:0.82rem;font-weight:600;margin:0 0 0.15rem;">No email accounts linked</p>
                                <p style="font-size:0.74rem;">Link a Gmail, Outlook, or IMAP account to automatically receive supplier emails.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </main>
        </div>
    </div>
@endsection
