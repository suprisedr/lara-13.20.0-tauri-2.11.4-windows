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
  "Set related_type='deferred_tax_item' and related_id to the deferred tax item ID where applicable.";

type ToolResult = { content: Array<{ type: "text"; text: string }>; isError?: boolean };

export const tools: Tool[] = [
  {
    name: "get_deferred_tax_items",
    description:
      "Fetch IAS 12 deferred tax items for a company. " +
      "Returns each item with source type, tax base, carrying amount, temporary difference, DTA, DTL, tax rate, and status. " +
      "Requires prior login.",
    inputSchema: {
      type: "object" as const,
      properties: {},
      required: [],
    },
  },
  {
    name: "get_deferred_tax_item",
    description:
      "Fetch a single IAS 12 deferred tax item by ID with full detail." + FLAG_RULE,
    inputSchema: {
      type: "object" as const,
      properties: {
        item_id: { type: "number", description: "Deferred tax item ID" },
      },
      required: ["item_id"],
    },
  },
  {
    name: "create_deferred_tax_item",
    description:
      "Register a new IAS 12 deferred tax item. " +
      "Calculates temporary difference and deferred tax asset/liability automatically." + FLAG_RULE,
    inputSchema: {
      type: "object" as const,
      properties: {
        name: { type: "string", description: "Descriptive name for the deferred tax item" },
        source_type: { type: "string", enum: ["ppe", "intangible", "lease", "provision", "revenue_contract", "inventory", "other"], description: "Source of the temporary difference" },
        tax_base: { type: "number", description: "Tax base amount" },
        carrying_amount: { type: "number", description: "Carrying amount per financial statements" },
        tax_rate: { type: "number", description: "Tax rate percentage, e.g. 27" },
        measurement_date: { type: "string", description: "YYYY-MM-DD" },
        source_id: { type: "number", description: "Optional ID of the related source asset/item" },
        is_taxable: { type: "boolean", description: "Whether the item is taxable (default true)" },
        notes: { type: "string" },
      },
      required: ["name", "source_type", "tax_base", "carrying_amount", "tax_rate", "measurement_date"],
    },
  },
  {
    name: "remeasure_deferred_tax",
    description:
      "Remeasure a deferred tax item with new carrying amount and tax base (IAS 12.37). AI posts the adjustment journal." + FLAG_RULE,
    inputSchema: {
      type: "object" as const,
      properties: {
        item_id: { type: "number", description: "Deferred tax item ID" },
        new_carrying_amount: { type: "number", description: "Updated carrying amount" },
        new_tax_base: { type: "number", description: "Updated tax base" },
        date: { type: "string", description: "YYYY-MM-DD" },
      },
      required: ["item_id", "new_carrying_amount", "new_tax_base", "date"],
    },
  },
  {
    name: "reverse_deferred_tax",
    description:
      "Reverse a deferred tax item when the temporary difference reverses (IAS 12.41). AI posts the reversal journal." + FLAG_RULE,
    inputSchema: {
      type: "object" as const,
      properties: {
        item_id: { type: "number", description: "Deferred tax item ID" },
        date: { type: "string", description: "YYYY-MM-DD" },
      },
      required: ["item_id", "date"],
    },
  },
];

export async function handle(name: string, args: Record<string, unknown>): Promise<ToolResult | null> {
  switch (name) {
    case "get_deferred_tax_items": {
      const data = await apiGet("/deferred-tax");
      return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
    }
    case "get_deferred_tax_item": {
      const data = await apiGet(`/deferred-tax/${args.item_id}`);
      return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
    }
    case "create_deferred_tax_item": {
      const data = await apiPost("/deferred-tax", args);
      return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
    }
    case "remeasure_deferred_tax": {
      const data = await apiPost(`/deferred-tax/${args.item_id}/remeasure`, args);
      return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
    }
    case "reverse_deferred_tax": {
      const data = await apiPost(`/deferred-tax/${args.item_id}/reverse`, args);
      return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
    }
    default:
      return null;
  }
}
