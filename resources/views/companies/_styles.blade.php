<style>
    .co-wrap {
        min-height: 100vh;
        background: #f7fbfd;
        display: flex;
        flex-direction: column;
        padding-top: 2.5rem;
        font-family: "Century Gothic", "URW Gothic", "Avant Garde", Futura, "Avenir Next", Avenir, "Trebuchet MS", Helvetica, Arial, "DejaVu Sans", sans-serif;
    }

    /* Compact topbar */
    .co-topbar {
        background: #1a345b;
        border-bottom: none;
        padding: 5pt 16pt;
        margin: 0;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 100;
        overflow: hidden;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        gap: 8pt;
    }

    .co-topbar > div {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 8pt;
        width: 100%;
    }

    .co-topbar .co-topbar-back {
        font-size: 10pt;
        font-weight: 600;
        letter-spacing: 0.04em;
        color: #9ec1f5;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 2pt;
        padding: 2pt 4pt;
        transition: color 0.15s;
        white-space: nowrap;
    }

    .co-topbar .co-topbar-back:hover {
        color: #fff;
    }

    .co-topbar .co-topbar-nav {
        display: inline-flex;
        align-items: center;
        gap: 1pt;
        flex-shrink: 0;
    }

    .co-topbar .co-topbar-nav-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 18pt;
        height: 18pt;
        background: transparent;
        border: 1px solid transparent;
        color: #9ec1f5;
        cursor: pointer;
        padding: 0;
        transition: background 0.15s, color 0.15s, border-color 0.15s;
    }

    .co-topbar .co-topbar-nav-btn:hover {
        background: rgba(255,255,255,0.12);
        border-color: #5a7186;
        color: #fff;
    }

    .co-topbar .co-topbar-nav-btn:active {
        background: rgba(255,255,255,0.2);
    }

    .co-topbar .co-topbar-divider {
        width: 1px;
        height: 10pt;
        background: #5a7186;
        flex-shrink: 0;
    }

    .co-topbar .co-topbar-title {
        font-size: 8pt;
        font-weight: 700;
        color: #fff;
        margin: 0;
        letter-spacing: -0.01em;
        white-space: nowrap;
    }

    .co-topbar .co-topbar-meta {
        font-size: 10pt;
        color: #2674f2;
        margin: 0;
        white-space: nowrap;
    }

    .co-topbar .co-topbar-actions {
        margin-left: auto;
        display: flex;
        align-items: center;
        gap: 4pt;
    }

    .co-topbar .co-topbar-actions a,
    .co-topbar .co-topbar-actions button {
        display: inline-flex;
        align-items: center;
        gap: 2pt;
        font-size: 10pt;
        font-weight: 600;
        padding: 2pt 6pt;
        text-decoration: none;
        border: 1px solid #5a7186;
        color: #9ec1f5;
        background: transparent;
        transition: background 0.15s, border-color 0.15s, color 0.15s;
        white-space: nowrap;
    }

    .co-topbar .co-topbar-actions a:hover,
    .co-topbar .co-topbar-actions button:hover {
        background: rgba(255,255,255,0.1);
        border-color: #2674f2;
        color: #fff;
    }

    @media (max-width: 760px) {
        .co-topbar {
            margin: 0;
            padding: 0.5rem 1rem;
        }

        .co-topbar > div {
            flex-wrap: wrap;
            gap: 0.4rem;
        }

        .co-topbar .co-topbar-meta {
            display: none;
        }
    }

    /* ── Sidebar + main layout ──────────────── */
    .co-body {
        display: flex;
        flex: 1;
    }

    .co-sidebar {
        width: 220px;
        flex-shrink: 0;
        background: #1a345b;
        border: none;
        transition: width 0.18s ease;
        overflow: hidden;
        margin: 0;
        align-self: flex-start;
        position: sticky;
        top: 2.5rem;
        height: auto;
        max-height: calc(100vh - 2.5rem);
        display: flex;
        flex-direction: column;
    }

    .co-sidebar.collapsed {
        width: 48px;
        overflow: visible;
    }

    .co-sidebar-header {
        flex-shrink: 0;
        padding: 8pt 6pt 0;
    }

    .co-sidebar.collapsed .co-sidebar-header {
        padding: 8pt 4pt 0;
    }

    .co-sidebar-nav {
        flex: 1;
        padding: 0 6pt 8pt;
        overflow-y: auto;
        overflow-x: hidden;
        scrollbar-width: thin;
        scrollbar-color: #5a7186 transparent;
    }

    .co-sidebar-nav::-webkit-scrollbar {
        width: 4px;
    }

    .co-sidebar-nav::-webkit-scrollbar-thumb {
        background: #5a7186;
        border-radius: 0;
    }

    .co-sidebar.collapsed .co-sidebar-nav {
        padding: 0 4pt 8pt;
        overflow: visible;
    }

    .co-sidebar-footer {
        flex-shrink: 0;
        padding: 6pt 6pt 8pt;
        border-top: 1px solid #5a7186;
        display: flex;
        flex-direction: column;
        gap: 1pt;
    }

    .co-sidebar-footer-grace {
        display: flex;
        align-items: center;
        gap: 5pt;
        padding: 4pt 6pt;
        font-size: 7pt;
        font-weight: 700;
        color: #fde68a;
        white-space: nowrap;
        overflow: hidden;
    }

    .co-sidebar-footer-link {
        display: flex;
        align-items: center;
        gap: 5pt;
        padding: 4pt 6pt;
        font-size: 10pt;
        font-weight: 600;
        color: #9ec1f5;
        text-decoration: none;
        background: none;
        border: none;
        cursor: pointer;
        font-family: inherit;
        white-space: nowrap;
        overflow: hidden;
        transition: color 0.15s, background 0.15s;
        width: 100%;
        text-align: left;
    }

    .co-sidebar-footer-link:hover {
        color: #fff;
        background: rgba(255,255,255,0.08);
    }

    .co-sidebar.collapsed .co-sidebar-footer {
        padding: 6pt 4pt 8pt;
    }

    .co-sidebar.collapsed .co-sidebar-footer span {
        display: none;
    }

    .co-sidebar.collapsed .co-sidebar-footer-grace span {
        display: none;
    }

    .co-sidebar.collapsed .co-sidebar-footer-link {
        justify-content: center;
        padding: 4pt;
    }

    .co-sidebar-toggle {
        display: flex;
        align-items: center;
        gap: 4pt;
        width: 100%;
        background: none;
        border: none;
        cursor: pointer;
        padding: 4pt 5pt;
        margin-bottom: 6pt;
        color: #9ec1f5;
        font-family: inherit;
        font-size: 10pt;
        font-weight: 700;
        text-transform: none;
        letter-spacing: 0;
        transition: color 0.12s, background 0.12s;
    }

    .co-sidebar-toggle:hover {
        color: #eaf8fb;
        background: rgba(255,255,255,0.06);
    }

    .co-sidebar-toggle svg {
        flex-shrink: 0;
        transition: transform 0.18s ease;
    }

    .co-sidebar.collapsed .co-sidebar-toggle svg {
        transform: rotate(180deg);
    }

    .co-sidebar.collapsed .co-sidebar-toggle {
        justify-content: center;
        padding: 4pt;
    }

    .co-sidebar.collapsed .co-sidebar-company-details,
    .co-sidebar.collapsed .co-sidebar-section-label,
    .co-sidebar.collapsed .co-sidebar-toggle span,
    .co-sidebar.collapsed .co-nav-item span {
        display: none;
    }

    .co-sidebar.collapsed .co-sidebar-company {
        justify-content: center;
        padding: 0 0 8pt;
    }

    .co-sidebar.collapsed .co-nav-item {
        justify-content: center;
        padding: 4pt;
        position: relative;
    }

    .co-sidebar.collapsed .co-nav-item::after {
        content: attr(title);
        position: absolute;
        left: calc(100% + 8px);
        top: 50%;
        transform: translateY(-50%);
        background: #1a345b;
        color: #fff;
        font-size: 10pt;
        font-weight: 600;
        padding: 3pt 5pt;
        white-space: nowrap;
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.15s;
        z-index: 200;
        border: 1px solid #5a7186;
    }

    .co-sidebar.collapsed .co-nav-item::before {
        content: '';
        position: absolute;
        left: calc(100% + 4px);
        top: 50%;
        transform: translateY(-50%);
        border: 4px solid transparent;
        border-right-color: #9ec1f5;
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.15s;
        z-index: 200;
    }

    .co-sidebar.collapsed .co-nav-item:hover::after,
    .co-sidebar.collapsed .co-nav-item:hover::before {
        opacity: 1;
    }

    .co-sidebar.collapsed .co-sidebar-company {
        position: relative;
    }

    .co-sidebar.collapsed .co-sidebar-company::after {
        content: attr(title);
        position: absolute;
        left: calc(100% + 8px);
        top: 50%;
        transform: translateY(-50%);
        background: #1a345b;
        color: #fff;
        font-size: 10pt;
        font-weight: 600;
        padding: 3pt 5pt;
        white-space: nowrap;
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.15s;
        z-index: 200;
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        border: 1px solid #5a7186;
    }

    .co-sidebar.collapsed .co-sidebar-company::before {
        content: '';
        position: absolute;
        left: calc(100% + 4px);
        top: 50%;
        transform: translateY(-50%);
        border: 4px solid transparent;
        border-right-color: #9ec1f5;
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.15s;
        z-index: 200;
    }

    .co-sidebar.collapsed .co-sidebar-company:hover::after,
    .co-sidebar.collapsed .co-sidebar-company:hover::before {
        opacity: 1;
    }

    .co-sidebar-company {
        display: flex;
        align-items: center;
        gap: 5pt;
        padding: 0 4pt 8pt;
        border-bottom: 1px solid #5a7186;
        margin-bottom: 8pt;
    }

    .co-sidebar-company-avatar {
        flex-shrink: 0;
        width: 26pt;
        height: 26pt;
        background: #005bf0;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11.5pt;
        font-weight: 800;
    }

    .co-sidebar-company-details {
        min-width: 0;
    }

    .co-sidebar-company-name {
        font-size: 11.5pt;
        font-weight: 700;
        color: #fff;
        margin: 0 0 1pt;
        line-height: 1.3;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .co-sidebar-company-type {
        font-size: 10pt;
        color: #2674f2;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .co-sidebar-section {
        margin: 8pt 0 4pt;
    }

    .co-sidebar-section:first-child {
        margin-top: 0;
    }

    .co-sidebar-section-label {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        font-size: 10pt;
        font-weight: 700;
        letter-spacing: 0;
        text-transform: none;
        color: #9ec1f5;
        padding: 0 5pt;
        margin: 0 0 3pt;
        font-family: inherit;
        background: none;
        border: none;
        cursor: pointer;
    }

    .co-sidebar-section-label:hover {
        color: #2674f2;
    }

    .co-sidebar-section-chevron {
        flex-shrink: 0;
        transition: transform 0.15s ease;
    }

    .co-sidebar-section.collapsed .co-sidebar-section-chevron {
        transform: rotate(-90deg);
    }

    .co-sidebar-section.collapsed .co-sidebar-section-items {
        display: none;
    }

    .co-sidebar.collapsed .co-sidebar-section-chevron {
        display: none;
    }

    .co-nav-item {
        display: flex;
        align-items: center;
        gap: 5pt;
        padding: 3.5pt 5pt;
        font-size: 10.5pt;
        font-weight: 500;
        color: #9ec1f5;
        text-decoration: none;
        transition: background 0.12s, color 0.12s;
        margin: 0 0 1pt;
    }

    .co-nav-item svg {
        flex-shrink: 0;
        width: 12pt;
        height: 12pt;
        transition: color 0.12s;
    }

    .co-nav-item:hover {
        background: rgba(255,255,255,0.08);
        color: #fff;
    }

    .co-nav-item.active {
        background: #005bf0;
        color: #fff;
        font-weight: 700;
    }

    .co-nav-item.active:hover {
        background: #005bf0;
    }

    /* ── Main content ───────────────────────── */
    .co-main {
        flex: 1;
        padding: 2.5rem 2rem;
        min-width: 0;
    }

    .co-card {
        background: #fff;
        border: 1px solid #9ec1f5;
        border-radius: 0;
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04), 0 1px 3px rgba(16, 24, 40, 0.03);
        margin-bottom: 1.5rem;
        overflow: hidden;
    }

    .co-card-head {
        padding: 1.4rem 1.75rem 1.25rem;
        border-bottom: 1px solid #f4fafc;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .co-section-label {
        font-size: 10pt;
        font-weight: 700;
        letter-spacing: 0;
        text-transform: none;
        color: #5a7186;
        margin: 0;
        white-space: nowrap;
    }

    .co-section-heading {
        display: flex;
        align-items: baseline;
        gap: 0.5rem;
        margin-bottom: 0.35rem;
    }

    .co-section-heading h2 {
        font-size: 1.05rem;
        font-weight: 800;
        color: #1a345b;
        margin: 0;
        white-space: nowrap;
    }

    .co-section-heading .co-section-label::after {
        content: '/';
        margin-left: 0.5rem;
        color: #d3e2f5;
        font-weight: 400;
    }

    /* ── Table ──────────────────────────────── */
    .co-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.855rem;
    }

    .co-table thead th {
        background: #005bf0;
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0;
        text-transform: none;
        color: #ffffff;
        padding: 0.7rem 1.25rem;
        text-align: left;
        border: none;
        white-space: nowrap;
    }

    .co-table thead th.right {
        text-align: right;
    }

    .co-table tbody tr {
        border-bottom: 1px solid #d3e2f5;
        transition: background 0.1s;
    }

    .co-table tbody tr:hover {
        background: #f7fbfd;
    }

    .co-table tbody tr:last-child {
        border-bottom: none;
    }

    .co-table td {
        padding: 0.7rem 1.25rem;
        color: #1a345b;
        vertical-align: middle;
    }

    .co-table td.muted {
        color: #5a7186;
        font-size: 0.8rem;
    }

    .co-table tfoot td {
        font-weight: 700;
        background: #d3e2f5;
        border-top: 1.5px solid #1a345b;
        padding: 0.7rem 1.25rem;
        color: #1a345b;
    }

    /* ── Badges ─────────────────────────────── */
    .type-badge {
        display: inline-block;
        font-size: 0.65rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        padding: 0.2rem 0.55rem;
        border-radius:0;
    }

    .type-assets {
        background: #eaf8fb;
        color: #00838f;
    }

    .type-liabilities {
        background: #fee2e2;
        color: #b91c1c;
    }

    .type-equity {
        background: #d1fae5;
        color: #065f46;
    }

    .type-revenue {
        background: #dcfce7;
        color: #16a34a;
    }

    .type-cost_of_goods_sold {
        background: #fef3c7;
        color: #92400e;
    }

    .type-expenses {
        background: #fef9c3;
        color: #854d0e;
    }

    .type-finance_costs {
        background: #eaf8fb;
        color: #1a345b;
    }

    .type-tax {
        background: #f4fafc;
        color: #005bf0;
    }

    .group-header td {
        background: #f7fbfd;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: #5a7186;
        padding: 0.6rem 1.25rem;
        border-top: 1px solid #d3e2f5;
    }

    .empty-state {
        padding: 3.5rem 2rem;
        text-align: center;
        color: #6f869b;
        font-size: 0.875rem;
    }

    /* ── Report filter bar ─────────────────── */
    .is-filter-bar {
        display: flex;
        align-items: flex-end;
        gap: 0.85rem;
        flex-wrap: wrap;
        padding: 0.95rem 1.35rem;
        border-bottom: 1px solid #9ec1f5;
        background: #f4fafc;
        font-family: inherit;
    }

    /* Top-level field labels (those bound to an input) — intangibles tiny-uppercase style.
       Inline labels (e.g. the checkbox wrappers) have no `for` attribute and keep normal type. */
    .is-filter-bar label[for] {
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #5a7186;
        display: block;
        margin-bottom: 0.22rem;
    }

    .is-filter-bar input[type='date'],
    .is-filter-bar select {
        border: 1px solid #d3e2f5;
        border-radius: 0;
        padding: 0.35rem 0.55rem;
        font-size: 0.78rem;
        font-family: inherit;
        color: #000;
        background: #fff;
        outline: none;
        transition: border-color 0.15s;
        height: 2rem;
        box-sizing: border-box;
        cursor: pointer;
    }

    .is-filter-bar input[type='date']:focus,
    .is-filter-bar select:focus {
        border-color: #000;
    }

    /* Native checkbox tint, matches the new black accent */
    .is-filter-bar input[type='checkbox'] {
        accent-color: #000;
        cursor: pointer;
    }

    .is-filter-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        background: #000;
        color: #fff;
        border: 1px solid #000;
        border-radius: 0;
        padding: 0.4rem 0.95rem;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        cursor: pointer;
        font-family: inherit;
        white-space: nowrap;
        height: 2rem;
        box-sizing: border-box;
        transition: background 0.15s, color 0.15s;
    }

    .is-filter-btn:hover {
        background: #1a345b;
    }

    .is-period-label {
        font-size: 0.72rem;
        color: #888;
        font-style: italic;
        margin: 0;
    }

    .is-section-header td {
        padding: 0.55rem 0.875rem;
        font-size: 0.67rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        background: #f4fafc;
        color: #1a345b;
        border-top: 1.5pt solid #1a345b;
    }

    .is-subtotal td {
        padding: 0.5rem 0.875rem;
        font-size: 0.8rem;
        font-weight: 700;
        background: #eaf8fb;
        border-top: 1px solid #9ec1f5;
        white-space: nowrap;
    }

    .is-net td {
        padding: 0.6rem 0.875rem;
        font-size: 0.875rem;
        font-weight: 800;
        border-top: 2px solid rgba(0, 91, 240, 0.25);
        background: #eaf8fb;
        white-space: nowrap;
    }

    .is-amount {
        font-family: inherit;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
        text-align: right;
    }

    .is-balanced-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.72rem;
        font-weight: 700;
        padding: 0.25rem 0.65rem;
        border-radius:0;
    }

    .is-balanced-badge.ok {
        background: #dcfce7;
        color: #15803d;
    }

    .is-balanced-badge.off {
        background: #fee2e2;
        color: #b91c1c;
    }

    /* ── Financial statement hierarchy ─────────────────── */
    /* Main section divider — Assets / Liabilities / Equity etc. */
    .is-section-label-lg td {
        padding: 0.7rem 0.875rem 0.35rem;
        font-size: 0.72rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.18em;
        color: #005bf0;
        background: #f4fafc;
        border-top: 1px solid #9ec1f5;
    }

    /* Subsection divider — Current Assets / Non-Current Assets etc. */
    .is-subsection-header td {
        padding: 0.45rem 0.875rem;
        font-size: 0.68rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: #191919;
        background: #f7fbfd;
        border-top: 1px solid #d3e2f5;
    }

    /* Parent (group) account row */
    .is-group-header td {
        padding: 0.4rem 0.875rem;
        font-size: 0.78rem;
        font-weight: 700;
        color: #191919;
        background: #fff;
        border-top: 1px solid #f4fafc;
    }

    /* Child (item) account row */
    .is-item-row td {
        padding: 0.35rem 0.875rem;
        font-size: 0.78rem;
        background: #f7fbfd;
    }

    .is-item-row td:nth-child(2) {
        padding-left: 2rem;
        color: #191919;
    }

    /* Subtotal row for a parent group */
    .is-group-subtotal td {
        padding: 0.4rem 0.875rem;
        font-size: 0.78rem;
        font-weight: 700;
        background: #f7fbfd;
        border-top: 1px dashed #d3e2f5;
        white-space: nowrap;
    }

    /* Section-level total (stronger than subtotal) */
    .is-section-total td {
        padding: 0.55rem 0.875rem;
        font-size: 0.82rem;
        font-weight: 800;
        background: #f4fafc;
        border-top: 2px solid rgba(0, 91, 240, 0.2);
        white-space: nowrap;
    }

    /* ── AFS statement table (shared by SOFP / SOCI / SOCF) ───
       Mirrors resources/views/pdf/_afs-styles.blade.php, which is
       measured from CGL-YE25-Annual-Financial-Statements.docx.
       Screen keeps the document's type scale and colour, but the
       table fills its container instead of the fixed 214.31mm the
       print sheet uses.

       The two details worth guarding:
         - the header is NOT a full blue band; only the current-year
           cell is filled #005BF0 with white text and a blue rule
         - there are no double rules anywhere, not even on the
           grand total; every rule is 0.5pt solid #000 */
    .afs-table {
        width: 100%;
        border-collapse: collapse;
        border: none;
        font-size: 10.5pt;
        line-height: 1.2857;
        color: #191919;
        font-family: inherit;
    }

    .afs-table td,
    .afs-table th {
        border: none;
        vertical-align: bottom;
        padding: 0 3.75pt 0 0;
        line-height: 1.2857;
    }

    /* Column proportions from the source grid: 7725/795/1815/1815 tw */
    .afs-table thead th.afs-col-note { width: 6.54%; }
    .afs-table thead th.afs-col-amount { width: 14.94%; }

    /* Header row — plain cells with a black hairline */
    .afs-table thead th {
        font-size: 10.5pt;
        font-weight: normal;
        color: #000000;
        background: #fff;
        text-align: right;
        vertical-align: bottom;
        border-bottom: 0.5pt solid #000000;
        white-space: nowrap;
    }

    .afs-table thead th.afs-col-label {
        text-align: left;
        font-style: normal;
    }

    /* Current-year header cell only */
    .afs-table thead th:nth-child(3) {
        background: #005bf0;
        color: #ffffff;
        border-bottom-color: #005bf0;
        padding-right: 4.5pt;
    }

    .afs-table tbody td,
    .afs-table tfoot td { color: #000000; }

    /* Current-year column: continuous tint, bold, wider gutter */
    .afs-table tbody td:nth-child(3),
    .afs-table tfoot td:nth-child(3) {
        background: #eaf8fb;
        font-weight: bold;
        text-align: right;
        padding-right: 4.5pt;
    }

    .afs-table tbody td:nth-child(4),
    .afs-table tfoot td:nth-child(4) { text-align: right; }

    /* Emphasis labels — sections, subtotals and totals alike */
    .afs-table tr.afs-section-main td:first-child,
    .afs-table tr.afs-section-sub td:first-child,
    .afs-table tr.afs-subtotal td:first-child,
    .afs-table tr.afs-named-subtotal td:first-child,
    .afs-table tr.afs-grand-total td:first-child {
        font-weight: bold;
        color: #005bf0;
    }

    /* Section headers get the document's leading gap */
    .afs-table tr.afs-section-main td { padding-top: 8pt; }
    .afs-table tr.afs-section-sub td { padding-top: 5pt; padding-left: 8pt; }

    /* Hairline closing a group or a total — never doubled */
    .afs-table tr.afs-item-last td,
    .afs-table tr.afs-subtotal td,
    .afs-table tr.afs-named-subtotal td,
    .afs-table tr.afs-grand-total td {
        border-bottom: 0.5pt solid #000000;
    }

    .afs-table tbody tr:first-child td { padding-top: 13.5pt; }

    .afs-table td.afs-amount {
        text-align: right;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    .afs-table td.afs-note { text-align: left; color: #000000; }
    .afs-table td.afs-name { text-align: left; }
    .afs-table td.afs-empty { color: #5a7186; text-align: left; }
    .afs-table td.afs-dim { color: #5a7186; }
    .afs-table td.afs-abnormal { color: #000000; }

    /* ── Matrix variant: statement of changes in equity ────────
       The document's SOCE carries no tint and no filled header
       cell — applying the single-column treatment would band an
       arbitrary equity component. */
    .afs-table.afs-matrix thead th,
    .afs-table.afs-matrix thead th:nth-child(3) {
        background: #fff;
        color: #000000;
        border-bottom: 0.5pt solid #000000;
        text-align: right;
        padding-right: 3.75pt;
    }

    .afs-table.afs-matrix thead th.afs-col-label { text-align: left; }
    .afs-table.afs-matrix thead th.afs-col-amount { width: auto; }

    .afs-table.afs-matrix tbody td:nth-child(3),
    .afs-table.afs-matrix tfoot td:nth-child(3) {
        background: transparent;
        font-weight: inherit;
        padding-right: 3.75pt;
    }

    .afs-table.afs-matrix tbody td + td,
    .afs-table.afs-matrix tfoot td + td { text-align: right; }

    .afs-table.afs-matrix tr.afs-subtotal td,
    .afs-table.afs-matrix tr.afs-named-subtotal td,
    .afs-table.afs-matrix tr.afs-grand-total td { font-weight: bold; }

    .afs-warning {
        margin: 13.5pt 0 0;
        padding: 5pt 8pt;
        background: #eaf8fb;
        color: #1a345b;
        font-size: 7pt;
        border-left: 2pt solid #005bf0;
    }

    .afs-details {
        margin-top: 6.75pt;
        font-size: 7pt;
        color: #191919;
        line-height: 1.2857;
    }

    /* Statement title — 25pt navy over an 11.5pt navy date line,
       with no rule between them, as in the document. */
    .afs-doc-header {
        text-align: left;
        margin: 14px 0 13.5pt;
        padding-bottom: 0;
        border-bottom: none;
    }

    .afs-doc-title {
        font-size: 25pt;
        font-weight: bold;
        color: #1a345b;
        line-height: 1.1;
        letter-spacing: 0;
        font-family: inherit;
    }

    .afs-doc-subtitle {
        font-size: 11.5pt;
        font-weight: normal;
        color: #1a345b;
        margin-top: 2pt;
        font-family: inherit;
    }

    .afs-letterhead {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        border-bottom: 0.375pt solid #1a345b;
        padding: 0 0 6pt;
        margin: 0;
    }

    .afs-letterhead-name {
        font-size: 13pt;
        font-weight: bold;
        color: #1a345b;
        letter-spacing: 0;
        line-height: 1.0714;
        font-family: inherit;
    }

    .afs-letterhead-meta {
        font-size: 10pt;
        color: #1a345b;
        margin-top: 3px;
        line-height: 1.2857;
        font-family: inherit;
    }

    .afs-letterhead-meta.afs-right { text-align: right; }

    /* ── Register / document styles (corporate) ── */
    .reg-mgmt-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8pt;
        flex-wrap: wrap;
        margin-bottom: 10pt;
    }
    .reg-mgmt-bar a {
        font-size: 10pt;
        color: #5a7186;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 3pt;
        transition: color 0.15s;
    }
    .reg-mgmt-bar a:hover { color: #1a345b; }

    .reg-btn {
        display: inline-flex;
        align-items: center;
        gap: 4pt;
        background: #fff;
        border: 1px solid #1a345b;
        color: #1a345b;
        font-size: 10pt;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        padding: 3pt 8pt;
        text-decoration: none;
        cursor: pointer;
        font-family: inherit;
        transition: background 0.15s, color 0.15s;
        border-radius: 0;
        white-space: nowrap;
    }
    .reg-btn:hover { background: #1a345b; color: #fff; }
    .reg-btn.primary { background: #1a345b; color: #fff; }
    .reg-btn.primary:hover { background: #005bf0; }

    .reg-doc {
        background: #fff;
        border-top: 1.5pt solid #1a345b;
        font-family: inherit;
        color: #191919;
        font-size: 7pt;
        line-height: 1.45;
        margin-bottom: 12pt;
    }
    .reg-doc-body { padding: 8pt 10pt; }

    .reg-doc-title {
        font-size: 9pt;
        font-weight: 800;
        letter-spacing: 0.02em;
        color: #1a345b;
        margin-bottom: 2pt;
    }
    .reg-doc-subtitle {
        font-size: 11.5pt;
        color: #5a7186;
        margin: 1pt 0 6pt;
    }

    .reg-divider {
        border: none;
        border-top: 1pt solid #9ec1f5;
        margin: 6pt 0 8pt;
    }

    .reg-section-header {
        font-weight: 700;
        font-size: 7pt;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #005bf0;
        border-bottom: 1pt solid #9ec1f5;
        padding-bottom: 2pt;
        margin-bottom: 4pt;
    }

    table.reg-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 7pt;
        font-family: inherit;
    }
    table.reg-table thead th {
        font-size: 10.5pt;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 3pt 4pt;
        color: #ffffff;
        border-top: 1.5pt solid #1a345b;
        border-bottom: 1pt solid #2674f2;
        background: #005bf0;
        text-align: left;
    }
    table.reg-table thead th.amt { text-align: right; }

    table.reg-table tbody td {
        padding: 2pt 4pt;
        font-size: 7pt;
        border-bottom: 0.4pt solid #d3e2f5;
        vertical-align: middle;
        color: #191919;
    }
    table.reg-table tbody td.amt {
        text-align: right;
        font-family: inherit;
        font-variant-numeric: tabular-nums;
        font-size: 7pt;
        white-space: nowrap;
    }
    table.reg-table tbody td.dim { color: #6f869b; }
    table.reg-table tbody tr:last-child td { border-bottom: none; }
    table.reg-table tbody tr:hover td { background: #f4fafc; }

    table.reg-table tfoot td {
        padding: 3pt 4pt;
        font-size: 7pt;
        font-weight: 800;
        color: #1a345b;
        background: #eaf8fb;
        border-top: 1.5pt solid #1a345b;
        border-bottom: 2pt solid #1a345b;
    }
    table.reg-table tfoot td.amt {
        text-align: right;
        font-family: inherit;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    .reg-status {
        display: inline-block;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 7pt;
        letter-spacing: 0.06em;
        padding: 1pt 4pt;
        border: 0.5pt solid #1a345b;
        color: #1a345b;
    }
    .reg-status.disposed { color: #dc2626; border-color: #dc2626; }
    .reg-status.hfs { color: #854d0e; border-color: #854d0e; }
    .reg-status.draft { color: #92400e; border-color: #92400e; }
    .reg-status.revaluation { color: #005bf0; border-color: #005bf0; background: #f4fafc; }

    .reg-link {
        color: #1a345b;
        font-weight: 700;
        text-decoration: none;
        border-bottom: 0.5pt solid #2674f2;
    }
    .reg-link:hover { border-bottom-color: #1a345b; }

    .reg-empty {
        color: #6f869b;
        font-size: 10.5pt;
        font-style: italic;
        padding: 4pt 0;
    }

    .reg-empty-state {
        text-align: center;
        padding: 3rem 1.5rem;
        color: #6f869b;
        font-style: normal;
    }

    .reg-empty-state p {
        margin: 0 0 0.3rem;
        font-size: 10.5pt;
    }

    .reg-empty-state .reg-empty-title {
        font-weight: 700;
        color: #5a7186;
        font-size: 10.5pt;
    }

    .reg-empty-state .reg-btn {
        margin-top: 1rem;
    }

    /* Register row actions (three-dot menu) */
    .reg-row-actions { position: relative; }
    .reg-row-dots {
        background: none; border: none; cursor: pointer; padding: 2pt 3pt;
        font-size: 9pt; line-height: 1; color: #6f869b; font-family: inherit;
    }
    .reg-row-dots:hover { color: #1a345b; }
    .reg-row-menu {
        display: none; position: fixed; z-index: 9999;
        background: #fff; border: 1px solid #9ec1f5; box-shadow: 0 4px 16px rgba(26, 52, 91,0.12);
        min-width: 100px; padding: 2pt 0;
    }
    .reg-row-menu.open { display: block; }
    .reg-row-menu a, .reg-row-menu button.menu-item {
        display: block; width: 100%; text-align: left; padding: 3pt 8pt;
        font-size: 10pt; font-weight: 600; font-family: inherit;
        background: none; border: none; cursor: pointer; color: #1a345b; text-decoration: none;
    }
    .reg-row-menu a:hover, .reg-row-menu button.menu-item:hover { background: #f4fafc; }
    .reg-row-menu button.menu-item.danger { color: #dc2626; }
    .reg-row-menu button.menu-item.danger:hover { background: #fef2f2; }

    /* Register modal fields */
    .reg-modal-field label { display:block; font-size:10pt; font-weight:700; letter-spacing:0.07em; text-transform:uppercase; color:#5a7186; margin-bottom:2pt; }
    .reg-modal-field input, .reg-modal-field select, .reg-modal-field textarea { width:100%; border:1px solid #9ec1f5; padding:3pt 5pt; font-size:7pt; font-family:inherit; color:#1a345b; background:#fff; outline:none; box-sizing:border-box; border-radius:0; }
    .reg-modal-field input:focus, .reg-modal-field select:focus, .reg-modal-field textarea:focus { border-color:#1a345b; }

    /* ── Add-panel (collapsible inline forms) ── */
    .add-panel { margin-top:8pt; }
    .add-panel-head {
        display:inline-flex; align-items:center; gap:3pt;
        font-size:10pt; font-weight:700; text-transform:uppercase; letter-spacing:0.06em;
        cursor:pointer; background:none; border:1px solid #1a345b; padding:3pt 7pt;
        font-family:inherit;
        transition:background 0.15s, color 0.15s; color:#1a345b;
    }
    .add-panel-head:hover { background:#1a345b; color:#fff; }
    .add-panel-body {
        display:none; margin-top:6pt; padding:8pt 10pt;
        border:1px solid #9ec1f5; background:#f4fafc;
    }
    .add-panel.open .add-panel-body { display:block; }

    /* ── Inline form grid (af-row / af-field) ── */
    .af-row { display:grid; grid-template-columns:repeat(4,1fr); gap:4pt 6pt; align-items:end; margin-bottom:5pt; }
    .af-field.wide { grid-column:span 2; }
    .af-field.full { grid-column:span 4; }
    .af-field label { display:block; font-size:10pt; font-weight:700; letter-spacing:0.07em; text-transform:uppercase; color:#5a7186; margin-bottom:2pt; }
    .af-field input, .af-field select, .af-field textarea {
        width:100%; border:1px solid #9ec1f5; padding:3pt 5pt; font-size:7pt;
        font-family:inherit; color:#1a345b;
        background:#fff; outline:none; box-sizing:border-box; border-radius:0;
    }
    .af-field input:focus, .af-field select:focus, .af-field textarea:focus { border-color:#1a345b; }
    .af-submit { display:flex; justify-content:flex-end; margin-top:4pt; }
    .af-hint { font-size:7pt; color:#6f869b; margin-bottom:5pt; line-height:1.4; }

    /* ── Toggle switch ── */
    .toggle-wrap { position:relative; display:inline-flex; flex-direction:column; align-items:flex-start; gap:2pt; cursor:pointer; user-select:none; padding:2pt 0; }
    .toggle-wrap input[type=checkbox] { position:absolute; opacity:0; width:0; height:0; pointer-events:none; }
    .toggle-track { position:relative; display:inline-block; width:22pt; height:12pt; background:#9ec1f5; border-radius:999px; transition:background 0.18s; flex-shrink:0; }
    .toggle-wrap input[type=checkbox]:checked ~ .toggle-track { background:#1a345b; }
    .toggle-track::after { content:''; display:block; position:absolute; top:1.5pt; left:1.5pt; width:9pt; height:9pt; background:#fff; border-radius:50%; transition:transform 0.18s; box-shadow:0 0.5pt 1pt rgba(0,0,0,0.15); }
    .toggle-wrap input[type=checkbox]:checked ~ .toggle-track::after { transform:translateX(10pt); }
    .toggle-text { font-size:10pt; color:#6f869b; line-height:1.3; }

    /* ── Responsive ─────────────────────────── */
    @media (max-width: 768px) {
        .co-sidebar {
            display: none;
        }

        .co-table .hide-mobile,
        .reg-table .hide-mobile {
            display: none;
        }

        .af-row { grid-template-columns:1fr 1fr; }
        .af-field.wide, .af-field.full { grid-column:span 2; }
    }
</style>
