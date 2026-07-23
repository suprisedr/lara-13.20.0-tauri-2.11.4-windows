import { getBearerToken } from "../lib/graphql.js";
const API_BASE = "http://localhost:8000/api";
async function apiPost(path, body) {
    const token = getBearerToken();
    if (!token)
        throw new Error("Not authenticated. Please run the login tool first.");
    const response = await fetch(`${API_BASE}${path}`, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
            Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify(body),
    });
    const json = await response.json();
    if (!response.ok) {
        const message = json.message ?? `HTTP ${response.status}`;
        throw new Error(message);
    }
    return json;
}
export const tools = [
    {
        name: "create_action",
        description: "Create a flagged action item for a company in the Chainbook Intelligence Actions list. " +
            "Use this whenever you detect an issue, discrepancy, or required follow-up that the user or accountant must attend to — " +
            "for example: a transaction missing a source document, an unreconciled balance, a missing VAT registration, " +
            "a deferred tax account that needs to be set up, an asset with no PPE class, or any other compliance flag. " +
            "Actions appear in the company's Actions page with a badge count in the sidebar so nothing gets missed. " +
            "Requires prior login.\n\n" +
            "Priority guide:\n" +
            "• high   — compliance risk, audit finding, material misstatement possible\n" +
            "• medium — should be resolved soon, accounting estimate or disclosure issue\n" +
            "• low    — housekeeping, informational follow-up",
        inputSchema: {
            type: "object",
            properties: {
                company_id: { type: "string", description: "The company ID this action belongs to." },
                title: { type: "string", description: "Short, clear description of the action required (max 255 chars)." },
                body: { type: "string", description: "Detailed explanation of the issue, what was found, and what needs to be done. Be specific." },
                priority: { type: "string", enum: ["low", "medium", "high"], description: "Urgency of the action." },
                related_type: { type: "string", description: "Optional: entity type the action relates to (e.g. 'transaction', 'asset', 'invoice', 'chart_of_account')." },
                related_id: { type: "number", description: "Optional: ID of the related entity." },
            },
            required: ["company_id", "title", "priority"],
        },
    },
    {
        name: "update_action",
        description: "Update an existing action item — resolve it, reopen it, or change its title/body/priority. " +
            "Use this when the issue that triggered the action has been addressed and the action should be marked as resolved, " +
            "or when the action's details need correcting. " +
            "Requires prior login.",
        inputSchema: {
            type: "object",
            properties: {
                id: { type: "number", description: "The action ID to update." },
                title: { type: "string", description: "Updated title (max 255 chars). Optional." },
                body: { type: "string", description: "Updated detail text. Optional." },
                priority: { type: "string", enum: ["low", "medium", "high"], description: "Updated priority. Optional." },
                resolved: { type: "boolean", description: "Set to true to resolve the action, false to reopen it. Optional." },
                related_type: { type: "string", description: "Optional: entity type the action relates to." },
                related_id: { type: "number", description: "Optional: ID of the related entity." },
            },
            required: ["id"],
        },
    },
];
export async function handle(name, args) {
    if (name === "update_action")
        return handleUpdateAction(args);
    if (name !== "create_action")
        return null;
    const { company_id, title, body, priority, related_type, related_id } = args;
    try {
        const result = await apiPost("/actions", {
            company_id: Number(company_id),
            title,
            body: body ?? null,
            priority,
            related_type: related_type ?? null,
            related_id: related_id ?? null,
        });
        return {
            content: [{
                    type: "text",
                    text: JSON.stringify({
                        success: true,
                        message: `Action flagged: "${title}" (${priority} priority). It will appear in the company's Actions list.`,
                        action: result.data,
                    }, null, 2),
                }],
        };
    }
    catch (err) {
        return {
            content: [{ type: "text", text: JSON.stringify({ error: err.message }, null, 2) }],
            isError: true,
        };
    }
}
async function handleUpdateAction(args) {
    const { id, ...fields } = args;
    const payload = {};
    for (const [k, v] of Object.entries(fields)) {
        if (v !== undefined)
            payload[k] = v;
    }
    if (Object.keys(payload).length === 0) {
        return {
            content: [{ type: "text", text: "update_action requires at least one field to change." }],
            isError: true,
        };
    }
    const token = getBearerToken();
    if (!token)
        throw new Error("Not authenticated. Please run the login tool first.");
    const response = await fetch(`${API_BASE}/actions/${id}`, {
        method: "PATCH",
        headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
            Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify(payload),
    });
    const result = await response.json();
    if (!response.ok)
        throw new Error(result.message ?? `HTTP ${response.status}`);
    return {
        content: [{
                type: "text",
                text: JSON.stringify({ success: true, action: result.data }, null, 2),
            }],
    };
}
