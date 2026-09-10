/**
 * Excel renderer for the monthly management accounts pack.
 *
 * The design is the one in CGL-YE25-Annual-Financial-Statements.docx, the same
 * source afs-docx.ts reproduces, carried onto a worksheet. The constants below
 * are the ones measured from that file — see afs-docx.ts, which remains the
 * reference for where each came from.
 *
 * The three rules most easily got wrong, all enforced here:
 *
 *   1. The header row is NOT a full blue band. Only a current-period cell is
 *      filled #005BF0 with white text and a #005BF0 rule; every other header
 *      cell is plain with a black hairline.
 *   2. #EAF8FB is a column band, not row striping. It falls only on a
 *      current-period column, runs unbroken through section and subtotal rows,
 *      and that column is bold on every row. Comparative and variance columns
 *      are plain and regular weight throughout.
 *   3. There are no double rules anywhere, not even under the grand total.
 *      Every rule is a single black hairline — Excel's `thin`, its closest
 *      equivalent to the document's 0.5pt.
 *
 * A management pack has two current columns where the statutory statements
 * have one: the period actual and the year-to-date actual. Both are marked
 * current by the model and both therefore carry the header fill and the band.
 * That is the honest extension of the rule — the band marks the figures the
 * reader came for — rather than an arbitrary choice between them.
 *
 * ── Deliberate deviation from the Word renderer ──────────────────────────────
 *
 * afs-docx.ts writes pre-formatted strings, because a Word table is a picture
 * of numbers. A workbook is not: a management pack that cannot be summed,
 * charted or extended is not a template. So each row carries a parallel `raw`
 * array of live floats, and the document's appearance — space thousands
 * separator, parentheses for negatives, em dash for nil — is reproduced with
 * an Excel number format instead of by writing text. `values` stays
 * authoritative for how a figure should look, and the two are built together
 * by ManagementAccountsModelService so they cannot disagree.
 */
import ExcelJS from "exceljs";
// ─── Measured constants (see afs-docx.ts for provenance) ──────────────────────
export const FONT = "Century Gothic";
/** Points. The document's ladder: 25 / 13 / 11.5 / 10.5 / 7. */
const SIZE = {
    body: 10.5,
    title: 25,
    sectionHead: 13,
    dateLine: 11.5,
    footnote: 7,
};
/** ARGB, as ExcelJS wants colours. */
const COLOR = {
    body: "FF191919",
    rule: "FF000000",
    emphasis: "FF005BF0", // section heads, subtotals, grand totals, header fill
    navy: "FF1A345B", // title and date line
    tint: "FFEAF8FB", // current-period column band
    white: "FFFFFFFF",
    muted: "FF5A7186",
};
/** w:line 270 exact = 13.5pt. Row height comes entirely from this. */
const ROW_HEIGHT = 13.5;
/** Excel's thinnest border is its nearest equivalent to the document's 0.5pt. */
const HAIRLINE = { style: "thin", color: { argb: COLOR.rule } };
const HAIRLINE_BLUE = { style: "thin", color: { argb: COLOR.emphasis } };
/** Row styles carrying a closing hairline across every cell. */
const RULED = new Set(["item-last", "subtotal", "named-subtotal", "grand-total"]);
/** Row styles whose label is bold #005BF0. */
const EMPHASIS = new Set(["section", "section-sub", "subtotal", "named-subtotal", "grand-total"]);
/**
 * Column widths, in the document's twips, converted to Excel's character unit.
 * Label and amount are the income statement's 7725 and 1815; the percentage
 * column is narrowed since it holds at most "(99.9%)".
 */
const WIDTH_TWIPS = {
    label: 7725,
    amount: 1815,
    variance: 1815,
    percent: 1150,
};
/**
 * Twips to Excel column width.
 *
 * A twip is 1/1440in; Excel's unit is the width of a digit in the workbook's
 * default font, which at 96dpi is 7px plus 5px of cell padding.
 */
function twipsToWidth(twips) {
    const px = twips / 15;
    return Math.round(((px - 5) / 7) * 100) / 100;
}
/** ExcelJS rejects null where it wants a string. */
export function str(value) {
    return value === null || value === undefined ? "" : String(value);
}
// ─── Number formats ───────────────────────────────────────────────────────────
/**
 * The document's figure conventions as an Excel format: space thousands
 * separator, parentheses for negatives, em dash for nil.
 *
 * The space is escaped so it survives as a group separator regardless of the
 * reader's locale, which would otherwise substitute a comma or a full stop.
 */
