#!/bin/bash
# ──────────────────────────────────────────────────────────────────────────────
# deploy-sftp.sh -- Upload changed src/ files to remote server via pscp (PuTTY)
#
# Called by the post-commit git hook. Reads connection details from
# src/sftp-config.json and uploads only the files changed in the last commit.
#
# Requirements: pscp (PuTTY), jq (optional, falls back to grep/sed)
# ──────────────────────────────────────────────────────────────────────────────

set -euo pipefail

REPO_ROOT="$(git rev-parse --show-toplevel)"
CONFIG="$REPO_ROOT/src/sftp-config.json"

if [[ ! -f "$CONFIG" ]]; then
	echo "[deploy] sftp-config.json not found -- skipping deploy."
	exit 0
fi

# Parse connection details from sftp-config.json
parse_json_value() {
	grep "\"$1\"" "$CONFIG" | sed 's/.*: *"\([^"]*\)".*/\1/'
}

HOST=$(parse_json_value host)
USER=$(parse_json_value user)
PORT=$(parse_json_value port)
REMOTE_PATH=$(parse_json_value remote_path)
SSH_KEY=$(parse_json_value ssh_key_file)

# Allow override via environment
SSH_KEY="${MOKODOLI_SSH_KEY:-$SSH_KEY}"

if [[ -z "$HOST" || -z "$USER" || -z "$REMOTE_PATH" ]]; then
	echo "[deploy] Missing connection details in sftp-config.json -- skipping."
	exit 0
fi

if [[ ! -f "$SSH_KEY" ]]; then
	echo "[deploy] SSH key not found: $SSH_KEY -- skipping."
	exit 0
fi

# Get files changed in the last commit, filter to src/ only
CHANGED_FILES=$(git diff-tree --no-commit-id --name-only -r HEAD -- src/)

if [[ -z "$CHANGED_FILES" ]]; then
	echo "[deploy] No src/ files changed -- nothing to upload."
	exit 0
fi

echo "[deploy] Uploading changed files to $USER@$HOST:$REMOTE_PATH"

UPLOADED=0
FAILED=0

while IFS= read -r file; do
	local_path="$REPO_ROOT/$file"
	# Strip 'src/' prefix to get relative path within remote_path
	remote_rel="${file#src/}"
	remote_dest="$REMOTE_PATH$remote_rel"

	if [[ ! -f "$local_path" ]]; then
		# File was deleted -- skip (would need separate delete logic)
		echo "[deploy]   SKIP (deleted): $file"
		continue
	fi

	echo -n "[deploy]   $file -> $remote_dest ... "
	if pscp -q -batch -i "$SSH_KEY" -P "$PORT" "$local_path" "$USER@$HOST:$remote_dest" 2>/dev/null; then
		echo "OK"
		((UPLOADED++))
	else
		echo "FAILED"
		((FAILED++))
	fi
done <<< "$CHANGED_FILES"

echo "[deploy] Done: $UPLOADED uploaded, $FAILED failed."
