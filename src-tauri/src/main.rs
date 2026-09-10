#![cfg_attr(not(debug_assertions), windows_subsystem = "windows")]

use std::fs;
use std::net::TcpStream;
use std::path::PathBuf;
use std::sync::Mutex;
use tauri::Manager;
use tauri::menu::{MenuBuilder, SubmenuBuilder, MenuItemBuilder};
use tauri_plugin_shell::process::CommandChild;
use tauri_plugin_shell::ShellExt;

struct ServiceChild {
    name: String,
    child: CommandChild,
}

struct ProcessChild {
    name: String,
    child: std::process::Child,
}

struct AppState {
    sidecar_services: Mutex<Vec<ServiceChild>>,
    process_services: Mutex<Vec<ProcessChild>>,
}

impl AppState {
    fn new() -> Self {
        Self {
            sidecar_services: Mutex::new(Vec::new()),
            process_services: Mutex::new(Vec::new()),
        }
    }
}

fn is_port_in_use(port: u16) -> bool {
    TcpStream::connect(format!("127.0.0.1:{}", port)).is_ok()
}

fn resolve_project_root(app: &tauri::AppHandle) -> PathBuf {
    if cfg!(debug_assertions) {
        let cwd = std::env::current_dir().unwrap_or_else(|_| PathBuf::from("."));
        if cwd.ends_with("src-tauri") {
            cwd.parent().unwrap_or(&cwd).to_path_buf()
        } else {
            cwd
        }
    } else {
        // Production: use Tauri's resource_dir API.
        // Tauri bundles "resources/laravel" from config, preserving the path,
        // so the actual location is: resource_dir()/resources/laravel/
        let candidates: Vec<PathBuf> = {
            let mut c = Vec::new();
            if let Ok(res_dir) = app.path().resource_dir() {
                c.push(res_dir.join("resources").join("laravel"));
                c.push(res_dir.join("laravel"));
                c.push(res_dir.clone());
            }
            let exe_dir = std::env::current_exe()
                .ok()
                .and_then(|p| p.parent().map(|p| p.to_path_buf()))
                .unwrap_or_else(|| PathBuf::from("."));
            // macOS fallback
            if let Some(contents) = exe_dir.parent() {
                c.push(contents.join("Resources").join("resources").join("laravel"));
                c.push(contents.join("Resources").join("laravel"));
            }
            // Linux fallback
            c.push(exe_dir.join("resources").join("laravel"));
            c
        };

        for candidate in &candidates {
            if candidate.join("artisan").exists() {
                println!("[resolve] Found bundled Laravel at {:?}", candidate);
                return candidate.clone();
            }
        }

        let fallback = candidates.first().cloned().unwrap_or_else(|| PathBuf::from("."));
        println!("[resolve] WARNING: No bundled Laravel found. Tried: {:?}", candidates);
        fallback
    }
}

/// In production the bundled storage dir is read-only.
/// Copy it to a writable location on first run.
fn ensure_writable_storage(project_root: &PathBuf) {
    if cfg!(debug_assertions) {
        return;
    }

    let bundled_storage = project_root.join("storage");
    let writable_root = runtime_home();
    let writable_storage = writable_root.join("storage");

    if !writable_storage.exists() {
        println!("[storage] Initialising writable storage at {:?}", writable_storage);
        if bundled_storage.exists() {
            let _ = copy_dir_recursive(&bundled_storage, &writable_storage);
        } else {
            let _ = fs::create_dir_all(writable_storage.join("app/public"));
            let _ = fs::create_dir_all(writable_storage.join("framework/cache/data"));
            let _ = fs::create_dir_all(writable_storage.join("framework/sessions"));
            let _ = fs::create_dir_all(writable_storage.join("framework/views"));
            let _ = fs::create_dir_all(writable_storage.join("logs"));
        }
    }

    for sub in &[
        "app/public",
        "framework/cache/data",
        "framework/sessions",
        "framework/views",
        "logs",
    ] {
        let _ = fs::create_dir_all(writable_storage.join(sub));
    }

    let _ = fs::create_dir_all(writable_root.join("data"));

    let db_dir = writable_root.join("database");
    let _ = fs::create_dir_all(&db_dir);
    let db_file = db_dir.join("database.sqlite");
    if !db_file.exists() {
        let _ = fs::File::create(&db_file);
    }
}

