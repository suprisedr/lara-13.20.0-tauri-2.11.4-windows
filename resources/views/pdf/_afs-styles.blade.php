{{-- Shared print stylesheet for AFS financial statements.

     Reproduces CGL-YE25-Annual-Financial-Statements.docx exactly.
     Every value below is measured from that file, not estimated.

     ── Page (word/document.xml sectPr, 15 of 16 sections) ──────
       pgSz   28800 x 16200 twips landscape = 20in x 11.25in
                                            = 508mm x 285.75mm
       pgMar  top/left/right 1500 tw = 26.46mm
              bottom          1095 tw = 19.32mm
              header           165 tw =  2.91mm
              footer           450 tw =  7.94mm

     ── Type (word/styles.xml docDefaults + run properties) ─────
       Face          Century Gothic, registered in pdf/_fonts
       Body          w:sz 21 -> 10.5pt, #191919
       Leading       w:line 270 exact -> 13.5pt (1.2857)
       Title         w:sz 50 -> 25pt bold, #1A345B
       Date line     w:sz 23 -> 11.5pt regular, #1A345B
       Section head  w:sz 26 -> 13pt bold, #005BF0
       Footnote      w:sz 14 -> 7pt, #191919

     ── Statement table (measured on the income statement) ──────
       Width         12150 tw = 8.4375in = 214.31mm. The table
                     occupies less than half the 17.92in text
                     area and sits flush left.
       Columns       7725 / 795 / 1815 / 1815 tw
                  =  63.58% / 6.54% / 14.94% / 14.94%
                     (cash flow: 7500/750/1950/1950)
       Cells         no vertical padding; row height comes from
                     the 13.5pt leading. w:vAlign bottom.
       Padding       right 75 tw = 3.75pt, except the current-year
                     column at 90 tw = 4.5pt.

     ── The two things most easily got wrong ────────────────────
       1. The header is NOT a full blue band. Only the
          current-year cell is filled #005BF0 with white text and
          a #005BF0 rule; the other three header cells are plain
          with a black hairline.
       2. There are NO double rules anywhere in the document, not
          even under the grand total. Every rule is the same
          w:sz 4 = 0.5pt solid #000000.

       The current-year column carries a continuous #EAF8FB tint
       AND bold figures on every row, including spacer rows. The
       comparative column is plain and regular weight throughout.

     dompdf has no CSS custom properties, so the token values
     from resources/css/app.css are repeated here as literals. --}}
@include('pdf._fonts')

