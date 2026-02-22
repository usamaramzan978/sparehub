#!/usr/bin/env bash

set -euo pipefail

PHP_BIN="${PHP_BIN:-php}"
ARTISAN="${ARTISAN:-artisan}"
DEPLOY_BASE_URL="${DEPLOY_BASE_URL:-${APP_URL:-http://127.0.0.1}}"
DEPLOY_SMOKE_TIMEOUT="${DEPLOY_SMOKE_TIMEOUT:-5}"
DEPLOY_SKIP_HTTP="${DEPLOY_SKIP_HTTP:-0}"

echo "[deploy] Running database migrations..."
"${PHP_BIN}" "${ARTISAN}" migrate --force --no-interaction

echo "[deploy] Clearing and warming caches..."
"${PHP_BIN}" "${ARTISAN}" optimize:clear
"${PHP_BIN}" "${ARTISAN}" config:cache
"${PHP_BIN}" "${ARTISAN}" route:cache
"${PHP_BIN}" "${ARTISAN}" view:cache

echo "[deploy] Restarting queue workers..."
"${PHP_BIN}" "${ARTISAN}" queue:restart

echo "[deploy] Running smoke checks..."
if [[ "${DEPLOY_SKIP_HTTP}" == "1" ]]; then
  "${PHP_BIN}" "${ARTISAN}" app:deploy-smoke-check --skip-http
else
  "${PHP_BIN}" "${ARTISAN}" app:deploy-smoke-check --base-url="${DEPLOY_BASE_URL}" --timeout="${DEPLOY_SMOKE_TIMEOUT}"
fi

echo "[deploy] Deployment automation finished successfully."
