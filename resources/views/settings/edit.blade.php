@extends('layouts.public')

@section('title', 'Settings')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    <style>
        .dash-wrap { min-height: 100vh; background: #f6f9fc; }

        .co-topbar {
            background: #4c1d95;
            padding: 14pt 16pt;
            margin: 28pt 16pt 0;
            position: relative;
            overflow: hidden;
        }

        .co-topbar::before {
            content: '';
            position: absolute;
            inset: 0;
            background: repeating-linear-gradient(
                -45deg,
                transparent,
                transparent 40px,
                rgba(255,255,255,0.02) 40px,
                rgba(255,255,255,0.02) 41px
            );
            pointer-events: none;
        }

        @media (max-width: 760px) {
            .co-topbar { margin: 6pt 8pt 0; padding: 10pt 10pt; }
        }

        .dash-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 16pt 20pt;
        }

        @media (max-width: 760px) {
            .dash-content { padding: 10pt 8pt; }
        }

        .cust-doc { background: #fff; border: 1px solid #c4b5fd; color: #4c1d95; font-size: 7pt; font-family: Helvetica, Arial, "DejaVu Sans", sans-serif; line-height: 1.45; margin-bottom: 12pt; }
        .cust-doc-body { padding: 16pt 18pt; }

        .section-header { border-top: 1.5pt solid #4c1d95; margin: 0 0 8pt; padding-top: 3pt; display: flex; justify-content: space-between; align-items: baseline; }
        .section-header-title { font-size: 5.5pt; font-weight: 800; text-transform: uppercase; letter-spacing: 0.12em; color: #4c1d95; }
        .section-header-sub { font-size: 5.5pt; color: #8b7aad; }

        .settings-field { margin-bottom: 10pt; }
        .settings-field-label { display: flex; align-items: center; gap: 4pt; margin-bottom: 2pt; }
        .settings-field-label label { font-size: 5.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.09em; color: #8b7aad; margin: 0; }
        .settings-field-label .badge { font-size: 4.5pt; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; padding: 1pt 3pt; }
        .badge-configured { background: #dcfce7; color: #15803d; border: 0.5pt solid #bbf7d0; }
        .badge-required { background: #fee2e2; color: #b91c1c; border: 0.5pt solid #fecaca; }

        .settings-input {
            width: 100%; box-sizing: border-box;
            border: 1px solid #c4b5fd; padding: 4pt 6pt;
            font-size: 7pt; font-family: Helvetica, Arial, "DejaVu Sans", sans-serif; color: #4c1d95;
            background: #fff; outline: none; border-radius: 0;
        }
        .settings-input:focus { border-color: #4c1d95; }
        .settings-input::placeholder { color: #c4b5fd; }

        .settings-help { font-size: 5.5pt; color: #8b7aad; margin: 2pt 0 0; }

        .settings-remove { display: inline-flex; align-items: center; gap: 2pt; font-size: 5.5pt; color: #8b7aad; margin-top: 2pt; cursor: pointer; }
        .settings-remove input[type=checkbox] { accent-color: #4c1d95; cursor: pointer; width: 8pt; height: 8pt; }

        .mgmt-btn { display: inline-flex; align-items: center; gap: 3pt; background: #fff; border: 1px solid #c4b5fd; color: #4c1d95; font-size: 6pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; padding: 3pt 7pt; text-decoration: none; cursor: pointer; font-family: Helvetica, Arial, "DejaVu Sans", sans-serif; transition: background 0.15s, color 0.15s; }
        .mgmt-btn:hover { background: #f5f3ff; }
        .mgmt-btn.primary { background: #7c3aed; color: #fff; border-color: #7c3aed; }
        .mgmt-btn.primary:hover { background: #6d28d9; }

        .agent-row {
            display: flex; align-items: center; justify-content: space-between;
            padding: 3pt 6pt; border-bottom: 0.4pt solid #ddd6fe;
            transition: background 0.1s;
        }
        .agent-row:last-child { border-bottom: none; }
        .agent-row:hover { background: #f5f3ff; }
        .agent-row-left { display: flex; align-items: center; gap: 4pt; }
        .agent-name { font-size: 7pt; font-weight: 600; color: #4c1d95; }
        .agent-provider { font-size: 5.5pt; color: #8b7aad; }

        .retry-option { display: flex; align-items: center; gap: 4pt; padding: 6pt 0 0; border-top: 0.4pt solid #ddd6fe; margin-top: 8pt; }
        .retry-option input[type=checkbox] { accent-color: #4c1d95; cursor: pointer; width: 9pt; height: 9pt; }
        .retry-option label { font-size: 6.5pt; font-weight: 600; color: #4c1d95; cursor: pointer; }
        .retry-help { font-size: 5.5pt; color: #8b7aad; margin: 2pt 0 0 13pt; }
    </style>
@endpush

@section('content')
    <div class="dash-wrap">

        {{-- Top bar --}}
        <div class="co-topbar">
            <div style="position:relative;z-index:1;display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:8pt;">
                <div>
                    <p style="font-size:5pt;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:#8b7aad;margin:0 0 3pt;font-family:Helvetica,Arial,'DejaVu Sans',sans-serif;">
                        Chainbook Intelligence
                    </p>
                    <h1 style="font-size:16pt;font-weight:900;color:#fff;margin:0;letter-spacing:-0.02em;font-family:Helvetica,Arial,'DejaVu Sans',sans-serif;">
                        Settings
                    </h1>
                    <p style="font-size:7pt;color:#a78bfa;margin:2pt 0 0;font-family:Helvetica,Arial,'DejaVu Sans',sans-serif;">
                        API keys and AI agent configuration
                    </p>
                </div>
                <div style="display:flex;align-items:center;gap:5pt;">
                    <a href="{{ route('dashboard') }}"
                        style="display:inline-flex;align-items:center;gap:3pt;background:rgba(255,255,255,0.1);color:#fff;border:1px solid #6b5b8a;font-size:6pt;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;padding:4pt 8pt;text-decoration:none;transition:background 0.15s;font-family:Helvetica,Arial,'DejaVu Sans',sans-serif;"
                        onmouseover="this.style.background='rgba(255,255,255,0.18)'"
                        onmouseout="this.style.background='rgba(255,255,255,0.1)'">
                        <svg width="9" height="9" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
                        Dashboard
                    </a>
                    <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                        @csrf
                        <button type="submit"
                            style="display:inline-flex;align-items:center;gap:3pt;background:rgba(255,255,255,0.1);color:#c4b5fd;border:1px solid #6b5b8a;font-size:6pt;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;padding:4pt 8pt;cursor:pointer;transition:background 0.15s,color 0.15s;font-family:Helvetica,Arial,'DejaVu Sans',sans-serif;"
                            onmouseover="this.style.background='rgba(255,255,255,0.18)';this.style.color='#fff'"
                            onmouseout="this.style.background='rgba(255,255,255,0.1)';this.style.color='#c4b5fd'">
                            <svg width="9" height="9" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
                            </svg>
                            Log Out
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="dash-content">

            {{-- Flash messages --}}
            @if (session('status') === 'settings-updated')
                <div style="background:#dcfce7;border:1px solid #bbf7d0;color:#15803d;padding:5pt 8pt;font-size:7pt;font-weight:600;margin-bottom:10pt;display:flex;align-items:center;gap:4pt;font-family:Helvetica,Arial,'DejaVu Sans',sans-serif;">
                    <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                    Settings saved successfully.
                </div>
            @endif

            {{-- API Keys --}}
            <div class="cust-doc">
                <div class="cust-doc-body">

                    <div class="section-header">
                        <span class="section-header-title">AI Provider API Keys</span>
                        <span class="section-header-sub">Configure keys for Chainbook's AI agents</span>
                    </div>

                    <form method="POST" action="{{ route('settings.update') }}">
                        @csrf
                        @method('PATCH')

                        @foreach($settings as $key => $setting)
                            <div class="settings-field">
                                <div class="settings-field-label">
                                    <label for="{{ $key }}">{{ $setting['label'] }}</label>
                                    @if($setting['has_value'])
                                        <span class="badge badge-configured">configured</span>
                                    @elseif($setting['required'] ?? false)
                                        <span class="badge badge-required">required</span>
                                    @endif
                                </div>

                                <input
                                    id="{{ $key }}"
                                    name="{{ $key }}"
                                    type="password"
                                    class="settings-input"
                                    placeholder="{{ $setting['has_value'] ? '••••••••  (leave blank to keep current)' : $setting['placeholder'] }}"
                                    autocomplete="off"
                                />

                                <p class="settings-help">{{ $setting['help'] }}</p>

                                @if($setting['has_value'])
                                    <label class="settings-remove">
                                        <input type="checkbox"
                                               onchange="if(this.checked){document.getElementById('{{ $key }}').value='';document.getElementById('{{ $key }}').type='text';document.getElementById('{{ $key }}').placeholder='Key will be removed on save';}else{document.getElementById('{{ $key }}').type='password';document.getElementById('{{ $key }}').placeholder='••••••••  (leave blank to keep current)';}"
                                        />
                                        Remove this key
                                    </label>
                                @endif
                            </div>
                        @endforeach

                        <div class="retry-option">
                            <input type="checkbox" name="retry_failed_jobs" value="1" id="retry_failed_jobs" />
                            <label for="retry_failed_jobs">Retry failed AI posting jobs after saving</label>
                        </div>
                        <p class="retry-help">Re-queues any invoices or assets whose AI posting previously failed.</p>

                        <div style="margin-top:10pt;display:flex;align-items:center;gap:6pt;">
                            <button type="submit" class="mgmt-btn primary">Save Settings</button>
                        </div>
                    </form>

                </div>
            </div>

            {{-- AI Agent Status --}}
            <div class="cust-doc">
                <div class="cust-doc-body">

                    <div class="section-header">
                        <span class="section-header-title">AI Agent Status</span>
                        <span class="section-header-sub">Provider connectivity overview</span>
                    </div>

                    @php
                        $agents = [
                            ['name' => 'Invoice Posting', 'provider' => 'Gemini', 'key' => 'gemini_api_key'],
                            ['name' => 'Asset Posting', 'provider' => 'Gemini', 'key' => 'gemini_api_key'],
                            ['name' => 'Lease Posting', 'provider' => 'Gemini', 'key' => 'gemini_api_key'],
                            ['name' => 'Inventory Posting', 'provider' => 'Gemini', 'key' => 'gemini_api_key'],
                            ['name' => 'Intangible Asset Posting', 'provider' => 'Gemini', 'key' => 'gemini_api_key'],
                            ['name' => 'Credit Note Posting', 'provider' => 'Gemini', 'key' => 'gemini_api_key'],
                            ['name' => 'Invoice Extraction', 'provider' => 'Gemini', 'key' => 'gemini_api_key'],
                            ['name' => 'Chart of Accounts Suggestion', 'provider' => 'Gemini', 'key' => 'gemini_api_key'],
                            ['name' => 'Email Summary', 'provider' => 'Gemini', 'key' => 'gemini_api_key'],
                            ['name' => 'ECL Posting', 'provider' => 'Gemini', 'key' => 'gemini_api_key'],
                            ['name' => 'Investment Property Posting', 'provider' => 'Gemini', 'key' => 'gemini_api_key'],
                        ];
                    @endphp

                    @foreach($agents as $agent)
                        <div class="agent-row">
                            <div class="agent-row-left">
                                @if($settings[$agent['key']]['has_value'])
                                    <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="#15803d">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                @else
                                    <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="#c4b5fd">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                    </svg>
                                @endif
                                <span class="agent-name">{{ $agent['name'] }}</span>
                            </div>
                            <span class="agent-provider">{{ $agent['provider'] }}</span>
                        </div>
                    @endforeach

                </div>
            </div>

        </div>
    </div>
@endsection
