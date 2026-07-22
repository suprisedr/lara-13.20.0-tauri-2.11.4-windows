{{-- Shared print stylesheet for AFS financial statement tables.
     Theme: corporate AFS look — navy headings, bright-blue accents,
     light-blue column shading, hairline rules. --}}
<style>
    @page { margin: 16mm 18mm 18mm 18mm; }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: Helvetica, Arial, "DejaVu Sans", sans-serif;
        font-size: 8pt;
        color: #23282d;
        background: #fff;
        line-height: 1.45;
    }

    .page-frame { padding: 2mm; }

    /* ── Letterhead (standalone PDFs only) ──────────────────── */
    .afs-letterhead-pdf {
        width: 100%;
        border-bottom: 1.5pt solid #16355c;
        padding-bottom: 8px;
        margin-bottom: 8px;
    }
    .afs-letterhead-pdf .lh-logo  { float: left; width: 14%; padding-right: 8px; }
    .afs-letterhead-pdf .lh-logo-img { max-width: 100%; max-height: 60px; }
    .afs-letterhead-pdf .lh-left  { float: left; }
    .afs-letterhead-pdf .lh-right { float: right; text-align: right; }
    .afs-letterhead-pdf::after { content: ''; display: table; clear: both; }
    .afs-letterhead-pdf .company-name {
        font-size: 16pt; font-weight: bold; color: #16355c; letter-spacing: 0.02em;
    }
    .afs-letterhead-pdf .company-meta { font-size: 9pt; color: #4a5f78; margin-top: 2px; line-height: 1.5; }

    /* ── Report title (standalone PDFs) ─────────────────────── */
    .afs-doc-header { text-align: left; margin: 14px 0 12px; border-bottom: 0.75pt solid #b8d6ec; padding-bottom: 8px; }
    .afs-doc-title  { font-size: 18pt; font-weight: bold; color: #16355c; letter-spacing: -0.01em; text-transform: none; }
    .afs-doc-subtitle { font-size: 9.5pt; color: #0079c8; margin-top: 3px; }

    /* ── Financial statement table ───────────────────────────── */
    .afs-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 6pt;
    }

    /* Column header row */
    .afs-table thead th {
        padding: 4pt 4pt 5pt 0;
        font-weight: normal;
        color: #4a5f78;
        border-bottom: 0.75pt solid #16355c;
        text-align: left;
        background: #fff;
        vertical-align: bottom;
    }
    .afs-table thead th.afs-col-label  { font-style: normal; }
    .afs-table thead th.afs-col-note   { text-align: center; width: 9%; }
    .afs-table thead th.afs-col-amount { text-align: right; width: 18%; font-weight: bold; color: #16355c; padding-right: 4pt; }

    /* Main section header */
    .afs-table tr.afs-section-main td {
        padding: 8pt 0 3pt 0;
        font-weight: bold;
        font-size: 6pt;
        background: #fff;
        color: #16355c;
    }

    /* Sub-section header */
    .afs-table tr.afs-section-sub td {
        padding: 4pt 0 3pt 8pt;
        font-weight: bold;
        font-size: 6pt;
        background: #fff;
        color: #0079c8;
    }

    /* Ordinary line item */
    .afs-table tr.afs-item-row td {
        padding: 3.5pt 4pt 3.5pt 18pt;
        border-bottom: 0.4pt solid #ddebf5;
        color: #23282d;
    }

    /* Last item in a group */
    .afs-table tr.afs-item-last td {
        border-bottom: 0.6pt solid #9cc3e0;
    }

    /* Intermediate subtotal */
    .afs-table tr.afs-subtotal td {
        padding: 4pt 4pt 4pt 18pt;
        font-weight: bold;
        color: #16355c;
        border-bottom: 0.75pt solid #16355c;
        background: #fff;
    }

    /* Named activity subtotal */
    .afs-table tr.afs-named-subtotal td {
        padding: 4pt 4pt;
        font-weight: bold;
        color: #16355c;
        border-bottom: 0.75pt solid #16355c;
        background: #fff;
    }

    /* Grand total */
    .afs-table tr.afs-grand-total td {
        padding: 5pt 4pt;
        font-weight: bold;
        color: #16355c;
        border-top: 0.75pt solid #16355c;
        border-bottom: 1.5pt solid #16355c;
        background: #e7f3fb;
        text-transform: none;
    }

    /* Amount cells */
    .afs-table td.afs-amount {
        text-align: right;
        padding-right: 4pt;
        white-space: nowrap;
    }

    /* Note reference cells */
    .afs-table td.afs-note {
        text-align: center;
        color: #4a5f78;
    }

    .afs-table td.afs-empty    { font-style: italic; color: #8aa2b8; }
    .afs-table td.afs-dim      { color: #a9bccd; }
    .afs-table td.afs-abnormal { color: #b91c1c; }

    .afs-warning {
        margin: 8pt 0 0;
        padding: 5pt 8pt;
        border-left: 2pt solid #0079c8;
        background: #eef6fc;
        font-size: 6pt;
        color: #16355c;
    }

    /* ── Footer ─────────────────────────────────────────────── */
    .afs-footer {
        margin-top: 16pt;
        padding-top: 6pt;
        border-top: 0.5pt solid #c9dff0;
        font-size: 9pt;
        color: #7a90a5;
        text-align: center;
    }
</style>
