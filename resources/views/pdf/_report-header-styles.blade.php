{{-- Shared CSS for the financial-report header.

     Modelled on the running header in
     CGL-YE25-Annual-Financial-Statements.docx (word/header1.xml):

       Header text   w:sz 20 -> 10pt bold, #1A345B, leading 1.0714
       Page chip     w:sz 16 -> 8pt bold, #FFFFFF on a #1A345B fill,
                     centred, 60 twips (3pt) vertical padding
       Rule beneath  w:sz 3  -> 0.375pt solid #1A345B

     Type steps below follow the docx scale (25 / 13 / 11.5 / 10.5 /
     7pt), reduced proportionally for portrait A4. See
     resources/views/pdf/_afs-styles.blade.php for the full mapping. --}}
@include('pdf._fonts')

<style>
    .rpt-letterhead {
        width: 100%;
        border-bottom: 0.375pt solid #1a345b;
        padding-bottom: 8px;
        margin-bottom: 8px;
    }
    .rpt-letterhead::after { content: ''; display: table; clear: both; }
    .rpt-lh-logo  { float: left; width: 15%; padding-right: 8px; }
    .rpt-logo-img { max-width: 100%; max-height: 56px; }
    .rpt-lh-left  { float: left; width: 45%; }
    .rpt-lh-right { float: right; width: 40%; text-align: right; }

    .rpt-company-name {
        font-size: 13pt;
        font-weight: bold;
        letter-spacing: 0;
        color: #1a345b;
        line-height: 1.0714;
    }
    .rpt-company-sub {
        font-size: 10pt;
        font-weight: bold;
        color: #005bf0;
        text-transform: none;
        letter-spacing: 0;
        margin-top: 2px;
    }
    .rpt-company-meta {
        font-size: 10pt;
        color: #5a7186;
        line-height: 1.2857;
        margin-top: 2px;
    }

    /* Statement title: docx sets 25pt bold navy over an 11.5pt
       regular navy date line, with no rule between the two. */
    .rpt-doc-header {
        text-align: left;
        margin: 0 0 13.5pt;
        border-bottom: none;
        padding-bottom: 0;
    }
    .rpt-doc-title {
        font-size: 25pt;
        font-weight: bold;
        letter-spacing: 0;
        text-transform: none;
        color: #1a345b;
        line-height: 1.1;
    }
    .rpt-doc-subtitle {
        font-size: 11.5pt;
        font-weight: normal;
        color: #1a345b;
        margin-top: 3px;
        font-style: normal;
    }
    .rpt-doc-currency {
        font-size: 10pt;
        color: #5a7186;
        margin-top: 4px;
        letter-spacing: 0;
    }

    /* Page-number chip — white on navy, as in word/header1.xml */
    .rpt-folio {
        display: inline-block;
        min-width: 18pt;
        padding: 3pt 4pt;
        background: #1a345b;
        color: #ffffff;
        font-size: 8pt;
        font-weight: bold;
        text-align: center;
        line-height: 1.0714;
    }
</style>