fn copy_dir_recursive(src: &PathBuf, dst: &PathBuf) -> std::io::Result<()> {
    fs::create_dir_all(dst)?;
    for entry in fs::read_dir(src)? {
        let entry = entry?;
        let ty = entry.file_type()?;
        let dest_path = dst.join(entry.file_name());
        if ty.is_dir() {
            copy_dir_recursive(&entry.path(), &dest_path)?;
        } else {
            fs::copy(entry.path(), &dest_path)?;
        }
    }
    Ok(())
}

/// Runtime data (pids, pgdata, meilisearch db) lives outside the project tree
/// even in dev mode so Tauri's file watcher does not detect changes and
/// restart the app. `~/.chainbook-dev/` in dev, `~/Chainbook/` in production.
fn runtime_home() -> PathBuf {
    let home = std::env::var("HOME").map(PathBuf::from).unwrap_or_else(|_| PathBuf::from("."));
    if cfg!(debug_assertions) {
        home.join(".chainbook-dev")
    } else {
        home.join("Chainbook")
    }
}

fn writable_data_dir() -> PathBuf {
    runtime_home().join("data")
}

fn writable_storage_dir() -> PathBuf {
    if cfg!(debug_assertions) {
        // In dev, Laravel already has its own storage/ in the project tree
        std::env::current_dir().unwrap_or_else(|_| PathBuf::from(".")).join("storage")
    } else {
        runtime_home().join("storage")
    }
}

fn writable_database_path() -> PathBuf {
    if cfg!(debug_assertions) {
        std::env::current_dir().unwrap_or_else(|_| PathBuf::from(".")).join("database").join("database.sqlite")
    } else {
        runtime_home().join("database").join("database.sqlite")
    }
}

fn pid_file_path() -> PathBuf {
    writable_data_dir().join(".chainbook_pids")
}

fn kill_stale_pids() {
    let path = pid_file_path();
    if let Ok(contents) = fs::read_to_string(&path) {
        for line in contents.lines() {
            if let Ok(pid) = line.trim().parse::<u32>() {
                if pid > 0 {
                    #[cfg(unix)]
                    unsafe {
                        libc::kill(-(pid as i32), libc::SIGTERM);
                        libc::kill(pid as i32, libc::SIGTERM);
                    }
                    #[cfg(not(unix))]
                    {
                        let _ = std::process::Command::new("taskkill")
                            .args(["/F", "/T", "/PID", &pid.to_string()])
                            .stdout(std::process::Stdio::null())
                            .stderr(std::process::Stdio::null())
                            .status();
                    }
                }
            }
        }
        println!("[cleanup] Cleaned up stale processes from previous run");
    }
    let _ = fs::remove_file(&path);
}

fn save_pid(pid: u32) {
    let path = pid_file_path();
    if let Some(parent) = path.parent() {
        let _ = fs::create_dir_all(parent);
    }
    let existing = fs::read_to_string(&path).unwrap_or_default();
    let _ = fs::write(&path, format!("{}{}\n", existing, pid));
}

fn clear_pid_file() {
    let _ = fs::remove_file(pid_file_path());
}

#[cfg(unix)]
fn kill_process_tree(child: &mut std::process::Child) {
    let pid = child.id() as i32;
    unsafe {
        libc::kill(-pid, libc::SIGTERM);
    }
    std::thread::sleep(std::time::Duration::from_millis(200));
    let _ = child.kill();
}

#[cfg(not(unix))]
fn kill_process_tree(child: &mut std::process::Child) {
    let _ = child.kill();
}

