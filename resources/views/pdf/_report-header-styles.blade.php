{{-- Shared CSS for the professional financial-report header.
     Theme: navy + bright-blue corporate AFS look. --}}
<style>
    .rpt-letterhead {
        width: 100%;
        border-bottom: 2px solid #16355c;
        padding-bottom: 8px;
        margin-bottom: 8px;
    }
    .rpt-letterhead::after { content: ''; display: table; clear: both; }
    .rpt-lh-logo  { float: left; width: 15%; padding-right: 8px; }
    .rpt-logo-img { max-width: 100%; max-height: 56px; }
    .rpt-lh-left  { float: left; width: 45%; }
    .rpt-lh-right { float: right; width: 40%; text-align: right; }

    .rpt-company-name {
        font-size: 12pt;
        font-weight: bold;
        letter-spacing: 0.01em;
        color: #16355c;
        line-height: 1.2;
    }
    .rpt-company-sub {
        font-size: 7pt;
        font-weight: bold;
        color: #0079c8;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin-top: 2px;
    }
    .rpt-company-meta {
        font-size: 6.5pt;
        color: #4a5f78;
        line-height: 1.45;
        margin-top: 2px;
    }

    .rpt-doc-header {
        text-align: left;
        margin: 12px 0 8px;
        border-bottom: 1px solid #b8d6ec;
        padding-bottom: 8px;
    }
    .rpt-doc-title {
        font-size: 13pt;
        font-weight: bold;
        letter-spacing: -0.01em;
        text-transform: none;
        color: #16355c;
    }
    .rpt-doc-subtitle {
        font-size: 7.5pt;
        color: #0079c8;
        margin-top: 3px;
        font-style: normal;
    }
    .rpt-doc-currency {
        font-size: 6.5pt;
        color: #7a90a5;
        margin-top: 4px;
        letter-spacing: 0.02em;
    }

</style>
