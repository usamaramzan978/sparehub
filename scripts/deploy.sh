#!/usr/bin/env bash

# =============================================================================
# Laravel Full Deployment Script
# =============================================================================
# Usage:
#    chmod +x deploy.sh
#   ./scripts/deploy.sh [options]
#
# Options:
#   --skip-composer      Skip Composer install
#   --skip-npm           Skip NPM install & build
#   --skip-migrations    Skip DB migrations
#   --skip-smoke         Skip smoke checks
#   --env <environment>  Override APP_ENV (default: production)
#
# Environment variables (all optional):
#   PHP_BIN              Path to PHP binary                   (default: php)
#   COMPOSER_BIN         Path to Composer binary              (default: composer)
#   NODE_BIN             Path to Node binary                  (default: node)
#   NPM_BIN              Path to NPM binary                   (default: npm)
#   ARTISAN              Path to Artisan file                 (default: artisan)
#   DEPLOY_BASE_URL      Base URL for smoke checks            (default: $APP_URL or http://127.0.0.1)
#   DEPLOY_SMOKE_TIMEOUT Timeout in seconds for smoke checks  (default: 10)
#   DEPLOY_SKIP_HTTP     Set to "1" to skip HTTP smoke checks (default: 0)
#   APP_ENV              Application environment              (default: production)
# =============================================================================

set -euo pipefail

# ---------------------------------------------------------------------------
# Colour output
# ---------------------------------------------------------------------------

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BOLD='\033[1m'
RESET='\033[0m'

log()     { echo -e "${CYAN}${BOLD}[deploy]${RESET} $*"; }
ok()      { echo -e "${GREEN}${BOLD}[deploy] ✔${RESET} $*"; }
warn()    { echo -e "${YELLOW}${BOLD}[deploy] ⚠${RESET} $*"; }
fail()    { echo -e "${RED}${BOLD}[deploy] ✘ ERROR:${RESET} $*" >&2; exit 1; }
section() {
  echo -e "\n${BOLD}${CYAN}══════════════════════════════════════════${RESET}"
  echo -e "${BOLD}${CYAN}  $*${RESET}"
  echo -e "${BOLD}${CYAN}══════════════════════════════════════════${RESET}"
}

# ---------------------------------------------------------------------------
# Parse arguments
# ---------------------------------------------------------------------------

SKIP_COMPOSER=0
SKIP_NPM=0
SKIP_MIGRATIONS=0
SKIP_SMOKE=0
FORCE_ENV=""

while [[ $# -gt 0 ]]; do
  case "$1" in
    --skip-composer)   SKIP_COMPOSER=1 ;;
    --skip-npm)        SKIP_NPM=1 ;;
    --skip-migrations) SKIP_MIGRATIONS=1 ;;
    --skip-smoke)      SKIP_SMOKE=1 ;;
    --env)             shift; FORCE_ENV="$1" ;;
    *) fail "Unknown argument: $1" ;;
  esac
  shift
done

# ---------------------------------------------------------------------------
# Config
# ---------------------------------------------------------------------------

PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
NODE_BIN="${NODE_BIN:-node}"
NPM_BIN="${NPM_BIN:-npm}"
ARTISAN="${ARTISAN:-artisan}"
DEPLOY_BASE_URL="${DEPLOY_BASE_URL:-${APP_URL:-http://127.0.0.1}}"
DEPLOY_SMOKE_TIMEOUT="${DEPLOY_SMOKE_TIMEOUT:-10}"
DEPLOY_SKIP_HTTP="${DEPLOY_SKIP_HTTP:-0}"
APP_ENV="${FORCE_ENV:-${APP_ENV:-production}}"

DEPLOY_START=$(date +%s)

# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------

artisan() {
  "${PHP_BIN}" "${ARTISAN}" "$@" || fail "Artisan command failed: artisan $*"
}

require_cmd() {
  command -v "$1" &>/dev/null || fail "'$1' not found. Install it or override with the appropriate env var."
}

elapsed() {
  local end; end=$(date +%s)
  echo $(( end - DEPLOY_START ))
}

# ---------------------------------------------------------------------------
# Step 0 — Pre-flight checks
# ---------------------------------------------------------------------------

section "Step 0 — Pre-flight Checks"

log "Timestamp  : $(date -u '+%Y-%m-%d %H:%M:%S UTC')"
log "Directory  : $(pwd)"
log "APP_ENV    : ${APP_ENV}"

