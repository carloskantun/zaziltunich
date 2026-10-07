#!/usr/bin/env bash
# Sincroniza el subdominio de revisión con main cuando exista un commit nuevo.
set -euo pipefail
cd "$(dirname "$0")/.."

log_file=storage/logs/deploy.log
if ! git fetch -q origin main 2>>"$log_file"; then
  exit 1
fi
if [[ "$(git rev-parse HEAD)" == "$(git rev-parse origin/main)" ]]; then
  exit 0
fi
{
  date -Is
  bash deploy/update.sh
} >>"$log_file" 2>&1
