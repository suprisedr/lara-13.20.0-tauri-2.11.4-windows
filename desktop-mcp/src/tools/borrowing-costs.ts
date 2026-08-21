import { Tool } from "@modelcontextprotocol/sdk/types.js";
import { getBearerToken } from "../lib/graphql.js";
import { API_BASE } from "../lib/api.js";


async function apiGet(path: string): Promise<unknown> {
  const token = getBearerToken();
  if (!token) throw new Error("Not authenticated. Please run the login tool first.");
  const response = await fetch(`${API_BASE}${path}`, {
    headers: { Accept: "application/json", Authorization: `Bearer ${token}` },
  });
  const json = await response.json();
  if (!response.ok) throw new Error((json as Record<string, unknown>).message as string ?? `HTTP ${response.status}`);
  return json;
}

async function apiPost(path: string, body: Record<string, unknown>): Promise<Record<string, unknown>> {
  const token = getBearerToken();
  if (!token) throw new Error("Not authenticated. Please run the login tool first.");
  const response = await fetch(`${API_BASE}${path}`, {
    method: "POST",
    headers: { "Content-Type": "application/json", Accept: "application/json", Authorization: `Bearer ${token}` },
    body: JSON.stringify(body),
  });
  const json = await response.json() as Record<string, unknown>;
  if (!response.ok) throw new Error((json.message as string) ?? `HTTP ${response.status}`);
  return json;
}

async function apiPatch(path: string, body: Record<string, unknown>): Promise<Record<string, unknown>> {
  const token = getBearerToken();
  if (!token) throw new Error("Not authenticated. Please run the login tool first.");
  const response = await fetch(`${API_BASE}${path}`, {
    method: "PATCH",
    headers: { "Content-Type": "application/json", Accept: "application/json", Authorization: `Bearer ${token}` },
    body: JSON.stringify(body),
  });
  const json = await response.json() as Record<string, unknown>;
  if (!response.ok) throw new Error((json.message as string) ?? `HTTP ${response.status}`);
  return json;
}

const FLAG_RULE =
  "\n\nACTION FLAGGING (mandatory): After completing this operation, if you identify ANY issue, " +
  "compliance gap, missing information, or recommended follow-up, you MUST call create_action for EACH one — " +
  "do not leave findings only in your text reply. " +
  "Use priority 'high' for compliance/audit risk, 'medium' for accounting estimate issues, 'low' for housekeeping. " +
  "Set related_type='borrowing_cost' and related_id to the capitalisation ID where applicable.";

type ToolResult = { content: Array<{ type: "text"; text: string }>; isError?: boolean };