[[ -f "${ARTISAN}" ]] || fail "artisan not found in $(pwd). Run this script from your Laravel project root."
[[ -f ".env" ]]       || fail ".env not found. Copy .env.example and configure it first."

require_cmd "${PHP_BIN}"

[[ "${SKIP_COMPOSER}" == "0" ]] && require_cmd "${COMPOSER_BIN}"
[[ "${SKIP_NPM}"      == "0" ]] && require_cmd "${NPM_BIN}"
[[ "${SKIP_NPM}"      == "0" ]] && require_cmd "${NODE_BIN}"

if [[ "${EUID:-$(id -u)}" -eq 0 ]]; then
  warn "Running as root is not recommended in production."
fi

if [[ "${APP_ENV}" != "production" ]]; then
  warn "APP_ENV is '${APP_ENV}', not 'production'. Proceeding anyway."
fi

ok "Pre-flight checks passed."

# ---------------------------------------------------------------------------
# Step 1 — Maintenance mode ON
# ---------------------------------------------------------------------------

section "Step 1 — Maintenance Mode ON"

log "Enabling maintenance mode..."
artisan down \
  --refresh=15 \
  --retry=10 \
  --render="errors::503" 2>/dev/null \
  || artisan down --message="Deploying — back shortly." \
  || warn "Could not enable maintenance mode (app may already be down)."

ok "Maintenance mode enabled."

# Lift maintenance mode automatically if anything fails from here on
trap '
  echo -e "\n${RED}[deploy] Script failed or interrupted — lifting maintenance mode...${RESET}"
  "${PHP_BIN}" "${ARTISAN}" up 2>/dev/null || true
' ERR INT TERM

# ---------------------------------------------------------------------------
# Step 2 — Git pull
# ---------------------------------------------------------------------------

section "Step 2 — Git"

if [[ -d ".git" ]]; then
  require_cmd git
  BRANCH="$(git rev-parse --abbrev-ref HEAD)"
  log "Branch : ${BRANCH}"
  log "Fetching latest code..."
  git fetch --all --prune
  git reset --hard "origin/${BRANCH}"
  ok "Code updated. Commit: $(git log -1 --pretty='%h — %s (%an, %ar)')"
else
  warn "No .git directory — skipping pull. Make sure code is already up to date."
fi

# ---------------------------------------------------------------------------
# Step 3 — Composer
# ---------------------------------------------------------------------------

section "Step 3 — Composer"

if [[ "${SKIP_COMPOSER}" == "1" ]]; then
  warn "Skipping Composer (--skip-composer)."
else
  log "PHP version      : $(${PHP_BIN} -r 'echo PHP_VERSION;')"
  log "Composer version : $(${COMPOSER_BIN} --version --no-ansi 2>&1 | head -1)"

  log "Installing dependencies (no-dev, optimised autoloader)..."
  "${COMPOSER_BIN}" install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader

  ok "Composer dependencies installed."
fi

# ---------------------------------------------------------------------------
# Step 4 — NPM & frontend build
# ---------------------------------------------------------------------------

section "Step 4 — NPM & Frontend Build"

if [[ "${SKIP_NPM}" == "1" ]]; then
  warn "Skipping NPM (--skip-npm)."
elif [[ ! -f "package.json" ]]; then
  warn "No package.json found — skipping NPM step."
else
  log "Node version : $(${NODE_BIN} --version)"
  log "NPM version  : $(${NPM_BIN} --version)"

  log "Running npm ci (clean install)..."
  "${NPM_BIN}" ci --no-audit --no-fund

  ok "NPM dependencies installed."

  # Auto-detect build script: prefer 'build' (Vite), fall back to 'production' (Mix)
  NPM_SCRIPTS="$("${NPM_BIN}" run --list 2>/dev/null || true)"

  if echo "${NPM_SCRIPTS}" | grep -qE '^\s+build'; then
    log "Running npm run build (Vite)..."
    "${NPM_BIN}" run build
  elif echo "${NPM_SCRIPTS}" | grep -qE '^\s+production'; then
    log "Running npm run production (Laravel Mix)..."
    "${NPM_BIN}" run production
  else
    warn "No 'build' or 'production' npm script found — skipping asset compilation."
  fi

  ok "Frontend assets built."
fi

# ---------------------------------------------------------------------------
# Step 5 — Database migrations
# ---------------------------------------------------------------------------

section "Step 5 — Database Migrations"