function moneyFormat(decimals) {
    const body = decimals > 0 ? `#\\ ##0.${"0".repeat(decimals)}` : "#\\ ##0";
    return `${body};(${body});"—"`;
}
/**
 * Percentages are stored as the number of percent — 12.4 means 12.4% — so the
 * literal sign is appended rather than using Excel's `%`, which would multiply
 * the stored value by a hundred.
 */
const PERCENT_FORMAT = `0.0"%";(0.0"%");"—"`;
function styleCell(cell, s) {
    cell.font = {
        name: FONT,
        size: s.size ?? SIZE.body,
        bold: s.bold ?? false,
        color: { argb: s.color ?? COLOR.rule },
    };
    if (s.fill) {
        cell.fill = { type: "pattern", pattern: "solid", fgColor: { argb: s.fill } };
    }
    if (s.ruled) {
        cell.border = { bottom: HAIRLINE };
    }
    cell.alignment = { vertical: "bottom", horizontal: s.align ?? "left", wrapText: s.wrap ?? false };
    if (s.numFmt) {
        cell.numFmt = s.numFmt;
    }
}
/**
 * Write one figure.
 *
 * A live number wherever the model has one, so the sheet stays a working
 * template. Where it has none the cell either carries the model's own marker
 * (an em dash for a rate that cannot be computed) or stays genuinely empty —
 * an empty cell and a nil cell are different facts and are kept apart.
 */
function writeFigure(cell, raw, value) {
    if (raw !== null && Number.isFinite(raw)) {
        cell.value = raw;
        return;
    }
    const text = str(value).trim();
    cell.value = text === "" ? null : text;
}
// ─── Statement sheet ──────────────────────────────────────────────────────────
/**
 * Render one statement onto its own worksheet: title block, header row, body,
 * then any footnotes.
 */
