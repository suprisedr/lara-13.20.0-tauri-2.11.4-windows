#![cfg_attr(not(debug_assertions), windows_subsystem = "windows")]

use std::fs;
use std::net::TcpStream;
use std::path::PathBuf;
use std::sync::Mutex;
use tauri::Manager;
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

fn resolve_project_root() -> PathBuf {
    let exe_dir = std::env::current_exe()
        .ok()
        .and_then(|p| p.parent().map(|p| p.to_path_buf()));

    if cfg!(debug_assertions) {
        let cwd = std::env::current_dir().unwrap_or_else(|_| PathBuf::from("."));
        // Tauri sets CWD to src-tauri/ during dev — go up one level to the Laravel project root
        if cwd.ends_with("src-tauri") {
            cwd.parent().unwrap_or(&cwd).to_path_buf()
        } else {
            cwd
        }
    } else {
        // Production: the exe is inside the app bundle, project files are bundled alongside
        exe_dir.unwrap_or_else(|| PathBuf::from("."))
    }
}

fn pid_file_path() -> PathBuf {
    resolve_project_root().join("data").join(".chainbook_pids")
}

fn kill_stale_pids() {
    let path = pid_file_path();
    if let Ok(contents) = fs::read_to_string(&path) {
        for line in contents.lines() {
            if let Ok(pid) = line.trim().parse::<i32>() {
                if pid > 0 {
                    unsafe {
                        // Kill the process group (negative pid) to catch sub-processes
                        libc::kill(-pid, libc::SIGTERM);
                        libc::kill(pid, libc::SIGTERM);
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
    let project_root = resolve_project_root();
    let binaries_dir = project_root.join("src-tauri").join("binaries");

    let target_triple = match (std::env::consts::ARCH, std::env::consts::OS) {
        ("x86_64", "macos") => "x86_64-apple-darwin",
        ("aarch64", "macos") => "aarch64-apple-darwin",
        ("x86_64", "linux") => "x86_64-unknown-linux-gnu",
        ("aarch64", "linux") => "aarch64-unknown-linux-gnu",
        ("x86_64", "windows") => "x86_64-pc-windows-msvc",
        _ => return None,
    };

    let binary_path = binaries_dir.join(format!("{}-{}", name, target_triple));
    if binary_path.exists() && binary_path.metadata().map(|m| m.len() > 0).unwrap_or(false) {
        Some(binary_path)
    } else {
        None
    }
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
) -> Option<std::process::Child> {
    let binary_path = find_optional_binary(binary_name)?;
    let project_root = resolve_project_root();

    println!("[startup] Starting {}...", name);
    let mut cmd = std::process::Command::new(&binary_path);
    cmd.args(args)
        .current_dir(&project_root)
        .stdout(std::process::Stdio::piped())
        .stderr(std::process::Stdio::piped());

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

async fn start_all_services(app: tauri::AppHandle) -> Result<(), String> {
    let state = app.state::<AppState>();
    let project_root = resolve_project_root();

    // 1. Start PostgreSQL — use system install if bundled binary not found
    if !is_port_in_use(5432) {
        let pg_data = project_root.join("data").join("pgdata");
        let pg_data_str = pg_data.to_string_lossy().to_string();

        // Initialize data directory if it doesn't exist
        if !pg_data.exists() {
            println!("[startup] Initializing PostgreSQL data directory...");
            let _ = std::process::Command::new("initdb")
                .args(&["-D", &pg_data_str])
                .current_dir(&project_root)
                .output();
        }

        let pg_binary = find_optional_binary("postgres")
            .unwrap_or_else(|| PathBuf::from("postgres"));

        println!("[startup] Starting PostgreSQL...");
        let mut cmd = std::process::Command::new(&pg_binary);
        cmd.args(&["-D", &pg_data_str, "-k", "/tmp", "-p", "5432"])
            .current_dir(&project_root)
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

    // Start Meilisearch
    if !is_port_in_use(7700) {
    if let Some(child) = start_optional_service("Meilisearch", "meilisearch", &["--http-addr", "127.0.0.1:7700", "--db-path", "data/meilisearch", "--env", "development", "--no-analytics"]) {
        state.process_services.lock().unwrap().push(ProcessChild {
            name: "Meilisearch".to_string(),
            child,
        });
    }
    } else {
        println!("[startup] Meilisearch already running on port 7700");
    }

    tokio::time::sleep(tokio::time::Duration::from_secs(1)).await;

    // 2. Start FrankenPHP — only if port 8080 is not already in use
    //    (in dev mode, `php artisan serve` already handles the web server)
    if is_port_in_use(8080) {
        println!("[startup] Port 8080 already in use (dev server running), skipping FrankenPHP sidecar");
    } else {
        println!("[startup] Starting FrankenPHP...");
        let public_dir = project_root.join("public");
        let root_arg = public_dir.to_string_lossy().to_string();

        let (mut rx, child) = app
            .shell()
            .sidecar("frankenphp")
            .map_err(|e| format!("Failed to create FrankenPHP sidecar: {}", e))?
            .args(&["php-server", "--listen", "127.0.0.1:8080", "--root", &root_arg])
            .spawn()
            .map_err(|e| format!("Failed to start FrankenPHP: {}", e))?;

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

        println!("[startup] FrankenPHP started on 127.0.0.1:8080");
        tokio::time::sleep(tokio::time::Duration::from_secs(2)).await;
    }

    // 3. Start PHP-based services (queue worker, Reverb) via artisan
    //    In dev mode these are handled by `composer dev`, so skip them
    if !is_port_in_use(8081) {
        let php_services: Vec<(&str, Vec<&str>)> = vec![
            ("Queue Worker", vec!["artisan", "queue:work", "--queue=asset-postings,intangible-postings,inventory-postings,lease-postings,embeddings,default", "--tries=3", "--timeout=60"]),
            ("Reverb", vec!["artisan", "reverb:start"]),
        ];

        for (name, args) in &php_services {
            println!("[startup] Starting {}...", name);
            let mut cmd = std::process::Command::new("php");
            cmd.args(args)
                .current_dir(&project_root)
                .stdout(std::process::Stdio::piped())
                .stderr(std::process::Stdio::piped());

            match spawn_in_process_group(&mut cmd) {
                Ok(child) => {
                    println!("[startup] {} started (pid {})", name, child.id());
                    save_pid(child.id());
                    state.process_services.lock().unwrap().push(ProcessChild {
                        name: name.to_string(),
                        child,
                    });
                }
                Err(e) => {
                    eprintln!("[startup] {} failed (non-critical): {}", name, e);
                }
            }
        }
    } else {
        println!("[startup] Port 8081 in use (Reverb already running), skipping PHP services");
    }

    // 4. Start deferred optional services (Temporal, RoadRunner)
    let deferred_services: Vec<(&str, &str, Vec<&str>)> = vec![
        ("Temporal", "temporal", vec!["server", "start-dev", "--port", "7233", "--ui-port", "8233"]),
        ("RoadRunner", "rr", vec!["serve", "-c", ".rr.yaml"]),
    ];

    for (name, binary, args) in &deferred_services {
        if let Some(child) = start_optional_service(name, binary, &args) {
            state.process_services.lock().unwrap().push(ProcessChild {
                name: name.to_string(),
                child,
            });
        }
    }

    // 5. Start the desktop MCP server
    {
        let mcp_dir = project_root.join("desktop-mcp");
        let mcp_entry = mcp_dir.join("dist").join("index.js");
        if mcp_entry.exists() {
            println!("[startup] Starting Desktop MCP server...");
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

    println!("[startup] All services initialized");
    Ok(())
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
        println!("[shutdown] Stopping {} (process, pid {})...", service.name, service.child.id());
        kill_process_tree(&mut service.child);
    }

    clear_pid_file();
    println!("[shutdown] All services stopped");
}

fn main() {
    kill_stale_pids();

    tauri::Builder::default()
        .plugin(tauri_plugin_shell::init())
        .plugin(tauri_plugin_dialog::init())
        .plugin(tauri_plugin_opener::init())
        .manage(AppState::new())
        .invoke_handler(tauri::generate_handler![get_service_status])
        .setup(|app| {
            let _window = tauri::WebviewWindowBuilder::new(
                app,
                "main",
                tauri::WebviewUrl::default(),
            )
            .title("Chainbook")
            .inner_size(1200.0, 800.0)
            .resizable(true)
            .on_download(|_webview, event| {
                match event {
                    tauri::webview::DownloadEvent::Requested {
                        url,
                        destination,
                    } => {
                        let filename = destination
                            .file_name()
                            .map(|f| f.to_os_string())
                            .filter(|f| !f.is_empty())
                            .unwrap_or_else(|| {
                                let path = url.path();
                                let name = path.rsplit('/').next().unwrap_or("download.pdf");
                                if name.is_empty() || !name.contains('.') {
                                    "download.pdf".into()
                                } else {
                                    name.into()
                                }
                            });

                        let downloads = std::env::var("HOME")
                            .map(|h| PathBuf::from(h).join("Downloads"))
                            .unwrap_or_else(|_| PathBuf::from("."));
                        let _ = fs::create_dir_all(&downloads);

                        *destination = downloads.join(&filename);
                        println!("[download] {} -> {:?}", url.as_str(), destination);
                        true
                    }
                    tauri::webview::DownloadEvent::Finished {
                        url: _,
                        path,
                        success,
                    } => {
                        if success {
                            if let Some(p) = path {
                                println!("[download] Complete: {:?}", p);
                                let _ = std::process::Command::new("open")
                                    .arg(&p)
                                    .spawn();
                            }
                        } else {
                            eprintln!("[download] Download failed");
                        }
                        true
                    }
                    _ => true,
                }
            })
            .build()?;

            let app_handle = app.handle().clone();
            tauri::async_runtime::spawn(async move {
                tokio::time::sleep(tokio::time::Duration::from_millis(500)).await;
                if let Err(e) = start_all_services(app_handle).await {
                    eprintln!("[startup] Fatal error: {}", e);
                }
            });
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
