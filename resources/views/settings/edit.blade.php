@extends('layouts.public')

@section('title', 'Settings')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    <style>
        .dash-wrap { min-height: 100vh; background: #f6f9fc; }

        /* ── Hero header ─────────────────────────── */
        .settings-hero {
            background: linear-gradient(135deg, #4c1d95 0%, #7c3aed 50%, #6d28d9 100%);
            padding: 24pt 24pt 26pt;
            position: relative;
            overflow: hidden;
        }

        .settings-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: repeating-linear-gradient(-45deg, transparent, transparent 40px, rgba(255,255,255,0.025) 40px, rgba(255,255,255,0.025) 41px);
            pointer-events: none;
        }

        .settings-hero::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -15%;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(167,139,250,0.15) 0%, transparent 70%);
            pointer-events: none;
        }

        .settings-hero-inner {
            position: relative;
            z-index: 1;
            max-width: 1200px;
            margin: 0 auto;
        }

        .settings-hero-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8pt;
            margin-bottom: 14pt;
        }

        .settings-hero-brand {
            display: flex;
            align-items: center;
            gap: 8pt;
        }

        .settings-hero-brand img {
            width: 26pt;
            height: 26pt;
            object-fit: contain;
        }

        .settings-hero-brand-text {
            font-family: Helvetica, Arial, "DejaVu Sans", sans-serif;
        }

        .settings-hero-brand-text .label {
            font-size: 5pt;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: rgba(167,139,250,0.8);
            margin: 0 0 1pt;
        }

        .settings-hero-brand-text .name {
            font-size: 9pt;
            font-weight: 800;
            color: #fff;
            letter-spacing: 0.02em;
        }

        .settings-hero-actions {
            display: flex;
            align-items: center;
            gap: 5pt;
        }

        .hero-btn {
            display: inline-flex;
            align-items: center;
            gap: 3pt;
            background: rgba(255,255,255,0.1);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.2);
            font-size: 6pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 4pt 10pt;
            text-decoration: none;
            transition: background 0.15s;
            font-family: Helvetica, Arial, "DejaVu Sans", sans-serif;
            cursor: pointer;
        }

        .hero-btn:hover { background: rgba(255,255,255,0.2); }
        .hero-btn.ghost { color: #c4b5fd; border-color: rgba(167,139,250,0.3); }
        .hero-btn.ghost:hover { color: #fff; }

        .settings-hero-heading h1 {
            font-size: 16pt;
            font-weight: 900;
            color: #fff;
            margin: 0 0 3pt;
            letter-spacing: -0.02em;
            font-family: Helvetica, Arial, "DejaVu Sans", sans-serif;
        }

        .settings-hero-heading p {
            font-size: 7pt;
            color: #c4b5fd;
            margin: 0;
            font-family: Helvetica, Arial, "DejaVu Sans", sans-serif;
        }

        @media (max-width: 760px) {
            .settings-hero { padding: 14pt 12pt 18pt; }
            .settings-hero-heading h1 { font-size: 13pt; }
        }

        /* ── Content ─────────────────────────────── */
        .settings-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 16pt 20pt 24pt;
        }

        @media (max-width: 760px) {
            .settings-content { padding: 10pt 8pt; }
        }

        .settings-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12pt;
        }

        @media (max-width: 760px) {
            .settings-grid { grid-template-columns: 1fr; }
        }

        /* ── Card ────────────────────────────────── */
        .settings-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            font-family: Helvetica, Arial, "DejaVu Sans", sans-serif;
            box-shadow: 0 1px 3px rgba(76,29,149,0.06);
        }

        .settings-card.full-width { grid-column: 1 / -1; }

        .settings-card-header {
            padding: 10pt 14pt 8pt;
            border-bottom: 1.5pt solid #4c1d95;
            display: flex;
            align-items: center;
            gap: 6pt;
        }

        .settings-card-icon {
            width: 22pt;
            height: 22pt;
            background: linear-gradient(135deg, #7c3aed, #4c1d95);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .settings-card-header-text h2 {
            font-size: 7pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #4c1d95;
            margin: 0 0 1pt;
        }

        .settings-card-header-text p {
            font-size: 5.5pt;
            color: #8b7aad;
            margin: 0;
        }

        .settings-card-body { padding: 12pt 14pt; }

        /* ── Form fields ─────────────────────────── */
        .s-field { margin-bottom: 10pt; }
        .s-field:last-child { margin-bottom: 0; }

        .s-field-top { display: flex; align-items: center; gap: 4pt; margin-bottom: 3pt; }

        .s-field-top label {
            font-size: 6pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.09em;
            color: #4c1d95;
            margin: 0;
        }

        .s-badge {
            font-size: 4.5pt;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            padding: 1pt 4pt;
        }

        .s-badge-ok { background: #dcfce7; color: #15803d; border: 0.5pt solid #bbf7d0; }
        .s-badge-req { background: #fee2e2; color: #b91c1c; border: 0.5pt solid #fecaca; }

        .s-input {
            width: 100%;
            box-sizing: border-box;
            border: 1px solid #ddd6fe;
            padding: 5pt 8pt;
            font-size: 7pt;
            font-family: Helvetica, Arial, "DejaVu Sans", sans-serif;
            color: #4c1d95;
            background: #faf8ff;
            outline: none;
            transition: border-color 0.15s, background 0.15s;
        }

        .s-input:focus { border-color: #7c3aed; background: #fff; }
        .s-input::placeholder { color: #c4b5fd; }

        .s-help { font-size: 5.5pt; color: #8b7aad; margin: 2pt 0 0; line-height: 1.5; }

        .s-remove {
            display: inline-flex;
            align-items: center;
            gap: 3pt;
            font-size: 5.5pt;
            color: #b91c1c;
            margin-top: 3pt;
            cursor: pointer;
        }

        .s-remove input[type=checkbox] { accent-color: #b91c1c; cursor: pointer; width: 8pt; height: 8pt; }

        /* ── Action bar ──────────────────────────── */
        .s-action-bar {
            display: flex;
            align-items: center;
            gap: 8pt;
            padding-top: 10pt;
            border-top: 0.4pt solid #ede9fe;
            margin-top: 10pt;
        }

        .s-btn {
            display: inline-flex;
            align-items: center;
            gap: 3pt;
            border: 1px solid #c4b5fd;
            color: #4c1d95;
            font-size: 6pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 4pt 10pt;
            text-decoration: none;
            cursor: pointer;
            font-family: Helvetica, Arial, "DejaVu Sans", sans-serif;
            transition: background 0.15s, color 0.15s;
            background: #fff;
        }

        .s-btn:hover { background: #f5f3ff; }
        .s-btn.primary { background: #7c3aed; color: #fff; border-color: #7c3aed; }
        .s-btn.primary:hover { background: #6d28d9; }

        .retry-option {
            display: flex;
            align-items: center;
            gap: 5pt;
        }

        .retry-option input[type=checkbox] { accent-color: #7c3aed; cursor: pointer; width: 9pt; height: 9pt; }
        .retry-option label { font-size: 6pt; font-weight: 600; color: #4c1d95; cursor: pointer; }

        /* ── Agent rows ──────────────────────────── */
        .agent-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 5pt 4pt;
            border-bottom: 0.4pt solid #ede9fe;
            transition: background 0.1s;
        }

        .agent-row:last-child { border-bottom: none; }
        .agent-row:hover { background: #faf8ff; }

        .agent-row-left { display: flex; align-items: center; gap: 5pt; }

        .agent-dot {
            width: 6pt;
            height: 6pt;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .agent-dot.on { background: #22c55e; box-shadow: 0 0 4px rgba(34,197,94,0.4); }
        .agent-dot.off { background: #ddd6fe; }

        .agent-name { font-size: 6.5pt; font-weight: 600; color: #4c1d95; }
        .agent-provider { font-size: 5.5pt; color: #8b7aad; font-weight: 600; }

        /* ── Flash ───────────────────────────────── */
        .settings-flash {
            background: #dcfce7;
            border: 1px solid #bbf7d0;
            color: #15803d;
            padding: 5pt 10pt;
            font-size: 7pt;
            font-weight: 600;
            margin-bottom: 12pt;
            display: flex;
            align-items: center;
            gap: 5pt;
            font-family: Helvetica, Arial, "DejaVu Sans", sans-serif;
        }
    </style>
@endpush

@section('content')
    <div class="dash-wrap">

        {{-- Hero --}}
        <div class="settings-hero">
            <div class="settings-hero-inner">
                <div class="settings-hero-top">
                    <div class="settings-hero-brand">
                        <img src="{{ asset('storage/images/chainbook-icon-light.png') }}" alt="">
                        <div class="settings-hero-brand-text">
                            <p class="label">Chainbook Intelligence</p>
                            <span class="name">Accounting Platform</span>
                        </div>
                    </div>
                    <div class="settings-hero-actions">
                        <a href="{{ route('dashboard') }}" class="hero-btn">
                            <svg width="9" height="9" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
                            Dashboard
                        </a>
                        <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                            @csrf
                            <button type="submit" class="hero-btn ghost">
                                <svg width="9" height="9" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
                                </svg>
                                Log Out
                            </button>
                        </form>
                    </div>
                </div>

                <div class="settings-hero-heading">
                    <h1>
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" style="vertical-align:-1pt;margin-right:2pt;">
                            <circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                        </svg>
                        Settings
                    </h1>
                    <p>Manage API keys and AI agent configuration for your workspace.</p>
                </div>
            </div>
        </div>

        <div class="settings-content">

            @if (session('status') === 'settings-updated')
                <div class="settings-flash">
                    <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                    Settings saved successfully.
                </div>
            @endif

            <form method="POST" action="{{ route('settings.update') }}">
                @csrf
                @method('PATCH')

                <div class="settings-grid">

                    {{-- API Keys card --}}
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <div class="settings-card-icon">
                                <svg width="12" height="12" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                    <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/>
                                </svg>
                            </div>
                            <div class="settings-card-header-text">
                                <h2>API Keys</h2>
                                <p>Configure provider keys for AI agents</p>
                            </div>
                        </div>
                        <div class="settings-card-body">
                            @foreach($settings as $key => $setting)
                                <div class="s-field">
                                    <div class="s-field-top">
                                        <label for="{{ $key }}">{{ $setting['label'] }}</label>
                                        @if($setting['has_value'])
                                            <span class="s-badge s-badge-ok">configured</span>
                                        @elseif($setting['required'] ?? false)
                                            <span class="s-badge s-badge-req">required</span>
                                        @endif
                                    </div>

                                    <input
                                        id="{{ $key }}"
                                        name="{{ $key }}"
                                        type="password"
                                        class="s-input"
                                        placeholder="{{ $setting['has_value'] ? '••••••••  (leave blank to keep current)' : $setting['placeholder'] }}"
                                        autocomplete="off"
                                    />

                                    <p class="s-help">{{ $setting['help'] }}</p>

                                    @if($setting['has_value'])
                                        <label class="s-remove">
                                            <input type="checkbox"
                                                   onchange="if(this.checked){document.getElementById('{{ $key }}').value='';document.getElementById('{{ $key }}').type='text';document.getElementById('{{ $key }}').placeholder='Key will be removed on save';}else{document.getElementById('{{ $key }}').type='password';document.getElementById('{{ $key }}').placeholder='••••••••  (leave blank to keep current)';}"
                                            />
                                            Remove this key
                                        </label>
                                    @endif
                                </div>
                            @endforeach

                            <div class="s-action-bar">
                                <button type="submit" class="s-btn primary">
                                    <svg width="9" height="9" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                                    Save Settings
                                </button>
                                <div class="retry-option">
                                    <input type="checkbox" name="retry_failed_jobs" value="1" id="retry_failed_jobs" />
                                    <label for="retry_failed_jobs">Retry failed AI jobs</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- AI Agent Status card --}}
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <div class="settings-card-icon">
                                <svg width="12" height="12" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                    <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm0 14a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm1-5.16V12h-2v-2a2 2 0 1 1 2-2"/>
                                </svg>
                            </div>
                            <div class="settings-card-header-text">
                                <h2>AI Agent Status</h2>
                                <p>Provider connectivity overview</p>
                            </div>
                        </div>
                        <div class="settings-card-body">
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
                                $configuredCount = collect($agents)->filter(fn($a) => $settings[$a['key']]['has_value'])->count();
                            @endphp

                            <div style="display:flex;align-items:center;gap:6pt;margin-bottom:8pt;padding-bottom:6pt;border-bottom:0.4pt solid #ede9fe;">
                                <div style="font-size:14pt;font-weight:900;color:#4c1d95;font-family:Helvetica,Arial,'DejaVu Sans',sans-serif;">{{ $configuredCount }}/{{ count($agents) }}</div>
                                <div>
                                    <p style="font-size:6pt;font-weight:700;color:#4c1d95;margin:0;">Agents Ready</p>
                                    <p style="font-size:5pt;color:#8b7aad;margin:1pt 0 0;">{{ $configuredCount === count($agents) ? 'All agents are connected' : 'Configure missing API keys to enable all agents' }}</p>
                                </div>
                            </div>

                            @foreach($agents as $agent)
                                <div class="agent-row">
                                    <div class="agent-row-left">
                                        <div class="agent-dot {{ $settings[$agent['key']]['has_value'] ? 'on' : 'off' }}"></div>
                                        <span class="agent-name">{{ $agent['name'] }}</span>
                                    </div>
                                    <span class="agent-provider">{{ $agent['provider'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                </div>
            </form>

        </div>
    </div>
@endsection
