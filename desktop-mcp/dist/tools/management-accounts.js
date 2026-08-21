import { mkdir, writeFile } from "node:fs/promises";
import { homedir } from "node:os";
import { isAbsolute, join, resolve } from "node:path";
import { getManagementAccountsModel } from "../lib/transport.js";
import { buildManagementWorkbook } from "../lib/management-xlsx.js";
/** Every sheet the tool can render, in presentation order. */
const SHEET_KEYS = ["trading", "monthly-trend", "financial-position", "cash-flow", "ratios", "trial-balance"];
/** Default destination. Overridable per call, or by AFS_DOCUMENTS_DIR. */
function documentsDir() {
    return process.env.AFS_DOCUMENTS_DIR ?? join(homedir(), "Documents");
}
export const tools = [
    {
        name: "generate_management_accounts",
        description: "Generate a monthly management accounts pack as an Excel workbook (.xlsx) and save it to the user's " +
            "Documents folder. The pack contains a cover sheet plus any combination of a Trading Statement " +
            "(profit or loss for the period and year to date, each against the equivalent prior-year span with " +
            "favourable/(adverse) variances and margins), a month-by-month Monthly Income Statement across the " +
            "financial year to date, a Statement of Financial Position with movement since the comparative " +
            "balance date, a Statement of Cash Flows for the month and year to date, derived ratio tables, and " +
            "a Trial Balance control sheet proving the pack ties to the ledger.\n\n" +
            "Figures come from the same builders that produce the app's statement PDFs and the annual financial " +
            "statements Word pack, so a management pack cannot drift from them. Presentation follows the CGL-YE25 " +
            "annual-financial-statements design: Century Gothic, 0.5pt black hairlines, a #005BF0 current-period " +
            "header cell and a continuous #EAF8FB tint down each current-period column.\n\n" +
            "Cells hold live numbers formatted to the document's conventions — space thousands separator, " +
            "parentheses for negatives, em dash for nil — so the workbook can be summed, charted and extended.\n\n" +
            "There is no budget in the system, so variances are against prior periods, never against budget. " +
            "Requires prior login. Returns the absolute path of the saved file.",
        inputSchema: {
            type: "object",
            properties: {
                company_id: { type: "string", description: "The ID of the company." },
                period_end: {
                    type: "string",
                    description: "Last day of the reporting period, in YYYY-MM-DD. Defaults to the end of the month just " +
                        "ended — a management pack is normally cut after month end.",
                },
                period_start: {
                    type: "string",
                    description: "First day of the reporting period, in YYYY-MM-DD. Defaults to the first of the month containing period_end.",
                },
                ytd_start: {
                    type: "string",
                    description: "Start of the year-to-date span, in YYYY-MM-DD. Defaults to the start of the financial year " +
                        "containing period_end, so a pack cut for a past month gets that year's figures.",
                },
                sheets: {
                    type: "array",
                    items: { type: "string", enum: [...SHEET_KEYS] },
                    description: "Which sheets to include. Defaults to all six. Order in the workbook is always cover, " +
                        "trading, monthly trend, financial position, cash flows, ratios, trial balance.",
                },
                rounding: {
                    type: "number",
                    enum: [1, 1000, 1000000],
                    description: "Figure basis: 1 for rands (default), 1000 for R'000, 1000000 for R'm.",
                },
                compare: {
                    type: "boolean",
                    description: "Include prior-period comparatives and the variance columns. Defaults to true. With false the " +
                        "pack shows actuals only.",
                },
                filename: {
                    type: "string",
                    description: "File name without extension. Defaults to '<company>-management-accounts-<YYYY-MM>'. " +
                        "An existing file with the same name is overwritten.",
                },
                output_dir: {
                    type: "string",
                    description: "Destination directory. Defaults to the user's Documents folder; a relative path is resolved " +
                        "inside it. The directory is created if it does not exist.",
                },
            },
            required: ["company_id"],
        },
    },
];
export async function handle(name, args) {
    if (name !== "generate_management_accounts")
        return null;
    const companyId = Number(args.company_id);
    if (!Number.isFinite(companyId)) {
        throw new Error("company_id must be numeric.");
    }
    const requested = Array.isArray(args.sheets) ? args.sheets : [...SHEET_KEYS];
    const unknown = requested.filter((k) => !SHEET_KEYS.includes(k));
    if (unknown.length > 0) {
        throw new Error(`Unknown sheet(s): ${unknown.join(", ")}. Valid keys: ${SHEET_KEYS.join(", ")}`);
    }
    // Presentation order is fixed, whatever order the caller asked in.
    const selected = SHEET_KEYS.filter((k) => requested.includes(k));
    const model = (await getManagementAccountsModel({
        companyId,
        periodStart: args.period_start,
        periodEnd: args.period_end,
        ytdStart: args.ytd_start,
        rounding: args.rounding,
        compare: args.compare,
        sheets: selected,
    }));
    const sheetCount = (model.statements?.length ?? 0) + (model.statistics?.length ?? 0);
    if (sheetCount === 0) {
        throw new Error("The server returned no sheets for this company and period.");
    }
    const workbook = buildManagementWorkbook(model);
    const dir = (() => {
        const requestedDir = args.output_dir;
        if (!requestedDir)
            return documentsDir();
        return isAbsolute(requestedDir) ? resolve(requestedDir) : resolve(documentsDir(), requestedDir);
    })();
    await mkdir(dir, { recursive: true });
    const month = model.period.period_end.slice(0, 7);
    const slug = args.filename ?? `${slugify(model.company.name)}-management-accounts-${month}`;
    const path = join(dir, `${slug.replace(/\.xlsx$/i, "")}.xlsx`);
    const buffer = await workbook.xlsx.writeBuffer();
    await writeFile(path, Buffer.from(buffer));
    return {
        content: [
            {
                type: "text",
                text: JSON.stringify({
                    path,
                    company: model.company.name,
                    period: model.period.label,
                    year_to_date: model.period.ytd_label,
                    basis: model.rounding.label,
                    comparatives: model.compare,
                    sheets: ["Cover", ...model.statements.map((s) => s.title), ...model.statistics.map((s) => s.title)],
                }, null, 2),
            },
        ],
    };
}
function slugify(value) {
    return (String(value ?? "")
        .toLowerCase()
        .normalize("NFKD")
        .replace(/[^a-z0-9]+/g, "-")
        .replace(/^-+|-+$/g, "") || "company");
}
