@php
    $steps = [
        1 => 'Identity',
        2 => 'Tax & Compliance',
        3 => 'Financial Setup',
    ];
@endphp
<div style="display:flex;align-items:center;gap:0;margin-bottom:1.75rem;">
    @foreach ($steps as $num => $label)
        @php
            $done   = $num < $current;
            $active = $num === $current;
            $last   = $num === count($steps);
        @endphp
        <div style="display:flex;align-items:center;flex:{{ $last ? '0 0 auto' : '1' }};">
            <div style="display:flex;flex-direction:column;align-items:center;">
                <div style="width:1.5rem;height:1.5rem;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:0.7rem;font-weight:700;
                    {{ $done   ? 'background:#000;color:#fff;'                                          : '' }}
                    {{ $active ? 'background:#005bf0;color:#fff;'                                       : '' }}
                    {{ !$done && !$active ? 'background:#f4fafc;color:#6f869b;border:1px solid #d3e2f5;' : '' }}
                ">
                    @if ($done)
                        <svg width="10" height="10" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                    @else
                        {{ $num }}
                    @endif
                </div>
                <span style="font-size:0.55rem;font-weight:700;margin-top:0.25rem;white-space:nowrap;letter-spacing:0.05em;text-transform:uppercase;
                    {{ $active ? 'color:#005bf0;' : '' }}
                    {{ $done   ? 'color:#000;'    : '' }}
                    {{ !$done && !$active ? 'color:#6f869b;' : '' }}
                ">{{ $label }}</span>
            </div>
            @if (!$last)
                <div style="flex:1;height:1.5px;background:{{ $done ? '#000' : '#d3e2f5' }};margin:0 0.5rem;margin-bottom:1rem;"></div>
            @endif
        </div>
    @endforeach
</div>