fn find_optional_binary(name: &str) -> Option<PathBuf> {
    let target_triple = current_target_triple();
    let exe_dir = std::env::current_exe()
        .ok()
        .and_then(|p| p.parent().map(|p| p.to_path_buf()))
        .unwrap_or_else(|| PathBuf::from("."));

    // Candidates ordered by likelihood:
    //   1. Next to the executable (production / Finder launch)
    //   2. Dev mode: src-tauri/binaries/ relative to CWD
    let mut candidates = Vec::new();
    // Production: next to exe, plain name (Tauri strips triple for externalBin)
    candidates.push(exe_dir.join(name));
    candidates.push(exe_dir.join(format!("{}-{}", name, target_triple)));
    // Dev: bundled binaries live at <project_root>/src-tauri/binaries/.
    // CWD may be either the project root or src-tauri/ depending on how the
    // Rust binary was launched, so try both layouts.
    if let Ok(cwd) = std::env::current_dir() {
        // CWD == project root
        candidates.push(cwd.join("src-tauri").join("binaries").join(format!("{}-{}", name, target_triple)));
        candidates.push(cwd.join("src-tauri").join("binaries").join(name));
        // CWD == src-tauri
        candidates.push(cwd.join("binaries").join(format!("{}-{}", name, target_triple)));
        candidates.push(cwd.join("binaries").join(name));
        // CWD == somewhere under src-tauri (e.g. target/debug/) — walk up looking for binaries/
        if let Some(parent) = cwd.parent() {
            candidates.push(parent.join("binaries").join(format!("{}-{}", name, target_triple)));
            if let Some(gp) = parent.parent() {
                candidates.push(gp.join("binaries").join(format!("{}-{}", name, target_triple)));
            }
        }
    }

    for candidate in &candidates {
        if candidate.exists() && candidate.metadata().map(|m| m.len() > 0).unwrap_or(false) {
            println!("[resolve] Found {} at {:?}", name, candidate);
            return Some(candidate.clone());
        }
    }
    eprintln!("[resolve] Could not find binary '{}'. Searched:", name);
    for c in &candidates {
        eprintln!("[resolve]   - {:?}", c);
    }
    None
}

fn current_target_triple() -> &'static str {
    match (std::env::consts::ARCH, std::env::consts::OS) {
        ("x86_64", "macos") => "x86_64-apple-darwin",
        ("aarch64", "macos") => "aarch64-apple-darwin",
        ("x86_64", "linux") => "x86_64-unknown-linux-gnu",
        ("aarch64", "linux") => "aarch64-unknown-linux-gnu",
        ("x86_64", "windows") => "x86_64-pc-windows-msvc",
        _ => "x86_64-apple-darwin",
    }
}

/// Build environment variables for Laravel subprocesses in production.
fn laravel_env(project_root: &PathBuf) -> Vec<(String, String)> {
    let mut env = Vec::new();
    if !cfg!(debug_assertions) {
        env.push(("APP_STORAGE_PATH".to_string(), writable_storage_dir().to_string_lossy().to_string()));
        env.push(("DB_DATABASE".to_string(), writable_database_path().to_string_lossy().to_string()));
        env.push(("LOG_CHANNEL".to_string(), "single".to_string()));
    }
    let _ = project_root;
    env
}

#[cfg(unix)]
fn spawn_in_process_group(cmd: &mut std::process::Command) -> std::io::Result<std::process::Child> {
    use std::os::unix::process::CommandExt;
    unsafe {
        cmd.pre_exec(|| {
            libc::setpgid(0, 0);
            Ok(())
        });
    }
    cmd.spawn()
}

#[cfg(not(unix))]
fn spawn_in_process_group(cmd: &mut std::process::Command) -> std::io::Result<std::process::Child> {
    cmd.spawn()
}

fn start_optional_service(
    name: &str,
    binary_name: &str,
    args: &[&str],
    working_dir: &PathBuf,
) -> Option<std::process::Child> {
    let binary_path = find_optional_binary(binary_name)?;

    println!("[startup] Starting {} (cwd: {:?})...", name, working_dir);
    let mut cmd = std::process::Command::new(&binary_path);
    cmd.args(args)
        .current_dir(working_dir)
        .stdout(std::process::Stdio::null())
        .stderr(std::process::Stdio::inherit());

    match spawn_in_process_group(&mut cmd) {
        Ok(child) => {
            println!("[startup] {} started (pid {})", name, child.id());
            save_pid(child.id());
            Some(child)
        }
        Err(e) => {
            eprintln!("[startup] {} failed (non-critical): {}", name, e);
            None
        }
    }
}

