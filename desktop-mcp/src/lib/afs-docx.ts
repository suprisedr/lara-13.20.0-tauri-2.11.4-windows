/**
 * Word renderer for the four primary financial statements.
 *
 * Reproduces CGL-YE25-Annual-Financial-Statements.docx. Every constant below
 * is measured from that file's OOXML — page geometry from its sectPr, type
 * from word/styles.xml, table geometry and rules from the income statement's
 * tblGrid and tcPr. Nothing here is estimated, and nothing should be changed
 * without re-measuring the source.
 *
 * The two things most easily got wrong, both enforced below:
 *   1. The header row is NOT a full blue band. Only the current-year cell is
 *      filled #005BF0 with white text and a #005BF0 rule; the other header
 *      cells are plain with a black hairline.
 *   2. There are no double rules anywhere, not even under the grand total.
 *      Every rule in the document is w:sz 4 = 0.5pt solid #000000.
 *
 * The #EAF8FB tint is a column band, not row striping: it falls only on the
 * current-year column, runs unbroken through section and subtotal rows, and
 * that column is bold on every row. The comparative column is plain and
 * regular weight throughout. The SOCE is a matrix and carries neither.
 */

import {
  AlignmentType,
  BorderStyle,
  Document,
  LineRuleType,
  PageOrientation,
  Paragraph,
  ShadingType,
  Table,
  TableCell,
  TableLayoutType,
  TableRow,
  TextRun,
  VerticalAlign,
  WidthType,
} from "docx";

// ─── Measured constants ───────────────────────────────────────────────────────

/**
 * pgSz 28800 x 16200 tw = 20in x 11.25in = 508mm x 285.75mm landscape.
 *
 * docx swaps width and height when the orientation is landscape, so these are
 * given the other way round and come out of the writer as w=28800 h=16200.
 * Swapping them "to match the document" silently rotates the page.
 */
const PAGE = { width: 16200, height: 28800 };
/** pgMar: top/left/right 1500 tw, bottom 1095 tw, header 165 tw, footer 450 tw. */
const MARGIN = { top: 1500, right: 1500, bottom: 1095, left: 1500, header: 165, footer: 450 };

export const FONT = "Century Gothic";

/** Half-points, as w:sz carries them. */
export const SIZE = {
  body: 21,        // 10.5pt
  title: 50,       // 25pt — the statement title
  sectionHead: 26, // 13pt  — also the company name on the letterhead line
  dateLine: 23,    // 11.5pt
  footnote: 14,    // 7pt
};

export const COLOR = {
  body: "191919",
  rule: "000000",
  emphasis: "005BF0",  // section heads, subtotals, grand totals, header fill
  navy: "1A345B",      // title and date line
  tint: "EAF8FB",      // current-year column band
  white: "FFFFFF",
  muted: "5A7186",
};

/**
 * Every paragraph in the document uses w:lineRule exact, so the leading must
 * be chosen per type size — an exact line shorter than the glyphs clips them,
 * silently and only at the top. The source's ladder, measured:
 *
 *   25pt title       w:line 585 (29.25pt)
 *   13pt section     w:line 315 (15.75pt)
 *   11.5pt date line w:line 285 (14.25pt)
 *   10.5pt body      w:line 270 (13.5pt)
 *
 * Roughly 1.2x the point size throughout. Do not reuse the body's 270 for
 * anything larger than 10.5pt.
 */
export const LINE = { title: 585, sectionHead: 315, dateLine: 285, body: 270 };

/** w:line 270 exact = 13.5pt leading. Table row height comes entirely from this. */
export const LEADING = { line: LINE.body, lineRule: LineRuleType.EXACT, before: 0, after: 0 };

/** w:sz 4 = 0.5pt. The only rule weight in the document. */
const HAIRLINE = { style: BorderStyle.SINGLE, size: 4, color: COLOR.rule };
const HAIRLINE_BLUE = { style: BorderStyle.SINGLE, size: 4, color: COLOR.emphasis };
const NO_BORDER = { style: BorderStyle.NONE, size: 0, color: "auto" };

