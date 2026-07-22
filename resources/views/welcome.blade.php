@extends('layouts.public')

@section('title', 'AI-Powered Accounting')
@section('page-id', 'home')
@section('meta-description', 'Chainbook Intelligence — AI-driven double-entry accounting for South African businesses: auto-generated IFRS chart of accounts, AI-posted invoices and assets, semantic transaction search, payroll, VAT, and full IFRS-for-SMEs reporting.')

@push('schema')
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@graph": [
    {
      "@@type": "Organization",
      "@@id": "{{ url('/') }}/#organization",
      "name": "Chainbook Intelligence",
      "url": "{{ url('/') }}"
    },
    {
      "@@type": "WebSite",
      "@@id": "{{ url('/') }}/#website",
      "url": "{{ url('/') }}",
      "name": "Chainbook Intelligence",
      "publisher": { "@@id": "{{ url('/') }}/#organization" }
    }
  ]
}
</script>
@endpush

@push('styles')
<style>
    /* ── Neutral / black-on-white palette ─────────────────────── */
    :root {
        --ink:        #0a0a0a;
        --ink-soft:   #1f2937;
        --muted:      #6b7280;
        --line:       #e5e7eb;
        --line-soft:  #f3f4f6;
        --tint:       #f9fafb;
        --accent:     #5e17eb;
        --accent-bg:  #3b0ea8;
    }

    /* ── Hero ─────────────────────────────────────────────────── */
    .hero-static { width: 100%; padding: 6.5rem 0 4rem; box-sizing: border-box; }
    .hero-grid { display: grid; grid-template-columns: 1.05fr 1fr; gap: 3rem; align-items: center; max-width: 1180px; margin: 0 auto; padding: 0 2rem; }
    .hero-tag { display: inline-flex; align-items: center; gap: 0.4rem; background: var(--line-soft); color: var(--ink-soft); padding: 0.3rem 0.8rem; border-radius:0; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; margin-bottom: 1.25rem; width: fit-content; }
    .hero-tag::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: #10b981; box-shadow: 0 0 6px #10b981; }
    .hero-text h1 { font-size: clamp(2.2rem, 5vw, 3.85rem); font-weight: 900; line-height: 1.05; margin: 0 0 1.1rem; color: var(--ink); letter-spacing: -0.025em; }
    .hero-text h1 em { font-style: normal; color: var(--muted); }
    .hero-text p { font-size: 1.05rem; color: var(--ink-soft); margin: 0 0 1.75rem; line-height: 1.65; max-width: 520px; }
    .hero-btn { display: inline-flex; align-items: center; gap: 0.4rem; background: var(--accent); color: #fff; padding: 0.78rem 1.6rem; border-radius: 0; font-weight: 700; font-size: 0.9rem; text-decoration: none; box-shadow: 0 4px 14px rgba(94,23,235,0.22); transition: transform 0.15s, background 0.2s, box-shadow 0.2s; }
    .hero-btn:hover { background: var(--accent-bg); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(94,23,235,0.28); }
    .hero-btn-outline { display: inline-flex; align-items: center; gap: 0.4rem; background: #fff; color: var(--ink); padding: 0.78rem 1.6rem; border-radius: 0; font-weight: 600; font-size: 0.9rem; text-decoration: none; border: 1px solid var(--line); transition: border-color 0.2s, transform 0.15s; }
    .hero-btn-outline:hover { border-color: var(--ink); transform: translateY(-1px); }

    /* Mock product surface on the right of the hero */
    .hero-mock { background: #fff; border: 1px solid var(--line); border-radius: 0; padding: 1.25rem; box-shadow: 0 1px 2px rgba(0,0,0,0.04), 0 12px 30px rgba(0,0,0,0.06); }
    .hero-mock-head { display: flex; align-items: center; justify-content: space-between; padding-bottom: 0.85rem; border-bottom: 1px solid var(--line-soft); margin-bottom: 0.85rem; }
    .hero-mock-head h4 { font-size: 0.85rem; font-weight: 800; color: var(--ink); margin: 0; }
    .hero-mock-head span { font-size: 0.66rem; font-weight: 700; color: #10b981; background: #ecfdf5; padding: 0.18rem 0.5rem; border-radius:0; letter-spacing: 0.04em; }
    .hero-mock-row { display: grid; grid-template-columns: 1fr auto auto; gap: 0.75rem; padding: 0.55rem 0; font-size: 0.78rem; align-items: center; border-bottom: 1px solid var(--line-soft); }
    .hero-mock-row:last-child { border-bottom: none; }
    .hero-mock-row .desc { color: var(--ink-soft); font-weight: 500; }
    .hero-mock-row .amt  { font-family: ui-monospace, SFMono-Regular, monospace; color: var(--ink); font-variant-numeric: tabular-nums; }
    .hero-mock-row .pill { font-size: 0.62rem; font-weight: 700; padding: 0.18rem 0.5rem; border-radius: 0; letter-spacing: 0.04em; }
    .pill.ai     { background: #eef2ff; color: #4338ca; }
    .pill.vat    { background: #fef3c7; color: #92400e; }
    .pill.dep    { background: #f0fdf4; color: #166534; }

    /* ── Feature blocks ───────────────────────────────────────── */
    .section { padding: 4rem 2rem; }
    .section-narrow { max-width: 1180px; margin: 0 auto; }
    .section-eyebrow { font-size: 0.7rem; font-weight: 700; letter-spacing: 0.16em; text-transform: uppercase; color: var(--accent); margin: 0 0 0.6rem; }
    .section-title { font-size: clamp(1.7rem, 3.4vw, 2.4rem); font-weight: 800; color: var(--ink); margin: 0 0 0.6rem; letter-spacing: -0.02em; line-height: 1.15; }
    .section-lead { font-size: 1rem; color: var(--ink-soft); line-height: 1.65; margin: 0 0 2rem; max-width: 640px; }

    .feature-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
    .feature-card { background: #fff; border: 1px solid var(--line); border-radius: 0; padding: 1.5rem 1.4rem; display: flex; flex-direction: column; gap: 0.55rem; transition: border-color 0.18s, transform 0.18s; }
    .feature-card:hover { border-color: var(--accent); transform: translateY(-2px); box-shadow: 0 6px 20px rgba(94,23,235,0.08); }
    .feature-card .ic { width: 32px; height: 32px; border-radius: 0; background: #ede9fe; display: flex; align-items: center; justify-content: center; color: var(--accent); margin-bottom: 0.35rem; }
    .feature-card h3 { font-size: 1rem; font-weight: 800; color: var(--ink); margin: 0; letter-spacing: -0.005em; }
    .feature-card p  { font-size: 0.84rem; color: var(--ink-soft); margin: 0; line-height: 1.55; }

    /* ── How-it-works ─────────────────────────────────────────── */
    .steps-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; }
    .step-card { background: var(--tint); border: 1px solid var(--line); border-radius: 0; padding: 1.25rem; }
    .step-card .n { font-size: 0.72rem; font-weight: 800; letter-spacing: 0.1em; color: var(--muted); }
    .step-card h4 { font-size: 0.95rem; font-weight: 800; color: var(--ink); margin: 0.2rem 0 0.4rem; }
    .step-card p  { font-size: 0.82rem; color: var(--ink-soft); margin: 0; line-height: 1.55; }

    /* ── Compliance strip ─────────────────────────────────────── */
    .strip { background: var(--tint); border-top: 1px solid var(--line); border-bottom: 1px solid var(--line); padding: 2rem; }
    .strip-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.25rem; max-width: 1180px; margin: 0 auto; }
    .strip-grid > div { text-align: center; }
    .strip-grid .lbl { font-size: 0.72rem; font-weight: 700; color: var(--muted); letter-spacing: 0.08em; text-transform: uppercase; margin-bottom: 0.25rem; }
    .strip-grid .val { font-size: 1.15rem; font-weight: 800; color: var(--ink); letter-spacing: -0.01em; }

    /* ── CTA ──────────────────────────────────────────────────── */
    .cta { background: var(--accent-bg); padding: 4.5rem 2rem; text-align: center; }
    .cta h2 { font-size: clamp(1.8rem, 4vw, 2.8rem); font-weight: 900; color: #fff; line-height: 1.1; margin: 0 0 1rem; letter-spacing: -0.025em; }
    .cta h2 em { font-style: normal; color: rgba(255,255,255,0.7); }
    .cta p  { font-size: 1rem; color: rgba(255,255,255,0.78); line-height: 1.65; margin: 0 0 2rem; max-width: 560px; margin-left: auto; margin-right: auto; }
    .cta .hero-btn { background: #fff; color: var(--accent); box-shadow: 0 4px 14px rgba(0,0,0,0.18); }
    .cta .hero-btn:hover { background: #f0e8ff; box-shadow: 0 6px 18px rgba(0,0,0,0.22); }
    .cta .hero-btn-outline { background: transparent; color: #fff; border-color: rgba(255,255,255,0.35); }
    .cta .hero-btn-outline:hover { border-color: #fff; }

    @media (max-width: 900px) {
        .hero-grid { grid-template-columns: 1fr; gap: 2rem; }
        .feature-grid { grid-template-columns: 1fr 1fr; }
        .steps-grid { grid-template-columns: 1fr 1fr; }
        .strip-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 560px) {
        .feature-grid { grid-template-columns: 1fr; }
        .steps-grid { grid-template-columns: 1fr; }
        .strip-grid { grid-template-columns: 1fr 1fr; }
    }
</style>
@endpush

@section('content')

{{-- Hero ─────────────────────────────────────────────────── --}}
<section class="hero-static">
    <div class="hero-grid">
        <div class="hero-text">
            <span class="hero-tag">IFRS-for-SMEs · South Africa</span>
            <h1>Accounting that <em>posts itself.</em></h1>
            <p>One AI-driven ledger.</p>
            <div style="display:flex;gap:0.75rem;flex-wrap:wrap;">
                @auth
                    <a href="{{ route('dashboard') }}" class="hero-btn">Go to Dashboard →</a>
                @else
                    <a href="{{ route('register') }}" class="hero-btn">Start free →</a>
                    <a href="{{ route('login') }}" class="hero-btn-outline">Log in</a>
                @endauth
            </div>
        </div>

        <div class="hero-mock" aria-hidden="true">
            <div class="hero-mock-head">
                <h4>Recent journal entries</h4>
                <span>● 4 posted by AI</span>
            </div>
            <div class="hero-mock-row">
                <span class="desc">INV-0098 — T &amp; C Financial Services</span>
                <span class="amt">R 12,450.00</span>
                <span class="pill ai">AI</span>
            </div>
            <div class="hero-mock-row">
                <span class="desc">FNB-512 — Incoming transfer</span>
                <span class="amt">R&nbsp;&nbsp;7,000.00</span>
                <span class="pill ai">AI</span>
            </div>
            <div class="hero-mock-row">
                <span class="desc">VAT201 output — June</span>
                <span class="amt">R&nbsp;&nbsp;1,867.50</span>
                <span class="pill vat">VAT</span>
            </div>
            <div class="hero-mock-row">
                <span class="desc">Depreciation — Motor Vehicles</span>
                <span class="amt">R&nbsp;&nbsp;2,083.33</span>
                <span class="pill dep">DEP</span>
            </div>
            <div class="hero-mock-row">
                <span class="desc">PAYE/UIF — Payroll run 06-2026</span>
                <span class="amt">R 18,734.12</span>
                <span class="pill ai">AI</span>
            </div>
        </div>
    </div>
</section>

{{-- Compliance strip ─────────────────────────────────────── --}}
<section class="strip">
    <div class="strip-grid">
        <div><div class="lbl">Standard</div><div class="val">IFRS-for-SMEs</div></div>
        <div><div class="lbl">Tax</div><div class="val">SARS VAT201 · EMP201</div></div>
        <div><div class="lbl">Payroll</div><div class="val">PAYE · UIF · SDL</div></div>
        <div><div class="lbl">Consolidation</div><div class="val">IFRS 10 · Groups</div></div>
    </div>
</section>

{{-- Features ────────────────────────────────────────────── --}}
<section class="section">
    <div class="section-narrow">
        <p class="section-eyebrow">What's inside</p>
        <h2 class="section-title">One ledger. Every workflow.</h2>
        <p class="section-lead">All in the box.</p>

        <div class="feature-grid" data-gsap="stagger">

            <div class="feature-card">
                <div class="ic">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2v20M2 12h20"/></svg>
                </div>
                <h3>AI-posted transactions</h3>
                <p>Invoices, assets, depreciation — auto-journalised.</p>
            </div>

            <div class="feature-card">
                <div class="ic">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 7h18M3 12h18M3 17h12"/></svg>
                </div>
                <h3>Auto-generated chart of accounts</h3>
                <p>IFRS-correct, per industry.</p>
            </div>

            <div class="feature-card">
                <div class="ic">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
                </div>
                <h3>Semantic transaction search</h3>
                <p>Find anything in plain English.</p>
            </div>

            <div class="feature-card">
                <div class="ic">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                </div>
                <h3>Asset register &amp; depreciation</h3>
                <p>PPE classes, auto monthly entries.</p>
            </div>

            <div class="feature-card">
                <div class="ic">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                </div>
                <h3>SA payroll &amp; SARS filings</h3>
                <p>PAYE, UIF, SDL, EMP201.</p>
            </div>

            <div class="feature-card">
                <div class="ic">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="M7 14l4-4 4 4 5-5"/></svg>
                </div>
                <h3>Full reporting bundle</h3>
                <p>One-click IFRS AFS PDF.</p>
            </div>

            <div class="feature-card">
                <div class="ic">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                </div>
                <h3>Group consolidation</h3>
                <p>IFRS 10 groups, NCI, goodwill.</p>
            </div>

            <div class="feature-card">
                <div class="ic">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16v16H4z"/><path d="M4 9h16"/><path d="M9 4v16"/></svg>
                </div>
                <h3>Customers &amp; suppliers</h3>
                <p>Statements, age analysis, IFRS 9 ECL.</p>
            </div>

            <div class="feature-card">
                <div class="ic">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 7l16-4v18l-16-4z"/><path d="M4 7v10"/></svg>
                </div>
                <h3>REST &amp; GraphQL APIs</h3>
                <p>Everything via REST or GraphQL.</p>
            </div>

        </div>
    </div>
</section>

{{-- How it works ────────────────────────────────────────── --}}
<section class="section" style="background: var(--tint); border-top: 1px solid var(--line); border-bottom: 1px solid var(--line);">
    <div class="section-narrow">
        <p class="section-eyebrow">How it works</p>
        <h2 class="section-title">From upload to audit-ready.</h2>

        <div class="steps-grid">
            <div class="step-card">
                <div class="n">STEP 01</div>
                <h4>Spin up a company</h4>
                <p>IFRS chart of accounts ready.</p>
            </div>
            <div class="step-card">
                <div class="n">STEP 02</div>
                <h4>Load your data</h4>
                <p>Statements, invoices, assets, employees.</p>
            </div>
            <div class="step-card">
                <div class="n">STEP 03</div>
                <h4>AI posts the journals</h4>
                <p>Correct debits and credits, automatically.</p>
            </div>
            <div class="step-card">
                <div class="n">STEP 04</div>
                <h4>Reports, ready</h4>
                <p>Any report. Any date.</p>
            </div>
        </div>
    </div>
</section>

{{-- CTA ─────────────────────────────────────────────────── --}}
<section class="cta">
    <p style="font-size:0.72rem;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;color:rgba(255,255,255,0.65);margin:0 0 0.85rem;">Get started today</p>
    <h2>Your accounting, on <em>autopilot.</em></h2>
    <p>You strategise. AI posts.</p>
    <div style="display:flex;gap:0.75rem;justify-content:center;flex-wrap:wrap;">
        @auth
            <a href="{{ route('dashboard') }}" class="hero-btn">Open dashboard →</a>
        @else
            <a href="{{ route('register') }}" class="hero-btn">Create free account →</a>
            <a href="{{ route('login') }}" class="hero-btn-outline">Log in</a>
        @endauth
    </div>
</section>

@endsection

@push('scripts')
<script>
gsap.registerPlugin(ScrollTrigger);
document.addEventListener('DOMContentLoaded', function () {
    var heroText = document.querySelector('.hero-text');
    if (heroText) {
        var tl   = gsap.timeline({ defaults: { ease: 'expo.out' } });
        var tag  = heroText.querySelector('.hero-tag');
        var h1   = heroText.querySelector('h1');
        var p    = heroText.querySelector('p');
        var btns = heroText.querySelector('div[style*="gap"]');
        var mock = document.querySelector('.hero-mock');
        if (tag)  tl.from(tag,  { y: 20, opacity: 0, duration: 0.7 }, 0.2);
        if (h1)   tl.from(h1,   { y: 60, opacity: 0, duration: 1.0 }, '-=0.4');
        if (p)    tl.from(p,    { y: 28, opacity: 0, duration: 0.78 }, '-=0.42');
        if (btns) tl.from(btns, { y: 16, opacity: 0, duration: 0.65 }, '-=0.35');
        if (mock) tl.from(mock, { x: 40, opacity: 0, duration: 1.1, ease: 'expo.out' }, 0.3);
        if (mock) {
            gsap.from(mock.querySelectorAll('.hero-mock-row'), {
                y: 14, opacity: 0, duration: 0.55, stagger: 0.1, delay: 0.65, ease: 'expo.out',
            });
        }
    }
    document.querySelectorAll('[data-gsap="stagger"]').forEach(function (section) {
        gsap.from(section.children, {
            scrollTrigger: { trigger: section, start: 'top 80%' },
            y: 30, opacity: 0, duration: 0.65, stagger: 0.08, ease: 'expo.out',
        });
    });
    gsap.utils.toArray('.steps-grid').forEach(function (grid) {
        gsap.from(grid.children, {
            scrollTrigger: { trigger: grid, start: 'top 80%' },
            y: 30, opacity: 0, duration: 0.7, stagger: 0.12, ease: 'expo.out',
        });
    });
});
</script>
@endpush