if [[ "${SKIP_MIGRATIONS}" == "1" ]]; then
  warn "Skipping migrations (--skip-migrations)."
else
  log "Running migrations..."
  artisan migrate --force --no-interaction
  ok "Migrations complete."
fi

# ---------------------------------------------------------------------------
# Step 6 — Storage & permissions
# ---------------------------------------------------------------------------

section "Step 6 — Storage & Permissions"

log "Creating storage symlink..."
artisan storage:link --force 2>/dev/null || warn "storage:link failed (symlink may already exist)."

log "Ensuring required directories exist..."
mkdir -p storage/framework/{sessions,views,cache}
mkdir -p storage/logs
mkdir -p bootstrap/cache

log "Setting permissions on storage & bootstrap/cache..."
chmod -R 775 storage bootstrap/cache

ok "Storage and permissions configured."

# ---------------------------------------------------------------------------
# Step 7 — Clear & warm caches
# ---------------------------------------------------------------------------

section "Step 7 — Caches"

log "Clearing all existing caches..."
artisan optimize:clear

log "Caching config..."
artisan config:cache

log "Caching routes..."
artisan route:cache

log "Caching views..."
artisan view:cache

# event:cache available in Laravel 10+
if "${PHP_BIN}" "${ARTISAN}" list --raw 2>/dev/null | grep -q "^event:cache"; then
  log "Caching events..."
  artisan event:cache
fi

ok "All caches warmed."

# ---------------------------------------------------------------------------
# Step 8 — Queue workers
# ---------------------------------------------------------------------------

section "Step 8 — Queue Workers"

log "Sending queue:restart signal..."
artisan queue:restart
ok "Queue workers will restart gracefully after finishing current jobs."

# ---------------------------------------------------------------------------
# Step 9 — Horizon & Octane (if installed)
# ---------------------------------------------------------------------------

section "Step 9 — Horizon / Octane"

ARTISAN_LIST="$("${PHP_BIN}" "${ARTISAN}" list --raw 2>/dev/null || true)"

if echo "${ARTISAN_LIST}" | grep -q "^horizon"; then
  log "Horizon detected — sending terminate signal..."
  artisan horizon:terminate || warn "horizon:terminate failed (Horizon may not be running)."
  ok "Horizon will restart gracefully."
else
  warn "Horizon not installed — skipping."
fi

if echo "${ARTISAN_LIST}" | grep -q "^octane"; then
  log "Octane detected — reloading workers..."
  artisan octane:reload || warn "octane:reload failed (Octane may not be running)."
  ok "Octane workers reloaded."
else
  warn "Octane not installed — skipping."
fi

# ---------------------------------------------------------------------------
# Step 10 — Maintenance mode OFF
# ---------------------------------------------------------------------------

section "Step 10 — Maintenance Mode OFF"

log "Taking application back online..."
artisan up

ok "Application is live."

# Deployment succeeded — remove the failure trap
trap - ERR INT TERM

# ---------------------------------------------------------------------------
# Step 11 — Smoke checks
# ---------------------------------------------------------------------------

section "Step 11 — Smoke Checks"

if [[ "${SKIP_SMOKE}" == "1" ]]; then
  warn "Skipping smoke checks (--skip-smoke)."
elif ! "${PHP_BIN}" "${ARTISAN}" list --raw 2>/dev/null | grep -q "^app:deploy-smoke-check"; then
  warn "app:deploy-smoke-check Artisan command not found — skipping."
  warn "Tip: php artisan make:command DeploySmokeCheck"
else
  if [[ "${DEPLOY_SKIP_HTTP}" == "1" ]]; then
    artisan app:deploy-smoke-check --skip-http
  else
    artisan app:deploy-smoke-check \
      --base-url="${DEPLOY_BASE_URL}" \
      --timeout="${DEPLOY_SMOKE_TIMEOUT}"
  fi
  ok "Smoke checks passed."
fi

# ---------------------------------------------------------------------------
# Summary
# ---------------------------------------------------------------------------

section "Deployment Complete"

echo -e "${GREEN}${BOLD}"
echo "  ✔ All steps finished successfully!"
echo "  ✔ Duration  : $(elapsed)s"
echo "  ✔ Completed : $(date -u '+%Y-%m-%d %H:%M:%S UTC')"
if [[ -d ".git" ]]; then
  echo "  ✔ Commit    : $(git log -1 --pretty='%h — %s')"
fi
echo -e "${RESET}"