/// Find the FrankenPHP binary — checks both with and without target triple.
fn find_frankenphp_binary() -> Option<PathBuf> {
    let exe_dir = std::env::current_exe()
        .ok()
        .and_then(|p| p.parent().map(|p| p.to_path_buf()))
        .unwrap_or_else(|| PathBuf::from("."));

    // Tauri strips the triple when bundling, so check plain name first
    let plain = exe_dir.join("frankenphp");
    if plain.exists() { return Some(plain); }

    let with_triple = exe_dir.join(format!("frankenphp-{}", current_target_triple()));
    if with_triple.exists() { return Some(with_triple); }

    None
}

/// Spawn a PHP artisan command using the correct PHP runtime.
fn spawn_artisan(
    project_root: &PathBuf,
    artisan_args: &[&str],
    label: &str,
) -> Option<std::process::Child> {
    let env_vars = laravel_env(project_root);

    if cfg!(debug_assertions) {
        // Dev: use system php
        println!("[startup] Starting {} via system php...", label);
        let mut cmd = std::process::Command::new("php");
        cmd.arg("artisan");
        cmd.args(artisan_args);
        cmd.current_dir(project_root);
        cmd.stdout(std::process::Stdio::null());
        cmd.stderr(std::process::Stdio::null());
        for (k, v) in &env_vars {
            cmd.env(k, v);
        }
        match spawn_in_process_group(&mut cmd) {
            Ok(child) => {
                println!("[startup] {} started (pid {})", label, child.id());
                save_pid(child.id());
                Some(child)
            }
            Err(e) => {
                eprintln!("[startup] {} failed (non-critical): {}", label, e);
                None
            }
        }
    } else {
        // Production: use the FrankenPHP sidecar for php-cli
        let php_bin = match find_frankenphp_binary() {
            Some(bin) => bin,
            None => {
                eprintln!("[startup] {} skipped: no FrankenPHP binary found for php-cli", label);
                return None;
            }
        };

        println!("[startup] Starting {} via FrankenPHP php-cli...", label);
        let mut cmd = std::process::Command::new(&php_bin);
        cmd.arg("php-cli");
        cmd.arg("artisan");
        cmd.args(artisan_args);
        cmd.current_dir(project_root);
        cmd.stdout(std::process::Stdio::null());
        cmd.stderr(std::process::Stdio::null());
        for (k, v) in &env_vars {
            cmd.env(k, v);
        }
        match spawn_in_process_group(&mut cmd) {
            Ok(child) => {
                println!("[startup] {} started (pid {})", label, child.id());
                save_pid(child.id());
                Some(child)
            }
            Err(e) => {
                eprintln!("[startup] {} failed (non-critical): {}", label, e);
                None
            }
        }
    }
}

fn resolve_mcp_dir(app: &tauri::AppHandle) -> PathBuf {
    if cfg!(debug_assertions) {
        let cwd = std::env::current_dir().unwrap_or_else(|_| PathBuf::from("."));
        let root = if cwd.ends_with("src-tauri") {
            cwd.parent().unwrap_or(&cwd).to_path_buf()
        } else {
            cwd
        };
        root.join("desktop-mcp")
    } else {
        let mut candidates = Vec::new();
        if let Ok(res_dir) = app.path().resource_dir() {
            candidates.push(res_dir.join("resources").join("desktop-mcp"));
            candidates.push(res_dir.join("desktop-mcp"));
        }
        let exe_dir = std::env::current_exe()
            .ok()
            .and_then(|p| p.parent().map(|p| p.to_path_buf()))
            .unwrap_or_else(|| PathBuf::from("."));
        if let Some(contents) = exe_dir.parent() {
            candidates.push(contents.join("Resources").join("resources").join("desktop-mcp"));
        }
        for candidate in &candidates {
            if candidate.join("dist").join("index.js").exists() {
                println!("[resolve] Found bundled MCP at {:?}", candidate);
                return candidate.clone();
            }
        }
        println!("[resolve] WARNING: No bundled MCP found. Tried: {:?}", candidates);
        candidates.first().cloned().unwrap_or_else(|| PathBuf::from("."))
    }
}

