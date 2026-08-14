#!/usr/bin/env bash
# Manual sync of docs/project-management/ into a dedicated Open WebUI
# Knowledge base on the llamanator platform, so it's queryable from chat.
#
# Deliberately NOT scheduled: this must run from a machine that's actually
# awake (llamanator's own systemd-timer pattern doesn't apply here -- this
# project's docs live on an operator machine, not on the always-on
# llamanator server). Run by hand whenever docs/project-management/ has
# meaningfully changed. See llamanator's task 0109 for the design/decisions
# (../../llamanator/docs/project-management/tasks/0109-generalize-rag-sync-multi-project.md)
# and task 0106 for the underlying sync mechanism + its real bug fixes.
#
# The actual sync engine (sync-knowledge.py) lives in the llamanator repo,
# not duplicated here -- it's fundamentally an Open WebUI/llamanator
# integration, this project just supplies its own docs path + KB name.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CMS2_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
LLAMANATOR_REPO="$(cd "$CMS2_ROOT/../llamanator" && pwd)"
SYNC_ENGINE="$LLAMANATOR_REPO/scripts/rag-sync/sync-knowledge.py"

if [ ! -f "$SYNC_ENGINE" ]; then
  echo "sync-knowledge.py not found at $SYNC_ENGINE -- is the llamanator repo checked out as a sibling directory?" >&2
  exit 1
fi

TAILSCALE_HOSTNAME=$(ssh llamanator "grep '^TAILSCALE_HOSTNAME=' /home/cyberdyne/llamanator/.env | cut -d= -f2-")
export OWUI_RAG_SYNC_API_KEY
OWUI_RAG_SYNC_API_KEY=$(ssh llamanator "grep '^OWUI_RAG_SYNC_API_KEY=' /home/cyberdyne/llamanator/.env | cut -d= -f2-")

if [ -z "$OWUI_RAG_SYNC_API_KEY" ]; then
  echo "OWUI_RAG_SYNC_API_KEY not found in llamanator's .env -- has task 0106's key been rotated/removed?" >&2
  exit 1
fi

python3 "$SYNC_ENGINE" \
  "$CMS2_ROOT/docs/project-management" \
  --base-url "https://${TAILSCALE_HOSTNAME}/api/v1" \
  --kb-name "Verdigris CMS Docs" \
  --kb-description "Project-management vault for the Verdigris/cms2 Drupal CMS project (github.com/deburca/verdigris) -- tasks, decisions, notes, infrastructure, and the privacy policy."
