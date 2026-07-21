# lara-13.20.0-tauri-2.11.4

A Laravel 13 application packaged as a native desktop app with [Tauri](https://tauri.app) 2, using [FrankenPHP](https://frankenphp.dev) as the embedded PHP runtime via the [`mucan54/tauri-php`](https://github.com/mucan54/tauri-php) package.

> **Note:** This repo ships a locally patched copy of `mucan54/tauri-php` at [`packages/mucan54/tauri-php`](packages/mucan54/tauri-php). The published package (v1.2.0) only supports Laravel up to `^12.0` and has a bug that doubles the icon path when resolving `tauri.conf.json`. The local copy widens the `illuminate/support`/`illuminate/console` constraints to `^13.0` and fixes the icon paths. `composer.json` points at it via a `path` repository, so `composer install` will pick it up automatically.

## Prerequisites

Install these before setting up the project:

| Tool | Version | Check |
|---|---|---|
| PHP | ^8.2 | `php -v` |
| Composer | 2.x | `composer -V` |
| Node.js | 18+ | `node -v` |
| npm | 9+ | `npm -v` |
| Rust & Cargo | stable | `cargo -V` |

- **PHP/Composer**: install via [Homebrew](https://brew.sh) (`brew install php composer`) or your OS package manager.
- **Node/npm**: install via [nvm](https://github.com/nvm-sh/nvm) or from [nodejs.org](https://nodejs.org).
- **Rust/Cargo**: Tauri compiles a native Rust binary, so Rust is required even for a PHP-only project. Install via [rustup](https://rustup.rs):
  ```bash
  curl --proto '=https' --tlsv1.2 -sSf https://sh.rustup.rs | sh
  source "$HOME/.cargo/env"
  ```

On macOS you'll also need the Xcode Command Line Tools (`xcode-select --install`) for the Rust/native build step.

## Installation

1. **Clone the repo**
   ```bash
   git clone https://github.com/suprisedr/lara-13.20.0-tauri-2.11.4.git
   cd lara-13.20.0-tauri-2.11.4
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install JS dependencies**
   ```bash
   npm install
   ```

4. **Set up your environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Set up the database** (SQLite by default)
   ```bash
   touch database/database.sqlite
   php artisan migrate
   ```

6. **Build the FrankenPHP sidecar binary**

   The FrankenPHP binary (`src-tauri/binaries/frankenphp-*`) is not committed to this repo — it's a ~180MB compiled artifact that exceeds GitHub's file size limit. Build it locally:
   ```bash
   php artisan tauri:build
   ```
   This compiles a static FrankenPHP binary for your platform and places it in `src-tauri/binaries/`. It only needs to be run once (or whenever you want to update the PHP runtime).

## Running in development

Start the desktop app in dev mode (spins up the Laravel dev server and opens a Tauri window against it):

```bash
php artisan tauri:dev
```

## Building for production

```bash
php artisan tauri:build
```

Packaged installers/binaries are produced under `src-tauri/target/release/bundle/`.

## Useful commands

| Command | Description |
|---|---|
| `php artisan tauri:dev` | Run the app in development mode |
| `php artisan tauri:build` | Build FrankenPHP binaries and produce a release build |
| `php artisan tauri:package` | Create distribution packages |
| `php artisan tauri:clean` | Clean build artifacts and temporary files |
| `php artisan tauri:mobile-init` | Initialize Android/iOS targets |
| `php artisan tauri:mobile-dev` | Run on a mobile device/emulator |

## Project versions

- Laravel `13.20.0`
- Tauri CLI `2.11.4`
- PHP `^8.2`