fn register_mcp_with_claude(mcp_entry: &PathBuf) {
    let home = std::env::var("HOME").unwrap_or_default();
    if home.is_empty() { return; }

    let config_path = PathBuf::from(&home)
        .join("Library")
        .join("Application Support")
        .join("Claude")
        .join("claude_desktop_config.json");

    let entry_str = mcp_entry.to_string_lossy().to_string();

    let mut config: serde_json::Value = if config_path.exists() {
        fs::read_to_string(&config_path)
            .ok()
            .and_then(|s| serde_json::from_str(&s).ok())
            .unwrap_or_else(|| serde_json::json!({}))
    } else {
        serde_json::json!({})
    };

    let servers = config
        .as_object_mut()
        .unwrap()
        .entry("mcpServers")
        .or_insert_with(|| serde_json::json!({}));

    let current = servers.get("chainbook-desktop-mcp");
    let needs_update = match current {
        Some(v) => v.get("args")
            .and_then(|a| a.as_array())
            .and_then(|a| a.first())
            .and_then(|a| a.as_str())
            != Some(&entry_str),
        None => true,
    };

    if needs_update {
        servers.as_object_mut().unwrap().insert(
            "chainbook-desktop-mcp".to_string(),
            serde_json::json!({
                "command": "node",
                "args": [entry_str]
            }),
        );
        if let Some(parent) = config_path.parent() {
            let _ = fs::create_dir_all(parent);
        }
        match fs::write(&config_path, serde_json::to_string_pretty(&config).unwrap()) {
            Ok(_) => println!("[mcp] Registered chainbook-desktop-mcp with Claude at {:?}", config_path),
            Err(e) => eprintln!("[mcp] Failed to update Claude config: {}", e),
        }
    } else {
        println!("[mcp] Claude MCP config already up to date");
    }
}

