<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Annual Financial Statements — {{ $company->registered_name }}</title>
    <style>
        @page {
            margin: 16mm 18mm 18mm 18mm;
            size: A4 potrait;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            height: 100%;
        }

        body {
            font-family: Helvetica, Arial, "DejaVu Sans", sans-serif;
            font-size: 6pt;
            color: #23282d;
            line-height: 1.4;
            background: #fff;
        }

        /* ── Running header (repeats on every page) ── */
        .run-header {
            display: none;
        }

        .run-header .rh-name {
            display: none;
        }

        /* ── Running footer with page number ── */
        .run-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 15mm;
            text-align: center;
            font-size: 8.5pt;
            color: #777;
            border-top: 0.5pt solid #ddd;
            padding-top: 4pt;
        }

        .run-footer .pagenum:after {
            content: counter(page);
        }

        .content {
            margin: 0;
            padding: 0;
        }

        .section {
            page-break-after: always;
            padding: 8mm 6mm;
            margin: 0;
            overflow: hidden;
        }

        .section:last-of-type {
            page-break-after: auto;
        }

        /* ── Section running header (company + period strip) ── */
        .stmt-page-header {
            font-size: 6pt;
            color: #555;
            border-bottom: 0.75pt solid #c9dff0;
            padding-bottom: 5pt;
            margin-bottom: 16pt;
            line-height: 1.5;
        }

        /* ── Statement title ── */
        h1.afs-title {
            font-size: 12pt;
            font-weight: bold;
            margin: 0 0 4pt 0;
            color: #16355c;
            letter-spacing: 0;
            text-transform: none;
            border-bottom: none;
        }

        hr.stmt-rule {
            border: none;
            border-top: 0.75pt solid #b8d6ec;
            margin: 0 0 10pt 0;
        }

        h2.afs-sub {
            font-size: 10.5pt;
            font-weight: bold;
            margin: 8pt 0 3pt 0;
            color: #16355c;
            text-transform: none;
        }

        p.afs-para {
            margin: 0 0 7pt 0;
            text-align: justify;
            font-size: 8pt;
            line-height: 1.4;
            orphans: 2;
            widows: 2;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            padding: 0;
        }

        /* General information / info tables */
        table.info {
            margin: 8pt 0;
            font-size: 8pt;
        }

        table.info tr {
            page-break-inside: avoid;
        }

        table.info td {
            padding: 5pt 8pt;
            vertical-align: top;
            border-bottom: 0.5pt solid #eaf3fa;
        }

        table.info td.label {
            width: 40%;
            color: #333;
            font-weight: 500;
        }

        /* Index */
        table.index {
            margin: 10pt 0;
            font-size: 8pt;
        }

        table.index td {
            padding: 6pt 5pt;
            border-bottom: 0.5pt dotted #c9dff0;
            vertical-align: middle;
        }

        table.index td.pg {
            text-align: right;
            width: 40pt;
            font-weight: 500;
        }

        /* Financial figure tables (SOCI condensed / SOCE / detailed) */
        table.fig {
            width: 100%;
            margin: 5pt 0;
            border-collapse: collapse;
            font-size: 6pt;
        }

        table.fig th {
            font-weight: normal;
            border-bottom: 0.75pt solid #16355c;
            padding: 4pt 4pt 5pt 0;
            text-align: left;
            vertical-align: bottom;
            background: #fff;
            color: #4a5f78;
        }

        table.fig th.amt {
            text-align: right;
            width: 18%;
            font-weight: bold;
            color: #16355c;
            padding-right: 3pt;
        }

        table.fig th.note {
            text-align: center;
            width: 9%;
        }

        table.fig td {
            padding: 3.5pt 4pt;
            vertical-align: middle;
            line-height: 1.35;
        }

        table.fig td.amt {
            text-align: right;
            white-space: nowrap;
            padding-right: 3pt;
        }

        table.fig td.amt-neg {
            text-align: right;
            white-space: nowrap;
            padding-right: 3pt;
            color: #b91c1c;
        }

        table.fig td.note {
            text-align: center;
            width: 9%;
            color: #555;
        }

        /* Regular data row */
        table.fig tr.line td {
            border-bottom: 0.4pt solid #ddebf5;
        }

        /* Last line before a subtotal */
        table.fig tr.line-last td {
            border-bottom: 0.6pt solid #9cc3e0;
        }

        /* Subtotal row */
        table.fig tr.subtot td {
            font-weight: bold;
            color: #16355c;
            border-bottom: 0.75pt solid #16355c;
            padding: 4pt 4pt;
        }

        /* Grand total */
        table.fig tr.grand td {
            font-weight: bold;
            color: #16355c;
            border-top: 0.75pt solid #16355c;
            border-bottom: 1.5pt solid #16355c;
            background: #e7f3fb;
            padding: 5pt 4pt;
            text-transform: none;
        }

        /* Section/group heading row */
        table.fig tr.head td {
            font-weight: bold;
            font-size: 10pt;
            padding: 8pt 0 3pt 0;
            background: #fff;
            color: #16355c;
        }

        /* Sub-group heading */
        table.fig tr.subhead td {
            font-weight: bold;
            padding: 4pt 0 3pt 8pt;
            color: #0079c8;
            background: #fff;
        }

        /* ── Cover page ── */
        .cover {
            text-align: center;
            padding: 100pt 0 80pt 0;
            page-break-inside: avoid;
        }

        .cover .c-name {
            font-size: 26pt;
            font-weight: bold;
            text-transform: none;
            letter-spacing: 0.2pt;
            color: #16355c;
            margin-bottom: 6pt;
        }

        .cover .c-reg {
            font-size: 10.5pt;
            color: #555;
            margin-bottom: 30pt;
        }

        .cover .c-title {
            font-size: 17pt;
            font-weight: bold;
            color: #0079c8;
            margin-bottom: 10pt;
        }

        .cover .c-period {
            font-size: 12pt;
            color: #333;
        }

        /* ── Signature space ── */
        .sign-space {
            height: 50pt;
            border-bottom: 1pt solid #16355c;
            width: 200pt;
            margin: 20pt 0 4pt 0;
        }
    </style>
    @include('pdf._afs-styles')
    <style>
        /* AFS bundle: hide per-statement letterheads (the bundle has its own cover) */
        .afs-doc-header,
        .afs-letterhead-pdf {
            display: none !important;
        }

        /* Tighten table spacing slightly for the multi-statement bundle */
        .afs-table {
            margin: 2pt 0;
        }

        .afs-table td.afs-note {
            color: #23282d;
        }

        /* ── Accounting policies: Clicks-style multi-column spread ── */
        table.policy-cols {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 5pt;
        }

        td.policy-col {
            width: 25%;
            vertical-align: top;
            padding: 0 12pt 0 0;
        }

        td.policy-col.policy-col-last {
            padding-right: 0;
        }

        .policy-title {
            font-size: 11.5pt;
            font-weight: bold;
            color: #0079c8;
            margin: 0 0 5pt 0;
            line-height: 1.3;
        }

        .policy-heading {
            font-size: 9.5pt;
            font-weight: bold;
            color: #16355c;
            margin: 8pt 0 3pt 0;
            line-height: 1.35;
        }

        .policy-title + .policy-heading,
        td.policy-col .policy-heading:first-child {
            margin-top: 0;
        }

        p.policy-para {
            font-size: 8pt;
            line-height: 1.45;
            text-align: left;
            color: #23282d;
            margin: 0 0 6pt 0;
            orphans: 2;
            widows: 2;
        }

        .afs-continued {
            font-weight: normal;
            font-style: italic;
            font-size: 8pt;
            color: #4a5f78;
        }

        /* ── Two-up spread: two logical pages per potrait sheet ── */
        table.spread {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        td.spread-half {
            width: 50%;
            vertical-align: top;
            padding: 0 12pt 0 0;
        }

        td.spread-half.spread-right {
            padding: 0 0 0 12pt;
        }

        /* Notes section styling */
        .notes-container {
            margin: 8pt 0;
        }

        .note-item {
            margin-bottom: 10pt;
            padding-bottom: 8pt;
            border-bottom: 0.5pt solid #ddebf5;
            page-break-inside: avoid;
        }

        .note-item.note-item-wide {
            page-break-inside: auto;
        }

        .note-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .note-heading {
            font-weight: bold;
            font-size: 10.5pt;
            margin-bottom: 5pt;
            color: #16355c;
        }

        .note-body {
            font-size: 8pt;
            line-height: 1.45;
            text-align: justify;
            color: #262626;
            orphans: 2;
            widows: 2;
        }

        .note-body p {
            margin: 0 0 4pt 0;
        }

        .note-body p:last-child {
            margin: 0;
        }

        /* Better formatting for content */
        .afs-footer {
            margin-top: 14pt;
            padding-top: 8pt;
            border-top: 0.5pt solid #ddd;
            font-size: 8pt;
            color: #666;
            text-align: center;
        }

        /* Prevent tables splitting mid-row */
        table.fig,
        .afs-table {
            table-layout: auto;
            width: 100%;
        }

        tr { page-break-inside: avoid; }

        /* The outer two-up row must be allowed to split across pages,
           otherwise dompdf pushes the whole spread to a fresh page. */
        tr.spread-row { page-break-inside: auto; }

        .afs-para { orphans: 2; widows: 2; }
    </style>
</head>

<body>
    @php
        $regLine = $company->registration_number ? '(Registration number: ' . $company->registration_number . ')' : '';
        $fyEndLabel = \Carbon\Carbon::parse($endDate)->format('d F Y');
        $headerPeriod = 'Financial Statements for the year ended ' . $fyEndLabel;
        $curYear = \Carbon\Carbon::parse($endDate)->format('Y');
        $priorYear = \Carbon\Carbon::parse($priorAsOfDate)->format('Y');

        $directors = $afs->directors ?? [];
        $natureOfBusiness =
            $afs->nature_of_business ?:
            ($company->industry
                ? \App\Models\Company::industries()[$company->industry] ?? $company->industry
                : '—');
        $approvalLabel = $afs->approval_date
            ? \Carbon\Carbon::parse($afs->approval_date)->format('d F Y')
            : '__________________';

        $companyAddress = trim(
            implode(
                "\n",
                array_filter([
                    $company->address_line_1,
                    $company->address_line_2,
                    trim(($company->city ?? '') . ' ' . ($company->postal_code ?? '')),
                    $company->province,
                ]),
            ),
        );
        $registeredOffice = $afs->registered_office ?: $companyAddress;
        $businessAddress = $afs->business_address ?: $companyAddress;
        $postalAddress = $afs->postal_address ?: $companyAddress;

        // ── Condensed SOCI classification ──
        $codePrefix = fn($a) => (int) substr(ltrim((string) $a->account_code, '0'), 0, 4);
        $codeBetween = fn($acc, $lo, $hi) => $acc->filter(
            fn($a) => $codePrefix($a) >= $lo && $codePrefix($a) < $hi,
        );
        $sumN = fn($c, $field = 'net_amount') => (float) $c->sum($field);

        $revenue = $incomeAccounts->filter(fn($a) => $codePrefix($a) < 4500);
        $otherInc = $incomeAccounts->filter(fn($a) => $codePrefix($a) >= 4500);
        $cos = $codeBetween($expenseAccounts, 5000, 6000);
        $opex = $codeBetween($expenseAccounts, 6000, 7000);
        $fin = $codeBetween($expenseAccounts, 7000, 8000);
        $tax = $codeBetween($expenseAccounts, 8000, 9000);

        $C = [
            'rev' => $sumN($revenue),
            'oi' => $sumN($otherInc),
            'cos' => $sumN($cos),
            'opex' => $sumN($opex),
            'fin' => $sumN($fin),
            'tax' => $sumN($tax),
        ];
        $P = [
            'rev' => $sumN($revenue, 'prior_net_amount'),
            'oi' => $sumN($otherInc, 'prior_net_amount'),
            'cos' => $sumN($cos, 'prior_net_amount'),
            'opex' => $sumN($opex, 'prior_net_amount'),
            'fin' => $sumN($fin, 'prior_net_amount'),
            'tax' => $sumN($tax, 'prior_net_amount'),
        ];
        $derive = function ($x) {
            $gp = $x['rev'] - $x['cos'];
            $op = $gp + $x['oi'] - $x['opex'];
            $pbt = $op - $x['fin'];
            $profit = $pbt - $x['tax'];
            return compact('gp', 'op', 'pbt', 'profit');
        };
        $Cd = $derive($C);
        $Pd = $derive($P);

        // Amount formatting: nil shows as '-', negatives in parentheses.
        $m = function ($v) {
            if (round($v, 2) == 0) {
                return '-';
            }
            $n = number_format(abs($v), 0);
            return $v < 0 ? "($n)" : $n;
        };
        // Expense rows are presented as negatives in the condensed statement.
        $mNeg = fn($v) => round($v, 2) == 0 ? '-' : '(' . number_format(abs($v), 0) . ')';

        $ac = fn($v) => round((float)$v, 2) == 0 ? 'amt' : ($v < 0 ? 'amt amt-neg' : 'amt');

        $noteN = fn($slug) => isset($noteRefs[$slug]) ? $noteRefs[$slug]['n'] : '';

        // Index entries (best-effort page numbers; the footer shows the true page).
        $indexRows = [
            ["Director's Responsibilities and Approval", 3],
            ["Director's Report", 3],
            ["Practitioner's Compilation Report", 4],
            ['Statement of Comprehensive Income', 5],
            ['Statement of Financial Position', 5],
            ['Statement of Changes in Equity', 6],
            ['Statement of Cash Flows', 6],
            ['Accounting Policies', 7],
            ['Notes to the Financial Statements', 8],
            ['Detailed Income Statement', 10],
        ];

        // Accounting-policy notes vs substantive numbered notes.
        $policySlugs      = ['basis-of-preparation', 'accounting-policies'];
        $excludeFromNotes = array_merge($policySlugs, ['general-information']);
        $policyNotes      = $notes->whereIn('slug', $policySlugs)->sortBy('sort_order');
        $numberedNotes    = $notes->whereNotIn('slug', $excludeFromNotes)->sortBy('sort_order')->values();

        // ── Accounting policies: split into blocks and flow across five
        //    potrait columns per page (dompdf has no CSS multi-column).
        //    A paragraph whose first line is short and unpunctuated is
        //    treated as a topic heading (e.g. "Revenue recognition").
        $policyBlocks = [];
        foreach ($policyNotes as $pn) {
            $policyBlocks[] = ['type' => 'title', 'text' => $pn->title];
            foreach (preg_split("/\n{2,}/", trim($pn->body ?? '')) as $para) {
                $para = trim($para);
                if ($para === '') {
                    continue;
                }
                $lines = explode("\n", $para);
                $first = trim($lines[0]);
                if (count($lines) > 1 && mb_strlen($first) <= 60 && ! preg_match('/[.:;,]$/u', $first)) {
                    $policyBlocks[] = ['type' => 'heading', 'text' => $first];
                    $rest = trim(implode("\n", array_slice($lines, 1)));
                    if ($rest !== '') {
                        $policyBlocks[] = ['type' => 'para', 'text' => $rest];
                    }
                } else {
                    $policyBlocks[] = ['type' => 'para', 'text' => $para];
                }
            }
        }

        // Estimated text lines per block (~40 characters per column line at 9pt).
        $policyLines = function (array $b): float {
            return match ($b['type']) {
                'title'   => ceil(mb_strlen($b['text']) / 22) + 1.2,
                'heading' => ceil(mb_strlen($b['text']) / 36) + 1.0,
                default   => ceil(mb_strlen($b['text']) / 40) + 0.7,
            };
        };

        $policyNumCols     = 4;  // four columns of larger type per potrait page
        $policyColCapacity = 30; // usable lines per column on a potrait page
        $policyPages = [];
        $policyCols  = array_fill(0, $policyNumCols, []);
        $policyColIx = 0;
        $policyUsed  = 0.0;
        foreach ($policyBlocks as $b) {
            $h = $policyLines($b);
            if ($policyUsed > 0 && $policyUsed + $h > $policyColCapacity) {
                // Column is full: move to the next one, carrying along any
                // heading(s) sitting at the bottom so they stay with their text.
                $carry = [];
                while (! empty($policyCols[$policyColIx])
                    && in_array(end($policyCols[$policyColIx])['type'], ['title', 'heading'], true)) {
                    $carry[] = array_pop($policyCols[$policyColIx]);
                }
                $carry = array_reverse($carry);
                $policyColIx++;
                if ($policyColIx > $policyNumCols - 1) {
                    $policyPages[] = $policyCols;
                    $policyCols  = array_fill(0, $policyNumCols, []);
                    $policyColIx = 0;
                }
                $policyCols[$policyColIx] = $carry;
                $policyUsed = array_sum(array_map($policyLines, $carry));
            }
            $policyCols[$policyColIx][] = $b;
            $policyUsed += $h;
        }
        if (array_filter($policyCols)) {
            $policyPages[] = $policyCols;
        }

        // ── Notes: two-column spread with full-width breakout for wide
        //    movement schedules (PPE / intangibles / inventory).
        $noteIsWide = fn ($note) => ($note->isPpe() && ! empty($ppeMovements))
            || ($note->isIntangible() && ! empty($intangibleMovements))
            || ($note->isInventory() && ! empty($inventoryMovements));

        $noteEst = function ($note) use ($noteFigures): float {
            $h = 2.5; // heading + spacing
            $h += ceil(mb_strlen(trim($note->body ?? '')) / 70) + 1;
            $nf = $noteFigures[$note->id] ?? null;
            if ($nf && $nf['has']) {
                $rows = collect($nf['rows'])
                    ->filter(fn ($r) => abs($r['current']) >= 0.01 || abs($r['prior']) >= 0.01)
                    ->count();
                $h += ($rows + 2) * 1.3 + 2;
            }
            return $h;
        };

        // Stable note numbers for the two-up layout (loop order no longer global).
        $noteSeq = $numberedNotes->values()
            ->mapWithKeys(fn ($n, $i) => [$n->id => $noteRefs[$n->slug]['n'] ?? ($i + 1)])
            ->all();

        $notesColCapacity = 32;
        $notesLayout = [];
        $notesCols   = [[], []];
        $notesColIx  = 0;
        $notesUsed   = 0.0;
        foreach ($numberedNotes as $note) {
            if ($noteIsWide($note)) {
                if ($notesCols[0] || $notesCols[1]) {
                    // Rebalance a short run across both columns before flushing.
                    if (empty($notesCols[1]) && count($notesCols[0]) > 1) {
                        $half = (int) ceil(count($notesCols[0]) / 2);
                        $notesCols = [array_slice($notesCols[0], 0, $half), array_slice($notesCols[0], $half)];
                    }
                    $notesLayout[] = ['type' => 'spread', 'cols' => $notesCols];
                    $notesCols  = [[], []];
                    $notesColIx = 0;
                    $notesUsed  = 0.0;
                }
                $notesLayout[] = ['type' => 'wide', 'note' => $note];
                continue;
            }
            $h = $noteEst($note);
            if ($notesUsed > 0 && $notesUsed + $h > $notesColCapacity) {
                $notesColIx++;
                $notesUsed = 0.0;
                if ($notesColIx > 1) {
                    $notesLayout[] = ['type' => 'spread', 'cols' => $notesCols];
                    $notesCols  = [[], []];
                    $notesColIx = 0;
                }
            }
            $notesCols[$notesColIx][] = $note;
            $notesUsed += $h;
        }
        if ($notesCols[0] || $notesCols[1]) {
            if (empty($notesCols[1]) && count($notesCols[0]) > 1) {
                $half = (int) ceil(count($notesCols[0]) / 2);
                $notesCols = [array_slice($notesCols[0], 0, $half), array_slice($notesCols[0], $half)];
            }
            $notesLayout[] = ['type' => 'spread', 'cols' => $notesCols];
        }

        // PPE movement schedule helpers
        $ppeMovements = $ppeMovements ?? [];
        $ppeAllKeys = [
            'cost_opening', 'additions', 'subsequent_costs', 'revaluations',
            'cost_disposals', 'cost_other', 'cost_closing',
            'ad_opening', 'ad_reval_eliminated', 'depreciation_charge', 'ad_disposals', 'ad_closing',
            'ai_opening', 'impairment_charge', 'impairment_reversal', 'ai_disposals', 'ai_closing',
            'rev_surplus_opening', 'rev_surplus_gain', 'rev_surplus_loss', 'rev_surplus_closing',
            'carrying_opening', 'carrying_closing',
        ];
        $ppeTotals = array_fill_keys($ppeAllKeys, 0.0);
        foreach ($ppeMovements as $pm) {
            foreach ($ppeAllKeys as $k) { $ppeTotals[$k] += ($pm[$k] ?? 0.0); }
        }
        $ppeFmt  = fn($v) => $v == 0.0 ? '—' : number_format(abs($v), 2);
        $ppeSign = fn($v) => $v < 0 ? '('.number_format(abs($v), 2).')' : ($v == 0.0 ? '—' : number_format($v, 2));
        $ppeAny  = fn(string $k) => collect($ppeMovements)->contains(fn($m) => ($m[$k] ?? 0.0) != 0.0);

        $ppeShowDisposals    = $ppeAny('cost_disposals') || $ppeAny('ad_disposals');
        $ppeShowSubsequent   = $ppeAny('subsequent_costs');
        $ppeShowRevaluations = $ppeAny('revaluations') || $ppeAny('ad_reval_eliminated');
        $ppeShowOther        = $ppeAny('cost_other');
        $ppeShowImpairment   = $ppeAny('ai_opening') || $ppeAny('impairment_charge') || $ppeAny('impairment_reversal');
        $ppeShowRevSurplus   = $ppeAny('rev_surplus_opening') || $ppeAny('rev_surplus_gain');

        $ppeSections = array_filter([
            'COST' => array_filter([
                'cost_opening'     => 'Opening balance',
                'additions'        => 'Additions',
                'subsequent_costs' => $ppeShowSubsequent   ? 'Subsequent expenditure (IAS 16.7)' : null,
                'revaluations'     => $ppeShowRevaluations ? 'Revaluations'                       : null,
                'cost_disposals'   => $ppeShowDisposals    ? 'Disposals'                          : null,
                'cost_other'       => $ppeShowOther        ? 'Other adjustments'                  : null,
                'cost_closing'     => 'Closing balance',
            ]),
            'ACCUMULATED DEPRECIATION' => array_filter([
                'ad_opening'          => 'Opening balance',
                'ad_reval_eliminated' => $ppeShowRevaluations ? 'Eliminated on revaluation' : null,
                'depreciation_charge' => 'Charge for period',
                'ad_disposals'        => $ppeShowDisposals    ? 'Disposals'                  : null,
                'ad_closing'          => 'Closing balance',
            ]),
            'ACCUMULATED IMPAIRMENT (IAS 36)' => $ppeShowImpairment ? array_filter([
                'ai_opening'          => 'Opening balance',
                'impairment_charge'   => $ppeAny('impairment_charge')   ? 'Impairment losses recognised' : null,
                'impairment_reversal' => $ppeAny('impairment_reversal') ? 'Reversals (IAS 36.114)'        : null,
                'ai_disposals'        => $ppeShowDisposals              ? 'Disposals'                     : null,
                'ai_closing'          => 'Closing balance',
            ]) : null,
            'REVALUATION SURPLUS — OCI' => $ppeShowRevSurplus ? [
                'rev_surplus_opening' => 'Opening balance',
                'rev_surplus_gain'    => 'Gains recognised in OCI',
                'rev_surplus_loss'    => 'Losses / transfers',
                'rev_surplus_closing' => 'Closing balance',
            ] : null,
            'CARRYING AMOUNT' => [
                'carrying_opening' => 'Opening',
                'carrying_closing' => 'Closing',
            ],
        ]);

        // Intangible movement schedule helpers (IAS 38)
        $intangibleMovements = $intangibleMovements ?? [];
        $intAllKeys = [
            'cost_opening', 'additions', 'subsequent_costs', 'revaluations',
            'cost_disposals', 'cost_closing',
            'ad_opening', 'amortisation_charge', 'ad_disposals', 'ad_closing',
            'ai_opening', 'impairment_charge', 'impairment_reversal', 'ai_closing',
            'rev_surplus_opening', 'rev_surplus_gain', 'rev_surplus_loss', 'rev_surplus_closing',
            'carrying_opening', 'carrying_closing',
        ];
        $intTotals = array_fill_keys($intAllKeys, 0.0);
        foreach ($intangibleMovements as $im) {
            foreach ($intAllKeys as $k) { $intTotals[$k] += ($im[$k] ?? 0.0); }
        }
        $intFmt  = fn($v) => $v == 0.0 ? '—' : number_format(abs($v), 2);
        $intSign = fn($v) => $v < 0 ? '('.number_format(abs($v), 2).')' : ($v == 0.0 ? '—' : number_format($v, 2));
        $intAny  = fn(string $k) => collect($intangibleMovements)->contains(fn($m) => ($m[$k] ?? 0.0) != 0.0);

        $intShowDisposals    = $intAny('cost_disposals') || $intAny('ad_disposals');
        $intShowSubsequent   = $intAny('subsequent_costs');
        $intShowRevaluations = $intAny('revaluations');
        $intShowImpairment   = $intAny('ai_opening') || $intAny('impairment_charge') || $intAny('impairment_reversal');
        $intShowRevSurplus   = $intAny('rev_surplus_opening') || $intAny('rev_surplus_gain');

        $intSections = array_filter([
            'COST' => array_filter([
                'cost_opening'     => 'Opening balance',
                'additions'        => 'Additions',
                'subsequent_costs' => $intShowSubsequent   ? 'Subsequent expenditure (IAS 38.18)' : null,
                'revaluations'     => $intShowRevaluations ? 'Revaluations (IAS 38.75)'            : null,
                'cost_disposals'   => $intShowDisposals    ? 'Disposals'                           : null,
                'cost_closing'     => 'Closing balance',
            ]),
            'ACCUMULATED AMORTISATION' => array_filter([
                'ad_opening'          => 'Opening balance',
                'amortisation_charge' => 'Charge for period',
                'ad_disposals'        => $intShowDisposals ? 'Disposals' : null,
                'ad_closing'          => 'Closing balance',
            ]),
            'ACCUMULATED IMPAIRMENT (IAS 36)' => $intShowImpairment ? array_filter([
                'ai_opening'          => 'Opening balance',
                'impairment_charge'   => 'Impairment losses recognised',
                'impairment_reversal' => 'Reversals (IAS 36.114)',
                'ai_closing'          => 'Closing balance',
            ]) : null,
            'REVALUATION SURPLUS — OCI' => $intShowRevSurplus ? [
                'rev_surplus_opening' => 'Opening balance',
                'rev_surplus_gain'    => 'Gains recognised in OCI',
                'rev_surplus_loss'    => 'Losses / transfers',
                'rev_surplus_closing' => 'Closing balance',
            ] : null,
            'CARRYING AMOUNT' => [
                'carrying_opening' => 'Opening',
                'carrying_closing' => 'Closing',
            ],
        ]);

        // Inventory movement schedule helpers (IAS 2)
        $inventoryMovements = $inventoryMovements ?? [];
        $invAllKeys = [
            'opening_qty', 'opening_val',
            'qty_received', 'cost_received',
            'qty_issued', 'cost_issued',
            'qty_adjusted', 'cost_adjusted',
            'write_downs', 'reversals',
            'closing_qty', 'closing_val', 'carrying_val',
        ];
        $invTotals = array_fill_keys($invAllKeys, 0.0);
        foreach ($inventoryMovements as $im) {
            foreach ($invAllKeys as $k) { $invTotals[$k] += ($im[$k] ?? 0.0); }
        }
        $invFmt  = fn($v) => $v == 0.0 ? '—' : number_format(abs($v), 2);
        $invSign = fn($v) => $v < 0 ? '('.number_format(abs($v), 2).')' : ($v == 0.0 ? '—' : number_format($v, 2));
        $invAny  = fn(string $k) => collect($inventoryMovements)->contains(fn($m) => ($m[$k] ?? 0.0) != 0.0);

        $invHasWriteDowns = $invAny('write_downs');
        $invHasReversals  = $invAny('reversals');
        $invHasAdjusted   = $invAny('cost_adjusted');
        $invHasNrv        = $invHasWriteDowns || $invHasReversals;

        $invSections = array_filter([
            'COST' => array_filter([
                'opening_val'   => 'Opening stock',
                'cost_received' => 'Purchases / goods received',
                'cost_issued'   => 'Cost of sales / goods issued',
                'cost_adjusted' => $invHasAdjusted ? 'Adjustments' : null,
                'closing_val'   => 'Closing stock (at cost)',
            ]),
            'NRV WRITE-DOWN (IAS 2.34)' => $invHasNrv ? array_filter([
                'write_downs' => $invHasWriteDowns ? 'Write-down to NRV' : null,
                'reversals'   => $invHasReversals  ? 'Reversal of write-down (IAS 2.33)' : null,
            ]) : null,
            'CARRYING AMOUNT' => [
                'carrying_val' => 'Carrying amount (lower of cost and NRV)',
            ],
        ]);
    @endphp

    <div class="run-header">
        <div class="rh-name">{{ $company->registered_name }}</div>
        @if ($regLine)
            <div>{{ $regLine }}</div>
        @endif
        <div>{{ $headerPeriod }}</div>
    </div>
    <div class="run-footer"><span class="pagenum"></span></div>

    <div class="content">

        {{-- ═══════════ COVER ═══════════ --}}
        <div class="section">
            <div class="cover">
                <div class="c-name">{{ $company->registered_name }}</div>
                @if ($regLine)
                    <div class="c-reg">{{ $regLine }}</div>
                @endif
                <div class="c-title">Annual Financial Statements</div>
                <div class="c-period">for the year ended {{ $fyEndLabel }}</div>
            </div>
        </div>

        {{-- ═══════════ GENERAL INFORMATION | INDEX (two-up) ═══════════ --}}
        <div class="section">
            <div class="stmt-page-header">
                <strong>{{ $company->registered_name }}</strong>@if ($regLine) &nbsp;{{ $regLine }}@endif<br>{{ $headerPeriod }}
            </div>
            <table class="spread">
                <tr class="spread-row">
                    <td class="spread-half">
                        <h1 class="afs-title">General Information</h1>
                        <hr class="stmt-rule">
            <table class="info">
                <tr>
                    <td class="label">Country of incorporation and domicile</td>
                    <td>{{ $afs->country_of_incorporation ?: 'South Africa' }}</td>
                </tr>
                <tr>
                    <td class="label">Nature of business and principal activities</td>
                    <td>{{ $natureOfBusiness }}</td>
                </tr>
                <tr>
                    <td class="label">Director{{ count($directors) > 1 ? 's' : '' }}</td>
                    <td>
                        @forelse ($directors as $d)
                            {{ $d }}@if (!$loop->last)
                                <br>
                            @endif @empty —
                        @endforelse
                    </td>
                </tr>
                <tr>
                    <td class="label">Registered office</td>
                    <td>{!! nl2br(e($registeredOffice ?: '—')) !!}</td>
                </tr>
                <tr>
                    <td class="label">Business address</td>
                    <td>{!! nl2br(e($businessAddress ?: '—')) !!}</td>
                </tr>
                <tr>
                    <td class="label">Postal address</td>
                    <td>{!! nl2br(e($postalAddress ?: '—')) !!}</td>
                </tr>
                @if ($company->registration_number)
                    <tr>
                        <td class="label">Company registration number</td>
                        <td>{{ $company->registration_number }}</td>
                    </tr>
                @endif
                @if ($company->income_tax_number)
                    <tr>
                        <td class="label">Income tax number</td>
                        <td>{{ $company->income_tax_number }}</td>
                    </tr>
                @endif
                @if ($afs->practitioner_name)
                    <tr>
                        <td class="label">Practitioners</td>
                        <td>{{ $afs->practitioner_name }}@if ($afs->practitioner_qualification)
                                <br>{{ $afs->practitioner_qualification }}
                                @endif @if ($afs->practitioner_membership)
                                    <br>{{ $afs->practitioner_membership }}
                                @endif
                        </td>
                    </tr>
                @endif
            </table>
                    </td>
                    <td class="spread-half spread-right">
                        <h1 class="afs-title">Index</h1>
                        <hr class="stmt-rule">
            <p class="afs-para">The reports and statements set out below comprise the financial statements presented to
                the
                shareholder:</p>
            <table class="index">
                <tr>
                    <td style="font-weight:bold;">Contents</td>
                    <td class="pg" style="font-weight:bold;">Page</td>
                </tr>
                @foreach ($indexRows as [$label, $pg])
                    <tr>
                        <td>{{ $label }}</td>
                        <td class="pg">{{ $pg }}</td>
                    </tr>
                @endforeach
            </table>
            <h2 class="afs-sub">Level of assurance</h2>
            <p class="afs-para">
                {{ $afs->level_of_assurance ?: 'These financial statements have not been audited or independently reviewed.' }}
            </p>
            @if ($afs->practitioner_name)
                <h2 class="afs-sub">Preparer</h2>
                <p class="afs-para">{{ $afs->practitioner_name }}@if ($afs->practitioner_qualification)
                        , {{ $afs->practitioner_qualification }}
                    @endif
                </p>
            @endif
                    </td>
                </tr>
            </table>
        </div>

        {{-- ═══════════ DIRECTOR'S RESPONSIBILITIES | DIRECTOR'S REPORT (two-up) ═══════════ --}}
        <div class="section">
            <div class="stmt-page-header">
                <strong>{{ $company->registered_name }}</strong>@if ($regLine) &nbsp;{{ $regLine }}@endif<br>{{ $headerPeriod }}
            </div>
            <table class="spread">
                <tr class="spread-row">
                    <td class="spread-half">
                        <h1 class="afs-title">Director's Responsibilities and Approval</h1>
                        <hr class="stmt-rule">
            <p class="afs-para">The director is required by the Companies Act of South Africa to maintain adequate
                accounting records and is responsible for the content and integrity of the financial statements and
                related
                financial information included in this report. It is the director's responsibility to ensure that the
                financial statements fairly present the state of affairs of the company as at the end of the financial
                year
                and the results of its operations and cash flows for the period then ended, in conformity with the IFRS
                for
                SMEs Accounting Standard as issued by the International Accounting Standards Board.</p>
            <p class="afs-para">The financial statements are prepared in accordance with the IFRS for SMEs Accounting
                Standard and are based upon appropriate accounting policies consistently applied and supported by
                reasonable
                and prudent judgements and estimates.</p>
            <p class="afs-para">The director acknowledges that he/she is ultimately responsible for the system of
                internal
                financial control established by the company and places considerable importance on maintaining a strong
                control environment. The director is of the opinion that the system of internal control provides
                reasonable
                assurance that the financial records may be relied on for the preparation of the financial statements.
            </p>
            <p class="afs-para">The director has reviewed the company's cash flow forecast for the year to
                {{ \Carbon\Carbon::parse($endDate)->addYear()->format('d F Y') }} and, in the light of this review and
                the
                current financial position, is satisfied that the company has access to adequate resources to continue
                in
                operational existence for the foreseeable future.</p>
            <p class="afs-para">The financial statements were approved by the director on {{ $approvalLabel }} and were
                signed on its behalf by:</p>
            @forelse ($directors as $d)
                <div class="sign-space"></div>
                <div>{{ $d }}</div>
            @empty
                <div class="sign-space"></div>
                <div>Director</div>
            @endforelse
                    </td>
                    <td class="spread-half spread-right">
                        <h1 class="afs-title">Director's Report</h1>
                        <hr class="stmt-rule">
            <p class="afs-para">The director has pleasure in submitting the report on the financial statements of
                {{ $company->registered_name }} for the year ended {{ $fyEndLabel }}.</p>
            <h2 class="afs-sub">1. Nature of business</h2>
            <p class="afs-para">{{ $company->registered_name }} was incorporated in
                {{ $afs->country_of_incorporation ?: 'South Africa' }} with interests in the {{ $natureOfBusiness }}
                industry. There have been no material changes to the nature of the company's business from the prior
                year.
            </p>
            <h2 class="afs-sub">2. Review of financial results and activities</h2>
            <p class="afs-para">The financial statements have been prepared in accordance with the IFRS for SMEs
                Accounting
                Standard and the requirements of the Companies Act of South Africa. The accounting policies have been
                applied consistently compared to the prior year. Full details of the financial position, results of
                operations and cash flows of the company are set out in these financial statements.</p>
            <h2 class="afs-sub">3. Director</h2>
            <p class="afs-para">The director(s) in office at the date of this report are as follows:</p>
            <p class="afs-para">
                @forelse ($directors as $d)
                    {{ $d }}@if (!$loop->last)
                        <br>
                    @endif @empty —
                @endforelse
            </p>
            <h2 class="afs-sub">4. Going concern</h2>
            <p class="afs-para">The financial statements have been prepared on the basis of accounting policies
                applicable
                to a going concern. The director believes that the company has adequate financial resources to continue
                in
                operation for the foreseeable future and accordingly the financial statements have been prepared on a
                going
                concern basis.</p>
                    </td>
                </tr>
            </table>
        </div>

        {{-- ═══════════ PRACTITIONER'S COMPILATION REPORT (two-up, right half open) ═══════════ --}}
        <div class="section">
            <div class="stmt-page-header">
                <strong>{{ $company->registered_name }}</strong>@if ($regLine) &nbsp;{{ $regLine }}@endif<br>{{ $headerPeriod }}
            </div>
            <table class="spread">
                <tr class="spread-row">
                    <td class="spread-half">
            @if ($afs->practitioner_contact)
                <p class="afs-para" style="font-size:8pt;color:#444;">{!! nl2br(e($afs->practitioner_contact)) !!}</p>
            @endif
            <h1 class="afs-title">Practitioner's Compilation Report</h1>
                        <hr class="stmt-rule">
            <p class="afs-para">We have compiled the financial statements of {{ $company->registered_name }}
                {{ $regLine }}, based on the information you have provided. These financial statements comprise
                the
                statement of financial position at {{ $fyEndLabel }}, the statement of comprehensive income,
                statement of
                changes in equity and statement of cash flows for the year then ended, and a summary of significant
                accounting policies and other explanatory information.</p>
            <p class="afs-para">We performed this compilation engagement in accordance with International Standard on
                Related Services 4410 (Revised), Compilation Engagements.</p>
            <p class="afs-para">We have applied our expertise in accounting and financial reporting to assist you in the
                preparation and presentation of these financial statements in accordance with the International
                Financial
                Reporting Standard for Small and Medium-sized Entities. We have complied with relevant ethical
                requirements,
                including principles of integrity, objectivity, professional competence and due care.</p>
            <p class="afs-para">These financial statements and the accuracy and completeness of the information used to
                compile them are your responsibility.</p>
            <p class="afs-para">Since a compilation engagement is not an assurance engagement, we are not required to
                verify
                the accuracy or completeness of the information you provided. Accordingly, we do not express an audit
                opinion or a review conclusion on these financial statements.</p>
            <div class="sign-space"></div>
            @if ($afs->practitioner_name)
                <div>{{ $afs->practitioner_name }}</div>
            @endif
            @if ($afs->compilation_directors)
                <div style="font-size:8pt;color:#444;">{{ $afs->compilation_directors }}</div>
            @endif
                    </td>
                    <td class="spread-half spread-right"></td>
                </tr>
            </table>
        </div>

        {{-- ═══════════ STATEMENT OF COMPREHENSIVE INCOME (own page) ═══════════ --}}
        <div class="section">
            <div class="stmt-page-header">
                <strong>{{ $company->registered_name }}</strong>@if ($regLine) &nbsp;{{ $regLine }}@endif<br>{{ $headerPeriod }}
            </div>
            <h1 class="afs-title">Statement of Comprehensive Income</h1>
            <hr class="stmt-rule">
            <table class="fig">
                <thead>
                    <tr>
                        <th style="font-style:italic;">Figures in Rand</th>
                        <th class="note">Note(s)</th>
                        <th class="amt">{{ $curYear }}</th>
                        <th class="amt">{{ $priorYear }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="line">
                        <td>Revenue</td>
                        <td class="note">{{ $noteN('revenue') }}</td>
                        <td class="{{ $ac($C['rev']) }}">{{ $m($C['rev']) }}</td>
                        <td class="{{ $ac($P['rev']) }}">{{ $m($P['rev']) }}</td>
                    </tr>
                    <tr class="line">
                        <td>Cost of sales</td>
                        <td class="note"></td>
                        <td class="{{ $ac($C['cos']) }}">{{ $mNeg($C['cos']) }}</td>
                        <td class="{{ $ac($P['cos']) }}">{{ $mNeg($P['cos']) }}</td>
                    </tr>
                    <tr class="subtot">
                        <td>Gross {{ $Cd['gp'] >= 0 ? 'profit' : 'loss' }}</td>
                        <td class="note"></td>
                        <td class="{{ $ac($Cd['gp']) }}">{{ $m($Cd['gp']) }}</td>
                        <td class="{{ $ac($Pd['gp']) }}">{{ $m($Pd['gp']) }}</td>
                    </tr>
                    @if ($C['oi'] != 0 || $P['oi'] != 0)
                        <tr class="line">
                            <td>Other income</td>
                            <td class="note"></td>
                            <td class="{{ $ac($C['oi']) }}">{{ $m($C['oi']) }}</td>
                            <td class="{{ $ac($P['oi']) }}">{{ $m($P['oi']) }}</td>
                        </tr>
                    @endif
                    <tr class="line">
                        <td>Operating expenses</td>
                        <td class="note"></td>
                        <td class="{{ $ac($C['opex']) }}">{{ $mNeg($C['opex']) }}</td>
                        <td class="{{ $ac($P['opex']) }}">{{ $mNeg($P['opex']) }}</td>
                    </tr>
                    <tr class="subtot">
                        <td>Operating {{ $Cd['op'] >= 0 ? 'profit' : 'loss' }}</td>
                        <td class="note">{{ $noteN('operating-profit') }}</td>
                        <td class="{{ $ac($Cd['op']) }}">{{ $m($Cd['op']) }}</td>
                        <td class="{{ $ac($Pd['op']) }}">{{ $m($Pd['op']) }}</td>
                    </tr>
                    @if ($C['fin'] != 0 || $P['fin'] != 0)
                        <tr class="line">
                            <td>Finance costs</td>
                            <td class="note">{{ $noteN('finance-income-and-costs') }}</td>
                            <td class="{{ $ac($C['fin']) }}">{{ $mNeg($C['fin']) }}</td>
                            <td class="{{ $ac($P['fin']) }}">{{ $mNeg($P['fin']) }}</td>
                        </tr>
                    @endif
                    <tr class="subtot">
                        <td>{{ $Cd['pbt'] >= 0 ? 'Profit' : 'Loss' }} before taxation</td>
                        <td class="note"></td>
                        <td class="{{ $ac($Cd['pbt']) }}">{{ $m($Cd['pbt']) }}</td>
                        <td class="{{ $ac($Pd['pbt']) }}">{{ $m($Pd['pbt']) }}</td>
                    </tr>
                    <tr class="line">
                        <td>Taxation</td>
                        <td class="note">{{ $noteN('taxation') }}</td>
                        <td class="{{ $ac($C['tax']) }}">{{ $mNeg($C['tax']) }}</td>
                        <td class="{{ $ac($P['tax']) }}">{{ $mNeg($P['tax']) }}</td>
                    </tr>
                    <tr class="line">
                        <td>{{ $Cd['profit'] >= 0 ? 'Profit' : 'Loss' }} for the year</td>
                        <td class="note"></td>
                        <td class="{{ $ac($Cd['profit']) }}">{{ $m($Cd['profit']) }}</td>
                        <td class="{{ $ac($Pd['profit']) }}">{{ $m($Pd['profit']) }}</td>
                    </tr>
                    @php
                        $bundleOciList  = $ociAccounts ?? collect();
                        $bundleOciC     = (float) $bundleOciList->sum('oci_net');
                        $bundleOciP     = (float) $bundleOciList->sum('oci_net_prior');
                        $bundleTotalC   = $Cd['profit'] + $bundleOciC;
                        $bundleTotalP   = $Pd['profit'] + $bundleOciP;
                    @endphp
                    <tr class="line">
                        <td>Other comprehensive income</td>
                        <td class="note"></td>
                        <td class="{{ $ac($bundleOciC) }}">{{ $m($bundleOciC) }}</td>
                        <td class="{{ $ac($bundleOciP) }}">{{ $m($bundleOciP) }}</td>
                    </tr>
                    <tr class="grand">
                        <td>Total comprehensive {{ $bundleTotalC >= 0 ? 'income' : 'loss' }} for the year</td>
                        <td class="note"></td>
                        <td class="{{ $ac($bundleTotalC) }}">{{ $m($bundleTotalC) }}</td>
                        <td class="{{ $ac($bundleTotalP) }}">{{ $m($bundleTotalP) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- ═══════════ STATEMENT OF FINANCIAL POSITION (own page) ═══════════ --}}
        <div class="section">
            <div class="stmt-page-header">
                <strong>{{ $company->registered_name }}</strong>@if ($regLine) &nbsp;{{ $regLine }}@endif<br>{{ $headerPeriod }}
            </div>
            <h1 class="afs-title">Statement of Financial Position as at {{ $fyEndLabel }}</h1>
            <hr class="stmt-rule">
            @include('companies.reports._partials.sofp')
        </div>

        {{-- ═══════════ STATEMENT OF CHANGES IN EQUITY (own page) ═══════════ --}}
        <div class="section">
            <div class="stmt-page-header">
                <strong>{{ $company->registered_name }}</strong>@if ($regLine) &nbsp;{{ $regLine }}@endif<br>{{ $headerPeriod }}
            </div>
            <h1 class="afs-title">Statement of Changes in Equity</h1>
            <hr class="stmt-rule">
            @php
                $eq = $equityMovement;

                $soceOciC = (float) collect($ociAccounts ?? collect())->sum('oci_net');
                $soceOciP = (float) collect($ociAccounts ?? collect())->sum('oci_net_prior');

                $retainedOtherPrior   = round($eq['retained_open']
                    - $eq['retained_open_prior']
                    - $eq['profit_prior']
                    - $soceOciP, 2);

                $retainedOtherCurrent = round($eq['retained_close']
                    - $eq['retained_open']
                    - $eq['profit_current']
                    - $soceOciC, 2);

                $shareOtherPrior   = round($eq['share_open']   - $eq['share_open_prior'], 2);
                $shareOtherCurrent = round($eq['share_close']  - $eq['share_open'] - $eq['share_issue'], 2);

                $hasOtherPrior   = ($retainedOtherPrior   != 0 || $shareOtherPrior   != 0);
                $hasOtherCurrent = ($retainedOtherCurrent != 0 || $shareOtherCurrent != 0);
            @endphp
            <table class="fig">
                <thead>
                    <tr>
                        <th>Figures in Rand</th>
                        <th class="amt">Share capital</th>
                        <th class="amt">Retained income</th>
                        <th class="amt">Total equity</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="line">
                        <td>Balance at beginning of {{ $priorYear }}</td>
                        <td class="{{ $ac($eq['share_open_prior']) }}">{{ $m($eq['share_open_prior']) }}</td>
                        <td class="{{ $ac($eq['retained_open_prior']) }}">{{ $m($eq['retained_open_prior']) }}</td>
                        <td class="{{ $ac($eq['share_open_prior'] + $eq['retained_open_prior']) }}">{{ $m($eq['share_open_prior'] + $eq['retained_open_prior']) }}</td>
                    </tr>
                    <tr class="line">
                        <td>{{ $eq['profit_prior'] >= 0 ? 'Profit' : 'Loss' }} for the year</td>
                        <td class="amt">-</td>
                        <td class="{{ $ac($eq['profit_prior']) }}">{{ $m($eq['profit_prior']) }}</td>
                        <td class="{{ $ac($eq['profit_prior']) }}">{{ $m($eq['profit_prior']) }}</td>
                    </tr>
                    @if ($soceOciP != 0)
                        <tr class="line">
                            <td>Other comprehensive income</td>
                            <td class="amt">-</td>
                            <td class="{{ $ac($soceOciP) }}">{{ $m($soceOciP) }}</td>
                            <td class="{{ $ac($soceOciP) }}">{{ $m($soceOciP) }}</td>
                        </tr>
                    @endif
                    @if ($hasOtherPrior)
                        <tr class="line">
                            <td>Contributions and other equity movements</td>
                            <td class="{{ $ac($shareOtherPrior) }}">{{ $m($shareOtherPrior) }}</td>
                            <td class="{{ $ac($retainedOtherPrior) }}">{{ $m($retainedOtherPrior) }}</td>
                            <td class="{{ $ac($shareOtherPrior + $retainedOtherPrior) }}">{{ $m($shareOtherPrior + $retainedOtherPrior) }}</td>
                        </tr>
                    @endif
                    <tr class="subtot">
                        <td>Balance at beginning of {{ $curYear }}</td>
                        <td class="{{ $ac($eq['share_open']) }}">{{ $m($eq['share_open']) }}</td>
                        <td class="{{ $ac($eq['retained_open']) }}">{{ $m($eq['retained_open']) }}</td>
                        <td class="{{ $ac($eq['share_open'] + $eq['retained_open']) }}">{{ $m($eq['share_open'] + $eq['retained_open']) }}</td>
                    </tr>
                    @if ($eq['share_issue'] != 0)
                        <tr class="line">
                            <td>Issue of shares</td>
                            <td class="{{ $ac($eq['share_issue']) }}">{{ $m($eq['share_issue']) }}</td>
                            <td class="amt">-</td>
                            <td class="{{ $ac($eq['share_issue']) }}">{{ $m($eq['share_issue']) }}</td>
                        </tr>
                    @endif
                    <tr class="line">
                        <td>{{ $eq['profit_current'] >= 0 ? 'Profit' : 'Loss' }} for the year</td>
                        <td class="amt">-</td>
                        <td class="{{ $ac($eq['profit_current']) }}">{{ $m($eq['profit_current']) }}</td>
                        <td class="{{ $ac($eq['profit_current']) }}">{{ $m($eq['profit_current']) }}</td>
                    </tr>
                    @if ($soceOciC != 0)
                        <tr class="line">
                            <td>Other comprehensive income</td>
                            <td class="amt">-</td>
                            <td class="{{ $ac($soceOciC) }}">{{ $m($soceOciC) }}</td>
                            <td class="{{ $ac($soceOciC) }}">{{ $m($soceOciC) }}</td>
                        </tr>
                    @endif
                    @if ($hasOtherCurrent)
                        <tr class="line">
                            <td>Contributions and other equity movements</td>
                            <td class="{{ $ac($shareOtherCurrent) }}">{{ $m($shareOtherCurrent) }}</td>
                            <td class="{{ $ac($retainedOtherCurrent) }}">{{ $m($retainedOtherCurrent) }}</td>
                            <td class="{{ $ac($shareOtherCurrent + $retainedOtherCurrent) }}">{{ $m($shareOtherCurrent + $retainedOtherCurrent) }}</td>
                        </tr>
                    @endif
                    <tr class="grand">
                        <td>Balance at end of {{ $curYear }}</td>
                        <td class="{{ $ac($eq['share_close']) }}">{{ $m($eq['share_close']) }}</td>
                        <td class="{{ $ac($eq['retained_close']) }}">{{ $m($eq['retained_close']) }}</td>
                        <td class="{{ $ac($eq['share_close'] + $eq['retained_close']) }}">{{ $m($eq['share_close'] + $eq['retained_close']) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- ═══════════ STATEMENT OF CASH FLOWS (own page) ═══════════ --}}
        <div class="section">
            <div class="stmt-page-header">
                <strong>{{ $company->registered_name }}</strong>@if ($regLine) &nbsp;{{ $regLine }}@endif<br>{{ $headerPeriod }}
            </div>
            <h1 class="afs-title">Statement of Cash Flows</h1>
            <hr class="stmt-rule">
            @include('companies.reports._partials.socf')
        </div>

        {{-- ═══════════ ACCOUNTING POLICIES (five-column potrait spread) ═══════════ --}}
        @forelse ($policyPages as $pageCols)
            <div class="section">
                <div class="stmt-page-header">
                    <strong>{{ $company->registered_name }}</strong>@if ($regLine) &nbsp;{{ $regLine }}@endif<br>{{ $headerPeriod }}
                </div>
                <h1 class="afs-title">Accounting Policies @unless ($loop->first)<span class="afs-continued">continued</span>@endunless</h1>
                <hr class="stmt-rule">
                <table class="policy-cols">
                    <tr>
                        @foreach ($pageCols as $col)
                            <td class="policy-col {{ $loop->last ? 'policy-col-last' : '' }}">
                                @foreach ($col as $block)
                                    @if ($block['type'] === 'title')
                                        <div class="policy-title">{{ $block['text'] }}</div>
                                    @elseif ($block['type'] === 'heading')
                                        <div class="policy-heading">{{ $block['text'] }}</div>
                                    @else
                                        <p class="policy-para">{!! nl2br(e($block['text'])) !!}</p>
                                    @endif
                                @endforeach
                            </td>
                        @endforeach
                    </tr>
                </table>
            </div>
        @empty
            <div class="section">
                <div class="stmt-page-header">
                    <strong>{{ $company->registered_name }}</strong>@if ($regLine) &nbsp;{{ $regLine }}@endif<br>{{ $headerPeriod }}
                </div>
                <h1 class="afs-title">Accounting Policies</h1>
                <hr class="stmt-rule">
                <p class="afs-para">The financial statements have been prepared in accordance with the IFRS for SMEs
                    Accounting Standard and the Companies Act of South Africa, on the historical cost basis. These
                    accounting policies are consistent with the previous period.</p>
            </div>
        @endforelse

        {{-- ═══════════ NOTES TO THE FINANCIAL STATEMENTS (two-up spread) ═══════════ --}}
        @foreach ($notesLayout as $notesChunk)
            <div class="section">
            <div class="stmt-page-header">
                <strong>{{ $company->registered_name }}</strong>@if ($regLine) &nbsp;{{ $regLine }}@endif<br>{{ $headerPeriod }}
            </div>
                <h1 class="afs-title">Notes to the Financial Statements @unless ($loop->first)<span class="afs-continued">continued</span>@endunless</h1>
                <hr class="stmt-rule">
                @if ($notesChunk['type'] === 'spread')
                    <table class="spread">
                        <tr class="spread-row">
                            @foreach ($notesChunk['cols'] as $colNotes)
                                <td class="spread-half {{ $loop->last ? 'spread-right' : '' }}">
                                    @foreach ($colNotes as $note)
                                        @php $seqNum = $noteSeq[$note->id] ?? ''; @endphp
                                        <div class="note-item">
                                            <div class="note-heading">{{ $seqNum }}. {{ $note->title }}</div>
                                            @if ($note->body)
                                                <div class="note-body">{!! nl2br(e($note->body)) !!}</div>
                                            @endif

                                        @php $nf = $noteFigures[$note->id] ?? null; @endphp
                        @if ($nf && $nf['has'] && ! $note->isPpe() && ! $note->isIntangible() && ! $note->isInventory())
                            <table style="width:100%;border-collapse:collapse;font-size:6pt;margin-top:8pt;">
                                <thead>
                                    <tr>
                                        <th style="text-align:left;padding:3pt 4pt;border-bottom:0.75pt solid #333;"></th>
                                        <th style="text-align:right;padding:3pt 4pt;border-bottom:0.75pt solid #333;white-space:nowrap;">{{ $curYear }}</th>
                                        <th style="text-align:right;padding:3pt 4pt;border-bottom:0.75pt solid #333;white-space:nowrap;">{{ $priorYear }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($nf['rows'] as $row)
                                        @if (abs($row['current']) >= 0.01 || abs($row['prior']) >= 0.01)
                                        <tr>
                                            <td style="padding:2.5pt 4pt;border-bottom:0.4pt dashed #e0e0e0;">{{ $row['label'] }}</td>
                                            <td style="text-align:right;padding:2.5pt 4pt;border-bottom:0.4pt dashed #e0e0e0;white-space:nowrap;{{ $row['current'] < 0 ? 'color:#b91c1c;' : '' }}">{{ $row['current'] != 0 ? number_format(abs($row['current']), 2) : '—' }}</td>
                                            <td style="text-align:right;padding:2.5pt 4pt;border-bottom:0.4pt dashed #e0e0e0;white-space:nowrap;{{ $row['prior'] < 0 ? 'color:#b91c1c;' : '' }}">{{ $row['prior'] != 0 ? number_format(abs($row['prior']), 2) : '—' }}</td>
                                        </tr>
                                        @endif
                                    @endforeach
                                    <tr>
                                        <td style="padding:3pt 4pt;font-weight:bold;border-top:0.75pt solid #333;border-bottom:1.5pt double #333;">Total</td>
                                        <td style="text-align:right;padding:3pt 4pt;font-weight:bold;border-top:0.75pt solid #333;border-bottom:1.5pt double #333;white-space:nowrap;{{ $nf['total_current'] < 0 ? 'color:#b91c1c;' : '' }}">{{ number_format(abs($nf['total_current']), 2) }}</td>
                                        <td style="text-align:right;padding:3pt 4pt;font-weight:bold;border-top:0.75pt solid #333;border-bottom:1.5pt double #333;white-space:nowrap;{{ $nf['total_prior'] < 0 ? 'color:#b91c1c;' : '' }}">{{ number_format(abs($nf['total_prior']), 2) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        @endif
                                        </div>
                                    @endforeach
                                </td>
                            @endforeach
                        </tr>
                    </table>
                @else
                    @php $note = $notesChunk['note']; $seqNum = $noteSeq[$note->id] ?? ''; @endphp
                    <div class="note-item note-item-wide">
                                            <div class="note-heading">{{ $seqNum }}. {{ $note->title }}</div>
                                            @if ($note->body)
                                                <div class="note-body">{!! nl2br(e($note->body)) !!}</div>
                                            @endif

                        @if ($note->isPpe() && !empty($ppeMovements))
                            {{-- PPE Movement Schedule ─────────────────────────── --}}
                            @php $ppeCols = count($ppeMovements) + 2; @endphp
                            <table class="fig" style="margin-top:6pt;">
                                <thead>
                                    <tr>
                                        <th></th>
                                        @foreach ($ppeMovements as $pm)
                                            <th class="amt">
                                                {{ $pm['class']->name }}@unless ($pm['has_cost_links'] && $pm['has_ad_links']) *@endunless
                                            </th>
                                        @endforeach
                                        <th class="amt">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($ppeSections as $sectionLabel => $keys)
                                        <tr class="subhead">
                                            <td colspan="{{ $ppeCols }}">{{ $sectionLabel }}</td>
                                        </tr>
                                        @foreach ($keys as $key => $rowLabel)
                                            @php
                                                $isClosing  = str_ends_with($key, '_closing');
                                                $isCarrying = $sectionLabel === 'CARRYING AMOUNT';
                                                $isMinus    = in_array($key, ['cost_disposals','ad_reval_eliminated','ad_disposals','ai_disposals','impairment_reversal','rev_surplus_loss']);
                                                $rowClass   = $isCarrying ? 'grand' : ($isClosing ? 'subtot' : 'line');
                                            @endphp
                                            <tr class="{{ $rowClass }}">
                                                <td style="padding-left:6pt;">{{ $isMinus ? '('.$rowLabel.')' : $rowLabel }}</td>
                                                @foreach ($ppeMovements as $pm)
                                                    @php $v = $pm[$key] ?? 0.0; @endphp
                                                    <td class="amt">{{ $isMinus ? ($v != 0 ? '('.$ppeFmt($v).')' : '—') : $ppeSign($v) }}</td>
                                                @endforeach
                                                @php $tv = $ppeTotals[$key] ?? 0.0; @endphp
                                                <td class="amt">{{ $isMinus ? ($tv != 0 ? '('.$ppeFmt($tv).')' : '—') : $ppeSign($tv) }}</td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        @endif

                        @if ($note->isIntangible() && !empty($intangibleMovements))
                            {{-- Intangible Movement Schedule (IAS 38) ─────────── --}}
                            @php $intCols = count($intangibleMovements) + 2; @endphp
                            <table class="fig" style="margin-top:6pt;">
                                <thead>
                                    <tr>
                                        <th></th>
                                        @foreach ($intangibleMovements as $im)
                                            <th class="amt">{{ $im['class']->name }}</th>
                                        @endforeach
                                        <th class="amt">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($intSections as $sectionLabel => $keys)
                                        <tr class="subhead">
                                            <td colspan="{{ $intCols }}">{{ $sectionLabel }}</td>
                                        </tr>
                                        @foreach ($keys as $key => $rowLabel)
                                            @php
                                                $isClosing  = str_ends_with($key, '_closing');
                                                $isCarrying = $sectionLabel === 'CARRYING AMOUNT';
                                                $isMinus    = in_array($key, ['cost_disposals','ad_disposals','impairment_reversal','rev_surplus_loss']);
                                                $rowClass   = $isCarrying ? 'grand' : ($isClosing ? 'subtot' : 'line');
                                            @endphp
                                            <tr class="{{ $rowClass }}">
                                                <td style="padding-left:6pt;">{{ $isMinus ? '('.$rowLabel.')' : $rowLabel }}</td>
                                                @foreach ($intangibleMovements as $im)
                                                    @php $v = $im[$key] ?? 0.0; @endphp
                                                    <td class="amt">{{ $isMinus ? ($v != 0 ? '('.$intFmt($v).')' : '—') : $intSign($v) }}</td>
                                                @endforeach
                                                @php $tv = $intTotals[$key] ?? 0.0; @endphp
                                                <td class="amt">{{ $isMinus ? ($tv != 0 ? '('.$intFmt($tv).')' : '—') : $intSign($tv) }}</td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        @endif

                        @if ($note->isInventory() && !empty($inventoryMovements))
                            {{-- Inventory Movement Schedule (IAS 2) ────────────── --}}
                            @php $invCols = count($inventoryMovements) + 2; @endphp
                            <table class="fig" style="margin-top:6pt;">
                                <thead>
                                    <tr>
                                        <th></th>
                                        @foreach ($inventoryMovements as $im)
                                            <th class="amt">{{ $im['type_label'] }}</th>
                                        @endforeach
                                        <th class="amt">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($invSections as $sectionLabel => $keys)
                                        <tr class="subhead">
                                            <td colspan="{{ $invCols }}">{{ $sectionLabel }}</td>
                                        </tr>
                                        @foreach ($keys as $key => $rowLabel)
                                            @php
                                                $isClosing  = in_array($key, ['closing_val']);
                                                $isCarrying = $sectionLabel === 'CARRYING AMOUNT';
                                                $isMinus    = in_array($key, ['cost_issued', 'write_downs']);
                                                $rowClass   = $isCarrying ? 'grand' : ($isClosing ? 'subtot' : 'line');
                                            @endphp
                                            <tr class="{{ $rowClass }}">
                                                <td style="padding-left:6pt;">{{ $isMinus ? '('.$rowLabel.')' : $rowLabel }}</td>
                                                @foreach ($inventoryMovements as $im)
                                                    @php $v = $im[$key] ?? 0.0; @endphp
                                                    <td class="amt">{{ $isMinus ? ($v != 0 ? '('.$invFmt($v).')' : '—') : $invSign($v) }}</td>
                                                @endforeach
                                                @php $tv = $invTotals[$key] ?? 0.0; @endphp
                                                <td class="amt">{{ $isMinus ? ($tv != 0 ? '('.$invFmt($tv).')' : '—') : $invSign($tv) }}</td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach

        {{-- ═══════════ DETAILED INCOME STATEMENT ═══════════ --}}
        <div class="section">
            <div class="stmt-page-header">
                <strong>{{ $company->registered_name }}</strong>@if ($regLine) &nbsp;{{ $regLine }}@endif<br>{{ $headerPeriod }}
            </div>
            <h1 class="afs-title">Detailed Income Statement</h1>
            <hr class="stmt-rule">
            @include('companies.reports._partials.soci')
        </div>

    </div>

</body>

</html>
