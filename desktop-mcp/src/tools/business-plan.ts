import { Tool } from "@modelcontextprotocol/sdk/types.js";
import { Packer } from "docx";
import { mkdir, writeFile } from "node:fs/promises";
import { homedir } from "node:os";
import { isAbsolute, join, resolve } from "node:path";

import { getBusinessPlanModel } from "../lib/transport.js";
import { BusinessPlanModel, buildBusinessPlanDocument } from "../lib/business-plan-docx.js";

type ToolResult = { content: Array<{ type: "text"; text: string }>; isError?: boolean };

/** Default destination, shared with generate_financial_statements. */
function documentsDir(): string {
  return process.env.AFS_DOCUMENTS_DIR ?? join(homedir(), "Documents");
}

export const tools: Tool[] = [
  {
    name: "generate_business_plan",
    description:
      "Generate a business plan as a Word document (.docx) and save it to the user's Documents folder. " +
      "Produces a cover page, a contents list, and one page per section: executive summary, company " +
      "overview, products and services, market analysis, competitive landscape, marketing and sales, " +
      "operations, management and team, financial performance, financial plan, and risks.\n\n" +
      "The financial performance section carries statistics derived from the accounting records — trading " +
      "performance, financial position, key ratios (margins, liquidity, solvency, working-capital days) and " +
      "register counts. Those figures come from the same builders that produce the annual financial " +
      "statements, so a plan and a set of statements for the same period cannot disagree.\n\n" +
      "Narrative sections are stored per company and are seeded with prompts describing what belongs in " +
      "each. Unwritten sections render in grey and are reported in the result as 'placeholder_sections' — " +
      "the tool never invents strategy, market data or projections. Edit the sections in the app (or via " +
      "the business_plan_sections table) and regenerate.\n\n" +
      "Presentation matches the financial statements: Century Gothic on a 508 x 285.75mm landscape page, " +
      "0.5pt black hairlines, a #005BF0 current-period header cell and an #EAF8FB column tint.\n\n" +
      "Requires prior login. Returns the absolute path of the saved file.",
    inputSchema: {
      type: "object",
      properties: {
        company_id: { type: "string", description: "The ID of the company." },
        start_date: {
          type: "string",
          description: "Period start in YYYY-MM-DD for the statistics. Defaults to the financial-year start.",
        },
        end_date: {
          type: "string",
          description: "Period end in YYYY-MM-DD for the statistics. Defaults to the financial-year end.",
        },
        include_statistics: {
          type: "boolean",
          description:
            "Include the derived statistics tables. Defaults to true. Set false for a narrative-only plan.",
        },
        rounding: {
          type: "number",
          enum: [1, 1000, 1000000],
          description: "Figure basis: 1 for rands (default), 1000 for R'000, 1000000 for R'm.",
        },
        compare: {
          type: "boolean",
          description: "Include the prior-period comparative column in the statistics. Defaults to true.",
        },
        filename: {
          type: "string",
          description:
            "File name without extension. Defaults to '<company>-business-plan-<year>'. " +
            "An existing file with the same name is overwritten.",
        },
        output_dir: {
          type: "string",
          description:
            "Destination directory. Defaults to the user's Documents folder; a relative path is resolved " +
            "inside it. The directory is created if it does not exist.",
        },
      },
      required: ["company_id"],
    },
  },
];

export async function handle(name: string, args: Record<string, unknown>): Promise<ToolResult | null> {
  if (name !== "generate_business_plan") return null;

  const companyId = Number(args.company_id);
  if (!Number.isFinite(companyId)) {
    throw new Error("company_id must be numeric.");
  }

  const model = (await getBusinessPlanModel({
    companyId,
    startDate: args.start_date as string | undefined,
    endDate: args.end_date as string | undefined,
    rounding: args.rounding as number | undefined,
    compare: args.compare as boolean | undefined,
    includeStatistics: args.include_statistics as boolean | undefined,
  })) as BusinessPlanModel;

  if (!model.sections || model.sections.length === 0) {
    throw new Error("The server returned no business plan sections for this company.");
  }

  const doc = buildBusinessPlanDocument(model);
  const buffer = await Packer.toBuffer(doc);

  const dir = (() => {
    const requestedDir = args.output_dir as string | undefined;
    if (!requestedDir) return documentsDir();
    return isAbsolute(requestedDir) ? resolve(requestedDir) : resolve(documentsDir(), requestedDir);
  })();
  await mkdir(dir, { recursive: true });

  const year = model.period.end_date.slice(0, 4);
  const slug = (args.filename as string | undefined) ?? `${slugify(model.company.name)}-business-plan-${year}`;
  const path = join(dir, `${slug.replace(/\.docx$/i, "")}.docx`);

  await writeFile(path, buffer);

  // Surfaced deliberately: a plan full of prompts looks finished at a glance,
  // and the caller should know which sections still need writing.
  const placeholders = model.sections.filter((s) => s.is_placeholder).map((s) => s.title);

  return {
    content: [
      {
        type: "text",
        text: JSON.stringify(
          {
            path,
            company: model.company.name,
            period: model.period.label,
            basis: model.rounding.label,
            sections: model.sections.length,
            statistics_tables: model.statistics.map((t) => t.title),
            placeholder_sections: placeholders,
            note:
              placeholders.length > 0
                ? `${placeholders.length} section(s) still contain the seeded prompt text and are rendered in grey. ` +
                  "Edit them and regenerate before sending this plan to anyone."
                : "All narrative sections have been written.",
          },
          null,
          2
        ),
      },
    ],
  };
}

function slugify(value: string): string {
  return (
    value
      .toLowerCase()
      .normalize("NFKD")
      .replace(/[^a-z0-9]+/g, "-")
      .replace(/^-+|-+$/g, "") || "company"
  );
}