async fn start_all_services(app: tauri::AppHandle) -> Result<(), String> {
    let state = app.state::<AppState>();
    let project_root = resolve_project_root(&app);

    println!("[startup] Project root: {:?}", project_root);
    println!("[startup] Debug mode: {}", cfg!(debug_assertions));
    println!("[startup] public/ exists: {}", project_root.join("public").exists());
    println!("[startup] artisan exists: {}", project_root.join("artisan").exists());

    ensure_writable_storage(&project_root);

    // 1. Start PostgreSQL
    if !is_port_in_use(5432) {
        let data_dir = writable_data_dir();
        let pg_data = data_dir.join("pgdata");
        let pg_data_str = pg_data.to_string_lossy().to_string();

        if !pg_data.exists() {
            println!("[startup] Initializing PostgreSQL data directory...");
            let _ = std::process::Command::new("initdb")
                .args(&["-D", &pg_data_str])
                .output();
        }

        let pg_binary = find_optional_binary("postgres")
            .unwrap_or_else(|| PathBuf::from("postgres"));

        println!("[startup] Starting PostgreSQL...");
        let mut cmd = std::process::Command::new(&pg_binary);
        cmd.args(&["-D", &pg_data_str, "-k", "/tmp", "-p", "5432"])
            .stdout(std::process::Stdio::piped())
            .stderr(std::process::Stdio::piped());

        match spawn_in_process_group(&mut cmd) {
            Ok(child) => {
                println!("[startup] PostgreSQL started (pid {})", child.id());
                save_pid(child.id());
                state.process_services.lock().unwrap().push(ProcessChild {
                    name: "PostgreSQL".to_string(),
                    child,
                });
            }
            Err(e) => {
                eprintln!("[startup] PostgreSQL not available (non-critical): {}", e);
            }
        }
    } else {
        println!("[startup] PostgreSQL already running on port 5432");
    }

    // 2. Start Meilisearch
    if !is_port_in_use(7700) {
        let ms_data = writable_data_dir().join("meilisearch");
        let ms_data_str = ms_data.to_string_lossy().to_string();
        if let Some(child) = start_optional_service(
            "Meilisearch",
            "meilisearch",
            &["--http-addr", "127.0.0.1:7700", "--db-path", &ms_data_str, "--env", "development", "--no-analytics"],
            &project_root,
        ) {
            state.process_services.lock().unwrap().push(ProcessChild {
                name: "Meilisearch".to_string(),
                child,
            });
        }
    } else {
        println!("[startup] Meilisearch already running on port 7700");
    }

    tokio::time::sleep(tokio::time::Duration::from_secs(1)).await;

    // 3. Start FrankenPHP web server
    // In dev mode, beforeDevCommand runs `php artisan serve`, so port 8080
    // should already be occupied — we skip. In production, we start FrankenPHP.
    if is_port_in_use(8080) {
        println!("[startup] Port 8080 already in use, skipping FrankenPHP sidecar");
    } else {
        println!("[startup] Starting FrankenPHP sidecar...");
        let public_dir = project_root.join("public");
        let root_arg = public_dir.to_string_lossy().to_string();
        println!("[startup] FrankenPHP --root={}", root_arg);

        let env_vars = laravel_env(&project_root);

        match app
            .shell()
            .sidecar("frankenphp")
            .and_then(|cmd| {
                Ok(cmd
                    .args(&["php-server", "--listen", "127.0.0.1:8080", "--root", &root_arg])
                    .envs(env_vars))
            }) {
            Ok(cmd) => {
                match cmd.spawn() {
                    Ok((mut rx, child)) => {
                        state.sidecar_services.lock().unwrap().push(ServiceChild {
                            name: "frankenphp".to_string(),
                            child,
                        });

                        tauri::async_runtime::spawn(async move {
                            while let Some(event) = rx.recv().await {
                                match event {
                                    tauri_plugin_shell::process::CommandEvent::Stdout(line) => {
                                        println!("[frankenphp] {}", String::from_utf8_lossy(&line));
                                    }
                                    tauri_plugin_shell::process::CommandEvent::Stderr(line) => {
                                        eprintln!("[frankenphp] {}", String::from_utf8_lossy(&line));
                                    }
                                    tauri_plugin_shell::process::CommandEvent::Terminated(status) => {
                                        println!("[frankenphp] terminated with {:?}", status);
                                        break;
                                    }
                                    _ => {}
                                }
                            }
                        });

                        println!("[startup] FrankenPHP sidecar spawned, waiting for it to be ready...");

                        // Wait up to 15 seconds for FrankenPHP to bind the port
                        for i in 0..30 {
                            if is_port_in_use(8080) {
                                println!("[startup] FrankenPHP is ready on port 8080 (took ~{}ms)", i * 500);
                                break;
                            }
                            tokio::time::sleep(tokio::time::Duration::from_millis(500)).await;
                        }
                        if !is_port_in_use(8080) {
                            eprintln!("[startup] WARNING: FrankenPHP did not bind to port 8080 within 15s");
                        }
                    }
                    Err(e) => {
                        eprintln!("[startup] FrankenPHP sidecar spawn failed (non-critical): {}", e);
                    }
                }
            }
            Err(e) => {
                eprintln!("[startup] FrankenPHP sidecar setup failed (non-critical): {}", e);
            }
        }
    }

    // 4. Run migrations in production on first launch
    if !cfg!(debug_assertions) {
        println!("[startup] Running database migrations...");
        let frankenphp = find_frankenphp_binary();

        if let Some(ref frankenphp) = frankenphp {
            let env_vars = laravel_env(&project_root);
            let mut cmd = std::process::Command::new(&frankenphp);
            cmd.args(&["php-cli", "artisan", "migrate", "--force"])
                .current_dir(&project_root);
            for (k, v) in &env_vars {
                cmd.env(k, v);
            }
            match cmd.output() {
                Ok(output) => {
                    let stdout = String::from_utf8_lossy(&output.stdout);
                    let stderr = String::from_utf8_lossy(&output.stderr);
                    if !stdout.is_empty() { println!("[migrate] {}", stdout.trim()); }
                    if !stderr.is_empty() { eprintln!("[migrate] {}", stderr.trim()); }
                }
                Err(e) => eprintln!("[migrate] Failed (non-critical): {}", e),
            }
        } else {
            eprintln!("[migrate] Skipped — FrankenPHP binary not found");
        }
    }

    // 5. Start Queue Worker
    if let Some(child) = spawn_artisan(
        &project_root,
        &[
            "queue:work",
            "--queue=asset-postings,intangible-postings,inventory-postings,lease-postings,embeddings,default",
            "--timeout=60",
        ],
        "Queue Worker",
    ) {
        state.process_services.lock().unwrap().push(ProcessChild {
            name: "Queue Worker".to_string(),
            child,
        });
    }

    // 6. Start Reverb
    if !is_port_in_use(8081) {
        if let Some(child) = spawn_artisan(&project_root, &["reverb:start"], "Reverb") {
            state.process_services.lock().unwrap().push(ProcessChild {
                name: "Reverb".to_string(),
                child,
            });
        }
    } else {
        println!("[startup] Reverb already running on port 8081");
    }

    // 7. Start Temporal
    if !is_port_in_use(7233) {
        if let Some(child) = start_optional_service(
            "Temporal",
            "temporal",
            &["server", "start-dev", "--port", "7233", "--ui-port", "8233"],
            &project_root,
        ) {
            state.process_services.lock().unwrap().push(ProcessChild {
                name: "Temporal".to_string(),
                child,
            });
        }
    } else {
        println!("[startup] Temporal already running on port 7233");
    }

    // 8. Start RoadRunner (depends on Temporal — wait for port 7233 first)
    if !is_port_in_use(9001) {
        // RoadRunner's Temporal plugin will crash the whole process if Temporal
        // isn't reachable. Wait up to 15s for port 7233 before spawning it.
        if is_port_in_use(7233) {
            println!("[startup] Temporal already up, starting RoadRunner immediately");
        } else {
            println!("[startup] Waiting for Temporal to bind port 7233 before starting RoadRunner...");
            for i in 0..30 {
                if is_port_in_use(7233) {
                    println!("[startup] Temporal ready (took ~{}ms)", i * 500);
                    break;
                }
                tokio::time::sleep(tokio::time::Duration::from_millis(500)).await;
            }
        }

        if !is_port_in_use(7233) {
            eprintln!("[startup] WARNING: Temporal never came up — RoadRunner will crash. Skipping.");
        } else if let Some(child) = start_optional_service("RoadRunner", "rr", &["serve", "-c", ".rr.yaml"], &project_root) {
            state.process_services.lock().unwrap().push(ProcessChild {
                name: "RoadRunner".to_string(),
                child,
            });
        }
    } else {
        println!("[startup] RoadRunner already running on port 9001");
    }

    // 9. Start Desktop MCP server
    {
        let mcp_dir = resolve_mcp_dir(&app);
        let mcp_entry = mcp_dir.join("dist").join("index.js");
        if mcp_entry.exists() {
            println!("[startup] Starting Desktop MCP server...");
            register_mcp_with_claude(&mcp_entry);
            let mut cmd = std::process::Command::new("node");
            cmd.arg(&mcp_entry)
                .current_dir(&mcp_dir)
                .stdout(std::process::Stdio::piped())
                .stderr(std::process::Stdio::piped());

            match spawn_in_process_group(&mut cmd) {
                Ok(child) => {
                    println!("[startup] Desktop MCP started (pid {})", child.id());
                    save_pid(child.id());
                    state.process_services.lock().unwrap().push(ProcessChild {
                        name: "Desktop MCP".to_string(),
                        child,
                    });
                }
                Err(e) => {
                    eprintln!("[startup] Desktop MCP failed (non-critical): {}", e);
                }
            }
        } else {
            println!("[startup] Desktop MCP not found at {:?}, skipping", mcp_entry);
        }
    }

    // Navigate the webview to the running server once it's ready.
    // The static loading page can't poll via XHR due to CORS (tauri:// → http://).
    if is_port_in_use(8080) {
        if let Some(window) = app.get_webview_window("main") {
            println!("[startup] Navigating webview to http://127.0.0.1:8080");
            let _ = window.navigate("http://127.0.0.1:8080".parse().unwrap());
        }
    }

    println!("[startup] All services initialized");
    Ok(())
}

