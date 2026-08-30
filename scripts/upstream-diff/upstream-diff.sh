#!/usr/bin/env bash
#
# upstream-diff.sh — compare this project's template files against the
# upstream drupal/cms project template.
#
# This project's composer.json / .gitignore / scaffold config began as a
# copy of drupal/cms at the version recorded in upstream/BASELINE, then
# diverged (extra contrib, patches, minimum-stability, scaffold
# overrides). `composer update drupal/core-*` never surfaces changes the
# Drupal CMS maintainers make to the template itself. This script does.
#
# Usage:
#   scripts/upstream-diff/upstream-diff.sh [TARGET_VERSION]
#   CMS_VERSION=2.1.3 scripts/upstream-diff/upstream-diff.sh
#
# With no argument it resolves the latest drupal/cms release tag.
#
# For each tracked file it prints two diffs:
#   [1] baseline -> target   — what upstream changed since you forked
#   [2] yours     -> target   — everything still differing (your
#                               intentional changes + unadopted upstream)
# Read [1], hand-apply what you want, then run:
#   scripts/upstream-diff/upstream-diff.sh --promote TARGET_VERSION
# to advance upstream/BASELINE and the vendored baseline copies.

set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
UPSTREAM_DIR="$REPO_ROOT/upstream"
RAW_BASE="https://git.drupalcode.org/project/cms/-/raw"
TAGS_API="https://git.drupalcode.org/api/v4/projects/project%2Fcms/repository/tags?per_page=1"

# Files tracked against upstream, one per line:
#   <path in this repo> : <path in the upstream repo> : <name under upstream/>
# The third field is a separate name so the vendored copy of an
# otherwise-active file (e.g. .gitignore) stays inert.
FILES=(
  "composer.json:composer.json:composer.json"
  ".gitignore:.gitignore:gitignore"
)

err() { printf '\033[31m%s\033[0m\n' "$*" >&2; }
hdr() { printf '\n\033[1;36m== %s ==\033[0m\n' "$*"; }
sub() { printf '\033[1;33m-- %s --\033[0m\n' "$*"; }

resolve_latest() {
  curl -sf "$TAGS_API" \
    | python3 -c 'import json,sys; print(json.load(sys.stdin)[0]["name"])'
}

fetch() { # <version> <upstream-path> -> stdout
  curl -sf "$RAW_BASE/$1/$2"
}

PROMOTE=0
TARGET="${CMS_VERSION:-}"
for arg in "$@"; do
  case "$arg" in
    --promote) PROMOTE=1 ;;
    *)         TARGET="$arg" ;;
  esac
done

[ -n "$TARGET" ] || TARGET="$(resolve_latest)"
BASELINE="$(tr -d '[:space:]' < "$UPSTREAM_DIR/BASELINE")"

if [ "$PROMOTE" -eq 1 ]; then
  hdr "Promoting baseline $BASELINE -> $TARGET"
  for row in "${FILES[@]}"; do
    IFS=: read -r _local up_path vendored_name <<<"$row"
    dest="$UPSTREAM_DIR/$vendored_name"
    fetch "$TARGET" "$up_path" > "$dest"
    printf '  updated %s\n' "${dest#"$REPO_ROOT"/}"
  done
  printf '%s\n' "$TARGET" > "$UPSTREAM_DIR/BASELINE"
  printf '  updated upstream/BASELINE -> %s\n' "$TARGET"
  hdr "Review & commit upstream/ ; then reconcile your own files."
  exit 0
fi

hdr "drupal/cms template diff — baseline $BASELINE  ->  target $TARGET"
if [ "$BASELINE" = "$TARGET" ]; then
  err "baseline and target are the same ($TARGET) — nothing to compare."
  err "pass a newer version, e.g.: $0 2.1.3"
  exit 1
fi

workdir="$(mktemp -d)"
trap 'rm -rf "$workdir"' EXIT

for row in "${FILES[@]}"; do
  IFS=: read -r local_path up_path vendored_name <<<"$row"
  vendored="$UPSTREAM_DIR/$vendored_name"
  yours="$REPO_ROOT/$local_path"
  target_file="$workdir/$vendored_name.target"

  if ! fetch "$TARGET" "$up_path" > "$target_file"; then
    err "could not fetch $up_path @ $TARGET — skipping"
    continue
  fi

  hdr "$local_path"

  if [ ! -f "$vendored" ]; then
    err "no vendored baseline at upstream/$vendored_name — run --promote once to seed it"
  else
    sub "[1] upstream $BASELINE -> $TARGET   (what the maintainers changed)"
    diff -u --label "upstream/$BASELINE/$up_path" --label "upstream/$TARGET/$up_path" \
      "$vendored" "$target_file" || true
  fi

  sub "[2] yours -> upstream $TARGET   (your changes + anything unadopted)"
  diff -u --label "$local_path (this repo)" --label "upstream/$TARGET/$up_path" \
    "$yours" "$target_file" || true
done

hdr "Next: hand-apply wanted changes from [1], then: $0 --promote $TARGET"
