import { getBearerToken } from "../lib/graphql.js";
import { API_BASE } from "../lib/api.js";
async function apiGet(path) {
    const token = getBearerToken();
    if (!token)
        throw new Error("Not authenticated. Please run the login tool first.");
    const response = await fetch(`${API_BASE}${path}`, {
        headers: { Accept: "application/json", Authorization: `Bearer ${token}` },
    });
    const json = await response.json();
    if (!response.ok)
        throw new Error(json.message ?? `HTTP ${response.status}`);
    return json;
}
async function apiPost(path, body) {
    const token = getBearerToken();
    if (!token)
        throw new Error("Not authenticated. Please run the login tool first.");
    const response = await fetch(`${API_BASE}${path}`, {
        method: "POST",
        headers: { "Content-Type": "application/json", Accept: "application/json", Authorization: `Bearer ${token}` },
        body: JSON.stringify(body),
    });
    const json = await response.json();
    if (!response.ok)
        throw new Error(json.message ?? `HTTP ${response.status}`);
    return json;
}
async function apiPatch(path, body) {
    const token = getBearerToken();
    if (!token)
        throw new Error("Not authenticated. Please run the login tool first.");
    const response = await fetch(`${API_BASE}${path}`, {
        method: "PATCH",
        headers: { "Content-Type": "application/json", Accept: "application/json", Authorization: `Bearer ${token}` },
        body: JSON.stringify(body),
    });
    const json = await response.json();
    if (!response.ok)
        throw new Error(json.message ?? `HTTP ${response.status}`);
    return json;
}
const FLAG_RULE = "\n\nACTION FLAGGING (mandatory): After completing this operation, if you identify ANY issue, " +
    "compliance gap, missing information, or recommended follow-up, you MUST call create_action for EACH one — " +
    "do not leave findings only in your text reply. " +
    "Use priority 'high' for compliance/audit risk, 'medium' for accounting estimate issues, 'low' for housekeeping. " +
    "Set related_type='share_based_payment' and related_id to the arrangement ID where applicable.";
export const tools = [
    {
        name: "get_share_based_payments",
        description: "Fetch IFRS 2 share-based payment arrangements for a company. " +
            "Returns each arrangement with type, grant date, instruments, fair value, total expense, vesting %, and status. " +
            "Requires prior login.",
        inputSchema: {
            type: "object",
            properties: {},
            required: [],
        },
    },
    {
        name: "get_share_based_payment",
        description: "Fetch a single IFRS 2 share-based payment arrangement by ID with full detail including events history." + FLAG_RULE,
        inputSchema: {
            type: "object",
            properties: {
                arrangement_id: { type: "number", description: "Arrangement ID" },
            },
            required: ["arrangement_id"],
        },
    },
    {
        name: "create_share_based_payment",
        description: "Register a new IFRS 2 share-based payment arrangement. " +
            "AI will record the grant event automatically." + FLAG_RULE,
        inputSchema: {
            type: "object",
            properties: {
                name: { type: "string", description: "Arrangement name" },
                arrangement_type: { type: "string", enum: ["equity_settled", "cash_settled", "choice"], description: "Type of arrangement" },
                grant_date: { type: "string", description: "YYYY-MM-DD" },
                number_of_instruments: { type: "number", description: "Number of instruments granted" },
                fair_value_at_grant: { type: "number", description: "Fair value per instrument at grant date" },
                vesting_start_date: { type: "string", description: "YYYY-MM-DD optional" },
                vesting_end_date: { type: "string", description: "YYYY-MM-DD optional" },
                exercise_price: { type: "number", description: "Exercise price per instrument, optional" },
                vesting_conditions: { type: "string", description: "Description of vesting conditions" },
                notes: { type: "string" },
            },
            required: ["name", "arrangement_type", "grant_date", "number_of_instruments", "fair_value_at_grant"],
        },
    },
    {
        name: "record_vesting_expense",
        description: "Record a vesting expense for a share-based payment arrangement (IFRS 2.7). AI posts the journal." + FLAG_RULE,
        inputSchema: {
            type: "object",
            properties: {
                arrangement_id: { type: "number", description: "Arrangement ID" },
                amount: { type: "number", description: "Expense amount" },
                date: { type: "string", description: "YYYY-MM-DD" },
            },
            required: ["arrangement_id", "amount", "date"],
        },
    },
    {
        name: "exercise_share_based_payment",
        description: "Exercise instruments in a share-based payment arrangement (IFRS 2.23). AI posts the journal." + FLAG_RULE,
        inputSchema: {
            type: "object",
            properties: {
                arrangement_id: { type: "number", description: "Arrangement ID" },
                instruments: { type: "number", description: "Number of instruments to exercise" },
                date: { type: "string", description: "YYYY-MM-DD" },
            },
            required: ["arrangement_id", "instruments", "date"],
        },
    },
    {
        name: "forfeit_share_based_payment",
        description: "Forfeit instruments in a share-based payment arrangement (IFRS 2.28). AI posts the reversal journal." + FLAG_RULE,
        inputSchema: {
            type: "object",
            properties: {
                arrangement_id: { type: "number", description: "Arrangement ID" },
                instruments: { type: "number", description: "Number of instruments to forfeit" },
                date: { type: "string", description: "YYYY-MM-DD" },
            },
            required: ["arrangement_id", "instruments", "date"],
        },
    },
];
export async function handle(name, args) {
    switch (name) {
        case "get_share_based_payments": {
            const data = await apiGet("/share-based-payments");
            return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
        }
        case "get_share_based_payment": {
            const data = await apiGet(`/share-based-payments/${args.arrangement_id}`);
            return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
        }
        case "create_share_based_payment": {
            const data = await apiPost("/share-based-payments", args);
            return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
        }
        case "record_vesting_expense": {
            const data = await apiPost(`/share-based-payments/${args.arrangement_id}/vesting-expense`, args);
            return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
        }
        case "exercise_share_based_payment": {
            const data = await apiPost(`/share-based-payments/${args.arrangement_id}/exercise`, args);
            return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
        }
        case "forfeit_share_based_payment": {
            const data = await apiPost(`/share-based-payments/${args.arrangement_id}/forfeit`, args);
            return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
        }
        default:
            return null;
    }
}