#[tauri::command]
fn save_pdf(bytes: Vec<u8>, filename: String) -> Result<String, String> {
    let downloads = std::env::var("HOME")
        .map(|h| PathBuf::from(h).join("Downloads"))
        .unwrap_or_else(|_| PathBuf::from("."));
    let _ = fs::create_dir_all(&downloads);

    let dest = downloads.join(&filename);

    // Avoid overwriting: append (1), (2), etc.
    let final_path = if dest.exists() {
        let stem = dest.file_stem().unwrap_or_default().to_string_lossy().to_string();
        let ext = dest.extension().unwrap_or_default().to_string_lossy().to_string();
        let mut n = 1u32;
        loop {
            let candidate = downloads.join(format!("{} ({}).{}", stem, n, ext));
            if !candidate.exists() {
                break candidate;
            }
            n += 1;
        }
    } else {
        dest
    };

    fs::write(&final_path, &bytes)
        .map_err(|e| format!("Failed to write: {}", e))?;

    let path_str = final_path.to_string_lossy().to_string();

    // Open in Preview / reveal in Finder
    let _ = std::process::Command::new("open").arg(&final_path).spawn();

    Ok(path_str)
}

#[tauri::command]
fn get_service_status(state: tauri::State<'_, AppState>) -> Vec<String> {
    let mut names: Vec<String> = Vec::new();
    names.extend(
        state
            .sidecar_services
            .lock()
            .unwrap()
            .iter()
            .map(|s| s.name.clone()),
    );
    names.extend(
        state
            .process_services
            .lock()
            .unwrap()
            .iter()
            .map(|s| s.name.clone()),
    );
    names
}