function writeStatement(workbook, statement, model, sheetName, numeric) {
    const sheet = workbook.addWorksheet(sheetName, {
        // The document draws its own hairlines; Excel's gridlines are chrome that
        // is not part of the design.
        views: [{ showGridLines: false }],
        pageSetup: {
            orientation: "landscape",
            fitToPage: true,
            fitToWidth: 1,
            fitToHeight: 0,
            margins: { left: 0.4, right: 0.4, top: 0.5, bottom: 0.4, header: 0.2, footer: 0.2 },
        },
    });
    const columns = statement.columns;
    const lastCol = columns.length;
    // The column style is what a cell with no explicit style of its own picks
    // up, so figures the reader adds to the sheet land in the document's face
    // rather than the workbook default of Calibri.
    sheet.columns = columns.map((column) => ({
        width: twipsToWidth(WIDTH_TWIPS[column.kind] ?? WIDTH_TWIPS.amount),
        style: { font: { name: FONT, size: SIZE.body } },
    }));
    const moneyFmt = moneyFormat(model.rounding.decimals);
    // ── Title block: company name, statement title, then the date line. The
    // 25pt slot belongs to the statement title; the company name rides the 13pt
    // letterhead treatment above it.
    const titleLines = [
        [str(model.company.name) || "Management Accounts", SIZE.sectionHead, true],
        [str(statement.title), SIZE.title, true],
        [str(statement.subtitle), SIZE.dateLine, false],
    ];
    titleLines.forEach(([text, size, bold]) => {
        const row = sheet.addRow([text]);
        // Display type needs room the 13.5pt body leading does not give it.
        row.height = Math.ceil(size * 1.35);
        styleCell(row.getCell(1), { size, bold, color: COLOR.navy });
        if (lastCol > 1) {
            sheet.mergeCells(row.number, 1, row.number, lastCol);
        }
    });
    sheet.addRow([]).height = ROW_HEIGHT;
    // ── Header row. Only a current-period cell is filled.
    const header = sheet.addRow(columns.map((column) => {
        const label = str(column.text);
        const sub = str(column.sub);
        // The rounding basis rides under its column label, stacked, as it does
        // in the source — where it is a second run carrying a line break. A
        // worksheet cannot vary type size within a cell, so the basis takes the
        // same size on its own line rather than the document's smaller one.
        return column.kind === "label" || sub === "" ? label : `${label}\n${sub}`;
    }));
    // Two stacked lines on the body's 13.5pt leading.
    header.height = ROW_HEIGHT * 2;
    columns.forEach((column, i) => {
        const cell = header.getCell(i + 1);
        const isCurrent = column.current === true;
        styleCell(cell, {
            bold: isCurrent,
            color: isCurrent ? COLOR.white : COLOR.rule,
            fill: isCurrent ? COLOR.emphasis : undefined,
            align: column.kind === "label" ? "left" : "right",
            wrap: true,
        });
        cell.border = { bottom: isCurrent ? HAIRLINE_BLUE : HAIRLINE };
    });
    const headerRowNumber = header.number;
    // ── Body rows.
    statement.rows.forEach((row) => {
        const sheetRow = sheet.addRow([str(row.label)]);
        sheetRow.height = ROW_HEIGHT;
        const emphasised = EMPHASIS.has(row.style);
        const ruled = RULED.has(row.style);
        // A margin line is a rate sitting among figures; it takes the muted
        // treatment so it reads as commentary on the subtotal above it.
        const margin = row.style === "margin";
        styleCell(sheetRow.getCell(1), {
            bold: emphasised,
            color: emphasised ? COLOR.emphasis : margin ? COLOR.muted : COLOR.rule,
            ruled,
            align: "left",
        });
        let valueIndex = 0;
        columns.forEach((column, i) => {
            if (column.kind === "label")
                return;
            const cell = sheetRow.getCell(i + 1);
            // Statistics tables come straight from the statistics service and carry
            // no `raw` at all, so this is read defensively rather than indexed.
            const raw = row.raw?.[valueIndex] ?? null;
            const value = row.values[valueIndex] ?? "";
            valueIndex += 1;
            const isCurrent = column.current === true;
            const isRate = column.kind === "percent" || margin;
            if (numeric) {
                writeFigure(cell, raw, value);
            }
            else {
                // Statistics tables mix money, days, ratios and counts in one column,
                // so no single number format fits; they stay as the strings the
                // statistics service formatted.
                const text = str(value).trim();
                cell.value = text === "" ? null : text;
            }
            styleCell(cell, {
                // The current column is bold on every row, including section and
                // spacer rows; comparatives and variances are regular throughout.
                bold: isCurrent,
                color: margin ? COLOR.muted : COLOR.rule,
                fill: isCurrent ? COLOR.tint : undefined,
                ruled,
                align: "right",
                numFmt: numeric ? (isRate ? PERCENT_FORMAT : moneyFmt) : undefined,
            });
        });
    });
    // ── Footnotes.
    const notes = statement.footnotes ?? [];
    if (notes.length > 0) {
        sheet.addRow([]).height = ROW_HEIGHT;
        notes.forEach((note) => {
            const row = sheet.addRow([str(note)]);
            row.height = ROW_HEIGHT;
            styleCell(row.getCell(1), { size: SIZE.footnote, color: COLOR.navy });
            if (lastCol > 1) {
                sheet.mergeCells(row.number, 1, row.number, lastCol);
            }
        });
    }
    // Keep the labels and the header in view while reading across the variance
    // columns — the one affordance a worksheet has that the page does not.
    sheet.views = [
        { state: "frozen", xSplit: 1, ySplit: headerRowNumber, showGridLines: false },
    ];
}
// ─── Cover sheet ──────────────────────────────────────────────────────────────
function writeCover(workbook, model) {
    const sheet = workbook.addWorksheet("Cover", {
        views: [{ showGridLines: false }],
        pageSetup: { orientation: "landscape", margins: { left: 0.4, right: 0.4, top: 0.5, bottom: 0.4, header: 0.2, footer: 0.2 } },
    });
    sheet.columns = [
        { width: twipsToWidth(WIDTH_TWIPS.label), style: { font: { name: FONT, size: SIZE.body } } },
        { width: twipsToWidth(4000), style: { font: { name: FONT, size: SIZE.body } } },
    ];
    const line = (text, size, bold, color) => {
        const row = sheet.addRow([text]);
        row.height = Math.ceil(size * 1.35);
        styleCell(row.getCell(1), { size, bold, color });
        sheet.mergeCells(row.number, 1, row.number, 2);
    };
    const field = (label, value) => {
        const row = sheet.addRow([label, value]);
        row.height = ROW_HEIGHT;
        styleCell(row.getCell(1), { color: COLOR.rule });
        styleCell(row.getCell(2), { bold: true, color: COLOR.rule });
    };
    line(str(model.company.name) || "Management Accounts", SIZE.sectionHead, true, COLOR.navy);
    line("Management Accounts", SIZE.title, true, COLOR.navy);
    line(str(model.period.label), SIZE.dateLine, false, COLOR.navy);
    sheet.addRow([]).height = ROW_HEIGHT;
    field("Reporting period", `${model.period.period_start} to ${model.period.period_end}`);
    field("Year to date", str(model.period.ytd_label));
    field("Figures stated in", str(model.rounding.label));
    field("Comparatives", model.compare ? "Prior year, equivalent period" : "None");
    if (model.company.registration_number)
        field("Registration number", str(model.company.registration_number));
    if (model.company.vat_number)
        field("VAT number", str(model.company.vat_number));
    if (model.company.income_tax_number)
        field("Income tax number", str(model.company.income_tax_number));
    field("Prepared", str(model.generated_at));
    sheet.addRow([]).height = ROW_HEIGHT;
    line("Contents", SIZE.sectionHead, true, COLOR.emphasis);
    [...model.statements, ...model.statistics].forEach((statement) => {
        const row = sheet.addRow([str(statement.title), str(statement.subtitle)]);
        row.height = ROW_HEIGHT;
        styleCell(row.getCell(1), { color: COLOR.rule });
        styleCell(row.getCell(2), { color: COLOR.muted });
    });
    sheet.addRow([]).height = ROW_HEIGHT;
    [
        "Management accounts are prepared for internal use. They are not audited or reviewed, and are not",
        "presented in compliance with a financial reporting framework.",
        "Figures are drawn from the same ledger builders as the statutory statements, so a management pack",
        "and annual financial statements covering an overlapping period will agree.",
    ].forEach((text) => {
        const row = sheet.addRow([text]);
        row.height = ROW_HEIGHT;
        styleCell(row.getCell(1), { size: SIZE.footnote, color: COLOR.navy });
        sheet.mergeCells(row.number, 1, row.number, 2);
    });
}
// ─── Workbook ─────────────────────────────────────────────────────────────────
/** Sheet names Excel will accept: 31 chars, none of : \ / ? * [ ] */
function sheetName(title, taken) {
    let base = title.replace(/[:\\/?*[\]]/g, " ").replace(/\s+/g, " ").trim().slice(0, 31) || "Sheet";
    let name = base;
    let n = 2;
    while (taken.has(name.toLowerCase())) {
        const suffix = ` ${n}`;
        name = base.slice(0, 31 - suffix.length) + suffix;
        n += 1;
    }
    taken.add(name.toLowerCase());
    return name;
}
const SHEET_TITLES = {
    trading: "Trading Statement",
    "monthly-trend": "Monthly Income Statement",
    "trial-balance": "Trial Balance",
    "balance-sheet": "Financial Position",
    "financial-position": "Financial Position",
    "cash-flow": "Cash Flows",
};
export function buildManagementWorkbook(model) {
    const workbook = new ExcelJS.Workbook();
    workbook.creator = "Chainbook";
    workbook.created = new Date();
    writeCover(workbook, model);
    const taken = new Set(["cover"]);
    model.statements.forEach((statement) => {
        writeStatement(workbook, statement, model, sheetName(SHEET_TITLES[statement.key] ?? statement.title, taken), true);
    });
    // Prefixed, not left to the collision suffix: the statistics service titles
    // one of its tables "Financial position", which against the statement sheet
    // of the same name would otherwise produce a tab reading "Financial
    // position 2" — indistinguishable from a mistake.
    model.statistics.forEach((table) => {
        writeStatement(workbook, table, model, sheetName(`KPI ${table.title}`, taken), false);
    });
    return workbook;
}
