<style>
    .co-wrap {
        min-height: 100vh;
        background: #f7f5ff;
        display: flex;
        flex-direction: column;
        padding-top: 2.5rem;
    }

    /* Compact topbar */
    .co-topbar {
        background: #16355c;
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
        font-size: 6.5pt;
        font-weight: 600;
        letter-spacing: 0.04em;
        color: #c9dff0;
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

    .co-topbar .co-topbar-divider {
        width: 1px;
        height: 10pt;
        background: #4a5f78;
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
        font-size: 6pt;
        color: #9cc3e0;
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
        font-size: 6pt;
        font-weight: 600;
        padding: 2pt 6pt;
        text-decoration: none;
        border: 1px solid #4a5f78;
        color: #c9dff0;
        background: transparent;
        transition: background 0.15s, border-color 0.15s, color 0.15s;
        white-space: nowrap;
    }

    .co-topbar .co-topbar-actions a:hover,
    .co-topbar .co-topbar-actions button:hover {
        background: rgba(255,255,255,0.1);
        border-color: #9cc3e0;
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
        background: #16355c;
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
        scrollbar-color: #4a5f78 transparent;
    }

    .co-sidebar-nav::-webkit-scrollbar {
        width: 4px;
    }

    .co-sidebar-nav::-webkit-scrollbar-thumb {
        background: #4a5f78;
        border-radius: 0;
    }

    .co-sidebar.collapsed .co-sidebar-nav {
        padding: 0 4pt 8pt;
        overflow: visible;
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
        color: #7a90a5;
        font-family: Helvetica, Arial, "DejaVu Sans", sans-serif;
        font-size: 5.5pt;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        transition: color 0.12s, background 0.12s;
    }

    .co-sidebar-toggle:hover {
        color: #e7f3fb;
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
        background: #16355c;
        color: #fff;
        font-size: 6pt;
        font-weight: 600;
        padding: 3pt 5pt;
        white-space: nowrap;
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.15s;
        z-index: 200;
        border: 1px solid #4a5f78;
    }

    .co-sidebar.collapsed .co-nav-item::before {
        content: '';
        position: absolute;
        left: calc(100% + 4px);
        top: 50%;
        transform: translateY(-50%);
        border: 4px solid transparent;
        border-right-color: #4a5f78;
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
        background: #16355c;
        color: #fff;
        font-size: 6pt;
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
        border: 1px solid #4a5f78;
    }

    .co-sidebar.collapsed .co-sidebar-company::before {
        content: '';
        position: absolute;
        left: calc(100% + 4px);
        top: 50%;
        transform: translateY(-50%);
        border: 4px solid transparent;
        border-right-color: #4a5f78;
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
        border-bottom: 1px solid #4a5f78;
        margin-bottom: 8pt;
    }

    .co-sidebar-company-avatar {
        flex-shrink: 0;
        width: 26pt;
        height: 26pt;
        background: #0079c8;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 8pt;
        font-weight: 800;
    }

    .co-sidebar-company-details {
        min-width: 0;
    }

    .co-sidebar-company-name {
        font-size: 7pt;
        font-weight: 700;
        color: #fff;
        margin: 0 0 1pt;
        line-height: 1.3;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .co-sidebar-company-type {
        font-size: 5.5pt;
        color: #9cc3e0;
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
        font-size: 5pt;
        font-weight: 700;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: #7a90a5;
        padding: 0 5pt;
        margin: 0 0 3pt;
        font-family: Helvetica, Arial, "DejaVu Sans", sans-serif;
        background: none;
        border: none;
        cursor: pointer;
    }

    .co-sidebar-section-label:hover {
        color: #9cc3e0;
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
        font-size: 7pt;
        font-weight: 500;
        color: #c9dff0;
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
        background: #0079c8;
        color: #fff;
        font-weight: 700;
    }

    .co-nav-item.active:hover {
        background: #0079c8;
    }

    /* ── Main content ───────────────────────── */
    .co-main {
        flex: 1;
        padding: 2.5rem 2rem;
        min-width: 0;
    }

    .co-card {
        background: #fff;
        border: 1px solid #c9dff0;
        border-radius: 0;
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04), 0 1px 3px rgba(16, 24, 40, 0.03);
        margin-bottom: 1.5rem;
        overflow: hidden;
    }

    .co-card-head {
        padding: 1.4rem 1.75rem 1.25rem;
        border-bottom: 1px solid #f3f3f6;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .co-section-label {
        font-size: 0.62rem;
        font-weight: 700;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: #8b8b9a;
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
        color: #1b1b18;
        margin: 0;
        white-space: nowrap;
    }

    .co-section-heading .co-section-label::after {
        content: '/';
        margin-left: 0.5rem;
        color: #d1d5db;
        font-weight: 400;
    }

    /* ── Table ──────────────────────────────── */
    .co-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.855rem;
    }

    .co-table thead th {
        background: #1a1f2e;
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: rgba(255,255,255,0.85);
        padding: 0.7rem 1.25rem;
        text-align: left;
        border: none;
        white-space: nowrap;
    }

    .co-table thead th.right {
        text-align: right;
    }

    .co-table tbody tr {
        border-bottom: 1px solid #f0f2f5;
        transition: background 0.1s;
    }

    .co-table tbody tr:hover {
        background: #f8f9fb;
    }

    .co-table tbody tr:last-child {
        border-bottom: none;
    }

    .co-table td {
        padding: 0.7rem 1.25rem;
        color: #1a1f2e;
        vertical-align: middle;
    }

    .co-table td.muted {
        color: #6b7280;
        font-size: 0.8rem;
    }

    .co-table tfoot td {
        font-weight: 700;
        background: #e8eaed;
        border-top: 1.5px solid #1a1f2e;
        padding: 0.7rem 1.25rem;
        color: #1a1f2e;
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
        background: #e0f3f5;
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
        background: #e7f3fb;
        color: #16355c;
    }

    .type-tax {
        background: #f3e8ff;
        color: #7e22ce;
    }

    .group-header td {
        background: #f9fafb;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: #6b7280;
        padding: 0.6rem 1.25rem;
        border-top: 1px solid #e5e7eb;
    }

    .empty-state {
        padding: 3.5rem 2rem;
        text-align: center;
        color: #9ca3af;
        font-size: 0.875rem;
    }

    /* ── Report filter bar ─────────────────── */
    .is-filter-bar {
        display: flex;
        align-items: flex-end;
        gap: 0.85rem;
        flex-wrap: wrap;
        padding: 0.95rem 1.35rem;
        border-bottom: 1px solid #c9dff0;
        background: #eef6fc;
        font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
    }

    /* Top-level field labels (those bound to an input) — intangibles tiny-uppercase style.
       Inline labels (e.g. the checkbox wrappers) have no `for` attribute and keep normal type. */
    .is-filter-bar label[for] {
        font-size: 0.58rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #555;
        display: block;
        margin-bottom: 0.22rem;
    }

    .is-filter-bar input[type='date'],
    .is-filter-bar select {
        border: 1px solid #ccc;
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
        background: #333;
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
        background: #eef6fc;
        color: #16355c;
        border-top: 1.5pt solid #16355c;
    }

    .is-subtotal td {
        padding: 0.5rem 0.875rem;
        font-size: 0.8rem;
        font-weight: 700;
        background: #e7f3fb;
        border-top: 1px solid #c9dff0;
        white-space: nowrap;
    }

    .is-net td {
        padding: 0.6rem 0.875rem;
        font-size: 0.875rem;
        font-weight: 800;
        border-top: 2px solid rgba(94, 23, 235, 0.25);
        background: #ede9fe;
        white-space: nowrap;
    }

    .is-amount {
        font-family: monospace;
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
        font-size: 0.62rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.18em;
        color: #0079c8;
        background: #f5faff;
        border-top: 1px solid #c9dff0;
    }

    /* Subsection divider — Current Assets / Non-Current Assets etc. */
    .is-subsection-header td {
        padding: 0.45rem 0.875rem;
        font-size: 0.68rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: #374151;
        background: #f9fafb;
        border-top: 1px solid #e5e7eb;
    }

    /* Parent (group) account row */
    .is-group-header td {
        padding: 0.4rem 0.875rem;
        font-size: 0.78rem;
        font-weight: 700;
        color: #1b1b18;
        background: #fff;
        border-top: 1px solid #f0f0f0;
    }

    /* Child (item) account row */
    .is-item-row td {
        padding: 0.35rem 0.875rem;
        font-size: 0.78rem;
        background: #fcfcfd;
    }

    .is-item-row td:nth-child(2) {
        padding-left: 2rem;
        color: #374151;
    }

    /* Subtotal row for a parent group */
    .is-group-subtotal td {
        padding: 0.4rem 0.875rem;
        font-size: 0.78rem;
        font-weight: 700;
        background: #f9fafb;
        border-top: 1px dashed #d1d5db;
        white-space: nowrap;
    }

    /* Section-level total (stronger than subtotal) */
    .is-section-total td {
        padding: 0.55rem 0.875rem;
        font-size: 0.82rem;
        font-weight: 800;
        background: #f3e8ff;
        border-top: 2px solid rgba(94, 23, 235, 0.2);
        white-space: nowrap;
    }

    /* ── AFS statement table (shared by SOFP / SOCI / SOCF) ─── */
    /* Corporate AFS look — matches PDF sizing and palette exactly */
    .afs-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 7pt;
        color: #23282d;
        line-height: 1.4;
        font-family: Helvetica, Arial, "DejaVu Sans", sans-serif;
    }

    .afs-table thead th {
        font-size: 7pt;
        font-weight: normal;
        padding: 4pt 4pt 5pt 0;
        background: #fff;
        color: #4a5f78;
        text-align: left;
        vertical-align: bottom;
        border-bottom: 0.75pt solid #16355c;
        white-space: nowrap;
    }

    .afs-table thead th.afs-col-label {
        font-style: normal;
    }

    .afs-table thead th.afs-col-note {
        text-align: center;
        width: 9%;
    }

    .afs-table thead th.afs-col-amount {
        text-align: right;
        width: 18%;
        padding-right: 3pt;
        font-weight: bold;
        color: #16355c;
        font-variant-numeric: tabular-nums;
    }

    .afs-table tr.afs-section-main td {
        background: #fff;
        font-size: 7pt;
        font-weight: bold;
        color: #16355c;
        padding: 8pt 0 3pt 0;
        border-top: none;
        border-bottom: none;
    }

    .afs-table tr.afs-section-sub td {
        background: #fff;
        font-size: 7pt;
        font-weight: bold;
        color: #0079c8;
        padding: 4pt 0 3pt 8pt;
        border-top: none;
    }

    .afs-table tr.afs-item-row td {
        padding: 3.5pt 4pt 3.5pt 18pt;
        font-size: 7pt;
        color: #23282d;
        border-bottom: 0.4pt solid #ddebf5;
        line-height: 1.35;
    }

    .afs-table tr.afs-item-last td {
        border-bottom: 0.6pt solid #9cc3e0;
    }

    .afs-table tr.afs-subtotal td {
        padding: 4pt 4pt 4pt 18pt;
        font-size: 7pt;
        font-weight: bold;
        color: #16355c;
        background: #fff;
        border-bottom: 0.75pt solid #16355c;
    }

    .afs-table tr.afs-named-subtotal td {
        padding: 4pt 4pt;
        font-size: 7pt;
        font-weight: bold;
        color: #16355c;
        background: #fff;
        border-bottom: 0.75pt solid #16355c;
    }

    .afs-table tr.afs-grand-total td {
        padding: 5pt 4pt;
        font-size: 7pt;
        font-weight: bold;
        background: #e7f3fb;
        border-top: 0.75pt solid #16355c;
        border-bottom: 1.5pt solid #16355c;
        color: #16355c;
    }

    .afs-table td.afs-amount {
        text-align: right;
        font-variant-numeric: tabular-nums;
        padding-right: 4pt;
        white-space: nowrap;
    }

    .afs-table td.afs-note {
        text-align: center;
        color: #4a5f78;
    }

    .afs-table td.afs-name {
        text-align: left;
    }

    .afs-table td.afs-empty {
        font-style: italic;
        color: #8aa2b8;
        text-align: left;
    }

    .afs-table td.afs-dim {
        color: #a9bccd;
    }

    .afs-table td.afs-abnormal {
        color: #b91c1c;
    }

    .afs-warning {
        margin: 8pt 0 0;
        padding: 5pt 8pt;
        background: #eef6fc;
        color: #16355c;
        font-size: 7pt;
        border-left: 2pt solid #0079c8;
    }

    .afs-doc-header {
        text-align: left;
        margin: 14px 0 12px;
        padding-bottom: 8px;
        border-bottom: 0.75pt solid #b8d6ec;
    }

    .afs-doc-title {
        font-size: 12pt;
        font-weight: bold;
        color: #16355c;
        letter-spacing: 0;
        font-family: Helvetica, Arial, "DejaVu Sans", sans-serif;
    }

    .afs-doc-subtitle {
        font-size: 8pt;
        color: #0079c8;
        margin-top: 3px;
        font-family: Helvetica, Arial, "DejaVu Sans", sans-serif;
    }

    .afs-letterhead {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        border-bottom: 1.5pt solid #16355c;
        padding: 0 0 8px;
        margin: 0;
    }

    .afs-letterhead-name {
        font-size: 11pt;
        font-weight: bold;
        color: #16355c;
        letter-spacing: 0.01em;
        font-family: Helvetica, Arial, "DejaVu Sans", sans-serif;
    }

    .afs-letterhead-meta {
        font-size: 7pt;
        color: #4a5f78;
        margin-top: 2px;
        line-height: 1.6;
        font-family: Helvetica, Arial, "DejaVu Sans", sans-serif;
    }

    .afs-letterhead-meta.afs-right {
        text-align: right;
    }

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
        font-size: 6.5pt;
        color: #4a5f78;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 3pt;
        transition: color 0.15s;
    }
    .reg-mgmt-bar a:hover { color: #16355c; }

    .reg-btn {
        display: inline-flex;
        align-items: center;
        gap: 4pt;
        background: #fff;
        border: 1px solid #16355c;
        color: #16355c;
        font-size: 6.5pt;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        padding: 3pt 8pt;
        text-decoration: none;
        cursor: pointer;
        font-family: Helvetica, Arial, "DejaVu Sans", sans-serif;
        transition: background 0.15s, color 0.15s;
        border-radius: 0;
        white-space: nowrap;
    }
    .reg-btn:hover { background: #16355c; color: #fff; }
    .reg-btn.primary { background: #16355c; color: #fff; }
    .reg-btn.primary:hover { background: #0f2640; }

    .reg-doc {
        background: #fff;
        border-top: 1.5pt solid #16355c;
        font-family: Helvetica, Arial, "DejaVu Sans", sans-serif;
        color: #23282d;
        font-size: 7pt;
        line-height: 1.45;
        margin-bottom: 12pt;
    }
    .reg-doc-body { padding: 8pt 10pt; }

    .reg-doc-title {
        font-size: 9pt;
        font-weight: 800;
        letter-spacing: 0.02em;
        color: #16355c;
        margin-bottom: 2pt;
    }
    .reg-doc-subtitle {
        font-size: 6.5pt;
        color: #4a5f78;
        margin: 1pt 0 6pt;
    }

    .reg-divider {
        border: none;
        border-top: 1pt solid #c9dff0;
        margin: 6pt 0 8pt;
    }

    .reg-section-header {
        font-weight: 700;
        font-size: 7pt;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #0079c8;
        border-bottom: 1pt solid #c9dff0;
        padding-bottom: 2pt;
        margin-bottom: 4pt;
    }

    table.reg-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 7pt;
        font-family: Helvetica, Arial, "DejaVu Sans", sans-serif;
    }
    table.reg-table thead th {
        font-size: 6.5pt;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 3pt 4pt;
        color: #16355c;
        border-top: 1.5pt solid #16355c;
        border-bottom: 1pt solid #9cc3e0;
        background: #eef6fc;
        text-align: left;
    }
    table.reg-table thead th.amt { text-align: right; }

    table.reg-table tbody td {
        padding: 2pt 4pt;
        font-size: 7pt;
        border-bottom: 0.4pt solid #ddebf5;
        vertical-align: middle;
        color: #23282d;
    }
    table.reg-table tbody td.amt {
        text-align: right;
        font-family: "DejaVu Sans Mono", monospace;
        font-size: 7pt;
        white-space: nowrap;
    }
    table.reg-table tbody td.dim { color: #6a86a0; }
    table.reg-table tbody tr:last-child td { border-bottom: none; }
    table.reg-table tbody tr:hover td { background: #f5faff; }

    table.reg-table tfoot td {
        padding: 3pt 4pt;
        font-size: 7pt;
        font-weight: 800;
        color: #16355c;
        background: #e7f3fb;
        border-top: 1.5pt solid #16355c;
        border-bottom: 2pt solid #16355c;
    }
    table.reg-table tfoot td.amt {
        text-align: right;
        font-family: "DejaVu Sans Mono", monospace;
        white-space: nowrap;
    }

    .reg-status {
        display: inline-block;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 5.5pt;
        letter-spacing: 0.06em;
        padding: 1pt 4pt;
        border: 0.5pt solid #16355c;
        color: #16355c;
    }
    .reg-status.disposed { color: #dc2626; border-color: #dc2626; }
    .reg-status.hfs { color: #854d0e; border-color: #854d0e; }
    .reg-status.draft { color: #92400e; border-color: #92400e; }
    .reg-status.revaluation { color: #0079c8; border-color: #0079c8; background: #eef6fc; }

    .reg-link {
        color: #16355c;
        font-weight: 700;
        text-decoration: none;
        border-bottom: 0.5pt solid #9cc3e0;
    }
    .reg-link:hover { border-bottom-color: #16355c; }

    .reg-empty {
        color: #7a90a5;
        font-size: 6.5pt;
        font-style: italic;
        padding: 4pt 0;
    }

    /* Register row actions (three-dot menu) */
    .reg-row-actions { position: relative; }
    .reg-row-dots {
        background: none; border: none; cursor: pointer; padding: 2pt 3pt;
        font-size: 9pt; line-height: 1; color: #7a90a5; font-family: inherit;
    }
    .reg-row-dots:hover { color: #16355c; }
    .reg-row-menu {
        display: none; position: absolute; right: 0; top: 100%; z-index: 50;
        background: #fff; border: 1px solid #c9dff0; box-shadow: 0 4px 16px rgba(22,53,92,0.12);
        min-width: 100px; padding: 2pt 0;
    }
    .reg-row-menu.open { display: block; }
    .reg-row-menu a, .reg-row-menu button.menu-item {
        display: block; width: 100%; text-align: left; padding: 3pt 8pt;
        font-size: 6.5pt; font-weight: 600; font-family: Helvetica, Arial, "DejaVu Sans", sans-serif;
        background: none; border: none; cursor: pointer; color: #16355c; text-decoration: none;
    }
    .reg-row-menu a:hover, .reg-row-menu button.menu-item:hover { background: #eef6fc; }
    .reg-row-menu button.menu-item.danger { color: #dc2626; }
    .reg-row-menu button.menu-item.danger:hover { background: #fef2f2; }

    /* Register modal fields */
    .reg-modal-field label { display:block; font-size:5.5pt; font-weight:700; letter-spacing:0.07em; text-transform:uppercase; color:#4a5f78; margin-bottom:2pt; }
    .reg-modal-field input, .reg-modal-field select, .reg-modal-field textarea { width:100%; border:1px solid #c9dff0; padding:3pt 5pt; font-size:7pt; font-family:Helvetica, Arial, "DejaVu Sans", sans-serif; color:#16355c; background:#fff; outline:none; box-sizing:border-box; border-radius:0; }
    .reg-modal-field input:focus, .reg-modal-field select:focus, .reg-modal-field textarea:focus { border-color:#16355c; }

    /* ── Add-panel (collapsible inline forms) ── */
    .add-panel { margin-top:8pt; }
    .add-panel-head {
        display:inline-flex; align-items:center; gap:3pt;
        font-size:6.5pt; font-weight:700; text-transform:uppercase; letter-spacing:0.06em;
        cursor:pointer; background:none; border:1px solid #16355c; padding:3pt 7pt;
        font-family:Helvetica, Arial, "DejaVu Sans", sans-serif;
        transition:background 0.15s, color 0.15s; color:#16355c;
    }
    .add-panel-head:hover { background:#16355c; color:#fff; }
    .add-panel-body {
        display:none; margin-top:6pt; padding:8pt 10pt;
        border:1px solid #c9dff0; background:#f5faff;
    }
    .add-panel.open .add-panel-body { display:block; }

    /* ── Inline form grid (af-row / af-field) ── */
    .af-row { display:grid; grid-template-columns:repeat(4,1fr); gap:4pt 6pt; align-items:end; margin-bottom:5pt; }
    .af-field.wide { grid-column:span 2; }
    .af-field.full { grid-column:span 4; }
    .af-field label { display:block; font-size:5.5pt; font-weight:700; letter-spacing:0.07em; text-transform:uppercase; color:#4a5f78; margin-bottom:2pt; }
    .af-field input, .af-field select, .af-field textarea {
        width:100%; border:1px solid #c9dff0; padding:3pt 5pt; font-size:7pt;
        font-family:Helvetica, Arial, "DejaVu Sans", sans-serif; color:#16355c;
        background:#fff; outline:none; box-sizing:border-box; border-radius:0;
    }
    .af-field input:focus, .af-field select:focus, .af-field textarea:focus { border-color:#16355c; }
    .af-submit { display:flex; justify-content:flex-end; margin-top:4pt; }
    .af-hint { font-size:6pt; color:#6a86a0; margin-bottom:5pt; line-height:1.4; }

    /* ── Toggle switch ── */
    .toggle-wrap { position:relative; display:inline-flex; flex-direction:column; align-items:flex-start; gap:2pt; cursor:pointer; user-select:none; padding:2pt 0; }
    .toggle-wrap input[type=checkbox] { position:absolute; opacity:0; width:0; height:0; pointer-events:none; }
    .toggle-track { position:relative; display:inline-block; width:22pt; height:12pt; background:#c9dff0; border-radius:999px; transition:background 0.18s; flex-shrink:0; }
    .toggle-wrap input[type=checkbox]:checked ~ .toggle-track { background:#16355c; }
    .toggle-track::after { content:''; display:block; position:absolute; top:1.5pt; left:1.5pt; width:9pt; height:9pt; background:#fff; border-radius:50%; transition:transform 0.18s; box-shadow:0 0.5pt 1pt rgba(0,0,0,0.15); }
    .toggle-wrap input[type=checkbox]:checked ~ .toggle-track::after { transform:translateX(10pt); }
    .toggle-text { font-size:6pt; color:#6a86a0; line-height:1.3; }

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
