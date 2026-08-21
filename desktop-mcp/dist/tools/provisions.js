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
    "Set related_type='provision' and related_id to the provision ID where applicable.";
export const tools = [
    {
        name: "get_provisions",
        description: "Fetch IAS 37 provisions, contingent liabilities and contingent assets for a company. " +
            "Returns each item with type, probability, initial/current estimate, present value, and status. " +
            "Requires prior login.",
        inputSchema: {
            type: "object",
            properties: {},
            required: [],
        },
    },
    {
        name: "get_provision",
        description: "Fetch a single IAS 37 provision by ID with full detail including events history." + FLAG_RULE,
        inputSchema: {
            type: "object",
            properties: {
                provision_id: { type: "number", description: "Provision ID" },
            },
            required: ["provision_id"],
        },
    },
    {
        name: "create_provision",
        description: "Register a new IAS 37 provision, contingent liability, or contingent asset. " +
            "For provisions (type=provision, probability=probable), AI will post the recognition journal automatically." + FLAG_RULE,
        inputSchema: {
            type: "object",
            properties: {
                name: { type: "string" },
                provision_type: { type: "string", enum: ["provision", "contingent_liability", "contingent_asset"] },
                recognition_date: { type: "string", description: "YYYY-MM-DD" },
                initial_estimate: { type: "number", description: "Best estimate amount" },
                probability: { type: "string", enum: ["probable", "possible", "remote"] },
                expected_settlement_date: { type: "string", description: "YYYY-MM-DD optional" },
                discount_rate: { type: "number", description: "Percentage, e.g. 8.5" },
                provision_class_id: { type: "number" },
                notes: { type: "string" },
            },
            required: ["name", "provision_type", "recognition_date", "initial_estimate"],
        },
    },
    {
        name: "remeasure_provision",
        description: "Remeasure a provision to a new best estimate (IAS 37.36). AI posts the adjustment journal." + FLAG_RULE,
        inputSchema: {
            type: "object",
            properties: {
                provision_id: { type: "number" },
                new_estimate: { type: "number" },
                date: { type: "string", description: "YYYY-MM-DD" },
            },
            required: ["provision_id", "new_estimate", "date"],
        },
    },
    {
        name: "utilise_provision",
        description: "Record utilisation/settlement against a provision (IAS 37.61). AI posts the journal." + FLAG_RULE,
        inputSchema: {
            type: "object",
            properties: {
                provision_id: { type: "number" },
                amount: { type: "number" },
                date: { type: "string", description: "YYYY-MM-DD" },
            },
            required: ["provision_id", "amount", "date"],
        },
    },
    {
        name: "reverse_provision",
        description: "Reverse a provision when outflow is no longer probable (IAS 37.59). AI posts the reversal journal." + FLAG_RULE,
        inputSchema: {
            type: "object",
            properties: {
                provision_id: { type: "number" },
                date: { type: "string", description: "YYYY-MM-DD" },
            },
            required: ["provision_id", "date"],
        },
    },
];
export async function handle(name, args) {
    switch (name) {
        case "get_provisions": {
            const data = await apiGet("/provisions");
            return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
        }
        case "get_provision": {
            const data = await apiGet(`/provisions/${args.provision_id}`);
            return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
        }
        case "create_provision": {
            const data = await apiPost("/provisions", args);
            return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
        }
        case "remeasure_provision": {
            const data = await apiPost(`/provisions/${args.provision_id}/remeasure`, args);
            return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
        }
        case "utilise_provision": {
            const data = await apiPost(`/provisions/${args.provision_id}/utilise`, args);
            return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
        }
        case "reverse_provision": {
            const data = await apiPost(`/provisions/${args.provision_id}/reverse`, args);
            return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }] };
        }
        default:
            return null;
    }
}
