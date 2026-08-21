/**
 * Word renderer for the business plan.
 *
 * Shares its design with the financial statements rather than restating it:
 * the page, the type ladder, the palette and the statement table all come from
 * afs-docx, so the two documents cannot drift apart. What is added here is the
 * prose treatment the statements do not need — running paragraphs, a numbered
 * heading per section, and a cover page.
 *
 * Prose sizes are the source document's own (`_afs-styles`' prose block):
 * h1 25pt navy, h2 13pt #005BF0, body 10.5pt on 13.5pt leading.
 */

import { AlignmentType, Document, LineRuleType, Paragraph, Table, TextRun } from "docx";

import {
  COLOR,
  FONT,
  LEADING,
  LINE,
  SIZE,
  Statement,
  buildDocument,
  pageBreak,
  statementTable,
  str,
} from "./afs-docx.js";

// ─── Model types (mirrors the businessPlanModel endpoint) ─────────────────────

export type PlanSection = {
  slug: string;
  number: number;
  title: string;
  body: string;
  include_statistics: boolean;
  is_placeholder: boolean;
};

export type BusinessPlanModel = {
  company: {
    name: string;
    registration_number?: string | null;
    income_tax_number?: string | null;
    industry?: string | null;
    company_type?: string | null;
  };
  period: { start_date: string; end_date: string; label: string };
  rounding: { divisor: number; label: string };
  compare: boolean;
  sections: PlanSection[];
  statistics: Statement[];
};

// ─── Paragraph helpers ────────────────────────────────────────────────────────

/**
 * Display type uses AT_LEAST for the same reason the statements do: Century
 * Gothic is referenced by name rather than embedded, and an exact line shorter
 * than a substituted font's ascent shears the glyph tops off.
 */
function lead(line: number, after: number, before = 0) {
  return { line, lineRule: LineRuleType.AT_LEAST, before, after };
}

function heading(text: string, size: number, color: string, line: number, after: number, before = 0): Paragraph {
  return new Paragraph({
    spacing: lead(line, after, before),
    children: [new TextRun({ text: str(text), bold: true, color, font: FONT, size })],
  });
}

/** Body prose. Blank lines in the stored body start a new paragraph. */
function prose(body: string, muted = false): Paragraph[] {
  const blocks = str(body)
    .split(/\n\s*\n/)
    .map((block) => block.replace(/\s*\n\s*/g, " ").trim())
    .filter((block) => block.length > 0);

  if (blocks.length === 0) {
    return [];
  }

  return blocks.map(
    (text) =>
      new Paragraph({
        spacing: { ...lead(LINE.body, 135), before: 0 },
        children: [
          new TextRun({
            text,
            color: muted ? COLOR.muted : COLOR.body,
            font: FONT,
            size: SIZE.body,
          }),
        ],
      })
  );
}

// ─── Cover page ───────────────────────────────────────────────────────────────

function coverPage(model: BusinessPlanModel): Paragraph[] {
  const meta: string[] = [];
  if (model.company.company_type) meta.push(str(model.company.company_type));
  if (model.company.registration_number) meta.push(`Registration number ${model.company.registration_number}`);
  if (model.company.income_tax_number) meta.push(`Income tax number ${model.company.income_tax_number}`);
  if (model.company.industry) meta.push(str(model.company.industry));

  const children = [
    heading(str(model.company.name) || "Business Plan", SIZE.sectionHead, COLOR.navy, LINE.sectionHead, 75),
    heading("Business Plan", SIZE.title, COLOR.navy, LINE.title, 120),
    new Paragraph({
      spacing: lead(LINE.dateLine, 210),
      children: [
        new TextRun({ text: str(model.period.label), color: COLOR.navy, font: FONT, size: SIZE.dateLine }),
      ],
    }),
  ];

  if (meta.length > 0) {
    children.push(
      ...meta.map(
        (line) =>
          new Paragraph({
            spacing: lead(LINE.body, 0),
            children: [new TextRun({ text: line, color: COLOR.navy, font: FONT, size: SIZE.body })],
          })
      )
    );
  }

  return children;
}

/** Contents list, so a reader can navigate a plan that runs to several pages. */
function contents(model: BusinessPlanModel): Paragraph[] {
  return [
    heading("Contents", SIZE.sectionHead, COLOR.emphasis, LINE.sectionHead, 75, 270),
    ...model.sections.map(
      (section) =>
        new Paragraph({
          spacing: lead(LINE.body, 0),
          children: [
            new TextRun({
              text: `${section.number}.  ${str(section.title)}`,
              color: COLOR.body,
              font: FONT,
              size: SIZE.body,
            }),
          ],
        })
    ),
  ];
}

// ─── Document ─────────────────────────────────────────────────────────────────

/**
 * Cover page, contents, then one page per narrative section. The section
 * flagged for statistics carries the derived tables after its prose.
 */
export function buildBusinessPlanDocument(model: BusinessPlanModel): Document {
  const children: (Paragraph | Table)[] = [];

  children.push(...coverPage(model));
  children.push(...contents(model));

  model.sections.forEach((section) => {
    children.push(pageBreak());
    children.push(
      heading(`${section.number}.  ${str(section.title)}`, SIZE.title, COLOR.navy, LINE.title, 150)
    );

    // Unwritten sections are set in the muted grey, so a plan that still
    // carries prompts reads as unfinished rather than as authored content.
    children.push(...prose(section.body, section.is_placeholder));

    if (!section.include_statistics) {
      return;
    }

    model.statistics.forEach((table, index) => {
      children.push(
        heading(str(table.title), SIZE.sectionHead, COLOR.emphasis, LINE.sectionHead, 75, index === 0 ? 270 : 300)
      );
      if (table.subtitle) {
        children.push(
          new Paragraph({
            spacing: lead(LINE.body, 75),
            children: [
              new TextRun({ text: str(table.subtitle), color: COLOR.navy, font: FONT, size: SIZE.body }),
            ],
          })
        );
      }
      children.push(statementTable(table));
      (table.footnotes ?? []).forEach((note) => {
        children.push(
          new Paragraph({
            spacing: lead(LINE.body, 0, 135),
            alignment: AlignmentType.LEFT,
            children: [new TextRun({ text: str(note), color: COLOR.navy, font: FONT, size: SIZE.footnote })],
          })
        );
      });
    });
  });

  return buildDocument(children);
}
