import { Packer } from "docx";
import { mkdir, writeFile } from "node:fs/promises";
import { homedir } from "node:os";
import { isAbsolute, join, resolve } from "node:path";
import { getAfsModel } from "../lib/transport.js";
import { buildAfsDocument } from "../lib/afs-docx.js";
/** Every statement the tool can render, in presentation order. */
const STATEMENT_KEYS = ["income-statement", "balance-sheet", "cash-flow", "changes-in-equity"];
const STATEMENT_TITLES = {
    "income-statement": "Statement of Profit or Loss and Other Comprehensive Income",
    "balance-sheet": "Statement of Financial Position",
    "cash-flow": "Statement of Cash Flows",
    "changes-in-equity": "Statement of Changes in Equity",
};
/** Default destination. Overridable per call, or by AFS_DOCUMENTS_DIR. */
function documentsDir() {
    return process.env.AFS_DOCUMENTS_DIR ?? join(homedir(), "Documents");
}
export const tools = [
    {
        name: "generate_financial_statements",
        description: "Generate the annual financial statements as a Word document (.docx) and save it to the user's " +
            "Documents folder. Renders any combination of the Statement of Profit or Loss and Other Comprehensive " +
            "Income (income statement), Statement of Financial Position (balance sheet), Statement of Cash Flows, " +
            "and Statement of Changes in Equity — one statement per page, in that order.\n\n" +
            "Figures come from the same builders that produce the app's statement PDFs, so the Word output cannot " +
            "drift from them. Presentation reproduces the CGL-YE25 annual-financial-statements design: Century Gothic " +
            "on a 508 x 285.75mm landscape page, 0.5pt black hairlines, a #005BF0 current-year header cell, and a " +
            "continuous #EAF8FB tint down the current-year column.\n\n" +
            "Requires prior login. Returns the absolute path of the saved file.",
        inputSchema: {
            type: "object",
            properties: {
                company_id: { type: "string", description: "The ID of the company." },
                start_date: {
                    type: "string",
                    description: "Period start in YYYY-MM-DD. Defaults to the company's financial-year start.",
                },
                end_date: {
                    type: "string",
                    description: "Period end (and balance-sheet date) in YYYY-MM-DD. Defaults to the financial-year end.",
                },
                statements: {
                    type: "array",
                    items: { type: "string", enum: [...STATEMENT_KEYS] },
                    description: "Which statements to include. Defaults to all four. Order in the document is always " +
                        "income statement, financial position, cash flows, changes in equity.",
                },
                rounding: {
                    type: "number",
                    enum: [1, 1000, 1000000],
                    description: "Figure basis: 1 for rands (default), 1000 for R'000, 1000000 for R'm.",
                },
                compare: {
                    type: "boolean",
                    description: "Include the prior-year comparative column. Defaults to true.",
                },
                filename: {
                    type: "string",
                    description: "File name without extension. Defaults to '<company>-annual-financial-statements-<year>'. " +
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
    if (name !== "generate_financial_statements")
        return null;
    const companyId = Number(args.company_id);
    if (!Number.isFinite(companyId)) {
        throw new Error("company_id must be numeric.");
    }
    const requested = Array.isArray(args.statements) ? args.statements : [...STATEMENT_KEYS];
    const unknown = requested.filter((k) => !STATEMENT_KEYS.includes(k));
    if (unknown.length > 0) {
        throw new Error(`Unknown statement(s): ${unknown.join(", ")}. Valid keys: ${STATEMENT_KEYS.join(", ")}`);
    }
    // Presentation order is fixed, whatever order the caller asked in.
    const selected = STATEMENT_KEYS.filter((k) => requested.includes(k));
    const model = (await getAfsModel({
        companyId,
        startDate: args.start_date,
        endDate: args.end_date,
        rounding: args.rounding,
        compare: args.compare,
        statements: selected,
    }));
    if (!model.statements || model.statements.length === 0) {
        throw new Error("The server returned no statements for this company and period.");
    }
    const doc = buildAfsDocument(model);
    const buffer = await Packer.toBuffer(doc);
    const dir = (() => {
        const requestedDir = args.output_dir;
        if (!requestedDir)
            return documentsDir();
        return isAbsolute(requestedDir) ? resolve(requestedDir) : resolve(documentsDir(), requestedDir);
    })();
    await mkdir(dir, { recursive: true });
    const year = model.period.end_date.slice(0, 4);
    const slug = args.filename ?? `${slugify(model.company.name)}-annual-financial-statements-${year}`;
    const path = join(dir, `${slug.replace(/\.docx$/i, "")}.docx`);
    await writeFile(path, buffer);
    const included = model.statements.map((s) => STATEMENT_TITLES[s.key] ?? s.title);
    return {
        content: [
            {
                type: "text",
                text: JSON.stringify({
                    path,
                    company: model.company.name,
                    period: model.period.label,
                    basis: model.rounding.label,
                    comparatives: model.compare,
                    statements: included,
                    pages: model.statements.length,
                }, null, 2),
            },
        ],
    };
}
function slugify(value) {
    return (value
        .toLowerCase()
        .normalize("NFKD")
        .replace(/[^a-z0-9]+/g, "-")
        .replace(/^-+|-+$/g, "") || "company");
}
