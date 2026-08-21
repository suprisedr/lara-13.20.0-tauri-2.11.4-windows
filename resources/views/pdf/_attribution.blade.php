{{-- Generation attribution, repeated on every page.

     Self-contained: carries its own <style> so it works in any PDF
     document regardless of which stylesheet that document uses.

     Defaults suit the 508mm landscape report page (inset 26.46mm).
     A4 documents pass their own geometry:
         @include('pdf._attribution', ['attrLeft' => '15mm', 'attrWidth' => '180mm'])

     dompdf repeats `position: fixed` elements on each page, so one
     block covers a report of any length.

     Laid out with a table, not a float: a floated child inside a
     fixed block makes dompdf emit phantom pages — a one-page cash
     flow rendered as five. Its table layout is reliable here. --}}
@php
    $attrLeft   = $attrLeft   ?? '26.46mm';
    $attrWidth  = $attrWidth  ?? '455.08mm';
    $attrBottom = $attrBottom ?? '7.94mm';
@endphp
<style>
    .pdf-attribution {
        position: fixed;
        bottom: {{ $attrBottom }};
        left: {{ $attrLeft }};
        width: {{ $attrWidth }};
        border-top: 0.375pt solid #d3e2f5;
        padding-top: 3pt;
    }
    .pdf-attribution-row { width: 100%; border-collapse: collapse; border: none; }
    .pdf-attribution-row td {
        border: none;
        padding: 0;
        font-size: 7pt;
        color: #5a7186;
    }
    .pdf-attribution-page { text-align: right; }
    .pdf-attribution-page:after { content: counter(page); }
</style>
<div class="pdf-attribution">
    <table class="pdf-attribution-row">
        <tr>
            <td class="pdf-attribution-mark">Generated using Chainbook Intelligence</td>
            <td class="pdf-attribution-page"></td>
        </tr>
    </table>
</div>
