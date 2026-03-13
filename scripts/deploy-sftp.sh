#!/bin/bash
# ──────────────────────────────────────────────────────────────────────────────
# deploy-sftp.sh -- Sync src/ directory to remote server via psftp (PuTTY)
#
# Called by the post-commit git hook. Reads connection details from
# src/sftp-config.json. Uploads changed/added files and deletes files
# removed from src/ so the remote matches the local src/ directory.
#
# Requirements: psftp (PuTTY)
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

# Get files changed in the last commit (added, modified, deleted), filter to src/
CHANGED=$(git diff-tree --no-commit-id --name-status -r HEAD -- src/)

if [[ -z "$CHANGED" ]]; then
	echo "[deploy] No src/ files changed -- nothing to sync."
	exit 0
fi

echo "[deploy] Syncing to $USER@$HOST:$REMOTE_PATH"

# Build psftp batch commands
BATCH_FILE=$(mktemp)
trap 'rm -f "$BATCH_FILE"' EXIT

echo "cd $REMOTE_PATH" >> "$BATCH_FILE"

UPLOADED=0
DELETED=0
SKIPPED=0

while IFS=$'\t' read -r status file; do
	# Strip 'src/' prefix to get relative path within remote_path
	remote_rel="${file#src/}"

	case "$status" in
		A|M)
			# Added or Modified -- upload
			local_path="$REPO_ROOT/$file"
			if [[ -f "$local_path" ]]; then
				# Ensure remote directory exists
				remote_dir=$(dirname "$remote_rel")
				if [[ "$remote_dir" != "." ]]; then
					echo "mkdir $remote_dir" >> "$BATCH_FILE"
				fi
				echo "put \"$local_path\" \"$remote_rel\"" >> "$BATCH_FILE"
				echo "[deploy]   PUT $file"
				((UPLOADED++))
			fi
			;;
		D)
			# Deleted -- remove from remote
			echo "rm \"$remote_rel\"" >> "$BATCH_FILE"
			echo "[deploy]   DEL $file"
			((DELETED++))
			;;
		*)
			echo "[deploy]   SKIP ($status): $file"
			((SKIPPED++))
			;;
	esac
done <<< "$CHANGED"

echo "quit" >> "$BATCH_FILE"

# Execute batch via psftp
if psftp "$USER@$HOST" -P "$PORT" -i "$SSH_KEY" -batch -b "$BATCH_FILE" 2>&1 | grep -v "^Remote working directory" | grep -v "^Using keyboard"; then
	true
fi

echo "[deploy] Done: $UPLOADED uploaded, $DELETED deleted, $SKIPPED skipped."
