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
    "Set related_type='revenue_contract' and related_id to the contract ID where applicable.";
export const tools = [
    {
        name: "get_revenue_contracts",
        description: "Fetch IFRS 15 revenue contracts for a company. Returns each contract with performance obligations, " +
            "transaction price, revenue recognised, and status. Requires prior login.",
        inputSchema: { type: "object", properties: {}, required: [] },
    },
    {
        name: "get_revenue_contract",
        description: "Fetch a single IFRS 15 revenue contract by ID with performance obligations." + FLAG_RULE,
        inputSchema: {
            type: "object",
            properties: { contract_id: { type: "number", description: "Revenue contract ID" } },
            required: ["contract_id"],
        },
    },
    {
        name: "create_revenue_contract",
        description: "Create a new IFRS 15 revenue contract." + FLAG_RULE,
        inputSchema: {
            type: "object",
            properties: {
                name: { type: "string" },
                contract_reference: { type: "string" },
                customer_name: { type: "string" },
                contract_date: { type: "string", description: "YYYY-MM-DD" },
                total_transaction_price: { type: "number" },
                currency: { type: "string", description: "ISO 4217, default ZAR" },
                notes: { type: "string" },
            },
            required: ["name", "customer_name", "contract_date", "total_transaction_price"],
        },
    },
    {
        name: "add_performance_obligation",
        description: "Add a performance obligation to a revenue contract (IFRS 15 Step 2)." + FLAG_RULE,
        inputSchema: {
            type: "object",
            properties: {
                contract_id: { type: "number" },
                name: { type: "string" },
                standalone_price: { type: "number" },
                allocated_price: { type: "number" },
                recognition_method: { type: "string", enum: ["point_in_time", "over_time"] },
                over_time_method: { type: "string", enum: ["output", "input", "cost_to_cost", "units", "time"] },
            },
            required: ["contract_id", "name", "standalone_price", "recognition_method"],
        },
    },
    {
        name: "recognise_revenue",
        description: "Recognise revenue for a performance obligation (IFRS 15 Step 5). AI posts the journal." + FLAG_RULE,
        inputSchema: {
            type: "object",
            properties: {
                contract_id: { type: "number" },
                performance_obligation_id: { type: "number" },
                amount: { type: "number" },
                date: { type: "string", description: "YYYY-MM-DD" },
            },
            required: ["contract_id", "performance_obligation_id", "amount", "date"],
        },
    },
    {
        name: "record_advance_receipt",
        description: "Record an advance receipt creating a contract liability (IFRS 15). AI posts the journal." + FLAG_RULE,
        inputSchema: {
            type: "object",
            properties: {
                contract_id: { type: "number" },
                amount: { type: "number" },
                date: { type: "string", description: "YYYY-MM-DD" },
            },
            required: ["contract_id", "amount", "date"],
        },
    },
    {
        name: "release_contract_liability",
        description: "Release contract liability to revenue when obligation is satisfied (IFRS 15). AI posts the journal." + FLAG_RULE,
        inputSchema: {
            type: "object",
            properties: {
                contract_id: { type: "number" },
                performance_obligation_id: { type: "number" },
                amount: { type: "number" },
                date: { type: "string", description: "YYYY-MM-DD" },
            },
            required: ["contract_id", "performance_obligation_id", "amount", "date"],
        },
    },
];
export async function handle(name, args) {
    switch (name) {
        case "get_revenue_contracts": {
            const data = await apiGet("/revenue-contracts");
            return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
        }
        case "get_revenue_contract": {
            const data = await apiGet(`/revenue-contracts/${args.contract_id}`);
            return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
        }
        case "create_revenue_contract": {
            const data = await apiPost("/revenue-contracts", args);
            return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
        }
        case "add_performance_obligation": {
            const data = await apiPost(`/revenue-contracts/${args.contract_id}/obligations`, args);
            return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
        }
        case "recognise_revenue": {
            const data = await apiPost(`/revenue-contracts/${args.contract_id}/recognise-revenue`, args);
            return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
        }
        case "record_advance_receipt": {
            const data = await apiPost(`/revenue-contracts/${args.contract_id}/advance-receipt`, args);
            return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
        }
        case "release_contract_liability": {
            const data = await apiPost(`/revenue-contracts/${args.contract_id}/release-liability`, args);
            return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
        }
        default:
            return null;
    }
}