export const tools: Tool[] = [
  {
    name: "get_borrowing_costs",
    description:
      "Fetch IAS 23 borrowing cost capitalisations for a company. " +
      "Returns each capitalisation with borrowing source, rate, total capitalised, status, and monthly capitalisable amount. " +
      "IAS 23 requires borrowing costs directly attributable to qualifying assets to be capitalised as part of asset cost. " +
      "Requires prior login.",
    inputSchema: {
      type: "object",
      properties: {
        company_id: { type: "string", description: "The ID of the company whose borrowing cost capitalisations to list." },
      },
      required: ["company_id"],
    },
  },
  {
    name: "get_borrowing_cost",
    description:
      "Fetch a single IAS 23 borrowing cost capitalisation by ID with full details. Requires prior login.",
    inputSchema: {
      type: "object",
      properties: {
        id: { type: "string", description: "The borrowing cost capitalisation ID to fetch." },
      },
      required: ["id"],
    },
  },
  {
    name: "create_borrowing_cost",
    description:
      "Register a new IAS 23 borrowing cost capitalisation. " +
      "USE THIS — NOT create_transaction — for tracking borrowing costs that should be capitalised to a qualifying asset. " +
      "A qualifying asset is one that necessarily takes a substantial period of time to get ready for its intended use or sale. " +
      "Examples: buildings under construction, power plants, intangible assets requiring development, investment properties under development. " +
      "IAS 23 requires capitalisation of borrowing costs directly attributable to the acquisition, construction, or production of a qualifying asset. " +
      "Requires prior login." +
      FLAG_RULE,
    inputSchema: {
      type: "object",
      properties: {
        company_id: { type: "string", description: "The ID of the company." },
        qualifying_asset_type: { type: "string", description: "Type of qualifying asset (e.g. 'PPE', 'Investment Property', 'Intangible')." },
        qualifying_asset_id: { type: "number", description: "ID of the qualifying asset." },
        borrowing_source: { type: "string", description: "Source of borrowing (e.g. 'Standard Bank Term Loan', 'FNB Mortgage')." },
        capitalisation_start_date: { type: "string", description: "Date capitalisation commences in YYYY-MM-DD format." },
        borrowing_rate: { type: "number", description: "Annual borrowing rate as a percentage (e.g. 11.5)." },
        weighted_average_rate: { type: "number", description: "Weighted average borrowing rate if multiple borrowings (optional)." },
        notes: { type: "string", description: "Optional notes." },
      },
      required: ["company_id", "qualifying_asset_type", "qualifying_asset_id", "borrowing_source", "capitalisation_start_date", "borrowing_rate"],
    },
  },
  {
    name: "capitalise_borrowing_cost",
    description:
      "IAS 23.12 Capitalise borrowing costs — record a capitalisation of borrowing costs to the qualifying asset. " +
      "Dr Qualifying Asset Cost / Cr Interest Expense. " +
      "Capitalisation should cease when substantially all activities necessary to prepare the asset for its intended use or sale are complete (IAS 23.22). " +
      "Capitalisation should be suspended during extended periods when active development is interrupted (IAS 23.20). " +
      "Requires prior login." +
      FLAG_RULE,
    inputSchema: {
      type: "object",
      properties: {
        id: { type: "string", description: "The borrowing cost capitalisation ID." },
        amount: { type: "number", description: "Amount of borrowing costs to capitalise." },
        date: { type: "string", description: "Effective date in YYYY-MM-DD format." },
      },
      required: ["id", "amount", "date"],
    },
  },
];

export async function handle(
  name: string,
  args: Record<string, unknown>
): Promise<ToolResult | null> {
  switch (name) {
    case "get_borrowing_costs":
      return handleGetAll(args);
    case "get_borrowing_cost":
      return handleGetOne(args);
    case "create_borrowing_cost":
      return handleCreate(args);
    case "capitalise_borrowing_cost":
      return handleCapitalise(args);
    default:
      return null;
  }
}

async function handleGetAll(args: Record<string, unknown>): Promise<ToolResult> {
  const data = await apiGet(`/borrowing-costs?company_id=${args.company_id}`);
  return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
}

async function handleGetOne(args: Record<string, unknown>): Promise<ToolResult> {
  const data = await apiGet(`/borrowing-costs/${args.id}`);
  return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
}

async function handleCreate(args: Record<string, unknown>): Promise<ToolResult> {
  const body: Record<string, unknown> = {
    company_id: args.company_id,
    qualifying_asset_type: args.qualifying_asset_type,
    qualifying_asset_id: args.qualifying_asset_id,
    borrowing_source: args.borrowing_source,
    capitalisation_start_date: args.capitalisation_start_date,
    borrowing_rate: args.borrowing_rate,
  };
  for (const k of ["weighted_average_rate", "notes"]) {
    if (args[k] !== undefined) body[k] = args[k];
  }
  const result = await apiPost("/borrowing-costs", body);
  return { content: [{ type: "text", text: JSON.stringify(result, null, 2) }] };
}

async function handleCapitalise(args: Record<string, unknown>): Promise<ToolResult> {
  const { id, ...body } = args;
  const result = await apiPost(`/borrowing-costs/${id}/capitalise`, body as Record<string, unknown>);
  return { content: [{ type: "text", text: JSON.stringify(result, null, 2) }] };
}
