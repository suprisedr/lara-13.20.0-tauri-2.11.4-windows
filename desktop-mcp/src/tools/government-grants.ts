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
  "Set related_type='government_grant' and related_id to the government grant ID where applicable.";

type ToolResult = { content: Array<{ type: "text"; text: string }>; isError?: boolean };

export const tools: Tool[] = [
  {
    name: "get_government_grants",
    description:
      "Fetch IAS 20 government grants for a company. " +
      "Returns each grant with type, authority, total/recognised/deferred amounts, and status. " +
      "Requires prior login.",
    inputSchema: {
      type: "object" as const,
      properties: {},
      required: [],
    },
  },
  {
    name: "get_government_grant",
    description:
      "Fetch a single IAS 20 government grant by ID with full detail including events history." + FLAG_RULE,
    inputSchema: {
      type: "object" as const,
      properties: {
        grant_id: { type: "number", description: "Government grant ID" },
      },
      required: ["grant_id"],
    },
  },
  {
    name: "create_government_grant",
    description:
      "Register a new IAS 20 government grant. " +
      "The grant is created with deferred_amount equal to total_amount and status active." + FLAG_RULE,
    inputSchema: {
      type: "object" as const,
      properties: {
        name: { type: "string", description: "Grant name" },
        grant_type: { type: "string", enum: ["income", "asset"], description: "Income or asset-related grant" },
        grant_date: { type: "string", description: "YYYY-MM-DD" },
        total_amount: { type: "number", description: "Total grant amount" },
        grant_reference: { type: "string", description: "Optional reference number" },
        granting_authority: { type: "string", description: "Granting authority name" },
        recognition_method: { type: "string", enum: ["systematic", "immediate"], description: "Recognition method (default: systematic)" },
        related_asset_type: { type: "string", description: "Related asset type (optional)" },
        related_asset_id: { type: "number", description: "Related asset ID (optional)" },
        conditions_text: { type: "string", description: "Grant conditions text (optional)" },
        notes: { type: "string", description: "Optional notes" },
      },
      required: ["name", "grant_type", "grant_date", "total_amount"],
    },
  },
  {
    name: "recognise_government_grant",
    description:
      "Recognise a government grant (IAS 20.7). AI posts the recognition journal." + FLAG_RULE,
    inputSchema: {
      type: "object" as const,
      properties: {
        grant_id: { type: "number", description: "Government grant ID" },
        amount: { type: "number", description: "Amount to recognise" },
        date: { type: "string", description: "YYYY-MM-DD" },
      },
      required: ["grant_id", "amount", "date"],
    },
  },
  {
    name: "amortise_government_grant",
    description:
      "Amortise a government grant to income (IAS 20.12). AI posts the amortisation journal." + FLAG_RULE,
    inputSchema: {
      type: "object" as const,
      properties: {
        grant_id: { type: "number", description: "Government grant ID" },
        amount: { type: "number", description: "Amount to amortise to income" },
        date: { type: "string", description: "YYYY-MM-DD" },
      },
      required: ["grant_id", "amount", "date"],
    },
  },
  {
    name: "refund_government_grant",
    description:
      "Record a government grant refund (IAS 20.32). AI posts the refund journal." + FLAG_RULE,
    inputSchema: {
      type: "object" as const,
      properties: {
        grant_id: { type: "number", description: "Government grant ID" },
        amount: { type: "number", description: "Amount refunded" },
        date: { type: "string", description: "YYYY-MM-DD" },
      },
      required: ["grant_id", "amount", "date"],
    },
  },
];

export async function handle(name: string, args: Record<string, unknown>): Promise<ToolResult | null> {
  switch (name) {
    case "get_government_grants": {
      const data = await apiGet("/government-grants");
      return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
    }
    case "get_government_grant": {
      const data = await apiGet(`/government-grants/${args.grant_id}`);
      return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
    }
    case "create_government_grant": {
      const data = await apiPost("/government-grants", args);
      return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
    }
    case "recognise_government_grant": {
      const data = await apiPost(`/government-grants/${args.grant_id}/recognise`, args);
      return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
    }
    case "amortise_government_grant": {
      const data = await apiPost(`/government-grants/${args.grant_id}/amortise`, args);
      return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
    }
    case "refund_government_grant": {
      const data = await apiPost(`/government-grants/${args.grant_id}/refund`, args);
      return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
    }
    default:
      return null;
  }
}