fn shutdown_all_services(state: &AppState) {
    let mut sidecars = state.sidecar_services.lock().unwrap();
    while let Some(service) = sidecars.pop() {
        println!("[shutdown] Stopping {} (sidecar)...", service.name);
        let _ = service.child.kill();
    }

    let mut processes = state.process_services.lock().unwrap();
    while let Some(mut service) = processes.pop() {
        println!(
            "[shutdown] Stopping {} (process, pid {})...",
            service.name,
            service.child.id()
        );
        kill_process_tree(&mut service.child);
    }

    clear_pid_file();
    println!("[shutdown] All services stopped");
}

fn main() {
    eprintln!("[main] entered main()");
    kill_stale_pids();
    eprintln!("[main] kill_stale_pids done, building app");

    tauri::Builder::default()
        .plugin(tauri_plugin_shell::init())
        .plugin(tauri_plugin_dialog::init())
        .plugin(tauri_plugin_opener::init())
        .manage(AppState::new())
        .invoke_handler(tauri::generate_handler![get_service_status, save_pdf])
        .setup(|app| {
            eprintln!("[setup] entered setup closure");
            // ── Native menu bar ──
            let add_company = MenuItemBuilder::with_id("add_company", "Add Company")
                .accelerator("CmdOrCtrl+N")
                .build(app)?;
            let settings = MenuItemBuilder::with_id("settings", "Settings")
                .accelerator("CmdOrCtrl+,")
                .build(app)?;
            let troubleshooting = MenuItemBuilder::with_id("troubleshooting", "Troubleshooting")
                .build(app)?;

            let menu = MenuBuilder::new(app)
                .item(
                    &SubmenuBuilder::new(app, "File")
                        .item(&add_company)
                        .separator()
                        .item(&settings)
                        .build()?,
                )
                .item(
                    &SubmenuBuilder::new(app, "Help")
                        .item(&troubleshooting)
                        .build()?,
                )
                .build()?;

            eprintln!("[setup] menu built, setting menu");
            app.set_menu(menu)?;
            eprintln!("[setup] menu set");

            app.on_menu_event(move |app_handle, event| {
                let id = event.id().as_ref();
                if let Some(window) = app_handle.get_webview_window("main") {
                    match id {
                        "add_company" => {
                            let _ = window.eval("window.location.href = '/onboarding/step/1';");
                        }
                        "settings" => {
                            let _ = window.eval("window.location.href = '/settings';");
                        }
                        "troubleshooting" => {
                            let _ = window.eval("window.location.href = '/troubleshooting';");
                        }
                        _ => {}
                    }
                }
            });

            eprintln!("[setup] menu event handler registered");

            let app_handle = app.handle().clone();
            tauri::async_runtime::spawn(async move {
                tokio::time::sleep(tokio::time::Duration::from_millis(500)).await;
                if let Err(e) = start_all_services(app_handle).await {
                    eprintln!("[startup] Fatal error: {}", e);
                }
            });
            eprintln!("[setup] returning Ok from setup closure");
            Ok(())
        })
        .on_window_event(|window, event| {
            if let tauri::WindowEvent::Destroyed = event {
                let state = window.state::<AppState>();
                shutdown_all_services(state.inner());
            }
        })
        .run(tauri::generate_context!())
        .expect("error while running tauri application");
}