/** Column grids in twips. Income statement / SOFP, then cash flow. */
const GRID_STANDARD = [7725, 795, 1815, 1815];
const GRID_CASHFLOW = [7500, 750, 1950, 1950];
/** SOCE spans the full 25800 tw text area: label 8400, amounts share the rest. */
const MATRIX_LABEL = 8400;
const MATRIX_TOTAL = 25800;

/** Right gutter: 75 tw = 3.75pt, except the current-year column at 90 tw. */
const PAD = { normal: 75, current: 90 };

/** Row styles carrying a closing hairline across every cell. */
const RULED = new Set(["item-last", "subtotal", "named-subtotal", "grand-total"]);
/** Row styles whose label is bold #005BF0. */
const EMPHASIS = new Set(["section", "section-sub", "subtotal", "named-subtotal", "grand-total"]);

// ─── Model types (mirrors AfsStatementModelService) ───────────────────────────

export type StatementColumn = {
  kind: "label" | "note" | "amount";
  text: string | null;
  sub?: string;
  current?: boolean;
};

export type StatementRow = {
  style: string;
  label: string;
  note?: string;
  indent?: boolean;
  values: string[];
};

export type Statement = {
  key: string;
  title: string;
  subtitle: string;
  layout: "standard" | "matrix";
  grid?: number[];
  columns: StatementColumn[];
  rows: StatementRow[];
  footnotes?: string[];
};

export type AfsModel = {
  company: { name: string; registration_number?: string | null; income_tax_number?: string | null };
  period: { start_date: string; end_date: string; label: string };
  rounding: { divisor: number; label: string; decimals: number };
  compare: boolean;
  statements: Statement[];
};

// ─── Cell helpers ─────────────────────────────────────────────────────────────

/**
 * docx throws on a null run, and the model carries nulls freely — an unnamed
 * company, a statement with no comparative column, a line with no note.
 */
export function str(value: unknown): string {
  return value === null || value === undefined ? "" : String(value);
}

type CellOpts = {
  text: string;
  width: number;
  align: (typeof AlignmentType)[keyof typeof AlignmentType];
  bold?: boolean;
  color?: string;
  fill?: string;
  bottom?: typeof HAIRLINE | typeof NO_BORDER;
  padRight?: number;
  indent?: boolean;
  /** Second line, set smaller — used by the header's rounding label. */
  sub?: string;
};

function cell(o: CellOpts): TableCell {
  const runs = [
    new TextRun({
      text: str(o.text),
      bold: o.bold ?? false,
      color: o.color ?? COLOR.rule,
      font: FONT,
      size: SIZE.body,
    }),
  ];
  if (o.sub) {
    runs.push(
      new TextRun({ text: str(o.sub), bold: o.bold ?? false, color: o.color ?? COLOR.rule, font: FONT, size: SIZE.body, break: 1 })
    );
  }

  return new TableCell({
    width: { size: o.width, type: WidthType.DXA },
    // No vertical padding anywhere; the 13.5pt leading sets the row height.
    margins: { top: 0, bottom: 0, left: 0, right: o.padRight ?? PAD.normal },
    verticalAlign: VerticalAlign.BOTTOM,
    shading: o.fill ? { type: ShadingType.CLEAR, color: "auto", fill: o.fill } : undefined,
    borders: {
      top: NO_BORDER,
      left: NO_BORDER,
      right: NO_BORDER,
      bottom: o.bottom ?? NO_BORDER,
    },
    children: [
      new Paragraph({
        alignment: o.align,
        spacing: LEADING,
        // Separately-shown children sit under their parent line.
        indent: o.indent ? { left: 283 } : undefined,
        children: runs,
      }),
    ],
  });
}

// ─── Header row ───────────────────────────────────────────────────────────────

