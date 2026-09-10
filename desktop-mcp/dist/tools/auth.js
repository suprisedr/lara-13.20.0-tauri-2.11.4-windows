import { login, hasBearerToken } from "../lib/transport.js";
export const tools = [
    {
        name: "login",
        description: "Authenticate against the accounting API. Stores the bearer token for all subsequent requests. Credentials can be overridden via parameters; defaults use the configured service account.",
        inputSchema: {
            type: "object",
            properties: {
                email: { type: "string", description: "User email address", default: "hlogi@nkonyanaaccountants.co.za" },
                password: { type: "string", description: "User password", default: "Hlogi@1993" },
                device_name: { type: "string", description: "Device / client identifier", default: "mcp-client" },
            },
            required: [],
        },
    },
    {
        name: "get_auth_status",
        description: "Check whether the MCP server currently holds a valid bearer token (i.e. whether login has been called).",
        inputSchema: { type: "object", properties: {}, required: [] },
    },
];
export async function handle(name, args) {
    if (name === "login") {
        const email = args.email ?? "hlogi@nkonyanaaccountants.co.za";
        const password = args.password ?? "Hlogi@1993";
        const device_name = args.device_name ?? "mcp-client";
        const result = await login(email, password, device_name);
        return {
            content: [
                {
                    type: "text",
                    text: JSON.stringify({ success: true, message: "Login successful. Bearer token stored.", user: result.user }, null, 2),
                },
            ],
        };
    }
    if (name === "get_auth_status") {
        const authenticated = hasBearerToken();
        return {
            content: [
                {
                    type: "text",
                    text: JSON.stringify({
                        authenticated,
                        message: authenticated
                            ? "Bearer token is present. Ready to make authenticated requests."
                            : "No bearer token. Please run the login tool.",
                    }, null, 2),
                },
            ],
        };
    }
    return null;
}
