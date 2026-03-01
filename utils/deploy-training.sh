#!/bin/bash
#
# Deploy/rollback the custom model training annotation feature to a local ZM install.
# Usage:
#   sudo ./deploy-training.sh deploy    # Copy files + run migration
#   sudo ./deploy-training.sh rollback  # Restore backed-up originals
#

set -euo pipefail

REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
ZM_WWW="/usr/share/zoneminder/www"
BACKUP_DIR="/tmp/zm-training-backup"
ZM_DB="zm"  # ZoneMinder database name

# Files to deploy (source relative to repo -> destination relative to ZM_WWW)
declare -A FILES=(
  ["web/ajax/training.php"]="ajax/training.php"
  ["web/skins/classic/css/base/views/training.css"]="skins/classic/css/base/views/training.css"
  ["web/skins/classic/views/js/training.js"]="skins/classic/views/js/training.js"
  ["web/skins/classic/views/event.php"]="skins/classic/views/event.php"
  ["web/skins/classic/views/js/event.js.php"]="skins/classic/views/js/event.js.php"
  ["web/lang/en_gb.php"]="lang/en_gb.php"
)

deploy() {
  echo "=== Deploying training annotation feature ==="

  # Create backup directory
  mkdir -p "$BACKUP_DIR"
  echo "Backing up originals to $BACKUP_DIR/"

  for src_rel in "${!FILES[@]}"; do
    dst_rel="${FILES[$src_rel]}"
    dst="$ZM_WWW/$dst_rel"

    if [ -f "$dst" ]; then
      # Preserve directory structure in backup
      backup_path="$BACKUP_DIR/$dst_rel"
      mkdir -p "$(dirname "$backup_path")"
      cp "$dst" "$backup_path"
      echo "  Backed up: $dst_rel"
    fi
  done

  # Copy new files
  echo ""
  echo "Copying files to $ZM_WWW/"
  for src_rel in "${!FILES[@]}"; do
    dst_rel="${FILES[$src_rel]}"
    src="$REPO_ROOT/$src_rel"
    dst="$ZM_WWW/$dst_rel"

    mkdir -p "$(dirname "$dst")"
    cp "$src" "$dst"
    echo "  Deployed: $dst_rel"
  done

  # Set ownership to match existing files
  chown -R www-data:www-data "$ZM_WWW/ajax/training.php"
  chown -R www-data:www-data "$ZM_WWW/skins/classic/css/base/views/training.css"
  chown -R www-data:www-data "$ZM_WWW/skins/classic/views/js/training.js"

  # Run database migration
  echo ""
  echo "Running database migration..."
  if mysql "$ZM_DB" < "$REPO_ROOT/db/zm_update-1.39.2.sql"; then
    echo "  Migration applied successfully."
  else
    echo "  WARNING: Migration may have failed. Check output above."
  fi

  echo ""
  echo "=== Deploy complete ==="
  echo "Next steps:"
  echo "  1. Go to ZM Options > Config tab"
  echo "  2. Enable 'ZM_OPT_TRAINING'"
  echo "  3. Set ZM_TRAINING_DATA_DIR (or leave empty for default)"
  echo "  4. Navigate to an event and click the Annotate button"
  echo ""
  echo "To rollback: sudo $0 rollback"
}

rollback() {
  echo "=== Rolling back training annotation feature ==="

  if [ ! -d "$BACKUP_DIR" ]; then
    echo "ERROR: No backup found at $BACKUP_DIR"
    exit 1
  fi

  # Restore backed-up files
  for src_rel in "${!FILES[@]}"; do
    dst_rel="${FILES[$src_rel]}"
    backup_path="$BACKUP_DIR/$dst_rel"
    dst="$ZM_WWW/$dst_rel"

    if [ -f "$backup_path" ]; then
      cp "$backup_path" "$dst"
      echo "  Restored: $dst_rel"
    else
      # New file (no backup) — remove it
      if [ -f "$dst" ]; then
        rm "$dst"
        echo "  Removed: $dst_rel (new file)"
      fi
    fi
  done

  # Remove Config entries from database
  echo ""
  echo "Removing Config entries from database..."
  mysql "$ZM_DB" -e "
    DELETE FROM Config WHERE Name IN ('ZM_OPT_TRAINING', 'ZM_TRAINING_DATA_DIR');
  "
  echo "  Config entries removed."

  echo ""
  echo "=== Rollback complete ==="
  echo "Reload ZM in your browser to verify."
}

case "${1:-}" in
  deploy)
    deploy
    ;;
  rollback)
    rollback
    ;;
  *)
    echo "Usage: sudo $0 {deploy|rollback}"
    exit 1
    ;;
esac