function headerRow(columns: StatementColumn[], grid: number[], matrix: boolean): TableRow {
  return new TableRow({
    children: columns.map((col, i) => {
      const isCurrent = !matrix && col.current === true;

      return cell({
        text: str(col.text),
        sub: col.sub,
        width: grid[i],
        align: col.kind === "label" ? AlignmentType.LEFT : AlignmentType.RIGHT,
        // Only the current-year cell is filled. Everything else is plain
        // with a black hairline — the matrix has no filled cell at all.
        color: isCurrent ? COLOR.white : COLOR.rule,
        fill: isCurrent ? COLOR.emphasis : undefined,
        bottom: isCurrent ? HAIRLINE_BLUE : HAIRLINE,
        padRight: isCurrent ? PAD.current : PAD.normal,
      });
    }),
  });
}

// ─── Body rows ────────────────────────────────────────────────────────────────

function bodyRow(row: StatementRow, columns: StatementColumn[], grid: number[], matrix: boolean): TableRow {
  const ruled = RULED.has(row.style) ? HAIRLINE : NO_BORDER;
  const emphasised = EMPHASIS.has(row.style);

  const cells: TableCell[] = [];
  let valueIndex = 0;

  columns.forEach((col, i) => {
    if (col.kind === "label") {
      cells.push(
        cell({
          text: str(row.label),
          width: grid[i],
          align: AlignmentType.LEFT,
          bold: emphasised,
          // Emphasis labels are bold #005BF0; nothing else is coloured except
          // the muted "None" / "no activity" placeholders.
          color: emphasised ? COLOR.emphasis : row.indent ? COLOR.muted : COLOR.rule,
          bottom: ruled,
          indent: row.indent,
        })
      );
      return;
    }

    if (col.kind === "note") {
      cells.push(
        cell({
          text: str(row.note),
          width: grid[i],
          align: AlignmentType.LEFT,
          bold: true,
          color: COLOR.emphasis,
          bottom: ruled,
        })
      );
      return;
    }

    // Amount column.
    const value = str(row.values[valueIndex]);
    valueIndex += 1;

    if (matrix) {
      // No tint, no per-column bold: emphasis rows are bold throughout,
      // ordinary rows regular throughout.
      cells.push(
        cell({
          text: value,
          width: grid[i],
          align: AlignmentType.RIGHT,
          bold: emphasised,
          bottom: ruled,
        })
      );
      return;
    }

    const isCurrent = col.current === true;
    cells.push(
      cell({
        text: value,
        width: grid[i],
        align: AlignmentType.RIGHT,
        // The current-year column is bold on every row, including spacer and
        // section rows; the comparative column is regular throughout.
        bold: isCurrent,
        fill: isCurrent ? COLOR.tint : undefined,
        bottom: ruled,
        padRight: isCurrent ? PAD.current : PAD.normal,
      })
    );
  });

  return new TableRow({ children: cells });
}

// ─── Statement table ──────────────────────────────────────────────────────────

export function statementTable(statement: Statement): Table {
  const matrix = statement.layout === "matrix";
  const columnCount = statement.columns.length;

  let grid: number[];
  if (matrix) {
    const amountWidth = Math.floor((MATRIX_TOTAL - MATRIX_LABEL) / Math.max(1, columnCount - 1));
    grid = [MATRIX_LABEL, ...Array(columnCount - 1).fill(amountWidth)];
  } else {
    const base = statement.grid ?? (statement.key === "cash-flow" ? GRID_CASHFLOW : GRID_STANDARD);
    // Drop the comparative column when the statement carries no comparatives,
    // giving its width back to the label so the grid still sums to 12150.
    grid = base.slice(0, columnCount);
    if (columnCount < base.length) {
      grid[0] += base.slice(columnCount).reduce((a, b) => a + b, 0);
    }
  }

  return new Table({
    width: { size: grid.reduce((a, b) => a + b, 0), type: WidthType.DXA },
    columnWidths: grid,
    layout: TableLayoutType.FIXED,
    borders: { top: NO_BORDER, bottom: NO_BORDER, left: NO_BORDER, right: NO_BORDER, insideHorizontal: NO_BORDER, insideVertical: NO_BORDER },
    rows: [
      headerRow(statement.columns, grid, matrix),
      ...statement.rows.map((row) => bodyRow(row, statement.columns, grid, matrix)),
    ],
  });
}

