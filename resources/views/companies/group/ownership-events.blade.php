@extends('layouts.public')

@section('title', $company->registered_name . ' — Ownership Changes')
@section('meta-robots', 'noindex, nofollow')

@php
    use App\Models\SubsidiaryOwnershipEvent;
@endphp

@push('styles')
    @include('companies._styles')
    <style>
        .co-topbar { padding: 1rem 2rem; }
        .co-main { padding: 1.25rem 1.5rem; }
        .grp-card { background:#fff; border:1px solid rgba(0, 91, 240,0.1); border-radius:0; padding:1.1rem 1.25rem; margin-bottom:1.25rem; }
        .grp-card h3 { font-size:0.95rem; font-weight:800; margin:0 0 0.2rem; color:#191919; }
        .grp-card p.sub { font-size:0.78rem; color:#888; margin:0 0 1rem; }
        .oe-grid { display:grid; grid-template-columns:repeat(4, 1fr); gap:0.75rem 0.9rem; }
        .oe-field label { display:block; font-size:0.64rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#191919; margin-bottom:0.25rem; }
        .oe-field .hint { font-size:0.7rem; color:#2674f2; font-weight:600; }
        .oe-input { width:100%; box-sizing:border-box; border:1.5px solid #d3e2f5; border-radius:0; padding:0.4rem 0.55rem; font-size:0.8rem; font-family:inherit; }
        .oe-input:focus { border-color:#005bf0; outline:none; }
        .oe-btn { background:#005bf0; color:#fff; border:none; border-radius:0; padding:0.5rem 1rem; font-size:0.8rem; font-weight:700; cursor:pointer; font-family:inherit; }
        .oe-btn:hover { background:#0047c4; }
        .oe-table { width:100%; border-collapse:collapse; }
        .oe-table th { text-align:left; font-size:0.7rem; font-weight:800; letter-spacing:0.08em; text-transform:uppercase; color:#6f869b; padding:0.45rem 0.6rem; border-bottom:1px solid #d3e2f5; }
        .oe-table td { padding:0.55rem 0.6rem; font-size:0.8rem; border-bottom:1px solid #f4fafc; vertical-align:top; }
        .oe-badge { display:inline-block; font-size:0.7rem; font-weight:800; border-radius:0; padding:0.1rem 0.4rem; text-transform:uppercase; letter-spacing:0.04em; }
        .oe-acq { background:#dcfce7; color:#166534; }
        .oe-inc { background:#dbeafe; color:#1e40af; }
        .oe-dec { background:#fef9c3; color:#854d0e; }
        .oe-dis { background:#fee2e2; color:#b91c1c; }
        .oe-remove { background:none; border:none; color:#b91c1c; font-size:1rem; cursor:pointer; line-height:1; }
    </style>
@endpush

@section('content')
    <div class="co-wrap">
        @include('companies._topbar')

        <div class="co-body">
            @include('companies._sidebar')
            <main class="co-main">
                @if (session('success'))
                    <div style="background:#dcfce7;border:1px solid #86efac;color:#166534;padding:0.6rem 0.9rem;border-radius:0;font-size:0.82rem;margin-bottom:1rem;">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div style="background:#fee2e2;border:1px solid #fca5a5;color:#b91c1c;padding:0.6rem 0.9rem;border-radius:0;font-size:0.82rem;margin-bottom:1rem;">{{ $errors->first() }}</div>
                @endif

                <div class="grp-card">
                    <h3>Record an ownership change</h3>
                    <p class="sub">
                        <strong>Acquisition</strong> sets goodwill (frozen) and begins consolidation. An
                        <strong>increase</strong>/<strong>decrease</strong> while control is retained is an equity transaction
                        (no goodwill change, difference to equity). A <strong>disposal</strong> that loses control
                        deconsolidates the subsidiary and recognises a gain/loss in profit or loss.
                        Net assets at event = the subsidiary's equity (identifiable net assets) on that date.
                    </p>

                    <form method="POST" action="{{ route('companies.group.ownership-events.store', $company) }}">
                        @csrf
                        <div class="oe-grid">
                            <div class="oe-field">
                                <label>Subsidiary</label>
                                <select class="oe-input" name="subsidiary_company_id" required>
                                    <option value="">Select…</option>
                                    @if ($subsidiaries->isNotEmpty())
                                        <optgroup label="In group">
                                            @foreach ($subsidiaries as $s)
                                                <option value="{{ $s->id }}">{{ $s->registered_name }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                    @if ($available->isNotEmpty())
                                        <optgroup label="Available to acquire">
                                            @foreach ($available as $s)
                                                <option value="{{ $s->id }}">{{ $s->registered_name }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                </select>
                            </div>
                            <div class="oe-field">
                                <label>Event type</label>
                                <select class="oe-input" name="type" required>
                                    @foreach (SubsidiaryOwnershipEvent::TYPES as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="oe-field">
                                <label>Date</label>
                                <input class="oe-input" type="date" name="event_date" value="{{ now()->toDateString() }}" required>
                            </div>
                            <div class="oe-field">
                                <label>Net assets at event</label>
                                <input class="oe-input" type="number" step="0.01" name="equity_at_event" placeholder="0.00">
                            </div>

                            <div class="oe-field">
                                <label>Holding before %</label>
                                <input class="oe-input" type="number" step="0.01" min="0" max="100" name="ownership_before" value="0" required>
                            </div>
                            <div class="oe-field">
                                <label>Holding after %</label>
                                <input class="oe-input" type="number" step="0.01" min="0" max="100" name="ownership_after" value="0" required>
                            </div>
                            <div class="oe-field">
                                <label>Consideration <span class="hint">paid / received</span></label>
                                <input class="oe-input" type="number" step="0.01" min="0" name="consideration" placeholder="0.00">
                            </div>
                            <div class="oe-field">
                                <label>FV previously held <span class="hint">step acq.</span></label>
                                <input class="oe-input" type="number" step="0.01" min="0" name="fair_value_previously_held" placeholder="0.00">
                            </div>

                            <div class="oe-field">
                                <label>Carrying of prev. interest <span class="hint">step acq.</span></label>
                                <input class="oe-input" type="number" step="0.01" min="0" name="carrying_previously_held" placeholder="0.00">
                            </div>
                            <div class="oe-field">
                                <label>FV retained <span class="hint">disposal</span></label>
                                <input class="oe-input" type="number" step="0.01" min="0" name="fair_value_retained" placeholder="0.00">
                            </div>
                            <div class="oe-field">
                                <label>Goodwill derecognised <span class="hint">disposal</span></label>
                                <input class="oe-input" type="number" step="0.01" name="goodwill_derecognised" placeholder="0.00">
                            </div>
                            <div class="oe-field">
                                <label>Notes</label>
                                <input class="oe-input" type="text" name="notes" placeholder="optional">
                            </div>
                        </div>
                        <div style="margin-top:1rem;">
                            <button type="submit" class="oe-btn">Record event</button>
                        </div>
                    </form>
                </div>

                <div class="grp-card">
                    <h3>Ownership history</h3>
                    @if ($events->isEmpty())
                        <p style="font-size:0.82rem;color:#888;margin:0;">No ownership events recorded. Subsidiaries added under Group Structure use a static holding.</p>
                    @else
                        <table class="oe-table">
                            <thead>
                                <tr>
                                    <th style="width:100px;">Date</th>
                                    <th>Subsidiary</th>
                                    <th>Type</th>
                                    <th style="width:120px;">Holding</th>
                                    <th>Effect</th>
                                    <th style="width:40px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($events as $event)
                                    @php
                                        $cls = ['acquisition'=>'oe-acq','increase'=>'oe-inc','decrease'=>'oe-dec','disposal'=>'oe-dis'][$event->type] ?? '';
                                    @endphp
                                    <tr>
                                        <td>{{ $event->event_date->format('d M Y') }}</td>
                                        <td><strong>{{ $event->subsidiary?->registered_name ?? '—' }}</strong>@if($event->notes)<div style="font-size:0.7rem;color:#6f869b;">{{ $event->notes }}</div>@endif</td>
                                        <td><span class="oe-badge {{ $cls }}">{{ ucfirst($event->type) }}</span></td>
                                        <td>{{ rtrim(rtrim(number_format($event->ownership_before, 2), '0'), '.') }}% → {{ rtrim(rtrim(number_format($event->ownership_after, 2), '0'), '.') }}%</td>
                                        <td style="font-size:0.75rem;">
                                            @if ($event->type === 'acquisition')
                                                Goodwill <strong>R {{ number_format($event->goodwillOnAcquisition(), 2) }}</strong>
                                                @if ($event->remeasurementGain() != 0.0)<br>Remeasurement gain R {{ number_format($event->remeasurementGain(), 2) }}@endif
                                            @elseif (in_array($event->type, ['increase','decrease']))
                                                Equity reserve <strong>R {{ number_format($event->equityAdjustment(), 2) }}</strong>
                                            @elseif ($event->type === 'disposal')
                                                Gain/(loss) <strong>R {{ number_format($event->disposalGain(), 2) }}</strong>
                                            @endif
                                        </td>
                                        <td>
                                            <form method="POST" action="{{ route('companies.group.ownership-events.destroy', [$company, $event]) }}" onsubmit="return false" data-confirm-label="Group Accounting" data-confirm-title="Delete Ownership Event" data-confirm-body="This event will be deleted and subsidiary state will be recomputed." data-confirm-text="Delete" data-confirm-danger="1">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="oe-remove" title="Delete">&times;</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </main>
        </div>
    </div>
@endsection
