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
  "Set related_type='related_party' and related_id to the party ID where applicable.";

type ToolResult = { content: Array<{ type: "text"; text: string }>; isError?: boolean };

export const tools: Tool[] = [
  {
    name: "get_related_parties",
    description:
      "Fetch IAS 24 related parties for a company. " +
      "Returns each party with name, relationship type, contact person, active status, and transaction count. " +
      "Related parties include parents, subsidiaries, associates, joint ventures, key management personnel, and close family members. " +
      "Requires prior login.",
    inputSchema: {
      type: "object",
      properties: {
        company_id: { type: "string", description: "The ID of the company whose related parties to list." },
      },
      required: ["company_id"],
    },
  },
  {
    name: "get_related_party",
    description:
      "Fetch a single IAS 24 related party by ID with full details. Requires prior login.",
    inputSchema: {
      type: "object",
      properties: {
        id: { type: "string", description: "The related party ID to fetch." },
      },
      required: ["id"],
    },
  },
  {
    name: "create_related_party",
    description:
      "Register a new IAS 24 related party. " +
      "IAS 24 requires disclosure of related party relationships, transactions, and outstanding balances. " +
      "Relationship types: parent, subsidiary, associate, joint_venture, key_management, close_family, entity_with_common_kmp, post_employment_plan, other. " +
      "Requires prior login." +
      FLAG_RULE,
    inputSchema: {
      type: "object",
      properties: {
        company_id: { type: "string", description: "The ID of the company." },
        name: { type: "string", description: "Related party name (e.g. 'ABC Holdings Ltd', 'John Smith — Director')." },
        relationship_type: { type: "string", description: "One of: parent, subsidiary, associate, joint_venture, key_management, close_family, entity_with_common_kmp, post_employment_plan, other." },
        description: { type: "string", description: "Optional description of the relationship." },
        contact_person: { type: "string", description: "Optional contact person name." },
        is_active: { type: "boolean", description: "Whether the party is currently active (default true)." },
      },
      required: ["company_id", "name", "relationship_type"],
    },
  },
  {
    name: "update_related_party",
    description:
      "Update an IAS 24 related party's details. " +
      "Requires prior login." +
      FLAG_RULE,
    inputSchema: {
      type: "object",
      properties: {
        id: { type: "string", description: "The related party ID to update." },
        name: { type: "string" },
        relationship_type: { type: "string" },
        description: { type: "string" },
        contact_person: { type: "string" },
        is_active: { type: "boolean" },
      },
      required: ["id"],
    },
  },
  {
    name: "get_related_party_transactions",
    description:
      "Fetch all IAS 24 transactions for a specific related party. " +
      "Returns transaction date, type, amount, outstanding balance, arm's length status, and terms. " +
      "Requires prior login.",
    inputSchema: {
      type: "object",
      properties: {
        id: { type: "string", description: "The related party ID whose transactions to list." },
      },
      required: ["id"],
    },
  },
  {
    name: "create_related_party_transaction",
    description:
      "Record a new IAS 24 related party transaction. " +
      "IAS 24.18 requires disclosure of the nature, amount, outstanding balances, terms, and whether transactions are at arm's length. " +
      "Transaction types: sale, purchase, loan, management_fee, dividend, guarantee, compensation, lease, other. " +
      "This is disclosure-only — no journal entries are posted. " +
      "Requires prior login." +
      FLAG_RULE,
    inputSchema: {
      type: "object",
      properties: {
        id: { type: "string", description: "The related party ID." },
        transaction_date: { type: "string", description: "Transaction date in YYYY-MM-DD format." },
        transaction_type: { type: "string", description: "One of: sale, purchase, loan, management_fee, dividend, guarantee, compensation, lease, other." },
        amount: { type: "number", description: "Transaction amount." },
        description: { type: "string", description: "Optional description of the transaction." },
        outstanding_balance: { type: "number", description: "Outstanding balance at reporting date (optional)." },
        terms_and_conditions: { type: "string", description: "Terms and conditions of the transaction (optional)." },
        is_arm_length: { type: "boolean", description: "Whether the transaction is at arm's length (default true)." },
        transaction_id: { type: "number", description: "Optional linked transaction ID from the general ledger." },
      },
      required: ["id", "transaction_date", "transaction_type", "amount"],
    },
  },
];

export async function handle(
  name: string,
  args: Record<string, unknown>
): Promise<ToolResult | null> {
  switch (name) {
    case "get_related_parties":
      return handleGetAll(args);
    case "get_related_party":
      return handleGetOne(args);
    case "create_related_party":
      return handleCreate(args);
    case "update_related_party":
      return handleUpdate(args);
    case "get_related_party_transactions":
      return handleGetTransactions(args);
    case "create_related_party_transaction":
      return handleCreateTransaction(args);
    default:
      return null;
  }
}

async function handleGetAll(args: Record<string, unknown>): Promise<ToolResult> {
  const data = await apiGet(`/related-parties?company_id=${args.company_id}`);
  return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
}

async function handleGetOne(args: Record<string, unknown>): Promise<ToolResult> {
  const data = await apiGet(`/related-parties/${args.id}`);
  return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
}

async function handleCreate(args: Record<string, unknown>): Promise<ToolResult> {
  const body: Record<string, unknown> = {
    company_id: args.company_id,
    name: args.name,
    relationship_type: args.relationship_type,
  };
  for (const k of ["description", "contact_person", "is_active"]) {
    if (args[k] !== undefined) body[k] = args[k];
  }
  const result = await apiPost("/related-parties", body);
  return { content: [{ type: "text", text: JSON.stringify(result, null, 2) }] };
}

async function handleUpdate(args: Record<string, unknown>): Promise<ToolResult> {
  const { id, ...body } = args;
  const result = await apiPatch(`/related-parties/${id}`, body);
  return { content: [{ type: "text", text: JSON.stringify(result, null, 2) }] };
}

async function handleGetTransactions(args: Record<string, unknown>): Promise<ToolResult> {
  const data = await apiGet(`/related-parties/${args.id}/transactions`);
  return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
}

async function handleCreateTransaction(args: Record<string, unknown>): Promise<ToolResult> {
  const { id, ...rest } = args;
  const body: Record<string, unknown> = {
    transaction_date: rest.transaction_date,
    transaction_type: rest.transaction_type,
    amount: rest.amount,
  };
  for (const k of ["description", "outstanding_balance", "terms_and_conditions", "is_arm_length", "transaction_id"]) {
    if (rest[k] !== undefined) body[k] = rest[k];
  }
  const result = await apiPost(`/related-parties/${id}/transactions`, body);
  return { content: [{ type: "text", text: JSON.stringify(result, null, 2) }] };
}
