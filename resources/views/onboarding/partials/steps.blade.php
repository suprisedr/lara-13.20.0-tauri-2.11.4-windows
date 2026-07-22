@php
    $steps = [
        1 => 'Identity',
        2 => 'Tax & Compliance',
        3 => 'Financial Setup',
    ];
@endphp
<div style="display:flex;align-items:center;gap:0;margin-top:1.25rem;">
    @foreach ($steps as $num => $label)
        @php
            $done   = $num < $current;
            $active = $num === $current;
            $last   = $num === count($steps);
        @endphp
        <div style="display:flex;align-items:center;flex:{{ $last ? '0 0 auto' : '1' }};">
            <div style="display:flex;flex-direction:column;align-items:center;">
                <div style="width:1.75rem;height:1.75rem;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:0.7rem;font-weight:700;
                    {{ $done   ? 'background:#fff;color:#5e17eb;'                                                          : '' }}
                    {{ $active ? 'background:#5e17eb;color:#fff;border:2px solid rgba(255,255,255,0.5);box-shadow:0 0 0 3px rgba(255,255,255,0.2);' : '' }}
                    {{ !$done && !$active ? 'background:rgba(255,255,255,0.12);color:rgba(255,255,255,0.45);border:1.5px solid rgba(255,255,255,0.25);' : '' }}
                ">
                    @if ($done)
                        <svg width="10" height="10" fill="none" stroke="#5e17eb" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                    @else
                        {{ $num }}
                    @endif
                </div>
                <span style="font-size:0.6rem;font-weight:700;margin-top:0.3rem;white-space:nowrap;
                    {{ $active ? 'color:#fff;'                      : '' }}
                    {{ $done   ? 'color:rgba(255,255,255,0.75);'    : '' }}
                    {{ !$done && !$active ? 'color:rgba(255,255,255,0.35);' : '' }}
                ">{{ $label }}</span>
            </div>
            @if (!$last)
                <div style="flex:1;height:1.5px;background:{{ $done ? 'rgba(255,255,255,0.6)' : 'rgba(255,255,255,0.2)' }};margin:0 0.5rem;margin-bottom:1.1rem;"></div>
            @endif
        </div>
    @endforeach
</div>
