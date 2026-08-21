import { grpcCall, getAuthService, getTransactionService, getAssetService, getLeaseService, getInventoryService, getIntangibleService, setGrpcBearerToken, getGrpcBearerToken } from "./grpc-client.js";
import { graphqlRequest, setBearerToken as setRestBearerToken, getBearerToken as getRestBearerToken } from "./graphql.js";
import { API_BASE } from "./api.js";
const USE_GRPC = process.env.USE_GRPC !== "false";
let grpcAvailable = null;
async function isGrpcAvailable() {
    if (!USE_GRPC)
        return false;
    if (grpcAvailable !== null)
        return grpcAvailable;
    try {
        await grpcCall(getAuthService, "getAuthStatus", {});
        grpcAvailable = true;
    }
    catch {
        grpcAvailable = false;
    }
    return grpcAvailable;
}
export function storeBearerToken(token) {
    setGrpcBearerToken(token);
    setRestBearerToken(token);
}
export function hasBearerToken() {
    return getGrpcBearerToken() !== null || getRestBearerToken() !== null;
}
export async function login(email, password, deviceName) {
    if (await isGrpcAvailable()) {
        const res = await grpcCall(getAuthService, "login", { email, password, device_name: deviceName });
        const token = res.token;
        storeBearerToken(token);
        return { token, user: res.user };
    }
    const query = `
    mutation Login($email: String!, $password: String!, $device_name: String!) {
      login(input: { email: $email, password: $password, device_name: $device_name }) {
        token
        user { id name email }
      }
    }
  `;
    const data = (await graphqlRequest(query, { email, password, device_name: deviceName }, false));
    storeBearerToken(data.login.token);
    return { token: data.login.token, user: data.login.user };
}
async function restGet(path) {
    const token = getRestBearerToken();
    const headers = { Accept: "application/json" };
    if (token)
        headers["Authorization"] = `Bearer ${token}`;
    const res = await fetch(`${API_BASE}${path}`, { headers });
    return res.json();
}
async function restPost(path, body) {
    const token = getRestBearerToken();
    const headers = { "Content-Type": "application/json", Accept: "application/json" };
    if (token)
        headers["Authorization"] = `Bearer ${token}`;
    const res = await fetch(`${API_BASE}${path}`, { method: "POST", headers, body: JSON.stringify(body) });
    return res.json();
}
async function restPatch(path, body) {
    const token = getRestBearerToken();
    const headers = { "Content-Type": "application/json", Accept: "application/json" };
    if (token)
        headers["Authorization"] = `Bearer ${token}`;
    const res = await fetch(`${API_BASE}${path}`, { method: "PATCH", headers, body: JSON.stringify(body) });
    return res.json();
}
function parseGrpcData(res) {
    if (res?.data && typeof res.data === "string") {
        try {
            return JSON.parse(res.data);
        }
        catch {
            return res.data;
        }
    }
    return res;
}
// --- Assets ---
export async function getAssets(companyId, status, ppeClass) {
    if (await isGrpcAvailable()) {
        const res = await grpcCall(getAssetService, "getAssets", { company_id: companyId, status, ppe_class: ppeClass });
        return parseGrpcData(res);
    }
    const params = new URLSearchParams({ company_id: String(companyId) });
    if (status)
        params.set("status", status);
    if (ppeClass)
        params.set("ppe_class", ppeClass);
    return restGet(`/assets?${params}`);
}
export async function getAsset(id) {
    if (await isGrpcAvailable()) {
        const res = await grpcCall(getAssetService, "getAsset", { id });
        return parseGrpcData(res);
    }
    return restGet(`/assets/${id}`);
}
export async function createAsset(data) {
    if (await isGrpcAvailable()) {
        const res = await grpcCall(getAssetService, "createAsset", { data_json: JSON.stringify(data) });
        return parseGrpcData(res);
    }
    return restPost("/assets", data);
}
export async function updateAsset(id, data) {
    if (await isGrpcAvailable()) {
        const res = await grpcCall(getAssetService, "updateAsset", { id, data_json: JSON.stringify(data) });
        return parseGrpcData(res);
    }
    return restPatch(`/assets/${id}`, data);
}
export async function disposeAsset(id, data) {
    if (await isGrpcAvailable()) {
        const res = await grpcCall(getAssetService, "disposeAsset", { id, data_json: JSON.stringify(data) });
        return parseGrpcData(res);
    }
    return restPost(`/assets/${id}/dispose`, data);
}
export async function assetAction(id, action, data) {
    if (await isGrpcAvailable()) {
        const methodMap = {
            revalue: "revalueAsset", impair: "impairAsset",
            "reverse-impairment": "reverseImpairment", capitalise: "capitaliseSubsequentCost",
        };
        const res = await grpcCall(getAssetService, methodMap[action] ?? action, { id, data_json: JSON.stringify(data) });
        return parseGrpcData(res);
    }
    return restPost(`/assets/${id}/${action}`, data);
}
export async function getDepreciationSchedule(id) {
    if (await isGrpcAvailable()) {
        const res = await grpcCall(getAssetService, "getDepreciationSchedule", { id });
        return parseGrpcData(res);
    }
    return restGet(`/assets/${id}/depreciation-schedule`);
}
// --- Leases ---
export async function getLeases(companyId, filters) {
    if (await isGrpcAvailable()) {
        const res = await grpcCall(getLeaseService, "getLeases", { company_id: companyId, ...filters });
        return parseGrpcData(res);
    }
    const params = new URLSearchParams({ company_id: String(companyId) });
    if (filters)
        Object.entries(filters).forEach(([k, v]) => params.set(k, v));
    return restGet(`/leases?${params}`);
}
export async function getLease(id) {
    if (await isGrpcAvailable()) {
        const res = await grpcCall(getLeaseService, "getLease", { id });
        return parseGrpcData(res);
    }
    return restGet(`/leases/${id}`);
}
export async function createLease(data) {
    if (await isGrpcAvailable()) {
        const res = await grpcCall(getLeaseService, "createLease", { data_json: JSON.stringify(data) });
        return parseGrpcData(res);
    }
    return restPost("/leases", data);
}
export async function leaseAction(id, action, data) {
    if (await isGrpcAvailable()) {
        const methodMap = {
            modify: "modifyLease", impair: "impairLease",
            "reverse-impairment": "reverseLeaseImpairment", terminate: "terminateLease",
        };
        const res = await grpcCall(getLeaseService, methodMap[action] ?? action, { id, data_json: JSON.stringify(data) });
        return parseGrpcData(res);
    }
    return restPost(`/leases/${id}/${action}`, data);
}
export async function getLeaseSchedule(id) {
    if (await isGrpcAvailable()) {
        const res = await grpcCall(getLeaseService, "getLeaseSchedule", { id });
        return parseGrpcData(res);
    }
    return restGet(`/leases/${id}/schedule`);
}
// --- Inventory ---
export async function getInventory(companyId, search) {
    if (await isGrpcAvailable()) {
        const res = await grpcCall(getInventoryService, "getInventory", { company_id: companyId, search });
        return parseGrpcData(res);
    }
    const params = new URLSearchParams({ company_id: String(companyId) });
    if (search)
        params.set("search", search);
    return restGet(`/inventory?${params}`);
}
export async function getInventoryItem(id) {
    if (await isGrpcAvailable()) {
        const res = await grpcCall(getInventoryService, "getInventoryItem", { id });
        return parseGrpcData(res);
    }
    return restGet(`/inventory/${id}`);
}
export async function createInventoryItem(data) {
    if (await isGrpcAvailable()) {
        const res = await grpcCall(getInventoryService, "createInventoryItem", { data_json: JSON.stringify(data) });
        return parseGrpcData(res);
    }
    return restPost("/inventory", data);
}
export async function updateInventoryItem(id, data) {
    if (await isGrpcAvailable()) {
        const res = await grpcCall(getInventoryService, "updateInventoryItem", { id, data_json: JSON.stringify(data) });
        return parseGrpcData(res);
    }
    return restPatch(`/inventory/${id}`, data);
}
export async function recordMovement(itemId, data) {
    if (await isGrpcAvailable()) {
        const res = await grpcCall(getInventoryService, "recordMovement", { inventory_item_id: itemId, data_json: JSON.stringify(data) });
        return res;
    }
    return restPost(`/inventory/${itemId}/movement`, data);
}
export async function writeDownInventory(itemId, data) {
    if (await isGrpcAvailable()) {
        const res = await grpcCall(getInventoryService, "writeDown", { inventory_item_id: itemId, data_json: JSON.stringify(data) });
        return parseGrpcData(res);
    }
    return restPost(`/inventory/${itemId}/write-down`, data);
}
export async function reverseWriteDown(itemId, data) {
    if (await isGrpcAvailable()) {
        const res = await grpcCall(getInventoryService, "reverseWriteDown", { inventory_item_id: itemId, data_json: JSON.stringify(data) });
        return parseGrpcData(res);
    }
    return restPost(`/inventory/${itemId}/reverse-write-down`, data);
}
// --- Intangibles ---
export async function getIntangibles(companyId, status) {
    if (await isGrpcAvailable()) {
        const res = await grpcCall(getIntangibleService, "getIntangibles", { company_id: companyId, status });
        return parseGrpcData(res);
    }
    const params = new URLSearchParams({ company_id: String(companyId) });
    if (status)
        params.set("status", status);
    return restGet(`/intangibles?${params}`);
}
export async function getIntangible(id) {
    if (await isGrpcAvailable()) {
        const res = await grpcCall(getIntangibleService, "getIntangible", { id });
        return parseGrpcData(res);
    }
    return restGet(`/intangibles/${id}`);
}
export async function createIntangible(data) {
    if (await isGrpcAvailable()) {
        const res = await grpcCall(getIntangibleService, "createIntangible", { data_json: JSON.stringify(data) });
        return parseGrpcData(res);
    }
    return restPost("/intangibles", data);
}
export async function intangibleAction(id, action, data) {
    if (await isGrpcAvailable()) {
        const methodMap = {
            dispose: "disposeIntangible", revalue: "revalueIntangible",
            impair: "impairIntangible", "reverse-impairment": "reverseImpairment",
            capitalise: "capitaliseSubsequentCost",
        };
        const res = await grpcCall(getIntangibleService, methodMap[action] ?? action, { id, data_json: JSON.stringify(data) });
        return parseGrpcData(res);
    }
    return restPost(`/intangibles/${id}/${action}`, data);
}
// --- Transactions ---
export async function searchTransactions(companyId, query, limit = 20) {
    if (await isGrpcAvailable()) {
        const res = await grpcCall(getTransactionService, "searchTransactions", { company_id: companyId, query, limit });
        return parseGrpcData(res);
    }
    return restPost("/transactions/search", { company_id: companyId, query, limit });
}
export async function getTransactionByReference(companyId, reference) {
    if (await isGrpcAvailable()) {
        const res = await grpcCall(getTransactionService, "getTransactionByReference", { company_id: companyId, reference });
        return parseGrpcData(res);
    }
    return restPost("/transactions/search", { company_id: companyId, query: reference, limit: 1 });
}
// --- Annual financial statements ---
/**
 * The row model for the four primary statements. REST only — this is a
 * presentation payload assembled from the same builders the PDF endpoints
 * use, and it has no gRPC counterpart.
 */