<style>
    /* dompdf ignores @page margins here — measured content sat at
       exactly the .page-frame padding, with the @page margin
       contributing nothing. The page inset is therefore carried by
       .page-frame below, where it is deterministic. @page keeps the
       size only; the controller also sets it explicitly via
       setPaper([0, 0, 1440, 810]). */
    @page {
        size: 508mm 285.75mm;
        margin: 0;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: "Century Gothic", Helvetica, Arial, "DejaVu Sans", sans-serif;
        font-size: 10.5pt;
        line-height: 1.2857;
        color: #191919;
        background: #fff;
    }

    /* Page inset. Reproduces the document's own margins —
       pgMar 1500 twips = 26.46mm on three sides, 1095 = 19.32mm at
       the foot — applied here rather than via @page because dompdf
       honours padding reliably and @page margin it does not. */
    .page-frame { padding: 26.46mm 26.46mm 19.32mm 26.46mm; }

    /* ── Letterhead (standalone PDFs only) ──────────────────── */
    .afs-letterhead-pdf {
        width: 100%;
        border-bottom: 0.375pt solid #1a345b;
        padding-bottom: 6pt;
        margin-bottom: 6pt;
    }
    .afs-letterhead-pdf .lh-logo  { float: left; width: 14%; padding-right: 8pt; }
    .afs-letterhead-pdf .lh-logo-img { max-width: 100%; max-height: 60px; }
    .afs-letterhead-pdf .lh-left  { float: left; }
    .afs-letterhead-pdf .lh-right { float: right; text-align: right; }
    .afs-letterhead-pdf::after { content: ''; display: table; clear: both; }
    .afs-letterhead-pdf .company-name {
        font-size: 13pt; font-weight: bold; color: #1a345b; line-height: 1.0714;
    }
    .afs-letterhead-pdf .company-meta {
        font-size: 10pt; color: #1a345b; margin-top: 3pt; line-height: 1.2857;
    }
    .afs-letterhead-pdf .company-sub {
        font-size: 10pt; font-weight: bold; color: #005bf0;
        margin-top: 2pt; line-height: 1.2857;
    }
    .afs-letterhead-pdf .company-meta strong { color: #1a345b; font-weight: bold; }

    /* Currency / rounding basis, carried over from the report header */
    .afs-doc-currency {
        font-size: 10pt;
        color: #1a345b;
        margin-top: 3pt;
    }

    /* ── Statement title block ───────────────────────────────
       25pt bold navy over an 11.5pt regular navy date line,
       with no rule between them. */
    .afs-doc-header {
        text-align: left;
        margin: 0 0 13.5pt;
        border: none;
        padding: 0;
    }
    .afs-doc-title {
        font-size: 25pt;
        font-weight: bold;
        color: #1a345b;
        line-height: 1.1;
        letter-spacing: 0;
        text-transform: none;
    }
    .afs-doc-subtitle {
        font-size: 11.5pt;
        font-weight: normal;
        color: #1a345b;
        margin-top: 2pt;
    }

    /* ── Prose pages in the bundle ───────────────────────────── */
    h1.afs-title  { font-size: 25pt;   font-weight: bold; color: #1a345b; line-height: 1.1; margin-bottom: 6pt; }
    h2.afs-sub    { font-size: 13pt;   font-weight: bold; color: #005bf0; margin: 13.5pt 0 3pt; }
    p.afs-para    { font-size: 10.5pt; color: #191919; line-height: 1.2857; margin-bottom: 6.75pt;
                    orphans: 2; widows: 2; }

    /* ── Statement table ─────────────────────────────────────
       12150 twips = 214.31mm, flush left. Fixed layout so the
       measured column proportions hold. */
    .afs-table {
        width: 214.31mm;
        table-layout: fixed;
        border-collapse: collapse;
        border: none;
        font-size: 10.5pt;
        margin: 0;
    }
    .afs-table td, .afs-table th {
        border: none;
        vertical-align: bottom;
        padding: 0 3.75pt 0 0;
        line-height: 1.2857;
    }

    /* Column widths — 7725 / 795 / 1815 / 1815 twips.
       Label is left auto so the 3-column (no comparative)
       layout absorbs the freed width correctly. */
    .afs-table th.afs-col-note   { width: 6.54%; }
    .afs-table th.afs-col-amount { width: 14.94%; }

    /* ── Header row ──────────────────────────────────────────
       Plain cells with a black hairline; only the current-year
       cell is filled. All right-aligned, bottom-aligned. */
    .afs-table thead th {
        font-size: 10.5pt;
        font-weight: normal;
        color: #000000;
        background: #fff;
        text-align: right;
        vertical-align: bottom;
        border-bottom: 0.5pt solid #000000;
        padding: 0 3.75pt 0 0;
    }
    .afs-table thead th.afs-col-label { text-align: left; }

    /* Current-year header: #005BF0 fill, white text, blue rule */
    .afs-table thead th:nth-child(3) {
        background: #005bf0;
        color: #ffffff;
        border-bottom-color: #005bf0;
        padding-right: 4.5pt;
    }

    /* ── Body rows ───────────────────────────────────────────
       No vertical padding anywhere; the 13.5pt leading sets the
       row height, exactly as w:line 270 does in the document. */
    .afs-table tbody td,
    .afs-table tfoot td { color: #000000; }

    /* Current-year column: continuous tint, bold, wider gutter.
       Declared before the row variants so nothing interrupts it. */
    .afs-table tbody td:nth-child(3),
    .afs-table tfoot td:nth-child(3) {
        background: #eaf8fb;
        font-weight: bold;
        text-align: right;
        padding-right: 4.5pt;
    }
    /* Comparative column: plain, regular weight */
    .afs-table tbody td:nth-child(4),
    .afs-table tfoot td:nth-child(4) {
        text-align: right;
    }

    /* Emphasis labels — section heads, subtotals and the grand
       total all share one treatment: bold #005BF0. */
    .afs-table tr.afs-section-main   td:first-child,
    .afs-table tr.afs-section-sub    td:first-child,
    .afs-table tr.afs-subtotal       td:first-child,
    .afs-table tr.afs-named-subtotal td:first-child,
    .afs-table tr.afs-grand-total    td:first-child {
        font-weight: bold;
        color: #005bf0;
    }

    /* Hairline closing a group or a total — spans every cell,
       always 0.5pt solid black. There is no heavier or double
       rule anywhere in the source document. */
    .afs-table tr.afs-item-last      td,
    .afs-table tr.afs-subtotal       td,
    .afs-table tr.afs-named-subtotal td,
    .afs-table tr.afs-grand-total    td {
        border-bottom: 0.5pt solid #000000;
    }

    /* Spacer row beneath the header (the document carries an
       empty row there, tint unbroken). */
    .afs-table tbody tr:first-child td { padding-top: 13.5pt; }

    /* ── Matrix variant: statement of changes in equity ──────
       The document's SOCE is a different animal from the other
       primary statements: 9 columns across the full 25800 tw
       (17.92in) text area, and — measured cell by cell — it
       carries NO #EAF8FB tint and NO filled header cell
       anywhere. Emphasis rows put a bold #005BF0 label beside
       bold black figures; ordinary rows are regular throughout.
       Applying the single-column treatment here would band an
       arbitrary equity component, so it is switched off. */
    /* The document's SOCE spans the full 25800 tw because it has
       nine columns. Sizing by column count instead of forcing that
       width keeps a 4- or 5-column matrix from spreading apart:
       label 8400 tw = 148mm, amount columns ~2200 tw = 38.8mm,
       both taken from the source table's grid. */
    .afs-table.afs-matrix { width: auto; table-layout: auto; }
    .afs-table.afs-matrix thead th.afs-col-label  { width: 148mm; }
    .afs-table.afs-matrix thead th.afs-col-amount { width: 38.8mm; }

    .afs-table.afs-matrix thead th,
    .afs-table.afs-matrix thead th:nth-child(3) {
        background: #fff;
        color: #000000;
        border-bottom: 0.5pt solid #000000;
        text-align: right;
        padding-right: 3.75pt;
    }
    .afs-table.afs-matrix thead th.afs-col-label { text-align: left; }

    .afs-table.afs-matrix tbody td:nth-child(3),
    .afs-table.afs-matrix tfoot td:nth-child(3) {
        background: transparent;
        font-weight: inherit;
        padding-right: 3.75pt;
    }
    /* Every figure column is right-aligned in the matrix */
    .afs-table.afs-matrix tbody td + td,
    .afs-table.afs-matrix tfoot td + td { text-align: right; }

    /* Emphasis rows: bold blue label, bold black figures */
    .afs-table.afs-matrix tr.afs-subtotal td,
    .afs-table.afs-matrix tr.afs-named-subtotal td,
    .afs-table.afs-matrix tr.afs-grand-total td { font-weight: bold; }

    /* Amount / note cells */
    .afs-table td.afs-amount {
        text-align: right;
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
    }
    .afs-table td.afs-note  { text-align: left; color: #000000; }
    .afs-table td.afs-name  { text-align: left; }
    .afs-table td.afs-right { text-align: right; }

    .afs-table td.afs-empty    { color: #5a7186; }
    .afs-table td.afs-dim      { color: #5a7186; }
    .afs-table td.afs-abnormal { color: #000000; }

    /* Footnotes below a statement — 7pt, as in the document */
    .afs-details {
        margin-top: 6.75pt;
        font-size: 7pt;
        color: #191919;
        line-height: 1.2857;
    }

    .afs-continued { font-size: 10.5pt; color: #1a345b; }

    .afs-warning {
        margin: 13.5pt 0 0;
        padding: 5pt 8pt;
        border-left: 2pt solid #005bf0;
        background: #eaf8fb;
        font-size: 7pt;
        color: #1a345b;
    }

    /* ── Running footer ─────────────────────────────────────── */
    .afs-footer {
        margin-top: 13.5pt;
        padding-top: 4pt;
        border-top: 0.375pt solid #1a345b;
        font-size: 10pt;
        color: #1a345b;
        text-align: left;
    }
</style>
