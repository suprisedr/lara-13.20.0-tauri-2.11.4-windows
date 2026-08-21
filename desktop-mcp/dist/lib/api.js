/**
 * The one definition of the REST base URL.
 *
 * Every tool file used to declare its own `const API_BASE`, and all sixteen of
 * them had drifted to `http://localhost:8000/api` while the app actually
 * serves Laravel on 8080 — Tauri starts FrankenPHP (or `php artisan serve`)
 * bound to 127.0.0.1:8080, and the webview navigates there. Nothing has ever
 * listened on 8000, so every one of those tools failed with a connection
 * refusal that surfaced to the user as the tool simply not working.
 *
 * Import this rather than redeclaring it. The env override exists so a
 * non-standard port can be set once, for the whole server, from outside.
 */
export const API_BASE = process.env.API_BASE ?? "http://localhost:8080/api";