export async function getAfsModel(params) {
    const query = new URLSearchParams();
    if (params.startDate)
        query.set("start_date", params.startDate);
    if (params.endDate)
        query.set("end_date", params.endDate);
    if (params.rounding)
        query.set("rounding", String(params.rounding));
    query.set("compare", params.compare === false ? "0" : "1");
    for (const key of params.statements ?? [])
        query.append("statements[]", key);
    const token = getRestBearerToken();
    if (!token)
        throw new Error("Not authenticated. Please run the login tool first.");
    const res = await fetch(`${API_BASE}/companies/${params.companyId}/afs-model?${query}`, {
        headers: { Accept: "application/json", Authorization: `Bearer ${token}` },
    });
    if (!res.ok) {
        const body = await res.text();
        throw new Error(`Failed to fetch statement data (HTTP ${res.status}): ${body.slice(0, 400)}`);
    }
    return res.json();
}
/**
 * Narrative sections plus derived statistics for the business plan. REST only,
 * for the same reason as getAfsModel — it is a presentation payload.
 */
export async function getBusinessPlanModel(params) {
    const query = new URLSearchParams();
    if (params.startDate)
        query.set("start_date", params.startDate);
    if (params.endDate)
        query.set("end_date", params.endDate);
    if (params.rounding)
        query.set("rounding", String(params.rounding));
    query.set("compare", params.compare === false ? "0" : "1");
    query.set("include_statistics", params.includeStatistics === false ? "0" : "1");
    const token = getRestBearerToken();
    if (!token)
        throw new Error("Not authenticated. Please run the login tool first.");
    const res = await fetch(`${API_BASE}/companies/${params.companyId}/business-plan-model?${query}`, {
        headers: { Accept: "application/json", Authorization: `Bearer ${token}` },
    });
    if (!res.ok) {
        const body = await res.text();
        throw new Error(`Failed to fetch business plan data (HTTP ${res.status}): ${body.slice(0, 400)}`);
    }
    return res.json();
}
/**
 * Trading, position, cash flow and ratios for the management accounts pack.
 * REST only, for the same reason as getAfsModel — it is a presentation payload.
 */
export async function getManagementAccountsModel(params) {
    const query = new URLSearchParams();
    if (params.periodStart)
        query.set("period_start", params.periodStart);
    if (params.periodEnd)
        query.set("period_end", params.periodEnd);
    if (params.ytdStart)
        query.set("ytd_start", params.ytdStart);
    if (params.rounding)
        query.set("rounding", String(params.rounding));
    query.set("compare", params.compare === false ? "0" : "1");
    for (const key of params.sheets ?? [])
        query.append("sheets[]", key);
    const token = getRestBearerToken();
    if (!token)
        throw new Error("Not authenticated. Please run the login tool first.");
    const res = await fetch(`${API_BASE}/companies/${params.companyId}/management-accounts-model?${query}`, {
        headers: { Accept: "application/json", Authorization: `Bearer ${token}` },
    });
    if (!res.ok) {
        const body = await res.text();
        throw new Error(`Failed to fetch management accounts data (HTTP ${res.status}): ${body.slice(0, 400)}`);
    }
    return res.json();
}
// Re-export graphqlRequest for tools that still need it
export { graphqlRequest } from "./graphql.js";