// ─── Title block and prose ────────────────────────────────────────────────────

/**
 * Company name, then statement title, then the date line — the hierarchy the
 * source document and the app's standalone statement PDFs both use: the
 * 25pt slot belongs to the statement title, and the company name rides the
 * 13pt letterhead treatment above it (_afs-styles' .company-name).
 */
function titleBlock(statement: Statement, model: AfsModel): Paragraph[] {
  /**
   * Display type uses AT_LEAST, not EXACT.
   *
   * The source can afford exact leading on its titles because it is a
   * hand-set document with a known font. Here the company name is arbitrary
   * text, may wrap, and Century Gothic is referenced by name rather than
   * embedded — so on a machine without it Word substitutes a face whose
   * ascent may exceed the line, and exact leading silently shears the tops
   * off the glyphs. AT_LEAST holds the document's measured rhythm whenever
   * the glyphs fit and grows the line when they do not.
   *
   * The table keeps EXACT: 13.5pt on 10.5pt body type is the design, it is
   * comfortably clear of the glyph height, and the row rhythm depends on it.
   */
  const lead = (line: number, after: number, before = 0) => ({
    line,
    lineRule: LineRuleType.AT_LEAST,
    before,
    after,
  });

  return [
    new Paragraph({
      spacing: lead(LINE.sectionHead, 75),
      children: [
        new TextRun({
          text: str(model.company.name) || "Annual Financial Statements",
          bold: true,
          color: COLOR.navy,
          font: FONT,
          size: SIZE.sectionHead,
        }),
      ],
    }),
    new Paragraph({
      spacing: lead(LINE.title, 120),
      children: [
        new TextRun({ text: str(statement.title), bold: true, color: COLOR.navy, font: FONT, size: SIZE.title }),
      ],
    }),
    new Paragraph({
      spacing: lead(LINE.dateLine, 270),
      children: [
        new TextRun({ text: str(statement.subtitle), color: COLOR.navy, font: FONT, size: SIZE.dateLine }),
      ],
    }),
  ];
}

function footnotes(statement: Statement): Paragraph[] {
  return (statement.footnotes ?? []).map(
    (text) =>
      new Paragraph({
        spacing: { ...LEADING, before: 135 },
        children: [new TextRun({ text: str(text), color: COLOR.navy, font: FONT, size: SIZE.footnote })],
      })
  );
}

// ─── Document ─────────────────────────────────────────────────────────────────

/**
 * Build the .docx. Each statement gets its own page, matching the app's
 * standalone statement PDFs. (The source document pairs statements two-up
 * per sheet; that is a bundling choice, not a property of the design.)
 */
export function buildAfsDocument(model: AfsModel): Document {
  const children: (Paragraph | Table)[] = [];

  model.statements.forEach((statement, index) => {
    if (index > 0) {
      children.push(pageBreak());
    }
    children.push(...titleBlock(statement, model));
    children.push(statementTable(statement));
    children.push(...footnotes(statement));
  });

  return buildDocument(children);
}

/**
 * The document shell: the source's page geometry and default run.
 *
 * Shared with the business-plan renderer so there is one definition of the
 * page rather than two that drift.
 */
export function buildDocument(children: (Paragraph | Table)[]): Document {
  return new Document({
    styles: {
      default: {
        document: {
          run: { font: FONT, size: SIZE.body, color: COLOR.body },
          paragraph: { spacing: LEADING },
        },
      },
    },
    sections: [
      {
        properties: {
          page: {
            size: { width: PAGE.width, height: PAGE.height, orientation: PageOrientation.LANDSCAPE },
            margin: MARGIN,
          },
        },
        children,
      },
    ],
  });
}

/** A page break, as its own empty paragraph. */
export function pageBreak(): Paragraph {
  return new Paragraph({ pageBreakBefore: true, spacing: LEADING, children: [] });
}
